<?php
// Runs inside the WordPress container (php reads it from stdin): WPForms Lite has no entries table, so the e2e tests create one with entries and files.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
require_once ABSPATH . 'wp-admin/includes/file.php';
global $wpdb, $wp_filesystem;

$command = $argv[1] ?? '';
$table = $wpdb->prefix . 'wpforms_entries';
$uploads = wp_upload_dir();
WP_Filesystem( false, $uploads['basedir'], true );

function create_entries_table( $table ) {
    global $wpdb;
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
}

if ( 'create' === $command ) {
    $form_title = $argv[2];
    $count = (int) $argv[3];
    create_entries_table( $table );
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
} elseif ( 'create-layout' === $command ) {
    $form_title = $argv[2];
    create_entries_table( $table );
    $form_id = wp_insert_post( array( 'post_type' => 'wpforms', 'post_status' => 'publish', 'post_title' => $form_title, 'post_content' => '{}' ) );
    $condition = function ( $type, $operator, $value ) {
        return array( 'conditional_logic' => '1', 'conditional_type' => $type, 'conditionals' => array( array( array( 'field' => 1, 'operator' => $operator, 'value' => $value ) ) ) );
    };
    $form = array(
        'id'       => $form_id,
        'fields'   => array(
            1 => array( 'id' => 1, 'type' => 'text', 'label' => 'Code' ),
            2 => array( 'id' => 2, 'type' => 'layout', 'label' => 'Layout', 'label_hide' => '1', 'display' => 'rows', 'columns' => array(
                array( 'width_preset' => '67', 'fields' => array( 3, 5 ) ),
                array( 'width_preset' => '33', 'fields' => array( 4 ) ),
            ) ) + $condition( 'show', '^', 'AB' ),
            3 => array( 'id' => 3, 'type' => 'name', 'label' => 'Name', 'format' => 'first-last' ),
            4 => array( 'id' => 4, 'type' => 'email', 'label' => 'Email', 'default_value' => '{user_email}' ),
            5 => array( 'id' => 5, 'type' => 'text', 'label' => 'City' ) + $condition( 'hide', '~', 'Z' ),
            6 => array( 'id' => 6, 'type' => 'repeater', 'label' => 'Guests', 'display' => 'rows', 'rows_limit_min' => '1', 'rows_limit_max' => '3', 'columns' => array(
                array( 'width_preset' => '50', 'fields' => array( 7 ) ),
                array( 'width_preset' => '50', 'fields' => array( 8 ) ),
            ) ) + $condition( 'show', 'c', 'A' ),
            7 => array( 'id' => 7, 'type' => 'text', 'label' => 'Guest Name' ),
            8 => array( 'id' => 8, 'type' => 'select', 'label' => 'Meal', 'choices' => array( 1 => array( 'label' => 'Fish' ), 2 => array( 'label' => 'Meat' ) ) ),
            9 => array( 'id' => 9, 'type' => 'hidden', 'label' => 'Source', 'default_value' => '{query_var key="utm_source"}' ),
        ),
        'settings' => array( 'form_title' => $form_title ),
    );
    wp_update_post( array( 'ID' => $form_id, 'post_content' => wp_slash( wp_json_encode( $form ) ) ) );
    $text = function ( $id, $value, $type = 'text' ) {
        return array( 'name' => '', 'value' => $value, 'id' => $id, 'type' => $type );
    };
    $entries = array(
        array(
            1     => $text( 1, 'ABC' ),
            3     => array( 'name' => 'Name', 'value' => 'Jane Roe', 'first' => 'Jane', 'middle' => '', 'last' => 'Roe', 'id' => 3, 'type' => 'name' ),
            4     => $text( 4, 'jane@example.com', 'email' ),
            7     => $text( '7', 'Ann' ),
            8     => $text( '8', 'Fish', 'select' ) + array( 'value_raw' => 'Fish' ),
            '7_1' => $text( '7_1', 'Bob' ),
            '8_1' => $text( '8_1', 'Meat', 'select' ) + array( 'value_raw' => 'Meat' ),
            '7_2' => $text( '7_2', 'Cid' ),
            9     => $text( 9, 'newsletter', 'hidden' ),
        ),
        array(
            1 => $text( 1, 'XY' ),
            7 => $text( '7', 'Dan' ),
        ),
    );
    foreach ( $entries as $fields ) {
        $wpdb->insert( $table, array( 'form_id' => $form_id, 'status' => '', 'fields' => wp_json_encode( $fields ), 'meta' => '{}', 'date' => current_time( 'mysql', true ), 'date_modified' => current_time( 'mysql', true ) ) );
    }
    echo wp_json_encode( array( 'formId' => $form_id ) );
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
    foreach ( (array) ( get_option( 'surveyjs_imported_forms', array() )[ 'wpforms:' . $form_id ]['files'] ?? array() ) as $copied ) {
        $key = SurveyJS_Uploaded_Files::key( $copied );
        if ( null !== $key ) {
            SurveyJS_Uploaded_Files::storage()->delete( $key );
        }
    }
    echo '{}';
} elseif ( 'stored-file' === $command ) {
    $key = SurveyJS_Uploaded_Files::key( $argv[2] );
    $stored_path = null === $key ? '' : $uploads['basedir'] . '/surveyjs/' . $key;
    echo wp_json_encode( array( 'content' => is_file( $stored_path ) ? file_get_contents( $stored_path ) : null ) );
}
