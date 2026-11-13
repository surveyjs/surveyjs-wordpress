<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Entries {

    const STATUSES = array( 'unread', 'read', 'partial', 'spam' );
    const PARTIAL = 'partial';
    const PARTIAL_MAX_AGE = 30 * DAY_IN_SECONDS;
    const PARTIAL_WARNING = 5 * DAY_IN_SECONDS;
    const CLEANUP_HOOK = 'surveyjs_cleanup_partial_entries';
    const BATCH = 200;
    const CACHE_GROUP = 'surveyjs';

    public static function init() {
        add_filter( 'wpmu_drop_tables', array( __CLASS__, 'drop_tables' ), 10, 2 );
        add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup_partial' ) );
    }

    public static function partial_max_age() {
        return (int) apply_filters( 'surveyjs_partial_entry_lifetime', self::PARTIAL_MAX_AGE );
    }

    public static function schedule_partial_cleanup() {
        if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
            wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
        }
    }

    public static function unschedule_partial_cleanup() {
        wp_clear_scheduled_hook( self::CLEANUP_HOOK );
    }

    public static function cleanup_partial() {
        global $wpdb;
        $table = self::table();
        $max_age = self::partial_max_age();
        if ( $max_age > 0 ) {
            $cutoff = gmdate( 'Y-m-d H:i:s', time() - $max_age );
            do {
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, form_id, json FROM %i WHERE status = %s AND token_hash IS NOT NULL AND kept = 0 AND created_at < %s ORDER BY id LIMIT %d', $table, self::PARTIAL, $cutoff, self::BATCH ) );
                self::delete_rows( $rows );
            } while ( count( $rows ) === self::BATCH );
        }
        if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE status = %s AND token_hash IS NOT NULL AND kept = 0 LIMIT 1', $table, self::PARTIAL ) ) ) {
            self::unschedule_partial_cleanup();
        }
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'sjs_results';
    }

    public static function schema() {
        global $wpdb;
        $table = self::table();
        return "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id bigint(20) unsigned NOT NULL DEFAULT 0,
            json longtext,
            status varchar(20) NOT NULL DEFAULT 'unread',
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            ip varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            token_hash char(64) DEFAULT NULL,
            last_page smallint(5) unsigned NOT NULL DEFAULT 0,
            kept tinyint(1) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY form_status_date (form_id,status,created_at),
            KEY status (status)
        ) {$wpdb->get_charset_collate()};";
    }

    public static function drop_tables( $tables, $site_id ) {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix( $site_id );
        $tables[] = $prefix . 'sjs_results';
        $tables[] = $prefix . 'sjs_my_surveys';
        return $tables;
    }

    public static function statuses() {
        return array(
            'unread' => __( 'Unread', 'surveyjs' ),
            'read'    => __( 'Read', 'surveyjs' ),
            'partial' => __( 'Partial', 'surveyjs' ),
            'spam'    => __( 'Spam', 'surveyjs' ),
        );
    }

    public static function status_label( $status, $last_page = 0 ) {
        $label = self::statuses()[ $status ] ?? $status;
        if ( self::PARTIAL === $status && $last_page > 0 ) {
            /* translators: 1: "Partial", 2: number of the form page the respondent reached */
            return sprintf( __( '%1$s, page %2$d', 'surveyjs' ), $label, $last_page );
        }
        return $label;
    }

    public static function partial_deletion_time( $created_at, $kept ) {
        $max_age = self::partial_max_age();
        $saved = strtotime( $created_at . ' UTC' );
        return $kept || $max_age <= 0 || ! $saved ? 0 : $saved + $max_age;
    }

    public static function partial_deletion_soon( $deletion_time ) {
        return $deletion_time && $deletion_time - time() <= self::PARTIAL_WARNING;
    }

    public static function count_partial_deleted_soon( $form_id ) {
        return self::partial_deleted_soon_by_form()[ (int) $form_id ] ?? 0;
    }

    public static function partial_deleted_soon_by_form() {
        $counts = wp_cache_get( 'partial_deleted_soon_by_form', self::CACHE_GROUP );
        if ( is_array( $counts ) ) {
            return $counts;
        }
        $counts = array();
        $max_age = self::partial_max_age();
        if ( $max_age > 0 ) {
            global $wpdb;
            $table = self::table();
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT r.form_id, COUNT(*) AS total FROM %i r
                INNER JOIN {$wpdb->posts} p ON p.ID = r.form_id AND p.post_type = %s AND p.post_status = 'publish'
                WHERE r.status = %s AND r.token_hash IS NOT NULL AND r.kept = 0 AND r.created_at < %s
                GROUP BY r.form_id",
                $table,
                SurveyJS_Forms::POST_TYPE,
                self::PARTIAL,
                gmdate( 'Y-m-d H:i:s', time() - $max_age + self::PARTIAL_WARNING )
            ) );
            foreach ( $rows as $row ) {
                $counts[ (int) $row->form_id ] = (int) $row->total;
            }
        }
        wp_cache_set( 'partial_deleted_soon_by_form', $counts, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $counts;
    }

    public static function set_kept( $form_id, $ids, $kept ) {
        global $wpdb;
        $ids = array_filter( array_map( 'absint', (array) $ids ) );
        if ( ! $ids ) {
            return 0;
        }
        self::flush_counts();
        $updated = (int) $wpdb->query( $wpdb->prepare(
            'UPDATE %i SET kept = %d WHERE form_id = %d AND token_hash IS NOT NULL AND id IN ( ' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' )',
            array_merge( array( self::table(), $kept ? 1 : 0, (int) $form_id ), $ids )
        ) );
        if ( ! $kept ) {
            self::schedule_partial_cleanup();
        }
        return $updated;
    }

    public static function partial_enabled( $settings ) {
        return ! empty( $settings['storeEntries'] ) && ! empty( $settings['savePartialEntries'] );
    }

    public static function save_partial( $form_id, $id, $token, $json, $last_page, $args ) {
        global $wpdb;
        $last_page = min( 65535, absint( $last_page ) );
        $row = self::find_unfinished( $form_id, $id, $token );
        if ( $row ) {
            if ( self::PARTIAL === $row->status ) {
                $wpdb->update(
                    self::table(),
                    array(
                        'json'       => $json,
                        'last_page'  => $last_page,
                        'user_id'    => (int) $args['user_id'],
                        'ip'         => $args['ip'],
                        'created_at' => current_time( 'mysql', true ),
                    ),
                    array( 'id' => (int) $row->id, 'token_hash' => $row->token_hash ),
                    array( '%s', '%d', '%d', '%s', '%s' ),
                    array( '%d', '%s' )
                );
            }
            return array( 'id' => (int) $row->id );
        }

        $token = bin2hex( random_bytes( 24 ) );
        $inserted = $wpdb->insert(
            self::table(),
            array(
                'form_id'    => (int) $form_id,
                'json'       => $json,
                'status'     => self::PARTIAL,
                'user_id'    => (int) $args['user_id'],
                'ip'         => $args['ip'],
                'created_at' => current_time( 'mysql', true ),
                'token_hash' => self::hash_token( $token ),
                'last_page'  => $last_page,
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d' )
        );
        if ( ! $inserted ) {
            return null;
        }
        self::schedule_partial_cleanup();
        return array( 'id' => (int) $wpdb->insert_id, 'token' => $token );
    }

    public static function mark_finished( $id ) {
        global $wpdb;
        self::flush_counts();
        return (int) $wpdb->query( $wpdb->prepare( "UPDATE %i SET status = IF( status = 'spam', 'spam', 'read' ), token_hash = NULL WHERE id = %d AND token_hash IS NOT NULL", self::table(), (int) $id ) );
    }

    public static function complete_partial( $form_id, $id, $token, $json, $args ) {
        global $wpdb;
        $row = self::find_unfinished( $form_id, $id, $token );
        if ( ! $row ) {
            return 0;
        }
        $updated = $wpdb->update(
            self::table(),
            array(
                'json'       => $json,
                'status'     => 'spam' === $row->status ? 'spam' : 'unread',
                'user_id'    => (int) $args['user_id'],
                'ip'         => $args['ip'],
                'created_at' => current_time( 'mysql', true ),
                'token_hash' => null,
            ),
            array( 'id' => (int) $row->id, 'token_hash' => $row->token_hash ),
            array( '%s', '%s', '%d', '%s', '%s', '%s' ),
            array( '%d', '%s' )
        );
        self::flush_counts();
        return $updated ? (int) $row->id : 0;
    }

    private static function find_unfinished( $form_id, $id, $token ) {
        global $wpdb;
        if ( ! $id || ! is_string( $token ) || '' === $token ) {
            return null;
        }
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT id, status, token_hash FROM %i WHERE id = %d AND form_id = %d AND token_hash IS NOT NULL', self::table(), (int) $id, (int) $form_id ) );
        return $row && hash_equals( $row->token_hash, self::hash_token( $token ) ) ? $row : null;
    }

    private static function hash_token( $token ) {
        return hash( 'sha256', $token );
    }

    public static function insert( $form_id, $json, $args = array() ) {
        global $wpdb;
        $args = array_merge( array(
            'status'     => 'unread',
            'user_id'    => 0,
            'ip'         => null,
            'created_at' => current_time( 'mysql', true ),
        ), $args );
        $status = in_array( $args['status'], self::STATUSES, true ) ? $args['status'] : 'unread';
        $inserted = $wpdb->insert(
            self::table(),
            array(
                'form_id'    => (int) $form_id,
                'json'       => $json,
                'status'     => $status,
                'user_id'    => (int) $args['user_id'],
                'ip'         => $args['ip'],
                'created_at' => $args['created_at'],
                // A partial entry with no respondent to continue it (e.g. imported) still needs a token hash: it marks the row as unfinished.
                'token_hash' => self::PARTIAL === $status ? self::hash_token( bin2hex( random_bytes( 24 ) ) ) : null,
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
        );
        if ( self::PARTIAL === $status ) {
            self::schedule_partial_cleanup();
        }
        self::flush_counts();
        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function set_status( $form_id, $ids, $status ) {
        global $wpdb;
        $ids = array_filter( array_map( 'absint', (array) $ids ) );
        if ( ! $ids || ! in_array( $status, self::STATUSES, true ) || self::PARTIAL === $status ) {
            return 0;
        }
        self::flush_counts();
        return (int) $wpdb->query( $wpdb->prepare(
            'UPDATE %i SET status = %s WHERE form_id = %d AND id IN ( ' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' ) AND ( %d = 1 OR token_hash IS NULL )',
            array_merge( array( self::table(), $status, (int) $form_id ), $ids, array( 'spam' === $status ? 1 : 0 ) )
        ) );
    }

    public static function unspam( $form_id, $ids ) {
        global $wpdb;
        $ids = array_filter( array_map( 'absint', (array) $ids ) );
        if ( ! $ids ) {
            return 0;
        }
        self::flush_counts();
        return (int) $wpdb->query( $wpdb->prepare(
            "UPDATE %i SET status = IF( token_hash IS NULL, 'read', %s ) WHERE form_id = %d AND status = 'spam' AND id IN ( " . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' )',
            array_merge( array( self::table(), self::PARTIAL, (int) $form_id ), $ids )
        ) );
    }

    public static function delete( $form, $ids ) {
        global $wpdb;
        $ids = array_filter( array_map( 'absint', (array) $ids ) );
        if ( ! $ids ) {
            return 0;
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT id, form_id, json FROM %i WHERE form_id = %d AND id IN ( ' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' )',
            array_merge( array( self::table(), (int) $form->id ), $ids )
        ) );
        return self::delete_rows( $rows );
    }

    public static function delete_for_form( $form ) {
        global $wpdb;
        $table = self::table();
        $deleted = 0;
        do {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, form_id, json FROM %i WHERE form_id = %d ORDER BY id LIMIT %d', $table, (int) $form->id, self::BATCH ) );
            $deleted += self::delete_rows( $rows );
        } while ( count( $rows ) === self::BATCH );
        return $deleted;
    }

    public static function delete_rows( $rows ) {
        global $wpdb;
        if ( ! $rows ) {
            return 0;
        }
        $file_names = array();
        foreach ( $rows as $row ) {
            $file_names = array_merge( $file_names, SurveyJS_Uploaded_Files::referenced_names( SurveyJS_Client::decode_survey_json( $row->json ) ) );
        }
        $ids = array_map( 'intval', wp_list_pluck( $rows, 'id' ) );
        self::flush_counts();
        $deleted = (int) $wpdb->query( $wpdb->prepare(
            'DELETE FROM %i WHERE id IN ( ' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' )',
            array_merge( array( self::table() ), $ids )
        ) );
        SurveyJS_Uploaded_Files::delete_unreferenced( $file_names );
        return $deleted;
    }

    public static function query( $form_id, $args = array() ) {
        global $wpdb;
        $args = array_merge( array(
            'status' => array( 'unread', 'read' ),
            'ids'    => array(),
            'after'  => '',
            'before' => '',
            'order'  => 'DESC',
            'limit'  => 0,
            'offset' => 0,
        ), $args );
        $statuses = array_values( array_intersect( (array) $args['status'], self::STATUSES ) );
        if ( ! $statuses ) {
            return array();
        }
        $ids = array_filter( array_map( 'absint', (array) $args['ids'] ) );
        $id_list = $ids ? $ids : array( 0 );
        $order = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
        $limit = absint( $args['limit'] );
        $values = array_merge(
            array( self::table(), (int) $form_id ),
            $statuses,
            array( $ids ? 1 : 0 ),
            $id_list,
            array(
                (string) $args['after'],
                (string) $args['after'],
                (string) $args['before'],
                (string) $args['before'],
                $limit ? $limit : PHP_INT_MAX,
                $limit ? absint( $args['offset'] ) : 0,
            )
        );
        // MySQL 8 rejects comparing a DATETIME column with '' even when that branch is never needed, so NULLIF() turns '' into NULL.
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT id, form_id, json, status, user_id, ip, created_at, last_page, kept FROM %i
            WHERE form_id = %d AND status IN ( ' . implode( ',', array_fill( 0, count( $statuses ), '%s' ) ) . ' )
            AND ( %d = 0 OR id IN ( ' . implode( ',', array_fill( 0, count( $id_list ), '%d' ) ) . " ) )
            AND ( %s = '' OR created_at >= NULLIF( %s, '' ) )
            AND ( %s = '' OR created_at < NULLIF( %s, '' ) )
            ORDER BY " . sanitize_sql_orderby( 'created_at ' . $order . ', id ' . $order ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- validated by sanitize_sql_orderby(), which WPCS does not list as SQL escaping; placeholders cannot hold a sort clause.
            ' LIMIT %d OFFSET %d',
            $values
        ) );
        return array_map( array( __CLASS__, 'entry' ), $rows );
    }

    public static function find_entry( $form_id, $id ) {
        $entries = self::query( $form_id, array( 'ids' => array( $id ), 'status' => self::STATUSES ) );
        return $entries ? $entries[0] : null;
    }

    private static function entry( $row ) {
        $data = SurveyJS_Client::decode_survey_json( $row->json );
        return (object) array(
            'id'         => (int) $row->id,
            'form_id'    => (int) $row->form_id,
            'status'     => $row->status,
            'user_id'    => (int) $row->user_id,
            'ip'         => (string) $row->ip,
            'created_at' => $row->created_at,
            'last_page'  => (int) $row->last_page,
            'kept'       => (bool) $row->kept,
            'data'       => is_array( $data ) ? $data : array(),
        );
    }

    public static function counts( $form_id ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i WHERE form_id = %d GROUP BY status', self::table(), (int) $form_id ) );
        $counts = array_fill_keys( self::STATUSES, 0 );
        foreach ( $rows as $row ) {
            if ( isset( $counts[ $row->status ] ) ) {
                $counts[ $row->status ] = (int) $row->total;
            }
        }
        return $counts;
    }

    public static function unread_by_form() {
        $counts = wp_cache_get( 'unread_by_form', self::CACHE_GROUP );
        if ( is_array( $counts ) ) {
            return $counts;
        }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.form_id, COUNT(*) AS total FROM %i r
            INNER JOIN {$wpdb->posts} p ON p.ID = r.form_id AND p.post_type = %s AND p.post_status = 'publish'
            WHERE r.status = 'unread'
            GROUP BY r.form_id",
            self::table(),
            SurveyJS_Forms::POST_TYPE
        ) );
        $counts = array();
        foreach ( $rows as $row ) {
            $counts[ (int) $row->form_id ] = (int) $row->total;
        }
        wp_cache_set( 'unread_by_form', $counts, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $counts;
    }

    public static function flush_counts() {
        wp_cache_delete( 'unread_by_form', self::CACHE_GROUP );
        wp_cache_delete( 'partial_deleted_soon_by_form', self::CACHE_GROUP );
        wp_cache_set_last_changed( self::CACHE_GROUP );
    }

    public static function exists() {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE status <> %s LIMIT 1', self::table(), self::PARTIAL ) );
    }

    public static function client_ip() {
        if ( ! SurveyJS_SettingsPage::option( 'store_ip' ) ) {
            return null;
        }
        $ip = apply_filters( 'surveyjs_entry_ip', isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' ); // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders -- validated with FILTER_VALIDATE_IP on the next line
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : null;
    }

    public static function format_date( $created_at ) {
        $timestamp = strtotime( (string) $created_at . ' UTC' );
        if ( ! $timestamp || $timestamp <= 0 ) {
            return '';
        }
        return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
    }
}
