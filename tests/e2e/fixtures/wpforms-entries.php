<?php
// Runs inside the WordPress container (php reads it from stdin): WPForms Lite has no entries table, so the e2e tests create one with entries and files.
require '/var/www/html/wp-load.php';
global $wpdb;

$command = $argv[1] ?? '';
$table = $wpdb->prefix . 'wpforms_entries';
$uploads = wp_upload_dir();

if ( 'create' === $command ) {
    $title = $argv[2];
    $count = (int) $argv[3];
    $wpdb->query( "CREATE TABLE IF NOT EXISTS $table (
        entry_id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id bigint(20) NOT NULL,
        status varchar(30) NOT NULL DEFAULT '',
        fields longtext NOT NULL,
        meta longtext NOT NULL,
        date datetime NOT NULL,
        date_modified datetime NOT NULL,
        PRIMARY KEY  (entry_id),
        KEY form_id (form_id)
    )" );
    $form_id = wp_insert_post( array( 'post_type' => 'wpforms', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => '{}' ) );
    $form = array(
        'id'       => $form_id,
        'fields'   => array(
            1 => array( 'id' => 1, 'type' => 'text', 'label' => 'Name' ),
            2 => array( 'id' => 2, 'type' => 'file-upload', 'label' => 'Documents', 'max_file_number' => '2' ),
        ),
        'settings' => array( 'form_title' => $title ),
    );
    wp_update_post( array( 'ID' => $form_id, 'post_content' => wp_slash( wp_json_encode( $form ) ) ) );

    $subdir = '/wpforms/' . $form_id . '-e2e';
    wp_mkdir_p( $uploads['basedir'] . $subdir );
    $files = array();
    for ( $i = 1; $i <= $count; $i++ ) {
        $name = "doc-$i.txt";
        file_put_contents( $uploads['basedir'] . "$subdir/$name", "E2E file $i of $title" );
        $url = $uploads['baseurl'] . "$subdir/$name";
        if ( 2 === $i ) {
            $url = 'http://old-host.example' . wp_parse_url( $url, PHP_URL_PATH );
        }
        $raw = array( array( 'name' => $name, 'value' => $url, 'file' => $name, 'file_original' => $name, 'file_user_name' => "Document $i.txt", 'type' => 'text/plain' ) );
        if ( $count === $i ) {
            $raw[] = array( 'name' => 'missing.txt', 'value' => $uploads['baseurl'] . "$subdir/missing.txt", 'file_original' => 'missing.txt', 'type' => 'text/plain' );
        }
        $files[] = $url;
        $fields = array(
            1 => array( 'name' => 'Name', 'value' => "Person $i", 'id' => 1, 'type' => 'text' ),
            2 => array( 'name' => 'Documents', 'value' => implode( "\n", wp_list_pluck( $raw, 'value' ) ), 'value_raw' => $raw, 'id' => 2, 'type' => 'file-upload' ),
        );
        $wpdb->insert( $table, array( 'form_id' => $form_id, 'status' => '', 'fields' => wp_json_encode( $fields ), 'meta' => '{}', 'date' => current_time( 'mysql' ), 'date_modified' => current_time( 'mysql' ) ) );
    }
    echo wp_json_encode( array( 'formId' => $form_id, 'files' => $files ) );
} elseif ( 'results' === $command ) {
    $rows = $wpdb->get_col( $wpdb->prepare( "SELECT json FROM {$wpdb->prefix}sjs_results WHERE surveyId = %d ORDER BY id", (int) $argv[2] ) );
    $stored = glob( $uploads['basedir'] . '/surveyjs/*.txt' );
    echo wp_json_encode( array( 'entries' => array_map( 'json_decode', $rows ), 'storedFiles' => count( $stored ? $stored : array() ) ) );
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
    @rmdir( $uploads['basedir'] . '/wpforms/' . $form_id . '-e2e' );
    foreach ( (array) ( get_option( 'surveyjs_imported_forms', array() )[ 'wpforms:' . $form_id ]['files'] ?? array() ) as $url ) {
        wp_delete_file( $uploads['basedir'] . '/surveyjs/' . basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
    }
    echo '{}';
}
