<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Entry_Retention {

    const MODES       = array( 'default', 'forever', 'days' );
    const HOOK        = 'surveyjs_delete_old_entries';
    const MAX_BATCHES = 25;
    const RETRY_DELAY = 5 * MINUTE_IN_SECONDS;

    public static function init() {
        add_action( self::HOOK, array( __CLASS__, 'run' ) );
        add_action( 'added_post_meta', array( __CLASS__, 'settings_saved' ), 10, 4 );
        add_action( 'updated_post_meta', array( __CLASS__, 'settings_saved' ), 10, 4 );
        add_action( 'add_option_' . SurveyJS_SettingsPage::OPTION, array( __CLASS__, 'schedule_if_needed' ) );
        add_action( 'update_option_' . SurveyJS_SettingsPage::OPTION, array( __CLASS__, 'schedule_if_needed' ) );
    }

    public static function site_days() {
        return SurveyJS_SettingsPage::retention_days();
    }

    public static function form_days( $settings, $site_days = null ) {
        switch ( $settings['entryRetention'] ?? 'default' ) {
            case 'forever':
                return 0;
            case 'days':
                return max( 1, absint( $settings['entryRetentionDays'] ?? 0 ) );
            default:
                return null === $site_days ? self::site_days() : $site_days;
        }
    }

    public static function settings_saved( $meta_id, $post_id, $meta_key, $meta_value ) {
        if ( SurveyJS_Forms::SETTINGS_META !== $meta_key ) {
            return;
        }
        $settings = json_decode( (string) $meta_value, true );
        if ( is_array( $settings ) && self::form_days( $settings ) > 0 ) {
            self::schedule();
        }
    }

    public static function schedule_if_needed() {
        if ( self::site_days() > 0 ) {
            self::schedule();
        }
    }

    public static function sync_schedule() {
        if ( self::policies() ) {
            self::schedule();
        } else {
            self::unschedule();
        }
    }

    private static function schedule() {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook( self::HOOK );
    }

    // form ID => days to keep, for forms whose entries are deleted after some days.
    private static function policies() {
        $site_days = self::site_days();
        $policies = array();
        foreach ( SurveyJS_Forms::all( true ) as $form ) {
            $days = self::form_days( SurveyJS_Form_Settings::get( $form ), $site_days );
            if ( $days > 0 ) {
                $policies[ (int) $form->id ] = $days;
            }
        }
        return $policies;
    }

    public static function run() {
        global $wpdb;
        $policies = self::policies();
        if ( ! $policies ) {
            self::unschedule();
            return;
        }
        $batches = 0;
        foreach ( $policies as $form_id => $days ) {
            $cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
            do {
                if ( $batches >= self::MAX_BATCHES ) {
                    wp_schedule_single_event( time() + self::RETRY_DELAY, self::HOOK );
                    return;
                }
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, form_id, json FROM %i WHERE form_id = %d AND created_at < %s ORDER BY id LIMIT %d', SurveyJS_Entries::table(), $form_id, $cutoff, SurveyJS_Entries::BATCH ) );
                SurveyJS_Entries::delete_rows( $rows );
                ++$batches;
            } while ( count( $rows ) === SurveyJS_Entries::BATCH );
        }
    }
}
