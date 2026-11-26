<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Form integrations: the form setting "integrations" is a list of items that send each entry to a connected account
// (SurveyJS_Connections). Delivery goes through SurveyJS_Delivery_Queue like webhooks; the request is built at
// delivery time from the answers snapshot taken at submission, so a key replaced in between is used for retries too.
class SurveyJS_Integrations {

    const TAB = 'integrations';
    const RETRY_DELAYS = array( 5 * MINUTE_IN_SECONDS, HOUR_IN_SECONDS, 6 * HOUR_IN_SECONDS );
    const MAX_INTEGRATIONS = 50;
    const TARGETS_TRANSIENT = 'surveyjs_integration_targets_';
    const TARGETS_LIFETIME = 10 * MINUTE_IN_SECONDS;

    private static $registered = array();
    private static $services = null;

    // Every file in integrations/services/ is one service that calls register() with its class name.
    public static function load_services() {
        foreach ( (array) glob( __DIR__ . '/services/*.php' ) as $file ) {
            include_once $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- files of this plugin's own folder.
        }
    }

    public static function register( $class_name ) {
        self::$registered[] = $class_name;
        self::$services = null;
    }

    // Add-ons return their services from the filter once all plugins are loaded, so an earlier list is not kept.
    public static function services() {
        if ( null !== self::$services ) {
            return self::$services;
        }
        $services = array();
        foreach ( self::$registered as $class_name ) {
            $services[] = new $class_name();
        }
        $list = array();
        foreach ( (array) apply_filters( 'surveyjs_integration_services', $services ) as $service ) {
            if ( $service instanceof SurveyJS_Integration_Service && preg_match( '/^[a-z0-9_]{1,40}$/', $service->id() ) ) {
                $list[ $service->id() ] = $service;
            }
        }
        uasort( $list, function ( $a, $b ) {
            return strcasecmp( $a->label(), $b->label() );
        } );
        if ( did_action( 'plugins_loaded' ) ) {
            self::$services = $list;
        }
        return $list;
    }

    public static function service( $id ) {
        return is_string( $id ) ? ( self::services()[ $id ] ?? null ) : null;
    }

    public static function empty_feed() {
        return array(
            'id'         => '',
            'name'       => '',
            'enabled'    => true,
            'service'    => '',
            'connection' => '',
            'conditions' => SurveyJS_Conditions::sanitize( array() ),
        );
    }

    // $stored are the integrations saved with the form. An item of a service that is not registered right now (its
    // add-on is inactive) cannot be sanitized by it, so the saved copy is kept as it is.
    public static function sanitize( $feeds, $stored = array() ) {
        $stored_by_id = array();
        foreach ( is_array( $stored ) ? $stored : array() as $feed ) {
            if ( is_array( $feed ) && is_scalar( $feed['id'] ?? null ) ) {
                $stored_by_id[ (string) $feed['id'] ] = $feed;
            }
        }
        $sanitized = array();
        $items = is_array( $feeds ) ? array_slice( array_values( array_filter( $feeds, 'is_array' ) ), 0, self::MAX_INTEGRATIONS ) : array();
        foreach ( $items as $feed ) {
            $common = array_merge( self::empty_feed(), array_intersect_key( $feed, self::empty_feed() ) );
            $item = array(
                'id'         => self::sanitize_id( $common['id'], array_column( $sanitized, 'id' ) ),
                'name'       => sanitize_text_field( is_scalar( $common['name'] ) ? (string) $common['name'] : '' ),
                'enabled'    => ! empty( $common['enabled'] ),
                'service'    => sanitize_key( is_scalar( $common['service'] ) ? (string) $common['service'] : '' ),
                'connection' => sanitize_key( is_scalar( $common['connection'] ) ? (string) $common['connection'] : '' ),
                'conditions' => SurveyJS_Conditions::sanitize( $common['conditions'] ),
            );
            if ( '' === $item['service'] ) {
                continue;
            }
            $service = self::service( $item['service'] );
            if ( $service ) {
                $sanitized[] = $item + $service->sanitize_feed( array_merge( $service->feed_defaults(), $feed ) );
                continue;
            }
            $kept = $stored_by_id[ $item['id'] ] ?? null;
            if ( $kept && ( $kept['service'] ?? '' ) === $item['service'] ) {
                $sanitized[] = $kept;
            }
        }
        return $sanitized;
    }

