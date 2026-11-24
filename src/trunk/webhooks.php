<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Webhooks {

    const DELIVER_HOOK = 'surveyjs_webhook_deliver';
    const QUEUE_PREFIX = 'surveyjs_webhook_queue_';
    const LOG_PREFIX = 'surveyjs_webhook_log_';
    const LOG_SIZE = 30;
    const MAX_WEBHOOKS = 50;
    const RETRY_DELAYS = array( 5 * MINUTE_IN_SECONDS, HOUR_IN_SECONDS );
    const EVENT = 'entry.submitted';
    const TEST_EVENT = 'test';
    const MAX_HEADERS = 10;
    const MAX_HEADER_VALUE = 1000;
    const RESERVED_HEADERS = array( 'content-type', 'content-length', 'host', 'connection', 'transfer-encoding', 'expect' );

    // Not routable from the public internet: private, loopback, link-local (cloud metadata at 169.254.169.254),
    // shared, documentation and multicast ranges, and IPv6 forms that embed an IPv4 address (mapped, NAT64, 6to4, Teredo).
    const BLOCKED_RANGES = array(
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/3',
        '::/96',
        '::ffff:0:0/96',
        '64:ff9b::/96',
        '64:ff9b:1::/48',
        '100::/64',
        '2001::/32',
        '2001:db8::/32',
        '2002::/16',
        'fc00::/7',
        'fe80::/10',
        'fec0::/10',
        'ff00::/8',
    );

    public static function init() {
        add_action( self::DELIVER_HOOK, array( __CLASS__, 'deliver' ) );
    }

    public static function unschedule() {
        wp_unschedule_hook( self::DELIVER_HOOK );
        self::delete_queue();
    }

    // Queued requests hold respondents' answers. They are found by name rather than through the cron events, so a
    // request whose event was lost is deleted too.
    private static function delete_queue() {
        global $wpdb;
        $names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( self::QUEUE_PREFIX ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- runs on deactivation only; the options API cannot list options by prefix.
        foreach ( $names as $name ) {
            delete_option( $name );
        }
    }

    public static function empty_webhook() {
        return array(
            'id'         => '',
            'name'       => '',
            'enabled'    => true,
            'url'        => '',
            'headers'    => array(),
            'conditions' => SurveyJS_Conditions::sanitize( array() ),
        );
    }

    public static function sanitize( $webhooks ) {
        $sanitized = array();
        $items = is_array( $webhooks ) ? array_slice( array_values( array_filter( $webhooks, 'is_array' ) ), 0, self::MAX_WEBHOOKS ) : array();
        foreach ( $items as $webhook ) {
            $item = array_merge( self::empty_webhook(), array_intersect_key( $webhook, self::empty_webhook() ) );
            $sanitized[] = array(
                'id'         => self::sanitize_id( $item['id'], array_column( $sanitized, 'id' ) ),
                'name'       => sanitize_text_field( is_scalar( $item['name'] ) ? (string) $item['name'] : '' ),
                'enabled'    => ! empty( $item['enabled'] ),
                'url'        => self::normalized_url( $item['url'] ),
                'headers'    => self::sanitize_headers( $item['headers'] ),
                'conditions' => SurveyJS_Conditions::sanitize( $item['conditions'] ),
            );
        }
        return $sanitized;
    }

    private static function sanitize_id( $id, $taken ) {
        $id = substr( (string) preg_replace( '/[^a-z0-9_]/', '', strtolower( is_scalar( $id ) ? (string) $id : '' ) ), 0, 40 );
        while ( '' === $id || in_array( $id, $taken, true ) ) {
            $id = 'w' . strtolower( wp_generate_password( 8, false ) );
        }
        return $id;
    }

    private static function sanitize_headers( $headers ) {
        $sanitized = array();
        foreach ( is_array( $headers ) ? $headers : array() as $header ) {
            $name = is_array( $header ) && is_scalar( $header['name'] ?? null ) ? trim( (string) $header['name'] ) : '';
            $lower = strtolower( $name );
            if ( ! preg_match( '/^[A-Za-z0-9!#$%&\'*+.^_`|~-]{1,100}$/', $name ) || in_array( $lower, self::RESERVED_HEADERS, true ) || str_starts_with( $lower, 'x-surveyjs-' ) ) {
                continue;
            }
            $value = is_scalar( $header['value'] ?? null ) ? (string) $header['value'] : '';
            $sanitized[] = array(
                'name'  => $name,
                'value' => substr( trim( (string) preg_replace( '/[\x00-\x1F\x7F]/', '', $value ) ), 0, self::MAX_HEADER_VALUE ),
            );
            if ( count( $sanitized ) >= self::MAX_HEADERS ) {
                break;
            }
        }
        return $sanitized;
    }

    // Custom header values let someone send requests as this site (API keys, tokens).
    public static function secret_values( $webhooks ) {
        $values = array();
        foreach ( is_array( $webhooks ) ? $webhooks : array() as $webhook ) {
            if ( ! is_array( $webhook ) ) {
                continue;
            }
            foreach ( is_array( $webhook['headers'] ?? null ) ? $webhook['headers'] : array() as $header ) {
                $values[] = is_array( $header ) ? ( $header['value'] ?? '' ) : '';
            }
        }
        return array_values( array_filter( $values, fn ( $value ) => is_string( $value ) && '' !== $value ) );
    }

    public static function find( $settings, $webhook_id ) {
        foreach ( (array) ( $settings['webhooks'] ?? array() ) as $webhook ) {
            if ( is_array( $webhook ) && ( $webhook['id'] ?? '' ) === $webhook_id ) {
                return $webhook;
            }
        }
        return null;
    }

    public static function normalized_url( $url ) {
        return esc_url_raw( trim( is_scalar( $url ) ? (string) $url : '' ), array( 'http', 'https' ) );
    }

    public static function dispatch( $survey, $settings, $data, $entry_id ) {
        $data = is_array( $data ) ? $data : array();
        $webhooks = array_filter( (array) ( $settings['webhooks'] ?? array() ), function ( $webhook ) use ( $data ) {
            return is_array( $webhook ) && ! empty( $webhook['enabled'] ) && '' !== (string) ( $webhook['url'] ?? '' )
                && SurveyJS_Conditions::matches( $webhook['conditions'] ?? array(), $data );
        } );
        if ( ! $webhooks ) {
            return;
        }
        $body = wp_json_encode( self::payload( $survey, $settings, $data, $entry_id, self::EVENT ) );
        foreach ( $webhooks as $webhook ) {
            $key = self::delivery_id();
            add_option( self::QUEUE_PREFIX . $key, array(
                'form'    => (int) $survey->id,
                'webhook' => $webhook['id'],
                'entry'   => (int) $entry_id,
                'body'    => $body,
                'attempt' => 1,
            ), '', false );
            if ( ! self::schedule( $key, time() ) ) {
                delete_option( self::QUEUE_PREFIX . $key );
                self::log( (int) $survey->id, $webhook['id'], array(
                    'ok'      => false,
                    'code'    => 0,
                    'error'   => __( 'WP-Cron could not schedule the request, so it was not sent.', 'surveyjs' ),
                    'event'   => self::EVENT,
                    'entry'   => (int) $entry_id,
                    'attempt' => 1,
                    'retry'   => false,
                ) );
            }
        }
        if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
            spawn_cron();
        }
    }

    private static function schedule( $key, $time ) {
        return true === wp_schedule_single_event( $time, self::DELIVER_HOOK, array( $key ), true );
    }

    private static function delivery_id() {
        return str_replace( '-', '', wp_generate_uuid4() );
    }

    // The payload stays queued until it is delivered or the last retry fails, so retries send the same body.
    public static function deliver( $key ) {
        if ( ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{32}$/', $key ) ) {
            return;
        }
        $item = get_option( self::QUEUE_PREFIX . $key );
        if ( ! is_array( $item ) ) {
            return;
        }
        $survey = SurveyJS_Forms::find( (int) $item['form'] );
        $webhook = $survey ? self::find( SurveyJS_Form_Settings::get( $survey ), $item['webhook'] ) : null;
        if ( ! $webhook || empty( $webhook['enabled'] ) ) {
            delete_option( self::QUEUE_PREFIX . $key );
            return;
        }
        $attempt = (int) $item['attempt'];
        $result = self::post( $webhook, (string) $item['body'], self::EVENT, $key );
        $retry = ! $result['ok'] && $attempt <= count( self::RETRY_DELAYS );
        if ( $retry ) {
            $item['attempt'] = $attempt + 1;
            update_option( self::QUEUE_PREFIX . $key, $item, false );
            $retry = self::schedule( $key, time() + self::RETRY_DELAYS[ $attempt - 1 ] );
        }
        if ( ! $retry ) {
            delete_option( self::QUEUE_PREFIX . $key );
        }
        self::log( (int) $item['form'], $webhook['id'], $result + array(
            'event'   => self::EVENT,
            'entry'   => (int) $item['entry'],
            'attempt' => $attempt,
            'retry'   => $retry,
        ) );
    }

    public static function test( $survey, $settings, $webhook ) {
        $entries = SurveyJS_Entries::query( $survey->id, array( 'limit' => 1 ) );
        $entry = $entries ? $entries[0] : null;
        $data = $entry ? $entry->data : self::sample_data( $survey );
        $body = wp_json_encode( self::payload( $survey, $settings, $data, $entry ? $entry->id : 0, self::TEST_EVENT, $entry ? $entry->created_at : '' ) );
        $result = self::post( $webhook, $body, self::TEST_EVENT, self::delivery_id() );
        self::log( (int) $survey->id, $webhook['id'], $result + array(
            'event'   => self::TEST_EVENT,
            'entry'   => $entry ? (int) $entry->id : 0,
            'attempt' => 1,
            'retry'   => false,
        ) );
        return $result;
    }

    private static function post( $webhook, $body, $event, $delivery ) {
        $url = (string) $webhook['url'];
        $resolve = self::checked_resolve( $url );
        if ( null === $resolve ) {
            return array(
                'ok'    => false,
                'code'  => 0,
                'error' => __( 'The URL is not allowed. Webhooks are sent only to public addresses over HTTP or HTTPS on ports 80, 443 or 8080.', 'surveyjs' ),
            );
        }
        $headers = array();
        foreach ( is_array( $webhook['headers'] ?? null ) ? $webhook['headers'] : array() as $header ) {
            $headers[ $header['name'] ] = $header['value'];
        }
        $pin = function ( $handle, $args, $request_url ) use ( $url, $resolve ) {
            if ( '' !== $resolve && $request_url === $url ) {
                curl_setopt( $handle, CURLOPT_RESOLVE, array( $resolve ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- the WordPress HTTP API has no option to connect to an address that was already checked.
            }
        };
        add_action( 'http_api_curl', $pin, 10, 3 );
        $response = wp_safe_remote_post( $url, array(
            'timeout'     => (int) apply_filters( 'surveyjs_webhook_timeout', 10 ),
            'redirection' => 0,
            'data_format' => 'body',
            'body'        => $body,
            'headers'     => array_merge( $headers, array(
                'Content-Type'        => 'application/json; charset=utf-8',
                'X-SurveyJS-Event'    => $event,
                'X-SurveyJS-Delivery' => $delivery,
            ) ),
        ) );
        remove_action( 'http_api_curl', $pin, 10 );
        if ( is_wp_error( $response ) ) {
            return array( 'ok' => false, 'code' => 0, 'error' => $response->get_error_message() );
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        return array(
            'ok'    => $code >= 200 && $code < 300,
            'code'  => $code,
            'error' => $code >= 200 && $code < 300 ? '' : trim( $code . ' ' . wp_remote_retrieve_response_message( $response ) ),
        );
    }

    // wp_http_validate_url() checks only the first IPv4 address of the host and lets link-local addresses through,
    // and cURL then resolves the host again, also over IPv6, so a DNS answer that changes in between (rebinding) or
    // a private AAAA record would reach the local network. Every A and AAAA address is checked here, and the
    // request is pinned to a checked one. Returns the CURLOPT_RESOLVE entry ('' for an IP address), or null when
    // the URL is not allowed.
    private static function checked_resolve( $url ) {
        if ( ! wp_http_validate_url( $url ) ) {
            return null;
        }
        $parts = wp_parse_url( $url );
        $host = (string) ( $parts['host'] ?? '' );
        $ips = self::host_ips( $host );
        if ( ! $ips ) {
            return null;
        }
        $blocked = array_filter( $ips, fn ( $ip ) => ! self::is_public_ip( $ip ) );
        if ( $blocked && ! apply_filters( 'http_request_host_is_external', false, $host, $url ) ) {
            return null;
        }
        if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
            return '';
        }
        $ipv4 = array_values( array_filter( $ips, fn ( $ip ) => filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) );
        $ip = $ipv4 ? $ipv4[0] : $ips[0];
        $port = $parts['port'] ?? ( 'https' === strtolower( (string) ( $parts['scheme'] ?? '' ) ) ? 443 : 80 );
        return $host . ':' . $port . ':' . ( $ipv4 ? $ip : '[' . $ip . ']' );
    }

    private static function host_ips( $host ) {
        if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
            return array( $host );
        }
        $ips = gethostbynamel( $host );
        $ips = is_array( $ips ) ? $ips : array();
        $records = dns_get_record( $host, DNS_A | DNS_AAAA );
        foreach ( is_array( $records ) ? $records : array() as $record ) {
            $ips[] = $record['ip'] ?? ( $record['ipv6'] ?? '' );
        }
        return array_values( array_unique( array_filter( $ips, fn ( $ip ) => (bool) filter_var( $ip, FILTER_VALIDATE_IP ) ) ) );
    }

    private static function is_public_ip( $ip ) {
        $packed = inet_pton( $ip );
        if ( false === $packed ) {
            return false;
        }
        foreach ( self::BLOCKED_RANGES as $range ) {
            if ( self::in_range( $packed, $range ) ) {
                return false;
            }
        }
        return true;
    }

    private static function in_range( $packed, $range ) {
        list( $subnet, $bits ) = explode( '/', $range );
        $subnet = inet_pton( $subnet );
        $bits = (int) $bits;
        if ( strlen( $subnet ) !== strlen( $packed ) ) {
            return false;
        }
        $bytes = intdiv( $bits, 8 );
        if ( substr( $packed, 0, $bytes ) !== substr( $subnet, 0, $bytes ) ) {
            return false;
        }
        $rest = $bits % 8;
        if ( ! $rest ) {
            return true;
        }
        $mask = ( 0xff << ( 8 - $rest ) ) & 0xff;
        return ( ord( $packed[ $bytes ] ) & $mask ) === ( ord( $subnet[ $bytes ] ) & $mask );
    }

    public static function payload( $survey, $settings, $data, $entry_id, $event, $created_at = '' ) {
        // Files of forms that do not store entries are deleted a day after the submission.
        $lifetime = empty( $settings['storeEntries'] ) ? DAY_IN_SECONDS : WEEK_IN_SECONDS;
        $answers = array();
        $fields = array();
        foreach ( SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) ) as $question ) {
            $name = $question['name'];
            $text = '';
            if ( array_key_exists( $name, $data ) ) {
                $links = SurveyJS_Uploaded_Files::links( $data[ $name ], $lifetime );
                $text = $links ? SurveyJS_Uploaded_Files::links_text( $links ) : SurveyJS_Survey_Values::display_value( $question, $data[ $name ] );
            }
            $answers[ $name ] = $text;
            $fields[] = array(
                'name'  => $name,
                'title' => SurveyJS_Survey_Values::question_title( $question ),
                'text'  => $text,
            );
        }
        foreach ( array_diff_key( $data, $answers ) as $name => $value ) {
            $answers[ $name ] = is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value );
        }
        $time = '' !== (string) $created_at ? strtotime( $created_at . ' UTC' ) : time();
        $data = self::with_file_urls( $data, $lifetime );
        return apply_filters( 'surveyjs_webhook_payload', array(
            'event'   => $event,
            'form'    => array(
                'id'   => (int) $survey->id,
                'name' => $survey->name,
            ),
            'entry'   => array(
                'id'           => $entry_id ? (int) $entry_id : null,
                'submitted_at' => gmdate( 'c', $time ? $time : time() ),
                'url'          => $entry_id ? add_query_arg( array( 'page' => 'surveyjs_results', 'id' => (int) $survey->id, 'entry' => (int) $entry_id ), admin_url( 'admin.php' ) ) : null,
            ),
            'site'    => array(
                'name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
                'url'  => home_url( '/' ),
            ),
            'answers' => $answers ? $answers : new stdClass(),
            'data'    => $data ? $data : new stdClass(),
            'fields'  => $fields,
        ), $survey, $data, (int) $entry_id );
    }

    private static function with_file_urls( $value, $lifetime ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }
        if ( null !== SurveyJS_Uploaded_Files::stored_name( $value ) ) {
            $value['content'] = SurveyJS_Uploaded_Files::signed_url( $value, $lifetime );
            return $value;
        }
        foreach ( $value as $key => $item ) {
            $value[ $key ] = self::with_file_urls( $item, $lifetime );
        }
        return $value;
    }

    private static function sample_data( $survey ) {
        $data = array();
        foreach ( SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) ) as $question ) {
            $multiple = in_array( $question['type'] ?? '', array( 'checkbox', 'tagbox', 'ranking' ), true );
            $data[ $question['name'] ] = $multiple ? array( __( 'Sample answer', 'surveyjs' ) ) : __( 'Sample answer', 'surveyjs' );
        }
        return $data;
    }

    private static function log( $form_id, $webhook_id, $result ) {
        $log = self::log_entries( $form_id );
        array_unshift( $log, array(
            'webhook' => $webhook_id,
            'time'    => time(),
            'event'   => $result['event'],
            'entry'   => $result['entry'],
            'attempt' => $result['attempt'],
            'ok'      => $result['ok'],
            'code'    => $result['code'],
            'error'   => mb_substr( (string) $result['error'], 0, 300 ),
            'retry'   => $result['retry'],
        ) );
        update_option( self::LOG_PREFIX . (int) $form_id, array_slice( $log, 0, self::LOG_SIZE ), false );
    }

    public static function log_entries( $form_id ) {
        $log = get_option( self::LOG_PREFIX . (int) $form_id, array() );
        return is_array( $log ) ? $log : array();
    }

    public static function client_log( $form_id ) {
        return array_map( function ( $item ) {
            $item['time'] = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $item['time'] );
            return $item;
        }, self::log_entries( $form_id ) );
    }

    public static function forget( $form_id ) {
        delete_option( self::LOG_PREFIX . (int) $form_id );
    }
}
