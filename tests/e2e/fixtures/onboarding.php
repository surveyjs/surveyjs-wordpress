<?php
// Runs inside the WordPress container: php reads it from stdin.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';

if ( 'get' === $command ) {
    echo wp_json_encode( array( 'state' => get_option( SurveyJS_Welcome::CHECKLIST_OPTION, null ) ) );
} elseif ( 'set' === $command ) {
    $state = json_decode( (string) ( $argv[2] ?? 'null' ), true );
    if ( null === $state ) {
        delete_option( SurveyJS_Welcome::CHECKLIST_OPTION );
    } else {
        update_option( SurveyJS_Welcome::CHECKLIST_OPTION, $state );
    }
    echo '{}';
} elseif ( 'entry' === $command ) {
    echo wp_json_encode( array( 'received' => SurveyJS_Welcome::received_entry( array( (int) $argv[2] ) ) ) );
} elseif ( 'progress' === $command ) {
    $state = array(
        'dismissed' => false,
        'completed' => false,
        'progress'  => array(
            'done'  => (int) $argv[2],
            'total' => 3,
            'next'  => (string) $argv[3],
            'url'   => admin_url( 'post-new.php?post_type=page' ),
            'time'  => time(),
        ),
    );
    update_option( SurveyJS_Welcome::CHECKLIST_OPTION, $state );
    echo '{}';
} elseif ( 'fresh' === $command ) {
    // Turns the site into one that has never had a form; destructive, so only for a throwaway sandbox.
    global $wpdb;
    do {
        $form_ids = get_posts( array( 'post_type' => SurveyJS_Forms::POST_TYPE, 'post_status' => 'any', 'numberposts' => 100, 'fields' => 'ids' ) );
        foreach ( $form_ids as $form_id ) {
            wp_delete_post( $form_id, true );
        }
    } while ( $form_ids );
    $placed = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND ( post_content LIKE '%wp:sjs/gutenberg-block%' OR post_content LIKE '%[survey%' )" );
    foreach ( $placed as $placed_id ) {
        wp_update_post( array( 'ID' => (int) $placed_id, 'post_status' => 'draft' ) );
    }
    $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', SurveyJS_Entries::table() ) );
    foreach ( array( SurveyJS_Welcome::CHECKLIST_OPTION, SurveyJS_Welcome::ENTRY_FORMS_OPTION, 'surveyjs_received_entry' ) as $option_name ) {
        delete_option( $option_name );
    }
    echo wp_json_encode( array( 'unpublished' => count( $placed ) ) );
} elseif ( 'delete' === $command ) {
    foreach ( array_slice( $argv, 2 ) as $delete_id ) {
        wp_delete_post( (int) $delete_id, true );
    }
    echo '{}';
} elseif ( 'forget-install' === $command ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $deactivated = array_values( array_filter( array( 'surveyjs/surveyjs.php', 'surveyjs-forms-addon/surveyjs-forms-addon.php' ), 'is_plugin_active' ) );
    deactivate_plugins( array_reverse( $deactivated ), true );
    $had_legacy_flag = (bool) get_option( SurveyJS_Upgrade_Notice::FLAG_OPTION );
    delete_option( SurveyJS_Install::VERSION_OPTION );
    echo wp_json_encode( array( 'deactivated' => $deactivated, 'hadLegacyFlag' => $had_legacy_flag ) );
} elseif ( 'restore-install' === $command ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    foreach ( array_slice( $argv, 3 ) as $plugin_file ) {
        activate_plugin( $plugin_file );
    }
    if ( ! $argv[2] ) {
        delete_option( SurveyJS_Upgrade_Notice::FLAG_OPTION );
    }
    echo '{}';
}