    public static function error( $feeds ) {
        foreach ( is_array( $feeds ) ? $feeds : array() as $feed ) {
            $service = is_array( $feed ) ? self::service( $feed['service'] ?? '' ) : null;
            $error = $service ? $service->feed_error( $feed ) : '';
            if ( '' !== $error ) {
                return $error;
            }
        }
        return '';
    }

    private static function sanitize_id( $id, $taken ) {
        $id = substr( (string) preg_replace( '/[^a-z0-9_]/', '', strtolower( is_scalar( $id ) ? (string) $id : '' ) ), 0, 40 );
        while ( '' === $id || in_array( $id, $taken, true ) ) {
            $id = 'i' . strtolower( wp_generate_password( 8, false ) );
        }
        return $id;
    }

    public static function find( $settings, $feed_id ) {
        foreach ( (array) ( $settings['integrations'] ?? array() ) as $feed ) {
            if ( is_array( $feed ) && ( $feed['id'] ?? '' ) === $feed_id ) {
                return $feed;
            }
        }
        return null;
    }

    public static function dispatch( $survey, $settings, $data, $entry_id ) {
        $data = is_array( $data ) ? $data : array();
        $feeds = array_filter( (array) ( $settings['integrations'] ?? array() ), function ( $feed ) use ( $data ) {
            return is_array( $feed ) && ! empty( $feed['enabled'] ) && self::service( $feed['service'] ?? '' ) && '' !== (string) ( $feed['connection'] ?? '' )
                && SurveyJS_Conditions::matches( $feed['conditions'] ?? array(), $data );
        } );
        if ( ! $feeds ) {
            return;
        }
        $payload = wp_json_encode( SurveyJS_Webhooks::snapshot( $survey, $settings, $data, $entry_id, SurveyJS_Webhooks::EVENT ) );
        foreach ( $feeds as $feed ) {
            SurveyJS_Delivery_Queue::enqueue( 'integration', $survey->id, $feed['id'], $entry_id, array( 'payload' => $payload ) );
        }
        SurveyJS_Delivery_Queue::spawn();
    }

    public static function retry_delays() {
        return self::RETRY_DELAYS;
    }

    public static function deliver_job( $job, $survey ) {
        $feed = self::find( SurveyJS_Form_Settings::get( $survey ), $job['item'] );
        if ( ! $feed || empty( $feed['enabled'] ) ) {
            return null;
        }
        $payload = json_decode( (string) $job['payload'], true );
        return self::send( $feed, is_array( $payload ) ? $payload : array(), false );
    }

    public static function resend_job( $survey, $settings, $feed_id, $entry ) {
        if ( ! self::find( $settings, $feed_id ) ) {
            return new WP_Error( 'surveyjs_integration_not_found', __( 'This integration was deleted.', 'surveyjs' ) );
        }
        return array( 'payload' => wp_json_encode( SurveyJS_Webhooks::snapshot( $survey, $settings, $entry->data, $entry->id, SurveyJS_Webhooks::EVENT, $entry->created_at ) ) );
    }

    public static function test( $survey, $settings, $feed ) {
        $entries = SurveyJS_Entries::query( $survey->id, array( 'limit' => 1 ) );
        $entry = $entries ? $entries[0] : null;
        $data = $entry ? $entry->data : SurveyJS_Webhooks::sample_data( $survey );
        $payload = SurveyJS_Webhooks::snapshot( $survey, $settings, $data, $entry ? $entry->id : 0, SurveyJS_Webhooks::TEST_EVENT, $entry ? $entry->created_at : '' );
        $result = self::send( $feed, $payload, true, ! $entry );
        SurveyJS_Delivery_Queue::log( (int) $survey->id, $feed['id'], $result + array(
            'type'    => 'integration',
            'event'   => SurveyJS_Webhooks::TEST_EVENT,
            'entry'   => $entry ? (int) $entry->id : 0,
            'attempt' => 1,
            'retry'   => false,
        ) );
        return $result;
    }

