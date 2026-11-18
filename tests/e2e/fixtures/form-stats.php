<?php
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- absolute path of WordPress in the sandbox container; the script is piped to php over stdin, so it has no __DIR__.
if ( ! defined( 'ABSPATH' ) ) exit;

$command = $argv[1] ?? '';
if ( 'create' === $command ) {
    $name = 'E2E Form stats ' . microtime( true );
    $form_id = SurveyJS_Forms::create( array(
        'name' => $name,
        'json' => wp_json_encode( array( 'elements' => array( array( 'type' => 'text', 'name' => 'e2e_name', 'title' => 'What is your name?' ) ) ) ),
    ) );
    $pages = array();
    $page_ids = array();
    foreach ( array( 'top' => '', 'below' => '<div style="height:3000px">E2E spacer</div>' ) as $key => $before ) {
        $page_ids[] = wp_insert_post( array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $name . ' ' . $key,
            'post_content' => $before . '[surveyjs id="' . $form_id . '"]',
        ) );
        $pages[ $key ] = get_permalink( end( $page_ids ) );
    }
    echo wp_json_encode( array( 'form' => $form_id, 'name' => $name, 'pages' => $pages, 'pageIds' => $page_ids ) ), "\n";
} elseif ( 'stats' === $command ) {
    $form_id = absint( $argv[2] ?? 0 );
    $totals = SurveyJS_Form_Stats::totals( array( $form_id ) );
    echo wp_json_encode( $totals[ $form_id ] ?? array( 'views' => 0, 'submissions' => 0 ) ), "\n";
} elseif ( 'setting' === $command ) {
    $settings = (array) get_option( 'surveyjs-settings', array() );
    $previous = array_key_exists( SurveyJS_Form_Stats::SETTING, $settings ) ? ( $settings[ SurveyJS_Form_Stats::SETTING ] ? 'on' : 'off' ) : 'default';
    if ( 'default' === ( $argv[2] ?? '' ) ) {
        unset( $settings[ SurveyJS_Form_Stats::SETTING ] );
    } else {
        $settings[ SurveyJS_Form_Stats::SETTING ] = 'on' === ( $argv[2] ?? '' );
    }
    update_option( 'surveyjs-settings', array_filter( $settings, 'is_string', ARRAY_FILTER_USE_KEY ) );
    echo wp_json_encode( array( 'previous' => $previous ) ), "\n";
} elseif ( 'cleanup' === $command ) {
    $form_id = absint( $argv[2] ?? 0 );
    foreach ( array_slice( $argv, 3 ) as $page_id ) {
        wp_delete_post( absint( $page_id ), true );
    }
    if ( $form_id ) {
        SurveyJS_Forms::delete( $form_id );
    }
    echo "{}\n";
}
