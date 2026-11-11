<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Install {

    const VERSION_OPTION = 'surveyjs_db_version';
    const LOCK_OPTION = 'surveyjs_upgrade_lock';
    const LOCK_TIMEOUT = 600;

    public static function maybe_upgrade() {
        if ( get_option( self::VERSION_OPTION ) !== SURVEYJS_DB_VERSION ) {
            self::install();
        }
    }

    public static function install() {
        if ( ! self::lock() ) {
            return;
        }
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        SurveyJS_Forms::register_post_type();
        if ( false === get_option( self::VERSION_OPTION ) && self::legacy_forms_exist() ) {
            SurveyJS_Upgrade_Notice::flag_legacy_update();
        }
        $legacy_ids = self::migrate_forms();
        self::upgrade_entries_table( $legacy_ids );
        update_option( self::VERSION_OPTION, SURVEYJS_DB_VERSION );
        delete_option( self::LOCK_OPTION );
    }

    private static function lock() {
        if ( add_option( self::LOCK_OPTION, time(), '', false ) ) {
            return true;
        }
        if ( time() - (int) get_option( self::LOCK_OPTION ) < self::LOCK_TIMEOUT ) {
            return false;
        }
        update_option( self::LOCK_OPTION, time(), false );
        return true;
    }

    private static function table_exists( $table ) {
        global $wpdb;
        return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    }

    private static function legacy_forms_exist() {
        global $wpdb;
        $legacy_table = $wpdb->prefix . 'sjs_my_surveys';
        return self::table_exists( $legacy_table ) && $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM %i LIMIT 1', $legacy_table ) );
    }

    // Forms from the pre-3 table become sjs_form posts. A form keeps its old ID as the post ID when that ID is free,
    // so existing [surveyjs id="N"] shortcodes and blocks keep working without a lookup; otherwise the old ID
    // goes to SurveyJS_Forms::LEGACY_MAP_OPTION. Free IDs are taken first: every post created after that gets a
    // higher ID, so a new form can never land on an old ID that still points to another form.
    private static function migrate_forms() {
        global $wpdb;
        $legacy_table = $wpdb->prefix . 'sjs_my_surveys';
        if ( ! self::table_exists( $legacy_table ) ) {
            return array();
        }

        $map = array();
        $migrated = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", SurveyJS_Forms::LEGACY_ID_META ) );
        foreach ( $migrated as $row ) {
            $map[ (int) $row->meta_value ] = (int) $row->post_id;
        }

        $pending = array();
        foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id', $legacy_table ) ) as $row ) {
            if ( ! isset( $map[ (int) $row->id ] ) ) {
                $pending[ (int) $row->id ] = $row;
            }
        }

        if ( $pending ) {
            $taken = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID IN ( " . implode( ',', array_fill( 0, count( $pending ), '%d' ) ) . ' )', array_keys( $pending ) ) ) );
            $free = array_diff_key( $pending, array_flip( $taken ) );
            $busy = array_intersect_key( $pending, array_flip( $taken ) );
            foreach ( array( $free, $busy ) as $pass => $rows ) {
                foreach ( $rows as $old_id => $row ) {
                    $new_id = SurveyJS_Forms::create( array(
                        'name'      => (string) $row->name,
                        'json'      => (string) $row->json,
                        'theme'     => (string) ( $row->theme ?? '' ),
                        'settings'  => (string) ( $row->settings ?? '' ),
                        'import_id' => 0 === $pass ? $old_id : 0,
                    ) );
                    if ( $new_id ) {
                        update_post_meta( $new_id, SurveyJS_Forms::LEGACY_ID_META, $old_id );
                        $map[ $old_id ] = $new_id;
                    }
                }
            }
        }

        $moved = array();
        foreach ( $map as $old_id => $new_id ) {
            if ( $old_id !== $new_id ) {
                $moved[ $old_id ] = $new_id;
            }
        }
        update_option( SurveyJS_Forms::LEGACY_MAP_OPTION, $moved );
        return $map;
    }

    private static function upgrade_entries_table( $legacy_ids ) {
        global $wpdb;
        $table = SurveyJS_Entries::table();
        $legacy_column = self::table_exists( $table ) && $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM %i WHERE Field = 'surveyId'", $table ) );
        if ( $legacy_column ) {
            $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i MODIFY surveyId mediumint(9) NOT NULL DEFAULT 0', $table ) );
        }

        dbDelta( SurveyJS_Entries::schema() );

        if ( $legacy_column ) {
            foreach ( $legacy_ids as $old_id => $new_id ) {
                $wpdb->query( $wpdb->prepare( "UPDATE %i SET form_id = %d, status = 'read' WHERE surveyId = %d AND form_id = 0", $table, $new_id, $old_id ) );
            }
            $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN surveyId', $table ) );
        }
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM %i WHERE Key_name = 'id'", $table ) ) ) {
            $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP INDEX id', $table ) );
        }
        SurveyJS_Entries::flush_counts();
    }
}
