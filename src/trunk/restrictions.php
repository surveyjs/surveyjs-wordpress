<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Restrictions {

    const PERIODS          = array( 'total', 'day', 'week', 'month', 'year' );
    const RESPONDENT_TYPES = array( 'user', 'ip' );
    const DATETIME_FORMAT  = 'Y-m-d\TH:i';
    const STATUS_ACTION    = 'SurveyJS_FormStatus';
    const MESSAGE_KEYS     = array(
        'schedule_pending' => 'schedulePendingMessage',
        'schedule_expired' => 'scheduleExpiredMessage',
        'login'            => 'requireLoginMessage',
        'entry_limit'      => 'entryLimitMessage',
        'respondent_limit' => 'respondentLimitMessage',
    );

    public static function init() {
        add_action( 'wp_ajax_' . self::STATUS_ACTION, array( __CLASS__, 'status' ) );
        add_action( 'wp_ajax_nopriv_' . self::STATUS_ACTION, array( __CLASS__, 'status' ) );
    }

    public static function sanitize_datetime( $value ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        return self::parse_datetime( $value ) ? $value : '';
    }

    private static function parse_datetime( $value ) {
        if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat( '!' . self::DATETIME_FORMAT, $value, wp_timezone() );
        return $date && $date->format( self::DATETIME_FORMAT ) === $value ? $date : null;
    }

    private static function format_datetime( $value ) {
        $date = self::parse_datetime( $value );
        return $date ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date->getTimestamp() ) : '';
    }

    private static function respondent_limit( $settings ) {
        return ! empty( $settings['storeEntries'] ) && ! empty( $settings['limitPerRespondent'] ) ? (string) $settings['respondentBy'] : '';
    }

    private static function entry_limit( $settings ) {
        return ! empty( $settings['storeEntries'] ) && ! empty( $settings['limitEntries'] );
    }

    public static function enabled( $settings ) {
        return ! empty( $settings['requireLogin'] ) || ! empty( $settings['scheduleEnabled'] ) || self::entry_limit( $settings ) || '' !== self::respondent_limit( $settings );
    }

    public static function requires_login( $settings ) {
        return ! empty( $settings['requireLogin'] ) || 'user' === self::respondent_limit( $settings );
    }

    public static function uses_ip_hash( $settings ) {
        return 'ip' === self::respondent_limit( $settings );
    }

    // Keyed with a site salt, so the stored value cannot be turned back into the address by trying every IP.
    public static function ip_hash() {
        $ip = SurveyJS_Entries::remote_ip();
        return $ip ? hash_hmac( 'sha256', $ip, wp_salt() ) : null;
    }

    public static function entry_ip_hash( $settings ) {
        return self::uses_ip_hash( $settings ) ? self::ip_hash() : null;
    }

    // A page with the form can be served from a page cache to every visitor, so the IP limit is left out
    // when $per_visitor is false: it is checked when the form loads (status()) and when it is submitted.
    public static function check( $survey, $settings, $per_visitor = true, $redirect = '' ) {
        $reason = self::reason( $survey, $settings, $per_visitor );
        if ( '' === $reason ) {
            return null;
        }
        return array(
            'reason'  => $reason,
            'message' => self::message( $reason, $settings, $redirect ),
        );
    }

    private static function reason( $survey, $settings, $per_visitor ) {
        if ( ! empty( $settings['scheduleEnabled'] ) ) {
            $start = self::parse_datetime( $settings['scheduleStart'] );
            if ( $start && time() < $start->getTimestamp() ) {
                return 'schedule_pending';
            }
            $end = self::parse_datetime( $settings['scheduleEnd'] );
            if ( $end && time() >= $end->getTimestamp() ) {
                return 'schedule_expired';
            }
        }
        if ( self::requires_login( $settings ) && ! is_user_logged_in() ) {
            return 'login';
        }
        if ( self::entry_limit( $settings ) && self::count_entries( (int) $survey->id, self::period_start( $settings['entryLimitPeriod'] ) ) >= (int) $settings['entryLimit'] ) {
            return 'entry_limit';
        }
        $by = self::respondent_limit( $settings );
        $respondent = '';
        if ( 'user' === $by ) {
            $respondent = (string) get_current_user_id();
        } elseif ( 'ip' === $by && $per_visitor ) {
            $respondent = (string) self::ip_hash();
        }
        if ( '' !== $respondent && self::count_respondent_entries( (int) $survey->id, $by, $respondent ) >= (int) $settings['respondentLimit'] ) {
            return 'respondent_limit';
        }
        return '';
    }

    private static function period_start( $period ) {
        $today = ( new DateTimeImmutable( 'now', wp_timezone() ) )->setTime( 0, 0 );
        switch ( $period ) {
            case 'day':
                $start = $today;
                break;
            case 'week':
                $start = $today->modify( '-' . ( ( (int) $today->format( 'w' ) - (int) get_option( 'start_of_week', 1 ) + 7 ) % 7 ) . ' days' );
                break;
            case 'month':
                $start = $today->modify( 'first day of this month' );
                break;
            case 'year':
                $start = $today->setDate( (int) $today->format( 'Y' ), 1, 1 );
                break;
            default:
                return '';
        }
        return $start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
    }

    private static function count_entries( $form_id, $since ) {
        global $wpdb;
        $cache_key = 'entry_count:' . $form_id . ':' . $since . ':' . wp_cache_get_last_changed( SurveyJS_Entries::CACHE_GROUP );
        $count = wp_cache_get( $cache_key, SurveyJS_Entries::CACHE_GROUP );
        if ( false !== $count ) {
            return (int) $count;
        }
        if ( '' === $since ) {
            $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE form_id = %d AND status IN ( 'unread', 'read' )", SurveyJS_Entries::table(), $form_id ) );
        } else {
            $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE form_id = %d AND status IN ( 'unread', 'read' ) AND created_at >= %s", SurveyJS_Entries::table(), $form_id, $since ) );
        }
        wp_cache_set( $cache_key, (int) $count, SurveyJS_Entries::CACHE_GROUP, HOUR_IN_SECONDS );
        return (int) $count;
    }

    private static function count_respondent_entries( $form_id, $by, $respondent ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE form_id = %d AND %i = %s AND status IN ( 'unread', 'read' )",
            SurveyJS_Entries::table(),
            $form_id,
            'user' === $by ? 'user_id' : 'ip_hash',
            $respondent
        ) );
    }

    private static function message( $reason, $settings, $redirect ) {
        $text = strtr(
            SurveyJS_Form_Settings::value( $settings, self::MESSAGE_KEYS[ $reason ] ),
            array(
                '{open_date}'  => esc_html( self::format_datetime( $settings['scheduleStart'] ) ),
                '{close_date}' => esc_html( self::format_datetime( $settings['scheduleEnd'] ) ),
            )
        );
        $html = wpautop( wp_kses_post( $text ) );
        if ( 'login' === $reason ) {
            $html .= '<p><a class="wp-sjs-restricted__login" href="' . esc_url( wp_login_url( $redirect ) ) . '">' . esc_html__( 'Log in', 'surveyjs' ) . '</a></p>';
        }
        return $html;
    }

    public static function render( $survey, $restriction ) {
        // The form can open again (schedule, limit raised, entries deleted) while a page cache would keep serving the message.
        // Visitors who are not logged in all get the same login message, so that page can stay cached.
        if ( 'login' !== $restriction['reason'] && ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        wp_enqueue_style( 'wps-survey-override-css' );
        return '<div class="wp-sjs-plugin wp-sjs-restricted" id="surveyContainer-' . esc_attr( $survey->id ) . '" data-reason="' . esc_attr( $restriction['reason'] ) . '">'
            . '<div class="wp-sjs-restricted__message">' . wp_kses_post( $restriction['message'] ) . '</div></div>';
    }

    public static function status_url( $survey ) {
        return add_query_arg( array( 'action' => self::STATUS_ACTION, 'SurveyId' => (int) $survey->id ), admin_url( 'admin-ajax.php' ) );
    }

    private static function referer() {
        $referer = wp_get_referer();
        return $referer ? $referer : home_url( '/' );
    }

    // Read-only and public: it tells the visitor whether the form is open for them, so it needs no nonce.
    public static function status() {
        $survey = ( new SurveyJS_Client() )->getSurvey( absint( wp_unslash( $_GET['SurveyId'] ?? 0 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only public status, see above.
        if ( ! $survey ) {
            wp_send_json_error( array( 'message' => 'Form not found' ), 404 );
        }
        $restriction = self::check( $survey, SurveyJS_Form_Settings::get( $survey ), true, self::referer() );
        wp_send_json_success( $restriction ? array( 'open' => false, 'reason' => $restriction['reason'], 'html' => $restriction['message'] ) : array( 'open' => true ) );
    }

    public static function reject_if_closed( $survey, $settings ) {
        $restriction = self::check( $survey, $settings, true, self::referer() );
        if ( ! $restriction ) {
            return;
        }
        wp_send_json_error(
            array(
                'restricted' => true,
                'reason'     => $restriction['reason'],
                'message'    => trim( wp_strip_all_tags( $restriction['message'] ) ),
                'html'       => $restriction['message'],
            ),
            403
        );
    }
}
