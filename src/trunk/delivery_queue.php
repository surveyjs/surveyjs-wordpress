<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Entries sent to other services (webhooks, integrations) wait in non-autoloaded options until WP-Cron delivers them.
// The option name holds the time the job is due, so the jobs sort by it, and one cron event runs every due job:
// a busy form or a long run of retries never fills the autoloaded "cron" option with one event per entry.
// A handler class sends one job: deliver_job( $job, $survey ) returns the result or null when the target is gone,
// retry_delays() says when a failed job is tried again, resend_job() builds the job again from a stored entry.
class SurveyJS_Delivery_Queue {

    const RUN_HOOK = 'surveyjs_deliver';
    const QUEUE_PREFIX = 'surveyjs_delivery_queue_';
    const LOG_PREFIX = 'surveyjs_delivery_log_';
    const LOG_SIZE = 30;
    const BATCH = 50;
    const LEASE = 15 * MINUTE_IN_SECONDS;
    const MAX_RETRY_AFTER = 6 * HOUR_IN_SECONDS;
    const EVENT = 'entry.submitted';
    const HANDLERS = array(
        'webhook'     => 'SurveyJS_Webhooks',
        'integration' => 'SurveyJS_Integrations',
    );

    public static function init() {
        add_action( self::RUN_HOOK, array( __CLASS__, 'run' ) );
    }

    // Queued jobs hold respondents' answers. They are found by name rather than through the cron event, so a job
    // whose event was lost is deleted too.
    public static function unschedule() {
        wp_unschedule_hook( self::RUN_HOOK );
        foreach ( self::names() as $name ) {
            delete_option( $name );
        }
    }

    public static function delivery_id() {
        return str_replace( '-', '', wp_generate_uuid4() );
    }

    // Returns the delivery id, or false when WP-Cron could not schedule the job and it was dropped.
    public static function enqueue( $type, $form_id, $item_id, $entry_id, $job ) {
        $job = self::job( $type, $form_id, $item_id, $entry_id, $job );
        $name = self::store( $job, time() );
        if ( self::schedule( time() ) ) {
            return $job['key'];
        }
        delete_option( $name );
        self::log( $form_id, $item_id, array(
            'type'    => $type,
            'ok'      => false,
            'code'    => 0,
            'error'   => __( 'WP-Cron could not schedule the request, so it was not sent.', 'surveyjs' ),
            'event'   => self::EVENT,
            'entry'   => (int) $entry_id,
            'attempt' => 1,
            'retry'   => false,
        ) );
        return false;
    }

