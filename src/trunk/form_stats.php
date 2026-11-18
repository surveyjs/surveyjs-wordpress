<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Form_Stats {

    const VIEW_ACTION = 'surveyjs_form_view';
    const SETTING = 'count_views';
    const EXCLUDED_CAPABILITY = 'edit_posts';
    const BOT_PATTERN = '/bot\b|crawl|spider|slurp|archiver|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|preview|facebookexternalhit|embedly|whatsapp|telegram|curl|wget|python|httpclient|okhttp|axios|node-fetch|go-http|java\/|phantomjs|selenium|puppeteer|playwright/i';

    public static function init() {
        add_action( 'wp_ajax_' . self::VIEW_ACTION, array( __CLASS__, 'handle_view' ) );
        add_action( 'wp_ajax_nopriv_' . self::VIEW_ACTION, array( __CLASS__, 'handle_view' ) );
        add_action( 'wp_surveyjs_save_result', array( __CLASS__, 'record_submission' ) );
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'sjs_form_stats';
    }

    public static function schema() {
        global $wpdb;
        $table = self::table();
        return "CREATE TABLE $table (
            form_id bigint(20) unsigned NOT NULL,
            stat_date date NOT NULL,
            views int(10) unsigned NOT NULL DEFAULT 0,
            submissions int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (form_id,stat_date),
            KEY stat_date (stat_date)
        ) {$wpdb->get_charset_collate()};";
    }

    public static function enabled() {
        $settings = (array) get_option( 'surveyjs-settings' );
        return ! array_key_exists( self::SETTING, $settings ) || ! empty( $settings[ self::SETTING ] );
    }

    public static function counts_form( $form_id ) {
        return (bool) apply_filters( 'surveyjs_count_form_views', self::enabled(), (int) $form_id );
    }

    private static function counts_visitor() {
        return ! current_user_can( self::EXCLUDED_CAPABILITY );
    }

    public static function view_url( $form_id ) {
        if ( ! self::counts_form( $form_id ) || ! self::counts_visitor() ) {
            return '';
        }
        return add_query_arg( array( 'action' => self::VIEW_ACTION ), admin_url( 'admin-ajax.php' ) );
    }

    // No nonce: the request comes from pages that page caches and CDNs serve for days, where a nonce would be stale.
    public static function handle_view() {
        $method = sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
        $form_id = absint( wp_unslash( $_POST['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- no nonce on purpose, see above.
        if ( 'post' === $method && $form_id && ! self::is_bot() && self::counts_visitor() && self::counts_form( $form_id ) && self::is_published_form( $form_id ) ) {
            self::increment( $form_id, 'views' );
        }
        wp_die( '', '', array( 'response' => 204 ) );
    }

    public static function record_submission( $form_id ) {
        $form_id = (int) $form_id;
        if ( $form_id && self::counts_visitor() && self::counts_form( $form_id ) ) {
            self::increment( $form_id, 'submissions' );
        }
    }

    private static function is_bot() {
        // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__ -- read in an uncached admin-ajax POST only, never on a cached page.
        $agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
        return '' === $agent || (bool) preg_match( self::BOT_PATTERN, $agent );
    }

    private static function is_published_form( $form_id ) {
        $post = get_post( $form_id );
        return $post && SurveyJS_Forms::POST_TYPE === $post->post_type && 'publish' === $post->post_status;
    }

    private static function increment( $form_id, $column ) {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            'INSERT INTO %i ( form_id, stat_date, %i ) VALUES ( %d, %s, 1 ) ON DUPLICATE KEY UPDATE %i = %i + 1',
            self::table(),
            $column,
            $form_id,
            wp_date( 'Y-m-d' ),
            $column,
            $column
        ) );
    }

    public static function delete_for_form( $form_id ) {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE form_id = %d', self::table(), (int) $form_id ) );
    }

    // Dates are site-local Y-m-d strings; the start date is included, the end date is not.
    public static function totals( $form_ids, $since = '', $until = '' ) {
        global $wpdb;
        $form_ids = array_values( array_filter( array_map( 'absint', (array) $form_ids ) ) );
        $totals = array();
        if ( ! $form_ids ) {
            return $totals;
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT form_id, SUM( views ) AS views, SUM( submissions ) AS submissions FROM %i
            WHERE form_id IN ( ' . implode( ',', array_fill( 0, count( $form_ids ), '%d' ) ) . ' ) AND ( %s = \'\' OR stat_date >= %s ) AND ( %s = \'\' OR stat_date < %s )
            GROUP BY form_id',
            array_merge( array( self::table() ), $form_ids, array( $since, $since, $until, $until ) )
        ) );
        foreach ( $rows as $row ) {
            $totals[ (int) $row->form_id ] = (object) array(
                'views'       => (int) $row->views,
                'submissions' => (int) $row->submissions,
            );
        }
        return $totals;
    }

    // Buckets are 'Y-m-d' for $unit 'day' and 'Y-m' for 'month'.
    public static function timeline( $form_ids, $since, $unit = 'day' ) {
        global $wpdb;
        $form_ids = array_values( array_filter( array_map( 'absint', (array) $form_ids ) ) );
        $timeline = array();
        if ( ! $form_ids ) {
            return $timeline;
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT DATE_FORMAT( stat_date, %s ) AS bucket, SUM( views ) AS views, SUM( submissions ) AS submissions FROM %i
            WHERE form_id IN ( ' . implode( ',', array_fill( 0, count( $form_ids ), '%d' ) ) . ' ) AND stat_date >= %s
            GROUP BY bucket',
            array_merge( array( 'month' === $unit ? '%Y-%m' : '%Y-%m-%d', self::table() ), $form_ids, array( $since ) )
        ) );
        foreach ( $rows as $row ) {
            $timeline[ $row->bucket ] = (object) array(
                'views'       => (int) $row->views,
                'submissions' => (int) $row->submissions,
            );
        }
        return $timeline;
    }

    public static function conversion( $views, $submissions ) {
        return $views > 0 ? $submissions / $views * 100 : null;
    }

    public static function format_conversion( $views, $submissions ) {
        $rate = self::conversion( $views, $submissions );
        if ( null === $rate ) {
            return "\u{2014}";
        }
        // U+2066/U+2069 keep "12.5%" left-to-right in RTL languages.
        return "\u{2066}" . number_format_i18n( $rate, $rate > 0 && $rate < 10 ? 1 : 0 ) . "%\u{2069}";
    }
}
