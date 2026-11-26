<?php
// Runs inside the WordPress container (php reads it from stdin).
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';

function surveyjs_e2e_queue() {
    global $wpdb;
    return $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name", $wpdb->esc_like( SurveyJS_Delivery_Queue::QUEUE_PREFIX ) . '%' ) );
}

function surveyjs_e2e_private( $method, ...$args ) {
    return ( new ReflectionMethod( 'SurveyJS_Webhooks', $method ) )->invoke( null, ...$args );
}

if ( 'run-webhooks' === $command ) {
    // The sandbox cannot reach its own wp-cron.php, so due deliveries are run here; "all" also runs retries that are not due yet.
    echo wp_json_encode( array( 'ran' => SurveyJS_Delivery_Queue::run( 'all' === ( $argv[2] ?? '' ) ? PHP_INT_MAX : null ) ) );
} elseif ( 'webhook-state' === $command ) {
    $queue = surveyjs_e2e_queue();
    echo wp_json_encode( array(
        'queued'    => count( $queue ),
        'scheduled' => array_values( array_filter( array_map( function ( $name ) {
            return (int) substr( $name, strlen( SurveyJS_Delivery_Queue::QUEUE_PREFIX ), 10 ) - time();
        }, $queue ), function ( $delay ) {
            return $delay > 0;
        } ) ),
        'log'       => SurveyJS_Delivery_Queue::log_entries( (int) $argv[2] ),
    ) );
} elseif ( 'clear-webhooks' === $command ) {
    SurveyJS_Delivery_Queue::unschedule();
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
    wp_unschedule_hook( SurveyJS_Delivery_Queue::RUN_HOOK );
    add_filter( 'schedule_event', '__return_false' );
    SurveyJS_Webhooks::dispatch( $form, SurveyJS_Form_Settings::get( $form ), array( 'visitor_name' => 'Ann' ), 0 );
    remove_filter( 'schedule_event', '__return_false' );
    echo wp_json_encode( array(
        'queued' => count( surveyjs_e2e_queue() ),
        'log'    => SurveyJS_Delivery_Queue::log_entries( (int) $argv[2] ),
    ) );
} elseif ( 'deactivate' === $command ) {
    add_option( SurveyJS_Delivery_Queue::QUEUE_PREFIX . '0000000000_' . str_repeat( 'a', 32 ), array( 'body' => '{}' ), '', false );
    $before = count( surveyjs_e2e_queue() );
    SurveyJS_Delivery_Queue::unschedule();
    echo wp_json_encode( array( 'before' => $before, 'after' => count( surveyjs_e2e_queue() ) ) );
}
