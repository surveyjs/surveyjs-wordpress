<?php
// Runs inside the WordPress container (php reads it from stdin): resets the site and sets up what the pre-3 plugin left behind.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $wpdb;

const UPGRADE_PLUGIN = 'surveyjs/surveyjs.php';
const UPGRADE_POST_META = '_sjs_upgrade_e2e';

function upgrade_table_exists( $table ) {
    global $wpdb;
    return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
}

function upgrade_debug_log() {
    return WP_CONTENT_DIR . '/debug.log';
}

$command = $argv[1] ?? '';
$result = array();

if ( 'reset' === $command ) {
    deactivate_plugins( array( UPGRADE_PLUGIN, 'surveyjs-forms-addon/surveyjs-forms-addon.php' ), true );
    foreach ( $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'sjs_' ) . '%' ) ) as $table ) {
        $wpdb->query( $wpdb->prepare( 'DROP TABLE %i', $table ) );
    }
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'surveyjs%' OR option_name LIKE 'sjs%' OR option_name LIKE '\_transient\_%surveyjs%' OR option_name LIKE '\_transient\_timeout\_%surveyjs%'" );
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'surveyjs%'" );
    $upgrade_posts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sjs_form' OR ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s )", UPGRADE_POST_META ) );
    foreach ( $upgrade_posts as $upgrade_post_id ) {
        wp_delete_post( (int) $upgrade_post_id, true );
    }
    $cron = _get_cron_array();
    foreach ( $cron as $time => $hooks ) {
        foreach ( array_keys( $hooks ) as $hook ) {
            if ( str_starts_with( $hook, 'surveyjs' ) ) {
                unset( $cron[ $time ][ $hook ] );
            }
        }
        if ( empty( $cron[ $time ] ) ) {
            unset( $cron[ $time ] );
        }
    }
    _set_cron_array( $cron );
} elseif ( 'activate' === $command ) {
    $activated = activate_plugin( UPGRADE_PLUGIN );
    $result['error'] = is_wp_error( $activated ) ? $activated->get_error_message() : null;
} elseif ( 'reactivate' === $command ) {
    deactivate_plugins( UPGRADE_PLUGIN );
    $activated = activate_plugin( UPGRADE_PLUGIN );
    $result['error'] = is_wp_error( $activated ) ? $activated->get_error_message() : null;
} elseif ( 'legacy-ids' === $command ) {
    // A pre-3 form ID that an existing post already has, and one that no post can have yet.
    $result['taken'] = (int) $wpdb->get_var( "SELECT MIN(ID) FROM {$wpdb->posts}" );
    $result['free'] = (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts}" ) + 1000;
} elseif ( 'legacy-next-id' === $command ) {
    $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i AUTO_INCREMENT = %d', $wpdb->prefix . 'sjs_my_surveys', (int) $argv[2] ) );
} elseif ( 'legacy-slashed-row' === $command ) {
    // Plugin versions before 2.5.x saved the form JSON without wp_unslash(), so old rows keep the slashes WordPress added.
    $wpdb->insert( $wpdb->prefix . 'sjs_my_surveys', array( 'name' => $argv[2], 'json' => wp_slash( $argv[3] ), 'theme' => '' ) );
    $result['id'] = (int) $wpdb->insert_id;
} elseif ( 'post' === $command ) {
    kses_remove_filters();
    $page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => wp_slash( $argv[2] ), 'post_content' => wp_slash( base64_decode( $argv[3] ) ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- the test passes block markup through docker exec arguments.
    if ( is_wp_error( $page_id ) ) {
        $result['error'] = $page_id->get_error_message();
    } else {
        update_post_meta( $page_id, UPGRADE_POST_META, 1 );
        $result['id'] = $page_id;
        $result['link'] = get_permalink( $page_id );
    }
} elseif ( 'state' === $command ) {
    $legacy_table = $wpdb->prefix . 'sjs_my_surveys';
    $entries_table = $wpdb->prefix . 'sjs_results';
    $result['forms'] = array_map(
        function ( $post ) {
            return array(
                'id'       => (int) $post->ID,
                'status'   => $post->post_status,
                'name'     => $post->post_title,
                'json'     => $post->post_content,
                'theme'    => (string) get_post_meta( $post->ID, '_sjs_theme', true ),
                'legacyId' => (int) get_post_meta( $post->ID, '_sjs_legacy_id', true ),
            );
        },
        $wpdb->get_results( "SELECT ID, post_status, post_title, post_content FROM {$wpdb->posts} WHERE post_type = 'sjs_form' ORDER BY ID" )
    );
    $result['legacyForms'] = upgrade_table_exists( $legacy_table ) ? $wpdb->get_results( $wpdb->prepare( 'SELECT id, name, json, theme FROM %i ORDER BY id', $legacy_table ) ) : array();
    $result['entries'] = upgrade_table_exists( $entries_table ) ? $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id', $entries_table ) ) : array();
    $result['entryColumns'] = upgrade_table_exists( $entries_table ) ? $wpdb->get_col( $wpdb->prepare( 'SHOW COLUMNS FROM %i', $entries_table ) ) : array();
    $result['options'] = array(
        'settings'    => get_option( 'surveyjs-settings' ),
        'legacyMap'   => get_option( 'surveyjs_legacy_form_ids' ),
        'dbVersion'   => get_option( 'surveyjs_db_version' ),
        'legacyMaxId' => get_option( 'surveyjs_legacy_max_id' ),
    );
} elseif ( 'log' === $command ) {
    // Returns the log lines written after the byte offset an earlier call returned as its size.
    $log = upgrade_debug_log();
    $size = file_exists( $log ) ? (int) filesize( $log ) : 0;
    $offset = min( (int) ( $argv[2] ?? $size ), $size );
    $result['size'] = $size;
    $result['lines'] = $size > $offset ? array_values( array_filter( explode( "\n", (string) file_get_contents( $log, false, null, $offset ) ) ) ) : array(); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- local log file.
}

echo "\n" . wp_json_encode( $result );
