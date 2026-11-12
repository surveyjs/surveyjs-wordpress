<?php
// Runs inside the WordPress container (php reads it from stdin): sets up what only the database migration creates.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
global $wpdb;

$command = $argv[1] ?? '';

if ( 'legacy' === $command ) {
    $form_id = (int) $argv[2];
    // The old ID belongs to another post, as when the migration finds it taken, so it resolves only through the map.
    $legacy_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'E2E legacy form ID' ) );
    $legacy_map = SurveyJS_Forms::legacy_map();
    $legacy_map[ $legacy_id ] = $form_id;
    update_option( SurveyJS_Forms::LEGACY_MAP_OPTION, $legacy_map );
    update_post_meta( $form_id, SurveyJS_Forms::LEGACY_ID_META, $legacy_id );
    echo wp_json_encode( array( 'legacyId' => $legacy_id ) );
} elseif ( 'legacy-cleanup' === $command ) {
    $legacy_id = (int) $argv[2];
    $legacy_map = SurveyJS_Forms::legacy_map();
    unset( $legacy_map[ $legacy_id ] );
    update_option( SurveyJS_Forms::LEGACY_MAP_OPTION, $legacy_map );
    if ( 'post' === get_post_type( $legacy_id ) ) {
        wp_delete_post( $legacy_id, true );
    }
    echo '{}';
} elseif ( 'legacy-row' === $command ) {
    // A form saved by the pre-3 plugin after a rollback: its table keeps counting from its own IDs,
    // so the new row can take the ID of a form created after the first migration.
    $legacy_table = $wpdb->prefix . 'sjs_my_surveys';
    $wpdb->query( $wpdb->prepare( 'CREATE TABLE IF NOT EXISTS %i ( id mediumint(9) NOT NULL AUTO_INCREMENT, name text NOT NULL, json LONGTEXT, theme LONGTEXT, UNIQUE KEY id (id) )', $legacy_table ) );
    $wpdb->insert( $legacy_table, array( 'id' => (int) $argv[2], 'name' => $argv[3], 'json' => $argv[4], 'theme' => '' ) );
    echo '{}';
} elseif ( 'legacy-row-cleanup' === $command ) {
    $legacy_id = (int) $argv[2];
    $wpdb->delete( $wpdb->prefix . 'sjs_my_surveys', array( 'id' => $legacy_id ) );
    foreach ( get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'meta_key' => SurveyJS_Forms::LEGACY_ID_META, 'meta_value' => $legacy_id, 'fields' => 'ids' ) ) as $post_id ) {
        wp_delete_post( $post_id, true );
    }
    $legacy_map = SurveyJS_Forms::legacy_map();
    unset( $legacy_map[ $legacy_id ] );
    update_option( SurveyJS_Forms::LEGACY_MAP_OPTION, $legacy_map );
    echo '{}';
} elseif ( 'entries' === $command ) {
    echo wp_json_encode( array( 'count' => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_id = %d', SurveyJS_Entries::table(), (int) $argv[2] ) ) ) );
} elseif ( 'age-entry' === $command ) {
    $wpdb->update( SurveyJS_Entries::table(), array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - (int) $argv[3] * DAY_IN_SECONDS ) ), array( 'id' => (int) $argv[2] ) );
    SurveyJS_Entries::flush_counts();
    echo '{}';
}
