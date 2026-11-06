<?php
// Runs inside the WordPress container (php reads it from stdin): sets up what only the database migration creates.
require '/var/www/html/wp-load.php';
global $wpdb;

$command = $argv[1] ?? '';

if ( 'legacy' === $command ) {
    $form_id = (int) $argv[2];
    // The old ID belongs to another post, as when the migration finds it taken, so it resolves only through the map.
    $legacy_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'E2E legacy form ID' ) );
    $map = SurveyJS_Forms::legacy_map();
    $map[ $legacy_id ] = $form_id;
    update_option( SurveyJS_Forms::LEGACY_MAP_OPTION, $map );
    update_post_meta( $form_id, SurveyJS_Forms::LEGACY_ID_META, $legacy_id );
    echo wp_json_encode( array( 'legacyId' => $legacy_id ) );
} elseif ( 'legacy-cleanup' === $command ) {
    $legacy_id = (int) $argv[2];
    $map = SurveyJS_Forms::legacy_map();
    unset( $map[ $legacy_id ] );
    update_option( SurveyJS_Forms::LEGACY_MAP_OPTION, $map );
    if ( 'post' === get_post_type( $legacy_id ) ) {
        wp_delete_post( $legacy_id, true );
    }
    echo '{}';
} elseif ( 'entries' === $command ) {
    $table = SurveyJS_Entries::table();
    echo wp_json_encode( array( 'count' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE form_id = %d", (int) $argv[2] ) ) ) );
} elseif ( 'age-entry' === $command ) {
    $wpdb->update( SurveyJS_Entries::table(), array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - (int) $argv[3] * DAY_IN_SECONDS ) ), array( 'id' => (int) $argv[2] ) );
    SurveyJS_Entries::flush_counts();
    echo '{}';
}