    private static function send( $feed, $payload, $is_test, $is_sample = false ) {
        $service = self::service( $feed['service'] ?? '' );
        if ( ! $service ) {
            return self::failure( __( 'This service is not available. Is the add-on that provides it active?', 'surveyjs' ) );
        }
        $connection = SurveyJS_Connections::find( (string) ( $feed['connection'] ?? '' ) );
        if ( ! $connection || $connection['service'] !== $service->id() ) {
            return self::failure( __( 'Choose a connection: the one this integration used was disconnected.', 'surveyjs' ) );
        }
        $credentials = SurveyJS_Connections::credentials( $connection );
        if ( ! $credentials ) {
            $error = __( 'The saved key cannot be read, for example because the site\'s security keys changed. Enter the key again on Settings > Integrations.', 'surveyjs' );
            SurveyJS_Connections::mark( $connection['id'], 'error', $error );
            return self::failure( $error, 'reauth' );
        }
        $result = $is_test ? $service->test( $feed, $credentials, $payload, $is_sample ) : $service->deliver( $feed, $credentials, $payload );
        if ( 'reauth' === ( $result['outcome'] ?? '' ) ) {
            SurveyJS_Connections::mark( $connection['id'], 'error', $result['error'] );
        } elseif ( ! empty( $result['ok'] ) ) {
            SurveyJS_Connections::mark( $connection['id'], 'ok' );
        }
        return $result;
    }

    private static function failure( $error, $outcome = 'permanent' ) {
        return array(
            'ok'      => false,
            'code'    => 0,
            'error'   => $error,
            'outcome' => $outcome,
            'note'    => '',
        );
    }

    public static function targets( $connection_id, $target, $args, $refresh ) {
        $connection = SurveyJS_Connections::find( $connection_id );
        $service = $connection ? self::service( $connection['service'] ) : null;
        $credentials = $service ? SurveyJS_Connections::credentials( $connection ) : null;
        if ( ! $credentials ) {
            return new WP_Error( 'surveyjs_connection_not_found', __( 'The connection was not found or its key cannot be read.', 'surveyjs' ) );
        }
        $cache_key = self::TARGETS_TRANSIENT . $connection_id;
        $cache = get_transient( $cache_key );
        $cache = is_array( $cache ) ? $cache : array();
        $hash = md5( $target . wp_json_encode( $args ) );
        if ( ! $refresh && isset( $cache[ $hash ] ) ) {
            return $cache[ $hash ];
        }
        $result = $service->targets( $credentials, $target, $args );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $cache[ $hash ] = $result;
        set_transient( $cache_key, $cache, self::TARGETS_LIFETIME );
        return $result;
    }

    public static function forget_targets( $connection_id ) {
        delete_transient( self::TARGETS_TRANSIENT . $connection_id );
    }

    // Replaces {field:<question name>}, {all_fields}, {form_name}, {site_name}, {entry_id} and {entry_url} with the
    // answers snapshot. $escape is applied to the answers and names, not to the template.
    public static function render_tags( $template, $payload, $escape = null, $all_fields = null ) {
        $escape = $escape ? $escape : function ( $text ) {
            return $text;
        };
        $answers = is_array( $payload['answers'] ?? null ) ? $payload['answers'] : array();
        $tags = array(
            '{form_name}' => $escape( self::plain( $payload['form']['name'] ?? '' ) ),
            '{site_name}' => $escape( self::plain( $payload['site']['name'] ?? '' ) ),
            '{entry_id}'  => (string) ( $payload['entry']['id'] ?? '' ),
            '{entry_url}' => (string) ( $payload['entry']['url'] ?? '' ),
        );
        if ( false !== strpos( (string) $template, '{all_fields}' ) ) {
            $tags['{all_fields}'] = $all_fields ? $all_fields( $payload ) : self::all_fields_text( $payload );
        }
        foreach ( $answers as $name => $text ) {
            $tags[ '{field:' . $name . '}' ] = $escape( self::plain( $text ) );
        }
        return (string) preg_replace( '/\{field:[^}]*\}/', '', strtr( (string) $template, $tags ) );
    }

    // Saved answers pass through wp_kses(), which stores "&" as "&amp;"; services get the plain text.
    public static function plain( $text ) {
        return wp_specialchars_decode( is_scalar( $text ) ? (string) $text : '', ENT_QUOTES );
    }

