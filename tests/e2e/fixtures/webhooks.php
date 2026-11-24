<?php
// Runs inside the WordPress container (php reads it from stdin).
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';

function surveyjs_e2e_queued() {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( SurveyJS_Webhooks::QUEUE_PREFIX ) . '%' ) );
}

function surveyjs_e2e_private( $method, ...$args ) {
    return ( new ReflectionMethod( 'SurveyJS_Webhooks', $method ) )->invoke( null, ...$args );
}

if ( 'run-webhooks' === $command ) {
    // The sandbox cannot reach its own wp-cron.php, so due deliveries are run here; "all" also runs scheduled retries.
    $ran = 0;
    foreach ( _get_cron_array() as $timestamp => $hooks ) {
        foreach ( $hooks[ SurveyJS_Webhooks::DELIVER_HOOK ] ?? array() as $event ) {
            if ( 'all' !== ( $argv[2] ?? '' ) && $timestamp > time() ) {
                continue;
            }
            wp_unschedule_event( $timestamp, SurveyJS_Webhooks::DELIVER_HOOK, $event['args'] );
            SurveyJS_Webhooks::deliver( ...$event['args'] );
            ++$ran;
        }
    }
    echo wp_json_encode( array( 'ran' => $ran ) );
} elseif ( 'webhook-state' === $command ) {
    $scheduled = array();
    foreach ( _get_cron_array() as $timestamp => $hooks ) {
        if ( isset( $hooks[ SurveyJS_Webhooks::DELIVER_HOOK ] ) ) {
            $scheduled[] = $timestamp - time();
        }
    }
    echo wp_json_encode( array(
        'queued'    => surveyjs_e2e_queued(),
        'scheduled' => $scheduled,
        'log'       => SurveyJS_Webhooks::log_entries( (int) $argv[2] ),
    ) );
} elseif ( 'clear-webhooks' === $command ) {
    wp_unschedule_hook( SurveyJS_Webhooks::DELIVER_HOOK );
    surveyjs_e2e_private( 'delete_queue' );
    echo '{}';
} elseif ( 'conditions' === $command ) {
    echo wp_json_encode( SurveyJS_Conditions::matches( json_decode( $argv[2], true ), json_decode( $argv[3], true ) ) );
} elseif ( 'addresses' === $command ) {
    $result = array();
    foreach ( json_decode( $argv[2], true ) as $ip ) {
        $result[ $ip ] = surveyjs_e2e_private( 'is_public_ip', $ip );
    }
    echo wp_json_encode( $result );
} elseif ( 'resolve' === $command ) {
    $result = array();
    foreach ( json_decode( $argv[2], true ) as $url ) {
        $result[ $url ] = surveyjs_e2e_private( 'checked_resolve', $url );
    }
    echo wp_json_encode( $result );
} elseif ( 'unschedulable' === $command ) {
    $form = SurveyJS_Forms::find( (int) $argv[2] );
    add_filter( 'schedule_event', '__return_false' );
    SurveyJS_Webhooks::dispatch( $form, SurveyJS_Form_Settings::get( $form ), array( 'visitor_name' => 'Ann' ), 0 );
    remove_filter( 'schedule_event', '__return_false' );
    echo wp_json_encode( array(
        'queued' => surveyjs_e2e_queued(),
        'log'    => SurveyJS_Webhooks::log_entries( (int) $argv[2] ),
    ) );
} elseif ( 'deactivate' === $command ) {
    add_option( SurveyJS_Webhooks::QUEUE_PREFIX . str_repeat( 'a', 32 ), array( 'body' => '{}' ), '', false );
    $before = surveyjs_e2e_queued();
    SurveyJS_Webhooks::unschedule();
    echo wp_json_encode( array( 'before' => $before, 'after' => surveyjs_e2e_queued() ) );
}
