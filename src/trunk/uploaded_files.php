<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Uploaded_Files {

    const CAPABILITY      = 'manage_options';
    const CLEANUP_HOOK    = 'surveyjs_cleanup_uploads';
    const CLEANUP_BATCH   = 500;
    const DELETE_HOOK     = 'surveyjs_delete_sent_files';
    const DOWNLOAD_ACTION = 'SurveyJS_DownloadFile';
    const INLINE_TYPES    = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf' );

    public static function paths( $value ) {
        $dir = self::dir();
        if ( null === $dir || ! is_array( $value ) ) {
            return array();
        }

        $paths = array();
        foreach ( $value as $file ) {
            $name = self::stored_name( $file );
            if ( null === $name || ! is_file( $dir . '/' . $name ) ) {
                continue;
            }
            $original = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
            $paths[ '' !== $original ? $original : $name ] = $dir . '/' . $name;
        }
        return $paths;
    }

    public static function entry_paths( $questions, $data ) {
        $paths = array();
        foreach ( $questions as $question ) {
            if ( 'file' === ( $question['type'] ?? '' ) ) {
                $paths = array_merge( $paths, array_values( self::paths( $data[ $question['name'] ] ?? null ) ) );
            }
        }
        return $paths;
    }

    public static function stored_name( $file ) {
        $url = is_array( $file ) ? ( $file['content'] ?? '' ) : '';
        if ( ! is_string( $url ) || '' === $url ) {
            return null;
        }
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        if ( ! str_ends_with( dirname( $path ), SurveyJS_UploadFiles::SUBDIR ) ) {
            return null;
        }
        $name = basename( $path );
        return self::is_valid_name( $name ) ? $name : null;
    }

    public static function referenced_names( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $names = array();
        $name = self::stored_name( $value );
        if ( null !== $name ) {
            $names[] = $name;
        }
        foreach ( $value as $item ) {
            $names = array_merge( $names, self::referenced_names( $item ) );
        }
        return array_values( array_unique( $names ) );
    }

    public static function path( $name ) {
        $dir = self::dir();
        if ( null === $dir || ! self::is_valid_name( $name ) ) {
            return null;
        }
        $path = $dir . '/' . $name;
        return is_file( $path ) ? $path : null;
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
                'signature' => self::signature( self::stored_name( $file ), $expires ),
            ),
            $url
        );
    }

    public static function has_valid_signature( $name, $expires, $signature ) {
        $expires = (int) $expires;
        if ( $expires < time() || ! is_string( $signature ) || '' === $signature ) {
            return false;
        }
        return hash_equals( self::signature( $name, $expires ), $signature );
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

    public static function links( $value ) {
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
            $links[] = array( $label, self::admin_url( $file ) );
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

    public static function delete_unreferenced( $names ) {
        global $wpdb;
        $names = array_values( array_unique( array_filter( (array) $names, array( self::class, 'is_valid_name' ) ) ) );
        if ( empty( $names ) ) {
            return;
        }

        $likes = array();
        foreach ( $names as $name ) {
            $likes[] = '%' . $wpdb->esc_like( $name ) . '%';
        }
        $where = implode( ' OR ', array_fill( 0, count( $likes ), 'json LIKE %s' ) );
        $rows = $wpdb->get_col( $wpdb->prepare( "SELECT json FROM {$wpdb->prefix}sjs_results WHERE $where", $likes ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $still_used = array();
        foreach ( $rows as $json ) {
            $still_used = array_merge( $still_used, self::referenced_names( SurveyJS_Client::decode_survey_json( $json ) ) );
        }

        foreach ( array_diff( $names, $still_used ) as $name ) {
            $path = self::path( $name );
            if ( null !== $path ) {
                wp_delete_file( $path );
            }
        }
    }

    // Mail plugins that queue emails read the attachments later, so files of unsaved entries outlive the request.
    public static function delete_later( $paths ) {
        $names = array_values( array_filter( array_map( 'wp_basename', (array) $paths ), array( self::class, 'is_valid_name' ) ) );
        if ( $names ) {
            wp_schedule_single_event( time() + DAY_IN_SECONDS, self::DELETE_HOOK, array( $names ) );
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
    }

    public static function cleanup() {
        // Entries saved by a custom surveyjs_save_result() live outside sjs_results, so their files cannot be told apart from abandoned uploads.
        if ( function_exists( 'surveyjs_save_result' ) ) {
            return;
        }
        $max_age = (int) apply_filters( 'surveyjs_unattached_upload_lifetime', DAY_IN_SECONDS );
        $dir = self::dir();
        if ( $max_age <= 0 || null === $dir ) {
            return;
        }

        $candidates = array();
        foreach ( (array) scandir( $dir ) as $name ) {
            $path = $dir . '/' . $name;
            if ( self::is_valid_name( $name ) && is_file( $path ) && filemtime( $path ) < time() - $max_age ) {
                $candidates[ $name ] = $path;
            }
        }

        global $wpdb;
        $last_id = 0;
        while ( $candidates ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, json FROM {$wpdb->prefix}sjs_results WHERE id > %d ORDER BY id LIMIT %d",
                $last_id,
                self::CLEANUP_BATCH
            ) );
            foreach ( $rows as $row ) {
                $last_id = (int) $row->id;
                foreach ( self::referenced_names( SurveyJS_Client::decode_survey_json( $row->json ) ) as $name ) {
                    unset( $candidates[ $name ] );
                }
            }
            if ( count( $rows ) < self::CLEANUP_BATCH ) {
                break;
            }
        }

        array_map( 'wp_delete_file', $candidates );
    }

    public static function serve() {
        $name = sanitize_file_name( wp_unslash( $_GET['file'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $signed = isset( $_GET['signature'] ) && self::has_valid_signature( // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $name,
            absint( $_GET['expires'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            sanitize_key( wp_unslash( $_GET['signature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        );

        if ( ! $signed && ! current_user_can( self::CAPABILITY ) ) {
            if ( ! is_user_logged_in() && ! isset( $_GET['signature'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                wp_safe_redirect( wp_login_url( add_query_arg( array() ) ) );
                exit;
            }
            wp_die( esc_html__( 'Sorry, you are not allowed to view this file or the link has expired.', 'surveyjs' ), '', array( 'response' => 403 ) );
        }

        $path = self::path( $name );
        if ( null === $path ) {
            wp_die( esc_html__( 'File not found.', 'surveyjs' ), '', array( 'response' => 404 ) );
        }

        $ext = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
        $type = wp_check_filetype( $name );
        $download_name = sanitize_file_name( wp_unslash( $_GET['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( strtolower( (string) pathinfo( $download_name, PATHINFO_EXTENSION ) ) !== $ext ) {
            $download_name = $name;
        }
        $disposition = in_array( $ext, self::INLINE_TYPES, true ) ? 'inline' : 'attachment';

        while ( ob_get_level() ) {
            ob_end_clean();
        }
        nocache_headers();
        header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
        header( 'Content-Length: ' . filesize( $path ) );
        header( sprintf( 'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s', $disposition, preg_replace( '/[^\x20-\x7E]+/', '_', remove_accents( $download_name ) ), rawurlencode( $download_name ) ) );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Robots-Tag: noindex, nofollow' );
        readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        exit;
    }

    private static function signature( $name, $expires ) {
        return hash_hmac( 'sha256', $name . '|' . (int) $expires, wp_salt( 'auth' ) );
    }

    public static function is_valid_name( $name ) {
        return is_string( $name ) && 'index.php' !== $name && ! str_contains( $name, '..' ) && 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $name );
    }

    private static function dir() {
        $upload_dir = wp_upload_dir( null, false );
        if ( ! empty( $upload_dir['error'] ) ) {
            return null;
        }
        $dir = $upload_dir['basedir'] . SurveyJS_UploadFiles::SUBDIR;
        return is_dir( $dir ) ? $dir : null;
    }
}
