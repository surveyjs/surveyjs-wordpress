<?php
// Runs inside the WordPress container (php reads it from stdin): WPForms Lite has no entries table, so the e2e tests create one with entries and files.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
require_once ABSPATH . 'wp-admin/includes/file.php';
global $wpdb, $wp_filesystem;

$command = $argv[1] ?? '';
$table = $wpdb->prefix . 'wpforms_entries';
$uploads = wp_upload_dir();
WP_Filesystem( false, $uploads['basedir'], true );

if ( 'create' === $command ) {
    $form_title = $argv[2];
    $count = (int) $argv[3];
    $wpdb->query( $wpdb->prepare( "CREATE TABLE IF NOT EXISTS %i (
        entry_id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id bigint(20) NOT NULL,
        status varchar(30) NOT NULL DEFAULT '',
        fields longtext NOT NULL,
        meta longtext NOT NULL,
        date datetime NOT NULL,
        date_modified datetime NOT NULL,
        PRIMARY KEY  (entry_id),
        KEY form_id (form_id)
    )", $table ) );
    $form_id = wp_insert_post( array( 'post_type' => 'wpforms', 'post_status' => 'publish', 'post_title' => $form_title, 'post_content' => '{}' ) );
    $form = array(
        'id'       => $form_id,
        'fields'   => array(
            1 => array( 'id' => 1, 'type' => 'text', 'label' => 'Name' ),
            2 => array( 'id' => 2, 'type' => 'file-upload', 'label' => 'Documents', 'max_file_number' => '2' ),
            3 => array( 'id' => 3, 'type' => 'gdpr-checkbox', 'label' => 'GDPR Agreement', 'required' => '1', 'choices' => array( 1 => array( 'label' => 'I consent to data storage.' ) ) ),
        ),
        'settings' => array( 'form_title' => $form_title ),
    );
    wp_update_post( array( 'ID' => $form_id, 'post_content' => wp_slash( wp_json_encode( $form ) ) ) );

    $subdir = '/wpforms/' . $form_id . '-e2e';
    wp_mkdir_p( $uploads['basedir'] . $subdir );
    $files = array();
    for ( $i = 1; $i <= $count; $i++ ) {
        $name = "doc-$i.txt";
        $wp_filesystem->put_contents( $uploads['basedir'] . "$subdir/$name", "E2E file $i of $form_title" );
        $file_url = $uploads['baseurl'] . "$subdir/$name";
        if ( 2 === $i ) {
            $file_url = 'http://old-host.example' . wp_parse_url( $file_url, PHP_URL_PATH );
        }
        $raw = array( array( 'name' => $name, 'value' => $file_url, 'file' => $name, 'file_original' => $name, 'file_user_name' => "Document $i.txt", 'type' => 'text/plain' ) );
        if ( $count === $i ) {
            $raw[] = array( 'name' => 'missing.txt', 'value' => $uploads['baseurl'] . "$subdir/missing.txt", 'file_original' => 'missing.txt', 'type' => 'text/plain' );
        }
        $files[] = $file_url;
        $fields = array(
            1 => array( 'name' => 'Name', 'value' => "Person $i", 'id' => 1, 'type' => 'text' ),
            2 => array( 'name' => 'Documents', 'value' => implode( "\n", wp_list_pluck( $raw, 'value' ) ), 'value_raw' => $raw, 'id' => 2, 'type' => 'file-upload' ),
            3 => array( 'name' => 'GDPR Agreement', 'value' => 'I consent to data storage.', 'id' => 3, 'type' => 'gdpr-checkbox' ),
        );
        $wpdb->insert( $table, array( 'form_id' => $form_id, 'status' => '', 'fields' => wp_json_encode( $fields ), 'meta' => '{}', 'date' => current_time( 'mysql' ), 'date_modified' => current_time( 'mysql' ) ) );
    }
    echo wp_json_encode( array( 'formId' => $form_id, 'files' => $files ) );
} elseif ( 'results' === $command ) {
    $rows = $wpdb->get_col( $wpdb->prepare( "SELECT json FROM {$wpdb->prefix}sjs_results WHERE form_id = %d ORDER BY id", (int) $argv[2] ) );
    $stored = glob( $uploads['basedir'] . '/surveyjs/*.txt' );
    echo wp_json_encode( array( 'survey' => json_decode( (string) get_post_field( 'post_content', (int) $argv[2] ) ), 'entries' => array_map( 'json_decode', $rows ), 'storedFiles' => count( $stored ? $stored : array() ) ) );
} elseif ( 'originals' === $command ) {
    $files = glob( $uploads['basedir'] . '/wpforms/' . (int) $argv[2] . '-e2e/*' );
    echo wp_json_encode( array_map( 'basename', $files ? $files : array() ) );
} elseif ( 'cleanup' === $command ) {
    $form_id = (int) $argv[2];
    if ( 'wpforms' === get_post_type( $form_id ) ) {
        wp_delete_post( $form_id, true );
    }
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
        $wpdb->delete( $table, array( 'form_id' => $form_id ) );
    }
    array_map( 'wp_delete_file', glob( $uploads['basedir'] . '/wpforms/' . $form_id . '-e2e/*' ) ?: array() );
    $wp_filesystem->rmdir( $uploads['basedir'] . '/wpforms/' . $form_id . '-e2e' );
    foreach ( (array) ( get_option( 'surveyjs_imported_forms', array() )[ 'wpforms:' . $form_id ]['files'] ?? array() ) as $file_url ) {
        wp_delete_file( $uploads['basedir'] . '/surveyjs/' . basename( (string) wp_parse_url( $file_url, PHP_URL_PATH ) ) );
    }
    echo '{}';
} elseif ( 'stored-file' === $command ) {
    $stored_path = $uploads['basedir'] . '/surveyjs/' . basename( (string) wp_parse_url( $argv[2], PHP_URL_PATH ) );
    echo wp_json_encode( array( 'content' => is_file( $stored_path ) ? file_get_contents( $stored_path ) : null ) );
}
