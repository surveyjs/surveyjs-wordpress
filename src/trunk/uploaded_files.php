<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Uploaded_Files {

    const CAPABILITY      = 'manage_options';
    const CLEANUP_HOOK    = 'surveyjs_cleanup_uploads';
    const CLEANUP_BATCH   = 500;
    const DELETE_HOOK     = 'surveyjs_delete_sent_files';
    const BACKFILL_HOOK   = 'surveyjs_backfill_files';
    const BACKFILL_OPTION = 'surveyjs_files_backfill';
    const BACKFILL_BATCH  = 200;
    const LIKE_BATCH      = 100;
    const DOWNLOAD_ACTION = 'SurveyJS_DownloadFile';
    const INLINE_TYPES    = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf' );

    private static $storage = null;
    private static $temp_files = array();

    public static function init() {
        add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup' ) );
        add_action( self::DELETE_HOOK, array( __CLASS__, 'delete_unreferenced' ) );
        add_action( self::BACKFILL_HOOK, array( __CLASS__, 'backfill' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_update_guards' ) );
    }

    public static function storage() {
        if ( null === self::$storage ) {
            $default = new SurveyJS_Uploads_Storage();
            $storage = apply_filters( 'surveyjs_file_storage', $default );
            if ( ! $storage instanceof SurveyJS_File_Storage ) {
                _doing_it_wrong( 'surveyjs_file_storage', esc_html__( 'The filter must return an object that implements SurveyJS_File_Storage.', 'surveyjs' ), esc_html( SURVEYJS_VERSION ) );
                $storage = $default;
            }
            self::$storage = $storage;
        }
        return self::$storage;
    }

    public static function default_storage() {
        $storage = self::storage();
        return $storage instanceof SurveyJS_Uploads_Storage ? $storage : null;
    }

    public static function maybe_update_guards() {
        $storage = self::default_storage();
        if ( null !== $storage ) {
            $storage->maybe_update_guards();
        }
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'sjs_files';
    }

    public static function schema() {
        global $wpdb;
        $table = self::table();
        return "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            file_key varchar(191) NOT NULL,
            form_id bigint(20) unsigned NOT NULL DEFAULT 0,
            entry_id bigint(20) unsigned DEFAULT NULL,
            draft_user_id bigint(20) unsigned DEFAULT NULL,
            original_name varchar(255) NOT NULL DEFAULT '',
            mime varchar(100) NOT NULL DEFAULT '',
            size bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY file_key (file_key),
            KEY entry_id (entry_id),
            KEY unattached (form_id,entry_id,draft_user_id,created_at)
        ) {$wpdb->get_charset_collate()};";
    }

    public static function download_capability() {
        $capability = apply_filters( 'surveyjs_file_download_capability', self::CAPABILITY );
        return is_string( $capability ) && '' !== $capability ? $capability : self::CAPABILITY;
    }

    public static function key( $content ) {
        if ( ! is_string( $content ) || '' === $content ) {
            return null;
        }
        if ( self::is_valid_name( $content ) ) {
            return $content;
        }
        // Entry values pass through wp_kses(), which stores "&" in a URL as "&amp;".
        $parts = wp_parse_url( wp_specialchars_decode( $content ) );
        if ( ! is_array( $parts ) ) {
            return null;
        }
        $path = (string) ( $parts['path'] ?? '' );
        if ( isset( $parts['query'] ) && str_ends_with( $path, '/admin-ajax.php' ) ) {
            $query = array();
            wp_parse_str( $parts['query'], $query );
            $key = $query['file'] ?? null;
            return self::DOWNLOAD_ACTION === ( $query['action'] ?? '' ) && self::is_valid_name( $key ) ? $key : null;
        }
        if ( ! str_ends_with( dirname( $path ), SurveyJS_Uploads_Storage::SUBDIR ) ) {
            return null;
        }
        $name = basename( $path );
        return self::is_valid_name( $name ) ? $name : null;
    }

    public static function stored_name( $file ) {
        if ( ! is_array( $file ) || ! is_string( $file['content'] ?? null ) ) {
            return null;
        }
        // A bare key is only taken from a file answer ({name, type, content}), so a "content" property of another value is not mistaken for a file.
        if ( self::is_valid_name( $file['content'] ) && ! array_key_exists( 'name', $file ) ) {
            return null;
        }
        return self::key( $file['content'] );
    }

    public static function stored_files( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $key = self::stored_name( $value );
        if ( null !== $key ) {
            return array( $key => is_string( $value['name'] ?? null ) ? $value['name'] : '' );
        }
        $files = array();
        foreach ( $value as $item ) {
            $files += self::stored_files( $item );
        }
        return $files;
    }

    public static function referenced_names( $value ) {
        return array_map( 'strval', array_keys( self::stored_files( $value ) ) );
    }

    public static function content_url( $key ) {
        $url = add_query_arg(
            array(
                'action' => self::DOWNLOAD_ACTION,
                'file'   => $key,
            ),
            admin_url( 'admin-ajax.php' )
        );
        $content = apply_filters( 'surveyjs_file_answer_content', $url, $key );
        return is_string( $content ) && self::key( $content ) === $key ? $content : $url;
    }

    public static function normalize_contents( $value ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }
        $key = self::stored_name( $value );
        if ( null !== $key ) {
            $value['content'] = self::content_url( $key );
            return $value;
        }
        foreach ( $value as $index => $item ) {
            $value[ $index ] = self::normalize_contents( $item );
        }
        return $value;
    }

    public static function store_upload( $tmp_path, $ext, $context ) {
        $storage = self::storage();
        $context = array_merge(
            array(
                'form_id'       => 0,
                'original_name' => '',
                'mime'          => '',
                'size'          => 0,
            ),
            $context
        );
        $key = $storage->save( $tmp_path, $ext, $context );
        if ( is_wp_error( $key ) ) {
            return $key;
        }
        if ( ! self::is_valid_name( $key ) ) {
            return new WP_Error( 'surveyjs_invalid_key', __( 'The file storage returned an invalid file key.', 'surveyjs' ) );
        }
        self::insert_records(
            array(
                array(
                    'file_key'      => $key,
                    'form_id'       => (int) $context['form_id'],
                    'original_name' => (string) $context['original_name'],
                    'mime'          => (string) $context['mime'],
                    'size'          => (int) $context['size'],
                ),
            )
        );
        self::schedule_cleanup();
        do_action( 'surveyjs_file_saved', $key, $context, $storage );
        return $key;
    }

    public static function record( $key ) {
        $records = self::records( array( $key ) );
        return $records[ $key ] ?? null;
    }

    public static function records( $keys ) {
        global $wpdb;
        $keys = array_values( array_unique( array_filter( (array) $keys, array( __CLASS__, 'is_valid_name' ) ) ) );
        if ( ! $keys ) {
            return array();
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT file_key, form_id, entry_id, draft_user_id, original_name FROM %i WHERE file_key IN ( ' . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ' )',
            array_merge( array( self::table() ), $keys )
        ) );
        $records = array();
        foreach ( $rows as $row ) {
            $records[ $row->file_key ] = $row;
        }
        return $records;
    }

    // Rows that already exist keep their values: a key belongs to one upload.
    public static function insert_records( $rows ) {
        global $wpdb;
        $rows = array_values( array_filter( (array) $rows, function ( $row ) {
            return self::is_valid_name( $row['file_key'] ?? null );
        } ) );
        foreach ( array_chunk( $rows, self::BACKFILL_BATCH ) as $chunk ) {
            $args = array( self::table() );
            foreach ( $chunk as $row ) {
                $args[] = $row['file_key'];
                $args[] = (int) ( $row['form_id'] ?? 0 );
                $args[] = (int) ( $row['entry_id'] ?? 0 );
                $args[] = (int) ( $row['draft_user_id'] ?? 0 );
                $args[] = substr( sanitize_file_name( (string) ( $row['original_name'] ?? '' ) ), 0, 255 );
                $args[] = substr( (string) ( $row['mime'] ?? '' ), 0, 100 );
                $args[] = max( 0, (int) ( $row['size'] ?? 0 ) );
                $args[] = self::valid_date( $row['created_at'] ?? '' );
            }
            $wpdb->query( $wpdb->prepare(
                'INSERT IGNORE INTO %i ( file_key, form_id, entry_id, draft_user_id, original_name, mime, size, created_at ) VALUES '
                . implode( ', ', array_fill( 0, count( $chunk ), '( %s, %d, NULLIF( %d, 0 ), NULLIF( %d, 0 ), %s, %s, %d, %s )' ) ),
                $args
            ) );
        }
    }

    private static function valid_date( $date ) {
        $date = (string) $date;
        return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date ) && 0 !== strpos( $date, '0000' ) ? $date : current_time( 'mysql', true );
    }

    // A respondent can only submit files uploaded to this form and not yet saved with another entry.
    public static function filter_submitted( $data, $form_id, $entry_id = 0 ) {
        $files = self::stored_files( $data );
        if ( ! $files ) {
            return $data;
        }
        $records = self::records( array_keys( $files ) );
        $allowed = array();
        $legacy = array();
        foreach ( $files as $key => $name ) {
            $key = (string) $key;
            $record = $records[ $key ] ?? null;
            if ( null === $record ) {
                $legacy[] = $key;
            } elseif ( in_array( (int) $record->form_id, array( 0, (int) $form_id ), true ) && ( null === $record->entry_id || ( $entry_id && (int) $record->entry_id === (int) $entry_id ) ) ) {
                $allowed[] = $key;
            }
        }
        if ( $legacy ) {
            $referenced = self::referencing_entries( $legacy );
            $storage = self::storage();
            $rows = array();
            foreach ( $legacy as $key ) {
                $used_elsewhere = isset( $referenced[ $key ] ) && (int) $referenced[ $key ]->id !== (int) $entry_id;
                if ( ! $used_elsewhere && $storage->exists( $key ) ) {
                    $allowed[] = $key;
                    $rows[] = array( 'file_key' => $key, 'form_id' => (int) $form_id, 'original_name' => $files[ $key ] );
                }
            }
            self::insert_records( $rows );
        }
        return self::remove_files( $data, $allowed );
    }

    private static function remove_files( $value, $allowed ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }
        $result = array();
        foreach ( $value as $index => $item ) {
            $key = self::stored_name( $item );
            if ( null !== $key && ! in_array( $key, $allowed, true ) ) {
                continue;
            }
            $result[ $index ] = null === $key ? self::remove_files( $item, $allowed ) : $item;
        }
        return array_is_list( $value ) ? array_values( $result ) : $result;
    }

    public static function attach( $form_id, $entry_id, $keys, $release_others = true ) {
        global $wpdb;
        $keys = array_values( array_unique( array_filter( (array) $keys, array( __CLASS__, 'is_valid_name' ) ) ) );
        $table = self::table();
        if ( $keys ) {
            $wpdb->query( $wpdb->prepare(
                'UPDATE %i SET entry_id = %d, form_id = %d, draft_user_id = NULL WHERE form_id IN ( 0, %d ) AND ( entry_id IS NULL OR entry_id = %d ) AND file_key IN ( ' . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ' )',
                array_merge( array( $table, (int) $entry_id, (int) $form_id, (int) $form_id, (int) $entry_id ), $keys )
            ) );
        }
        if ( $release_others ) {
            $kept = self::not_in_list( $keys );
            $wpdb->query( $wpdb->prepare(
                'UPDATE %i SET entry_id = NULL WHERE entry_id = %d AND file_key NOT IN ( ' . implode( ',', array_fill( 0, count( $kept ), '%s' ) ) . ' )',
                array_merge( array( $table, (int) $entry_id ), $kept )
            ) );
        }
    }

    // An empty string is never a key, so it keeps NOT IN valid when there is nothing to exclude.
    private static function not_in_list( $keys ) {
        return $keys ? $keys : array( '' );
    }

    public static function claim_for_draft( $user_id, $form_id, $keys ) {
        global $wpdb;
        $keys = array_values( array_unique( array_filter( (array) $keys, array( __CLASS__, 'is_valid_name' ) ) ) );
        $table = self::table();
        $kept = self::not_in_list( $keys );
        $wpdb->query( $wpdb->prepare(
            'UPDATE %i SET draft_user_id = NULL WHERE draft_user_id = %d AND form_id = %d AND file_key NOT IN ( ' . implode( ',', array_fill( 0, count( $kept ), '%s' ) ) . ' )',
            array_merge( array( $table, (int) $user_id, (int) $form_id ), $kept )
        ) );
        if ( $keys ) {
            $wpdb->query( $wpdb->prepare(
                'UPDATE %i SET draft_user_id = %d, form_id = %d WHERE entry_id IS NULL AND form_id IN ( 0, %d ) AND ( draft_user_id IS NULL OR draft_user_id = %d ) AND file_key IN ( ' . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ' )',
                array_merge( array( $table, (int) $user_id, (int) $form_id, (int) $form_id, (int) $user_id ), $keys )
            ) );
        }
    }

    public static function release_draft( $user_id, $form_id = 0 ) {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( 'UPDATE %i SET draft_user_id = NULL WHERE draft_user_id = %d AND ( %d = 0 OR form_id = %d )', self::table(), (int) $user_id, (int) $form_id, (int) $form_id ) );
    }

    public static function entry_keys( $entry_ids ) {
        global $wpdb;
        $entry_ids = array_values( array_filter( array_map( 'absint', (array) $entry_ids ) ) );
        if ( ! $entry_ids ) {
            return array();
        }
        return $wpdb->get_col( $wpdb->prepare(
            'SELECT file_key FROM %i WHERE entry_id IN ( ' . implode( ',', array_fill( 0, count( $entry_ids ), '%d' ) ) . ' )',
            array_merge( array( self::table() ), $entry_ids )
        ) );
    }

    public static function client_files( $value, $user_id ) {
        $files = array();
        $contents = self::contents_by_key( $value );
        if ( ! $contents || ! $user_id ) {
            return $files;
        }
        $expires = time() + SurveyJS_UploadFiles::PREVIEW_LIFETIME;
        foreach ( self::records( array_keys( $contents ) ) as $key => $record ) {
            if ( null === $record->entry_id && (int) $record->draft_user_id === (int) $user_id ) {
                $files[ $contents[ $key ] ] = self::client_file( $key, $expires );
            }
        }
        return $files;
    }

    public static function client_file( $key, $expires ) {
        return array(
            'url'         => self::content_url( $key ),
            'file'        => $key,
            'preview'     => self::signed_url( array( 'name' => '', 'content' => $key ), $expires - time() ),
            'expires'     => $expires,
            'deleteToken' => self::signature( 'delete', $key, $expires ),
        );
    }

    private static function contents_by_key( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $key = self::stored_name( $value );
        if ( null !== $key ) {
            return array( $key => $value['content'] );
        }
        $contents = array();
        foreach ( $value as $item ) {
            $contents += self::contents_by_key( $item );
        }
        return $contents;
    }

    public static function paths( $value ) {
        $paths = array();
        if ( ! is_array( $value ) ) {
            return $paths;
        }
        foreach ( $value as $file ) {
            $key = self::stored_name( $file );
            $path = null === $key ? false : self::storage()->local_path( $key );
            if ( false === $path ) {
                continue;
            }
            $original = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
            $paths[ '' !== $original ? $original : $key ] = $path;
        }
        return $paths;
    }

    public static function admin_url( $file ) {
        $name = self::stored_name( $file );
        if ( null === $name ) {
            return '';
        }
        return add_query_arg(
            array(
                'action' => self::DOWNLOAD_ACTION,
                'file'   => $name,
                'name'   => rawurlencode( (string) ( $file['name'] ?? '' ) ),
            ),
            admin_url( 'admin-ajax.php' )
        );
    }

    public static function signed_url( $file, $lifetime ) {
        $url = self::admin_url( $file );
        if ( '' === $url ) {
            return '';
        }
        $expires = time() + (int) $lifetime;
        return add_query_arg(
            array(
                'expires'   => $expires,
                'signature' => self::signature( 'download', self::stored_name( $file ), $expires ),
            ),
            $url
        );
    }

    public static function has_valid_signature( $name, $expires, $signature ) {
        return self::verify( 'download', $name, $expires, $signature );
    }

    public static function has_valid_delete_token( $key, $expires, $token ) {
        return self::verify( 'delete', $key, $expires, $token );
    }

    private static function verify( $purpose, $key, $expires, $signature ) {
        $expires = (int) $expires;
        if ( $expires < time() || ! is_string( $signature ) || '' === $signature || ! self::is_valid_name( $key ) ) {
            return false;
        }
        return hash_equals( self::signature( $purpose, $key, $expires ), $signature );
    }

    private static function signature( $purpose, $key, $expires ) {
        return hash_hmac( 'sha256', $purpose . '|' . $key . '|' . (int) $expires, wp_salt( 'auth' ) );
    }

    public static function with_admin_urls( $value ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }
        if ( null !== self::stored_name( $value ) ) {
            $value['content'] = self::admin_url( $value );
            return $value;
        }
        foreach ( $value as $key => $item ) {
            $value[ $key ] = self::with_admin_urls( $item );
        }
        return $value;
    }

    public static function links( $value, $lifetime = 0 ) {
        if ( ! is_array( $value ) || ! array_is_list( $value ) ) {
            return array();
        }
        $links = array();
        foreach ( $value as $file ) {
            $name = self::stored_name( $file );
            if ( null === $name ) {
                return array();
            }
            $label = is_string( $file['name'] ?? null ) && '' !== $file['name'] ? $file['name'] : $name;
            $links[] = array( $label, $lifetime ? self::signed_url( $file, $lifetime ) : self::admin_url( $file ) );
        }
        return $links;
    }

    public static function links_text( $links ) {
        $parts = array();
        foreach ( $links as list( $label, $url ) ) {
            $parts[] = $label . ' (' . $url . ')';
        }
        return implode( ', ', $parts );
    }

    public static function links_html( $links ) {
        $parts = array();
        foreach ( $links as list( $label, $url ) ) {
            $parts[] = sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $url ), esc_html( $label ) );
        }
        return implode( ', ', $parts );
    }

    public static function delete_unattached( $key ) {
        global $wpdb;
        $record = self::record( $key );
        if ( null === $record ) {
            return 'not_found';
        }
        if ( null !== $record->entry_id ) {
            return 'attached';
        }
        $wpdb->query( $wpdb->prepare( 'UPDATE %i SET draft_user_id = NULL WHERE file_key = %s AND entry_id IS NULL', self::table(), $key ) );
        self::delete_unreferenced( array( $key ) );
        return null === self::record( $key ) ? 'deleted' : 'attached';
    }

    // Deletes the files no entry refers to. Entries are searched by their JSON, not only by sjs_files: an imported file can be shared by entries,
    // and entries saved before the sjs_files table may not be indexed yet. Referenced files are attached to the entry found.
    public static function delete_unreferenced( $names ) {
        $names = array_values( array_unique( array_filter( array_map( 'strval', (array) $names ), array( __CLASS__, 'is_valid_name' ) ) ) );
        if ( ! $names ) {
            return;
        }
        $records = self::records( $names );
        $names = array_values( array_filter( $names, function ( $name ) use ( $records ) {
            return ! isset( $records[ $name ] ) || null === $records[ $name ]->draft_user_id;
        } ) );
        $referenced = self::referencing_entries( $names );
        foreach ( $names as $name ) {
            if ( isset( $referenced[ $name ] ) ) {
                self::reattach( $name, $referenced[ $name ], isset( $records[ $name ] ) );
            } else {
                self::delete_file( $name );
            }
        }
    }

    private static function referencing_entries( $names ) {
        global $wpdb;
        $found = array();
        foreach ( array_chunk( $names, self::LIKE_BATCH ) as $chunk ) {
            $likes = array();
            foreach ( $chunk as $name ) {
                $likes[] = '%' . $wpdb->esc_like( $name ) . '%';
            }
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT id, form_id, json FROM %i WHERE ' . implode( ' OR ', array_fill( 0, count( $likes ), 'json LIKE %s' ) ),
                array_merge( array( SurveyJS_Entries::table() ), $likes )
            ) );
            foreach ( $rows as $row ) {
                foreach ( array_intersect( self::referenced_names( SurveyJS_Client::decode_survey_json( $row->json ) ), $chunk ) as $name ) {
                    $found[ $name ] = $row;
                }
            }
        }
        return $found;
    }

    private static function reattach( $key, $entry, $has_record ) {
        global $wpdb;
        if ( $has_record ) {
            $wpdb->query( $wpdb->prepare( 'UPDATE %i SET entry_id = %d, form_id = %d, draft_user_id = NULL WHERE file_key = %s', self::table(), (int) $entry->id, (int) $entry->form_id, $key ) );
            return;
        }
        self::insert_records( array( array( 'file_key' => $key, 'form_id' => (int) $entry->form_id, 'entry_id' => (int) $entry->id ) ) );
    }

    private static function delete_file( $key ) {
        global $wpdb;
        $storage = self::storage();
        if ( ! $storage->delete( $key ) ) {
            // Kept for the next cleanup, which only looks at unattached files.
            $wpdb->query( $wpdb->prepare( 'UPDATE %i SET entry_id = NULL, draft_user_id = NULL WHERE file_key = %s', self::table(), $key ) );
            return;
        }
        $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE file_key = %s', self::table(), $key ) );
        do_action( 'surveyjs_file_deleted', $key, $storage );
    }

    // Mail plugins that queue emails read the attachments later, so files of unsaved entries outlive the request.
    public static function delete_later( $keys ) {
        $keys = array_values( array_filter( array_map( 'strval', (array) $keys ), array( __CLASS__, 'is_valid_name' ) ) );
        if ( $keys ) {
            wp_schedule_single_event( time() + DAY_IN_SECONDS, self::DELETE_HOOK, array( $keys ) );
        }
    }

    public static function schedule_cleanup() {
        if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
        }
    }

    public static function unschedule_cleanup() {
        wp_clear_scheduled_hook( self::CLEANUP_HOOK );
        wp_unschedule_hook( self::DELETE_HOOK );
        wp_unschedule_hook( self::BACKFILL_HOOK );
    }

    public static function cleanup() {
        self::maybe_schedule_backfill();
        // Entries saved by a custom surveyjs_save_result() live outside sjs_results, so their files cannot be told apart from abandoned uploads.
        if ( function_exists( 'surveyjs_save_result' ) ) {
            return;
        }
        $max_age = (int) apply_filters( 'surveyjs_unattached_upload_lifetime', DAY_IN_SECONDS );
        if ( $max_age <= 0 ) {
            return;
        }

        global $wpdb;
        $table = self::table();
        $form_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT form_id FROM %i WHERE entry_id IS NULL AND draft_user_id IS NULL AND created_at < %s', $table, gmdate( 'Y-m-d H:i:s', time() - $max_age ) ) );
        foreach ( $form_ids as $form_id ) {
            $cutoff = gmdate( 'Y-m-d H:i:s', time() - self::unattached_lifetime( (int) $form_id, $max_age ) );
            $last_id = 0;
            do {
                $rows = $wpdb->get_results( $wpdb->prepare(
                    'SELECT id, file_key FROM %i WHERE form_id = %d AND entry_id IS NULL AND draft_user_id IS NULL AND created_at < %s AND id > %d ORDER BY id LIMIT %d',
                    $table,
                    (int) $form_id,
                    $cutoff,
                    $last_id,
                    self::CLEANUP_BATCH
                ) );
                if ( $rows ) {
                    $last_id = (int) end( $rows )->id;
                    self::delete_unreferenced( wp_list_pluck( $rows, 'file_key' ) );
                }
            } while ( count( $rows ) === self::CLEANUP_BATCH );
        }
    }

    // Guests keep unfinished answers of Save & Resume forms in the browser, where the server cannot see which files they still use.
    private static function unattached_lifetime( $form_id, $max_age ) {
        $form = $form_id ? SurveyJS_Forms::find( $form_id ) : null;
        if ( $form && ! empty( SurveyJS_Form_Settings::get( $form )['saveResume'] ) ) {
            return max( $max_age, SurveyJS_Drafts::MAX_AGE );
        }
        return $max_age;
    }

    public static function start_backfill() {
        if ( false === get_option( self::BACKFILL_OPTION ) ) {
            update_option( self::BACKFILL_OPTION, array( 'step' => 'entries', 'last_id' => 0 ), false );
        }
        self::maybe_schedule_backfill();
    }

    private static function maybe_schedule_backfill() {
        if ( is_array( get_option( self::BACKFILL_OPTION ) ) && ! wp_next_scheduled( self::BACKFILL_HOOK ) ) {
            wp_schedule_single_event( time(), self::BACKFILL_HOOK );
        }
    }

    // Indexes the files of entries and drafts saved before the sjs_files table, then unindexed files where the folder can be listed.
    public static function backfill() {
        global $wpdb;
        $state = get_option( self::BACKFILL_OPTION );
        if ( ! is_array( $state ) ) {
            return;
        }
        $step = $state['step'] ?? 'entries';
        if ( 'entries' === $step ) {
            $entries = $wpdb->get_results( $wpdb->prepare(
                'SELECT id, form_id, json, created_at FROM %i WHERE id > %d ORDER BY id LIMIT %d',
                SurveyJS_Entries::table(),
                (int) ( $state['last_id'] ?? 0 ),
                self::BACKFILL_BATCH
            ) );
            $rows = array();
            foreach ( $entries as $entry ) {
                $state['last_id'] = (int) $entry->id;
                foreach ( self::stored_files( SurveyJS_Client::decode_survey_json( $entry->json ) ) as $key => $name ) {
                    $rows[] = self::backfill_row( (string) $key, $name, (int) $entry->form_id, $entry->created_at, array( 'entry_id' => (int) $entry->id ) );
                }
            }
            self::insert_records( $rows );
            if ( count( $entries ) < self::BACKFILL_BATCH ) {
                $state = array( 'step' => 'drafts' );
            }
        } elseif ( 'drafts' === $step ) {
            $rows = array();
            foreach ( SurveyJS_Drafts::all() as $draft ) {
                foreach ( self::stored_files( $draft['data'] ) as $key => $name ) {
                    $rows[] = self::backfill_row( (string) $key, $name, $draft['form_id'], '', array( 'draft_user_id' => $draft['user_id'] ) );
                }
            }
            self::insert_records( $rows );
            $state = array( 'step' => 'orphans' );
        } else {
            self::backfill_orphans();
            update_option( self::BACKFILL_OPTION, 'done', false );
            return;
        }
        update_option( self::BACKFILL_OPTION, $state, false );
        wp_schedule_single_event( time(), self::BACKFILL_HOOK );
    }

    private static function backfill_row( $key, $name, $form_id, $created_at, $link ) {
        $type = wp_check_filetype( $key );
        return array(
            'file_key'      => $key,
            'form_id'       => $form_id,
            'original_name' => $name,
            'mime'          => (string) $type['type'],
            'created_at'    => $created_at,
        ) + $link;
    }

    // Stream wrappers such as vip:// cannot list folders; there, files abandoned before the table stay until removed by hand.
    private static function backfill_orphans() {
        $storage = self::default_storage();
        if ( null === $storage || SurveyJS_Uploads_Storage::class !== get_class( $storage ) ) {
            return;
        }
        $dir = $storage->dir();
        if ( null === $dir || wp_is_stream( $dir['path'] ) || ! is_dir( $dir['path'] ) ) {
            return;
        }
        $names = scandir( $dir['path'] );
        if ( ! is_array( $names ) ) {
            return;
        }
        $rows = array();
        foreach ( $names as $name ) {
            $path = $dir['path'] . '/' . $name;
            if ( self::is_valid_name( $name ) && is_file( $path ) ) {
                $mtime = filemtime( $path );
                $rows[] = self::backfill_row( $name, '', 0, $mtime ? gmdate( 'Y-m-d H:i:s', $mtime ) : '', array() );
            }
        }
        self::insert_records( $rows );
    }

    public static function serve() {
        $key = sanitize_file_name( wp_unslash( $_GET['file'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $signed = isset( $_GET['signature'] ) && self::has_valid_signature( // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key,
            absint( $_GET['expires'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            sanitize_key( wp_unslash( $_GET['signature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        );
        $record = self::is_valid_name( $key ) ? self::record( $key ) : null;
        $entry = $record && null !== $record->entry_id ? SurveyJS_Entries::find_entry( (int) $record->form_id, (int) $record->entry_id ) : null;
        $allowed = (bool) apply_filters( 'surveyjs_can_download_file', $signed || current_user_can( self::download_capability() ), $key, $entry, get_current_user_id() );

        if ( ! $allowed ) {
            if ( ! is_user_logged_in() && ! isset( $_GET['signature'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                wp_safe_redirect( wp_login_url( add_query_arg( array() ) ) );
                exit;
            }
            wp_die( esc_html__( 'Sorry, you are not allowed to view this file or the link has expired.', 'surveyjs' ), '', array( 'response' => 403 ) );
        }

        $storage = self::storage();
        if ( ! self::is_valid_name( $key ) || ! $storage->exists( $key ) ) {
            wp_die( esc_html__( 'File not found.', 'surveyjs' ), '', array( 'response' => 404 ) );
        }

        $ext = strtolower( (string) pathinfo( $key, PATHINFO_EXTENSION ) );
        $type = wp_check_filetype( $key );
        $download_name = $key;
        $candidates = array( wp_unslash( $_GET['name'] ?? '' ), $record ? $record->original_name : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized with sanitize_file_name() below.
        foreach ( $candidates as $candidate ) {
            $candidate = sanitize_file_name( (string) $candidate );
            if ( '' !== $candidate && strtolower( (string) pathinfo( $candidate, PATHINFO_EXTENSION ) ) === $ext ) {
                $download_name = $candidate;
                break;
            }
        }
        $disposition = in_array( $ext, self::INLINE_TYPES, true ) ? 'inline' : 'attachment';
        $default_headers = array(
            'Content-Type'           => $type['type'] ? $type['type'] : 'application/octet-stream',
            'Content-Disposition'    => sprintf( '%s; filename="%s"; filename*=UTF-8\'\'%s', $disposition, preg_replace( '/[^\x20-\x7E]+/', '_', remove_accents( $download_name ) ), rawurlencode( $download_name ) ),
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag'           => 'noindex, nofollow',
        );
        $headers = apply_filters( 'surveyjs_file_serve_headers', $default_headers, $key, $entry );

        while ( ob_get_level() ) {
            ob_end_clean();
        }
        nocache_headers();
        $storage->serve( $key, $download_name, $disposition, is_array( $headers ) ? $headers : $default_headers );
        exit;
    }

    public static function temp_copy( $source ) {
        if ( ! function_exists( 'wp_tempnam' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $reserved = wp_tempnam( wp_basename( $source ) );
        if ( ! $reserved ) {
            return false;
        }
        // PHPMailer takes the attachment's MIME type from the path's extension, and wp_tempnam() always ends in .tmp.
        $ext = strtolower( (string) pathinfo( $source, PATHINFO_EXTENSION ) );
        $path = '' !== $ext ? $reserved . '.' . $ext : $reserved;
        if ( ! self::$temp_files ) {
            register_shutdown_function( array( __CLASS__, 'delete_temp_files' ) );
        }
        self::$temp_files[] = $reserved;
        if ( $path !== $reserved ) {
            self::$temp_files[] = $path;
        }
        return copy( $source, $path ) ? $path : false;
    }

    // PHPMailer refuses paths with a scheme (vip://, s3://) and wp_mail() then drops the attachment silently.
    public static function local_attachments( $attachments ) {
        if ( ! is_array( $attachments ) ) {
            return $attachments;
        }
        foreach ( $attachments as $name => $path ) {
            if ( is_string( $path ) && wp_is_stream( $path ) ) {
                $local = self::temp_copy( $path );
                if ( false === $local ) {
                    unset( $attachments[ $name ] );
                } else {
                    $attachments[ $name ] = $local;
                }
            }
        }
        return $attachments;
    }

    public static function delete_temp_files() {
        array_map( 'wp_delete_file', self::$temp_files );
        self::$temp_files = array();
    }

    public static function is_valid_name( $name ) {
        return is_string( $name ) && 'index.php' !== $name && ! str_contains( $name, '..' ) && 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $name ) && strlen( $name ) <= 191;
    }
}
