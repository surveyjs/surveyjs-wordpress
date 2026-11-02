<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Entries {

    const STATUSES = array( 'unread', 'read', 'spam' );
    const BATCH = 200;
    const CACHE_GROUP = 'surveyjs';

    public static function init() {
        add_filter( 'wpmu_drop_tables', array( __CLASS__, 'drop_tables' ), 10, 2 );
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
            'read'   => __( 'Read', 'surveyjs' ),
            'spam'   => __( 'Spam', 'surveyjs' ),
        );
    }

    public static function insert( $form_id, $json, $args = array() ) {
        global $wpdb;
        $args = array_merge( array(
            'status'     => 'unread',
            'user_id'    => 0,
            'ip'         => null,
            'created_at' => current_time( 'mysql', true ),
        ), $args );
        $inserted = $wpdb->insert(
            self::table(),
            array(
                'form_id'    => (int) $form_id,
                'json'       => $json,
                'status'     => in_array( $args['status'], self::STATUSES, true ) ? $args['status'] : 'unread',
                'user_id'    => (int) $args['user_id'],
                'ip'         => $args['ip'],
                'created_at' => $args['created_at'],
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s' )
        );
        self::flush_counts();
        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function set_status( $form_id, $ids, $status ) {
        global $wpdb;
        $ids = array_filter( array_map( 'absint', (array) $ids ) );
        if ( ! $ids || ! in_array( $status, self::STATUSES, true ) ) {
            return 0;
        }
        $table = self::table();
        $in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        self::flush_counts();
        return (int) $wpdb->query( $wpdb->prepare( "UPDATE $table SET status = %s WHERE form_id = %d AND id IN ( $in )", array_merge( array( $status, (int) $form_id ), $ids ) ) );
    }

    public static function delete( $form, $ids ) {
        global $wpdb;
        $ids = array_filter( array_map( 'absint', (array) $ids ) );
        if ( ! $ids ) {
            return 0;
        }
        $table = self::table();
        $in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, form_id, json FROM $table WHERE form_id = %d AND id IN ( $in )", array_merge( array( (int) $form->id ), $ids ) ) );
        return self::delete_rows( $rows );
    }

    public static function delete_for_form( $form ) {
        global $wpdb;
        $table = self::table();
        $deleted = 0;
        do {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, form_id, json FROM $table WHERE form_id = %d ORDER BY id LIMIT %d", (int) $form->id, self::BATCH ) );
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
        $table = self::table();
        $in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        self::flush_counts();
        $deleted = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN ( $in )", $ids ) );
        SurveyJS_Uploaded_Files::delete_unreferenced( $file_names );
        return $deleted;
    }

    public static function counts( $form_id ) {
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS total FROM $table WHERE form_id = %d GROUP BY status", (int) $form_id ) );
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
        $table = self::table();
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.form_id, COUNT(*) AS total FROM $table r
            INNER JOIN {$wpdb->posts} p ON p.ID = r.form_id AND p.post_type = %s AND p.post_status = 'publish'
            WHERE r.status = 'unread'
            GROUP BY r.form_id",
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
    }

    public static function exists() {
        global $wpdb;
        $table = self::table();
        return (bool) $wpdb->get_var( "SELECT id FROM $table LIMIT 1" );
    }

    public static function client_ip() {
        if ( ! SurveyJS_SettingsPage::option( 'store_ip' ) ) {
            return null;
        }
        $ip = apply_filters( 'surveyjs_entry_ip', isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
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