    public static function spawn() {
        if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
            spawn_cron();
        }
    }

    private static function job( $type, $form_id, $item_id, $entry_id, $job ) {
        return array_merge( $job, array(
            'type'    => $type,
            'form'    => (int) $form_id,
            'item'    => (string) $item_id,
            'entry'   => (int) $entry_id,
            'attempt' => 1,
            'key'     => self::delivery_id(),
        ) );
    }

    private static function store( $job, $due ) {
        $name = self::QUEUE_PREFIX . sprintf( '%010d', $due ) . '_' . $job['key'];
        add_option( $name, $job, '', false );
        return $name;
    }

    private static function due( $name ) {
        return (int) substr( $name, strlen( self::QUEUE_PREFIX ), 10 );
    }

    // Oldest due first: the due time leads the option name.
    private static function names( $limit = 0 ) {
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name", $wpdb->esc_like( self::QUEUE_PREFIX ) . '%' );
        if ( $limit ) {
            $sql .= $wpdb->prepare( ' LIMIT %d', $limit );
        }
        return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- prepared above; the options API cannot list options by prefix, and the queue is read only by WP-Cron.
    }

    private static function schedule( $time ) {
        $next = wp_next_scheduled( self::RUN_HOOK );
        if ( $next && $next <= $time ) {
            return true;
        }
        if ( $next ) {
            wp_unschedule_event( $next, self::RUN_HOOK );
        }
        return true === wp_schedule_single_event( $time, self::RUN_HOOK, array(), true );
    }

    // WP-Cron calls this without a time; tests pass one to run retries that are not due yet.
    public static function run( $until = null ) {
        $until = is_numeric( $until ) ? (int) $until : time();
        $ran = 0;
        foreach ( self::names( self::BATCH ) as $name ) {
            if ( self::due( $name ) > $until ) {
                break;
            }
            self::deliver( $name );
            ++$ran;
        }
        $next = self::names( 1 );
        if ( $next ) {
            self::schedule( max( time(), self::due( $next[0] ) ) );
        }
        return $ran;
    }

    // Moves the job to a name due after the lease, so a run that dies while sending leaves it to be retried, and
    // a second run that read the same job finds it gone and skips it.
    private static function claim( $name ) {
        $job = get_option( $name );
        if ( ! is_array( $job ) || empty( $job['key'] ) ) {
            delete_option( $name );
            return null;
        }
        $lease = self::QUEUE_PREFIX . sprintf( '%010d', time() + self::LEASE ) . '_' . $job['key'];
        if ( $lease === $name || ! add_option( $lease, $job, '', false ) ) {
            return null;
        }
        if ( ! delete_option( $name ) ) {
            delete_option( $lease );
            return null;
        }
        return array( $lease, $job );
    }

    private static function deliver( $name ) {
        $claimed = self::claim( $name );
        if ( ! $claimed ) {
            return;
        }
        list( $lease, $job ) = $claimed;
        $handler = self::HANDLERS[ $job['type'] ?? '' ] ?? null;
        $survey = $handler ? SurveyJS_Forms::find( (int) $job['form'], true ) : null;
        $result = $survey ? $handler::deliver_job( $job, $survey ) : null;
        if ( null === $result ) {
            delete_option( $lease );
            return;
        }
        $attempt = (int) $job['attempt'];
        $delays = $handler::retry_delays();
        $retry = ! $result['ok'] && 'permanent' !== ( $result['outcome'] ?? 'retry' ) && $attempt <= count( $delays );
        if ( $retry ) {
            $job['attempt'] = $attempt + 1;
            $due = time() + max( $delays[ $attempt - 1 ], min( (int) ( $result['retry_after'] ?? 0 ), self::MAX_RETRY_AFTER ) );
            $retry_name = self::store( $job, $due );
            $retry = self::schedule( $due );
            if ( ! $retry ) {
                delete_option( $retry_name );
            }
        }
        delete_option( $lease );
        self::log( (int) $job['form'], $job['item'], $result + array(
            'type'    => $job['type'],
            'event'   => self::EVENT,
            'entry'   => (int) $job['entry'],
            'attempt' => $attempt,
            'retry'   => $retry,
        ) );
    }

    public static function resend( $survey, $type, $item_id, $entry_id ) {
        $handler = self::HANDLERS[ $type ] ?? null;
        $entry = $handler ? SurveyJS_Entries::find_entry( $survey->id, $entry_id ) : null;
        if ( ! $entry ) {
            return new WP_Error( 'surveyjs_entry_not_found', __( 'The entry is no longer stored, so it cannot be sent again.', 'surveyjs' ) );
        }
        $job = $handler::resend_job( $survey, SurveyJS_Form_Settings::get( $survey ), $item_id, $entry );
        if ( is_wp_error( $job ) ) {
            return $job;
        }
        self::deliver( self::store( self::job( $type, $survey->id, $item_id, $entry->id, $job ), time() ) );
        return true;
    }

    public static function log( $form_id, $item_id, $result ) {
        $log = self::log_entries( $form_id );
        array_unshift( $log, array(
            'type'    => (string) $result['type'],
            'item'    => (string) $item_id,
            'time'    => time(),
            'event'   => (string) $result['event'],
            'entry'   => (int) $result['entry'],
            'attempt' => (int) $result['attempt'],
            'ok'      => (bool) $result['ok'],
            'code'    => (int) $result['code'],
            'error'   => mb_substr( (string) $result['error'], 0, 300 ),
            'note'    => mb_substr( (string) ( $result['note'] ?? '' ), 0, 300 ),
            'retry'   => (bool) $result['retry'],
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
