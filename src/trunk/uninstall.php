<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

function surveyjs_uninstall_site() {
    global $wpdb;
    $preview_page_id = (int) get_option( 'surveyjs_full_preview_page_id' );
    if ( $preview_page_id ) {
        wp_delete_post( $preview_page_id, true );
        delete_option( 'surveyjs_full_preview_page_id' );
    }
    $settings = (array) get_option( 'surveyjs-settings' );
    if ( empty( $settings['uninstall_data'] ) ) {
        return;
    }

    $form_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( %s, %s )", 'sjs_form', 'sjs_template' ) );
    foreach ( $form_ids as $form_id ) {
        wp_delete_post( (int) $form_id, true );
    }

    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sjs_results, {$wpdb->prefix}sjs_my_surveys" );

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE ( option_name = %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s ) AND option_name NOT LIKE %s AND option_name NOT LIKE %s AND option_name NOT LIKE %s",
        'surveyjs-settings',
        $wpdb->esc_like( 'surveyjs_' ) . '%',
        $wpdb->esc_like( '_transient_surveyjs_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_surveyjs_' ) . '%',
        $wpdb->esc_like( 'surveyjs_pro_' ) . '%',
        $wpdb->esc_like( '_transient_surveyjs_pro_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_surveyjs_pro_' ) . '%'
    ) );
    wp_cache_delete( 'alloptions', 'options' );
    wp_cache_delete( 'notoptions', 'options' );
    delete_metadata( 'user', 0, 'surveyjs_upgrade_notice_dismissed', '', true );

    $upload_dir = wp_upload_dir( null, false );
    if ( empty( $upload_dir['error'] ) ) {
        surveyjs_uninstall_remove_dir( $upload_dir['basedir'] . '/surveyjs' );
    }
}

function surveyjs_uninstall_remove_dir( $dir ) {
    global $wp_filesystem;
    if ( ! is_dir( $dir ) || is_link( $dir ) ) {
        return;
    }
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $can_rmdir = WP_Filesystem();
    $items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $items as $item ) {
        if ( $item->isDir() && ! $item->isLink() ) {
            if ( $can_rmdir ) {
                $wp_filesystem->rmdir( $item->getPathname() );
            }
        } else {
            wp_delete_file( $item->getPathname() );
        }
    }
    if ( $can_rmdir ) {
        $wp_filesystem->rmdir( $dir );
    }
}

if ( is_multisite() ) {
    foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
        switch_to_blog( $site_id );
        surveyjs_uninstall_site();
        restore_current_blog();
    }
} else {
    surveyjs_uninstall_site();
}