    public static function all_fields_text( $payload ) {
        $lines = array();
        foreach ( is_array( $payload['fields'] ?? null ) ? $payload['fields'] : array() as $field ) {
            if ( '' !== (string) ( $field['text'] ?? '' ) ) {
                $lines[] = self::plain( $field['title'] ) . ': ' . self::plain( $field['text'] );
            }
        }
        return implode( "\n", $lines );
    }

    public static function settings_url( $service_id = '' ) {
        return add_query_arg( array( 'page' => 'surveyjs-settings', 'tab' => SurveyJS_Connections::TAB ), admin_url( 'admin.php' ) ) . ( '' !== $service_id ? '#sjs-service-' . $service_id : '' );
    }

    public static function editor_config() {
        $services = array();
        foreach ( self::services() as $service ) {
            $services[] = array(
                'id'       => $service->id(),
                'label'    => $service->label(),
                'kind'     => $service->editor_kind(),
                'defaults' => (object) $service->feed_defaults(),
            );
        }
        return array(
            'services'    => $services,
            'connections' => SurveyJS_Connections::public_list(),
            'settingsUrl' => self::settings_url(),
            'testUrl'     => add_query_arg( array( 'action' => 'SurveyJS_TestIntegration' ), admin_url( 'admin-ajax.php' ) ),
            'targetsUrl'  => add_query_arg( array( 'action' => 'SurveyJS_IntegrationTargets' ), admin_url( 'admin-ajax.php' ) ),
        );
    }

    public static function render_templates() {
        ?>
        <template id="wp-sjs-integration-template-missing">
            <div class="wp-sjs-item">
                <?php SurveyJS_Editor::render_item_header(); ?>
                <div class="wp-sjs-item__body" id="wp-sjs-item-__KEY__-body" hidden>
                    <p><?php esc_html_e( 'This integration uses a service that is not available now, for example because the add-on that provides it is inactive. It is kept and sends nothing until the service is back.', 'surveyjs' ); ?></p>
                    <?php SurveyJS_Editor::render_item_actions(); ?>
                </div>
            </div>
        </template>
        <?php
        foreach ( self::services() as $service ) {
            ?>
            <template id="wp-sjs-integration-template-<?php echo esc_attr( $service->id() ); ?>">
                <div class="wp-sjs-item" data-service="<?php echo esc_attr( $service->id() ); ?>">
                    <?php SurveyJS_Editor::render_item_header(); ?>
                    <div class="wp-sjs-item__body" id="wp-sjs-item-__KEY__-body" hidden>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="wp-sjs-item-__KEY__-name"><?php esc_html_e( 'Name', 'surveyjs' ); ?></label></th>
                                <td><input type="text" id="wp-sjs-item-__KEY__-name" class="regular-text" data-field="name" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wp-sjs-item-__KEY__-connection"><?php esc_html_e( 'Connection', 'surveyjs' ); ?></label></th>
                                <td>
                                    <select id="wp-sjs-item-__KEY__-connection" data-field="connection" data-connections></select>
                                    <span class="wp-sjs-single-choice" data-single-choice hidden></span>
                                    <p class="description" data-no-connection hidden>
                                        <?php
                                        printf(
                                            /* translators: %s: service name, for example Slack */
                                            esc_html__( 'Connect %s on Settings > Integrations first.', 'surveyjs' ),
                                            esc_html( $service->label() )
                                        );
                                        ?>
                                        <a href="<?php echo esc_url( self::settings_url( $service->id() ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open the settings', 'surveyjs' ); ?></a>
                                    </p>
                                </td>
                            </tr>
                            <?php $service->render_feed_rows(); ?>
                            <?php SurveyJS_Editor::render_conditions_row(); ?>
                            <?php $service->render_rows_after_conditions(); ?>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Test', 'surveyjs' ); ?></th>
                                <td>
                                    <p><button type="button" class="button" data-action="test-integration"><?php esc_html_e( 'Send test', 'surveyjs' ); ?></button></p>
                                    <p class="description"><?php esc_html_e( 'Sends the latest entry of this form, or sample answers when there are none, with the settings above (saved or not).', 'surveyjs' ); ?></p>
                                    <div data-test-result aria-live="polite"></div>
                                </td>
                            </tr>
                            <?php SurveyJS_Editor::render_log_row(); ?>
                        </table>
                        <?php SurveyJS_Editor::render_item_actions(); ?>
                    </div>
                </div>
            </template>
            <?php
        }
    }
}
