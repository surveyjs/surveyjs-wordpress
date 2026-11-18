<?php
// Runs inside the WordPress container (php reads it from stdin). Gravity Forms is a paid plugin, so the e2e tests create its tables
// with the schema from GF_Upgrade::get_db_schema() (Gravity Forms 2.3+) and store a form and entries in the formats Gravity Forms uses.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
require_once ABSPATH . 'wp-admin/includes/file.php';
global $wpdb, $wp_filesystem;

const EXPORT_FILE = '/tmp/surveyjs-e2e-gravity-forms-export.json';
const TABLES_OPTION = 'surveyjs_e2e_gravity_forms_tables';

$command = $argv[1] ?? '';
$uploads = wp_upload_dir();
WP_Filesystem( false, $uploads['basedir'], true );
$prefix = $wpdb->prefix;

function surveyjs_e2e_gf_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    return array(
        "{$wpdb->prefix}gf_form"       => "CREATE TABLE {$wpdb->prefix}gf_form (
            id mediumint unsigned not null auto_increment,
            title varchar(150) not null,
            date_created datetime not null,
            date_updated datetime,
            is_active tinyint not null default 1,
            is_trash tinyint not null default 0,
            PRIMARY KEY  (id)
        ) $charset_collate",
        "{$wpdb->prefix}gf_form_meta"  => "CREATE TABLE {$wpdb->prefix}gf_form_meta (
            form_id mediumint unsigned not null,
            display_meta longtext,
            entries_grid_meta longtext,
            confirmations longtext,
            notifications longtext,
            PRIMARY KEY  (form_id)
        ) $charset_collate",
        "{$wpdb->prefix}gf_entry"      => "CREATE TABLE {$wpdb->prefix}gf_entry (
            id int unsigned not null auto_increment,
            form_id mediumint unsigned not null,
            post_id bigint unsigned,
            date_created datetime not null,
            date_updated datetime,
            is_starred tinyint not null default 0,
            is_read tinyint not null default 0,
            ip varchar(45) not null,
            source_url varchar(200) not null default '',
            user_agent varchar(250) not null default '',
            currency varchar(5),
            payment_status varchar(15),
            payment_date datetime,
            payment_amount decimal(19,2),
            payment_method varchar(30),
            transaction_id varchar(50),
            is_fulfilled tinyint,
            created_by bigint unsigned,
            transaction_type tinyint,
            status varchar(20) not null default 'active',
            source_id bigint unsigned,
            PRIMARY KEY  (id),
            KEY form_id (form_id),
            KEY form_id_status (form_id,status)
        ) $charset_collate",
        "{$wpdb->prefix}gf_entry_meta" => "CREATE TABLE {$wpdb->prefix}gf_entry_meta (
            id bigint unsigned not null auto_increment,
            form_id mediumint unsigned not null default 0,
            entry_id bigint unsigned not null,
            meta_key varchar(255),
            meta_value longtext,
            item_index varchar(60),
            PRIMARY KEY  (id),
            KEY meta_key (meta_key(191)),
            KEY entry_id (entry_id),
            KEY meta_value (meta_value(191))
        ) $charset_collate",
    );
}

