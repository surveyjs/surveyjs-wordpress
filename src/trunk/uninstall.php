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

    surveyjs_uninstall_delete_files();

    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sjs_results, {$wpdb->prefix}sjs_my_surveys, {$wpdb->prefix}sjs_files, {$wpdb->prefix}sjs_form_stats" );

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE ( option_name = %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s ) AND option_name NOT LIKE %s AND option_name NOT LIKE %s AND option_name NOT LIKE %s",
        'surveyjs-settings',
        $wpdb->esc_like( 'surveyjs_' ) . '%',
        $wpdb->esc_like( '_transient_surveyjs_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_surveyjs_' ) . '%',
        $wpdb->esc_like( 'surveyjs_addon_' ) . '%',
        $wpdb->esc_like( '_transient_surveyjs_addon_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_surveyjs_addon_' ) . '%'
    ) );
    wp_cache_delete( 'alloptions', 'options' );
    wp_cache_delete( 'notoptions', 'options' );
    delete_metadata( 'user', 0, 'surveyjs_upgrade_notice_dismissed', '', true );
}

// The plugin is not loaded during uninstall, so the storage classes are included here; a custom storage from
// the surveyjs_file_storage filter is used when the code that adds it is still active.
function surveyjs_uninstall_delete_files() {
    global $wpdb;
    require_once __DIR__ . '/file_storage.php';
    require_once __DIR__ . '/uploaded_files.php';
    $storage = SurveyJS_Uploaded_Files::storage();
    $table = $wpdb->prefix . 'sjs_files';
    if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
        $last_id = 0;
        do {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, file_key FROM %i WHERE id > %d ORDER BY id LIMIT 500', $table, $last_id ) );
            foreach ( $rows as $row ) {
                $last_id = (int) $row->id;
                $storage->delete( $row->file_key );
            }
        } while ( count( $rows ) === 500 );
    }
    if ( $storage instanceof SurveyJS_Uploads_Storage ) {
        $dir = $storage->dir();
        if ( null !== $dir ) {
            surveyjs_uninstall_remove_dir( $dir['path'] );
        }
    }
}

// Removes what the table does not list (the .htaccess guard, files of very old versions). Folders cannot be listed on some
// stream wrappers (vip://), where the iterator throws.
function surveyjs_uninstall_remove_dir( $dir ) {
    global $wp_filesystem;
    if ( ! is_dir( $dir ) || is_link( $dir ) ) {
        return;
    }
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $can_rmdir = ! wp_is_stream( $dir ) && WP_Filesystem();
    try {
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
    } catch ( Exception ) {
        return;
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