if ( 'create' === $command ) {
    $created = array();
    foreach ( surveyjs_e2e_gf_tables() as $table => $sql ) {
        if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
            $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed test schema.
            $created[] = $table;
        }
    }
    if ( $created ) {
        update_option( TABLES_OPTION, array_merge( (array) get_option( TABLES_OPTION, array() ), $created ), false );
    }

    $export = json_decode( (string) file_get_contents( EXPORT_FILE ), true );
    $form = $export['0'];
    $form['title'] = $argv[2];
    $wpdb->insert( "{$prefix}gf_form", array( 'title' => $form['title'], 'date_created' => current_time( 'mysql', true ), 'is_active' => 1, 'is_trash' => 0 ) );
    $form_id = (int) $wpdb->insert_id;
    $form['id'] = $form_id;
    $key_by_id = function ( $items ) {
        return array_column( $items, null, 'id' );
    };
    $notifications = $key_by_id( $form['notifications'] );
    $confirmations = $key_by_id( $form['confirmations'] );
    unset( $form['notifications'], $form['confirmations'] );
    $wpdb->insert( "{$prefix}gf_form_meta", array(
        'form_id'       => $form_id,
        'display_meta'  => wp_json_encode( $form ),
        'confirmations' => wp_json_encode( $confirmations ),
        'notifications' => wp_json_encode( $notifications ),
    ) );
    $wpdb->insert( "{$prefix}gf_form", array( 'title' => $argv[2] . ' (trashed)', 'date_created' => current_time( 'mysql', true ), 'is_trash' => 1 ) );
    $trashed_id = (int) $wpdb->insert_id;
    $wpdb->insert( "{$prefix}gf_form_meta", array( 'form_id' => $trashed_id, 'display_meta' => wp_json_encode( array( 'id' => $trashed_id, 'title' => $argv[2] . ' (trashed)', 'fields' => array() ) ) ) );

    $subdir = '/gravity_forms/' . $form_id . '-' . wp_hash( $form_id ) . '/2026/01';
    wp_mkdir_p( $uploads['basedir'] . $subdir );
    $files = array();
    foreach ( array( 'ticket.txt', 'id card.txt' ) as $name ) {
        $wp_filesystem->put_contents( $uploads['basedir'] . "$subdir/$name", "E2E $name of {$argv[2]}" );
        $files[] = $uploads['baseurl'] . $subdir . '/' . rawurlencode( $name );
    }
    $missing = $uploads['baseurl'] . $subdir . '/missing.txt';

    $entries = array(
        array(
            'status' => 'active',
            'date'   => '2026-01-15 10:00:00',
            'meta'   => array(
                '1.3'  => 'Ann',
                '1.6'  => 'Lee',
                '2'    => 'ann@example.com',
                '3'    => '(555) 123-4567',
                '4.1'  => 'Main St 1',
                '4.3'  => 'Berlin',
                '4.5'  => '10115',
                '4.6'  => 'Germany',
                '5'    => 'student',
                '6'    => 'S-123',
                '9.1'  => 'Workshop A',
                '9.3'  => 'Workshop C',
                '10'   => '2026-12-01',
                '11'   => '02:30 pm',
                '12'   => '2',
                '13.1' => 'glikertrow13r1:glikertcol13a3',
                '13.2' => 'glikertrow13r2:glikertcol13a1',
                '14'   => 'grating14c2',
                '15'   => serialize( array( array( 'Person' => 'Ann', 'Need' => 'Vegan' ), array( 'Person' => 'Max', 'Need' => 'No nuts' ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Gravity Forms stores List values serialized.
                '16'   => wp_json_encode( array( 'music', 'art' ) ),
                '18'   => wp_json_encode( $files ),
                '19.1' => '1',
                '19.2' => 'I agree to the event rules.',
                '19.3' => '1',
                '21'   => 'gf-e2e',
                '23.1' => 'Ticket price',
                '23.2' => '$10.00',
                '23.3' => '1',
            ),
        ),
        array(
            'status' => 'active',
            'date'   => '2026-01-16 08:30:00',
            'meta'   => array(
                '1.3' => 'Bob',
                '1.6' => 'Stone',
                '2'   => 'bob@example.com',
                '4.6' => 'US',
                '5'   => 'Press pass',
                '11'  => '12:05 am',
                '12'  => '0',
                '18'  => $missing,
                '19.1' => '1',
            ),
        ),
        array( 'status' => 'spam', 'date' => '2026-01-17 08:30:00', 'meta' => array( '1.3' => 'Spammer', '2' => 'spam@example.com' ) ),
        array( 'status' => 'trash', 'date' => '2026-01-18 08:30:00', 'meta' => array( '1.3' => 'Deleted', '2' => 'trash@example.com' ) ),
    );
    foreach ( $entries as $entry ) {
        $wpdb->insert( "{$prefix}gf_entry", array( 'form_id' => $form_id, 'date_created' => $entry['date'], 'ip' => '127.0.0.1', 'status' => $entry['status'], 'currency' => 'USD', 'is_read' => 0 ) );
        $entry_id = (int) $wpdb->insert_id;
        foreach ( $entry['meta'] as $meta_key => $meta_value ) {
            $wpdb->insert( "{$prefix}gf_entry_meta", array( 'form_id' => $form_id, 'entry_id' => $entry_id, 'meta_key' => (string) $meta_key, 'meta_value' => $meta_value, 'item_index' => '' ) );
        }
    }
    echo wp_json_encode( array( 'formId' => $form_id, 'trashedId' => $trashed_id, 'files' => $files ) );
} elseif ( 'survey' === $command ) {
    $found = null;
    foreach ( SurveyJS_Forms::all() as $survey ) {
        if ( $survey->name === $argv[2] ) {
            $found = $survey;
            break;
        }
    }
    echo wp_json_encode( $found ? array( 'id' => $found->id, 'json' => json_decode( $found->json ), 'settings' => json_decode( $found->settings ) ) : null );
} elseif ( 'results' === $command ) {
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT json, created_at FROM {$prefix}sjs_results WHERE form_id = %d ORDER BY id", (int) $argv[2] ) );
    echo wp_json_encode( array_map( function ( $row ) {
        return array( 'data' => json_decode( $row->json ), 'createdAt' => $row->created_at );
    }, $rows ) );
} elseif ( 'cleanup' === $command ) {
    $form_ids = array_map( 'intval', array_slice( $argv, 2 ) );
    foreach ( $form_ids as $form_id ) {
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$prefix}gf_form" ) ) ) {
            $wpdb->delete( "{$prefix}gf_entry_meta", array( 'form_id' => $form_id ) );
            $wpdb->delete( "{$prefix}gf_entry", array( 'form_id' => $form_id ) );
            $wpdb->delete( "{$prefix}gf_form_meta", array( 'form_id' => $form_id ) );
            $wpdb->delete( "{$prefix}gf_form", array( 'id' => $form_id ) );
        }
        $wp_filesystem->rmdir( $uploads['basedir'] . '/gravity_forms/' . $form_id . '-' . wp_hash( $form_id ), true );
        foreach ( (array) ( get_option( 'surveyjs_imported_forms', array() )[ 'gravityforms:' . $form_id ]['files'] ?? array() ) as $copied ) {
            $key = SurveyJS_Uploaded_Files::key( $copied );
            if ( null !== $key ) {
                SurveyJS_Uploaded_Files::storage()->delete( $key );
            }
        }
    }
    $created = (array) get_option( TABLES_OPTION, array() );
    if ( $created && ! $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}gf_form" ) ) {
        foreach ( $created as $table ) {
            $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
        }
        delete_option( TABLES_OPTION );
    }
    echo '{}';
}
