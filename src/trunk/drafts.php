<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Drafts {

    const META_PREFIX  = 'surveyjs_draft_';
    const CLEANUP_HOOK = 'surveyjs_cleanup_drafts';
    const NONCE        = 'surveyjs-draft';
    const MAX_AGE      = 30 * DAY_IN_SECONDS;
    const MAX_BYTES    = MB_IN_BYTES;

    function __construct() {
        add_action( 'init', array( $this, 'load_before_survey_script' ), 20 );
        add_action( 'wp_ajax_SurveyJS_SaveDraft', array( $this, 'handle_save' ) );
        add_action( 'wp_ajax_SurveyJS_DeleteDraft', array( $this, 'handle_delete' ) );
        add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup' ) );
        add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
    }

    public function load_before_survey_script() {
        wp_register_script( 'wps-front--drafts.js', plugins_url( 'drafts.js', __FILE__ ), array(), (string) filemtime( plugin_dir_path( __FILE__ ) . 'drafts.js' ) );
        $survey_script = wp_scripts()->query( 'wps-front--survey.js' );
        if ( $survey_script && ! in_array( 'wps-front--drafts.js', $survey_script->deps, true ) ) {
            $survey_script->deps[] = 'wps-front--drafts.js';
        }
    }

    public static function client_config( $survey ) {
        if ( ! SurveyJS_Form_Settings::get( $survey )['saveResume'] ) {
            return null;
        }
        $config = array(
            'maxAge'        => self::MAX_AGE,
            'restoredText'  => __( 'We restored your previous answers.', 'surveyjs' ),
            'startOverText' => __( 'Start over', 'surveyjs' ),
        );
        if ( ! is_user_logged_in() ) {
            return $config + array( 'storageKey' => 'surveyjs-draft:' . home_url() . ':' . absint( $survey->id ) );
        }
        return $config + array(
            'saved' => self::get( get_current_user_id(), $survey->id ),
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( self::NONCE ),
        );
    }

    public static function get( $user_id, $survey_id ) {
        $draft = self::decode( get_user_option( self::META_PREFIX . absint( $survey_id ), $user_id ) );
        return null === $draft || self::is_expired( $draft ) ? null : $draft;
    }

    public static function delete( $user_id, $survey_id ) {
        if ( $user_id ) {
            delete_user_option( $user_id, self::META_PREFIX . absint( $survey_id ) );
        }
    }

    public function handle_save() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
        check_ajax_referer( self::NONCE );

        $survey = ( new SurveyJS_Client() )->getSurvey( absint( wp_unslash( $_POST['SurveyId'] ?? 0 ) ) );
        if ( ! $survey || ! SurveyJS_Form_Settings::get( $survey )['saveResume'] ) {
            wp_send_json_error( array( 'message' => 'Form not found' ), 404 );
        }

        $raw = (string) wp_unslash( $_POST['Data'] ?? '' );
        if ( strlen( $raw ) > self::MAX_BYTES ) {
            wp_send_json_error( array( 'message' => 'Draft is too large' ), 413 );
        }
        $data = json_decode( $raw );
        if ( ! $data instanceof stdClass ) {
            wp_send_json_error( array( 'message' => 'Invalid draft payload' ), 400 );
        }

        $draft = array(
            'data'          => map_deep( $data, array( __CLASS__, 'sanitize_value' ) ),
            'currentPageNo' => absint( wp_unslash( $_POST['CurrentPageNo'] ?? 0 ) ),
            'savedAt'       => time(),
        );
        $partial = json_decode( (string) wp_unslash( $_POST['Partial'] ?? '' ) );
        if ( $partial instanceof stdClass && isset( $partial->id, $partial->token ) && is_string( $partial->token ) ) {
            $draft['partial'] = array(
                'id'    => absint( $partial->id ),
                'token' => preg_replace( '/[^a-f0-9]/', '', $partial->token ),
            );
        }
        update_user_option( get_current_user_id(), self::META_PREFIX . absint( $survey->id ), wp_slash( wp_json_encode( $draft ) ) );

        if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
            wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
        }
        wp_send_json_success();
    }

    public function handle_delete() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
        check_ajax_referer( self::NONCE );
        self::delete( get_current_user_id(), absint( wp_unslash( $_POST['SurveyId'] ?? 0 ) ) );
        wp_send_json_success();
    }

    public static function sanitize_value( $value ) {
        return is_string( $value ) ? sanitize_textarea_field( $value ) : $value;
    }

    public static function cleanup() {
        $drafts = self::stored_drafts();
        $expired = array_filter( $drafts, function ( $row ) {
            $draft = self::decode( $row->meta_value );
            return null === $draft || self::is_expired( $draft );
        } );
        foreach ( $expired as $row ) {
            delete_user_meta( $row->user_id, $row->meta_key );
        }
        if ( count( $expired ) === count( $drafts ) ) {
            wp_clear_scheduled_hook( self::CLEANUP_HOOK );
        }
    }

    public static function unschedule_cleanup() {
        wp_clear_scheduled_hook( self::CLEANUP_HOOK );
    }

    public function register_exporter( $exporters ) {
        $exporters['surveyjs-drafts'] = array(
            'exporter_friendly_name' => __( 'SurveyJS Forms unfinished answers', 'surveyjs' ),
            'callback'               => array( __CLASS__, 'export' ),
        );
        return $exporters;
    }

    public function register_eraser( $erasers ) {
        $erasers['surveyjs-drafts'] = array(
            'eraser_friendly_name' => __( 'SurveyJS Forms unfinished answers', 'surveyjs' ),
            'callback'             => array( __CLASS__, 'erase' ),
        );
        return $erasers;
    }

    public static function export( $email ) {
        $user = get_user_by( 'email', $email );
        $items = array();
        if ( $user ) {
            $client = new SurveyJS_Client();
            foreach ( self::stored_drafts( $user->ID ) as $row ) {
                $draft = self::decode( $row->meta_value );
                if ( null === $draft ) {
                    continue;
                }
                $survey_id = absint( substr( $row->meta_key, strlen( self::meta_key_prefix() ) ) );
                $survey = $client->getSurvey( $survey_id );
                $items[] = array(
                    'group_id'    => 'surveyjs-drafts',
                    'group_label' => __( 'Unfinished form answers', 'surveyjs' ),
                    'item_id'     => 'surveyjs-draft-' . $survey_id,
                    'data'        => array_merge(
                        array(
                            array( 'name' => __( 'Form', 'surveyjs' ), 'value' => $survey ? $survey->name : '#' . $survey_id ),
                            array( 'name' => __( 'Saved', 'surveyjs' ), 'value' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $draft->savedAt ) ),
                        ),
                        self::export_answers( $survey, json_decode( wp_json_encode( $draft->data ), true ) )
                    ),
                );
            }
        }
        return array( 'data' => $items, 'done' => true );
    }

    public static function erase( $email ) {
        $user = get_user_by( 'email', $email );
        $drafts = $user ? self::stored_drafts( $user->ID ) : array();
        foreach ( $drafts as $row ) {
            delete_user_meta( $row->user_id, $row->meta_key );
        }
        return array(
            'items_removed'  => ! empty( $drafts ),
            'items_retained' => false,
            'messages'       => array(),
            'done'           => true,
        );
    }

    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        $content = '<p class="privacy-policy-tutorial">' . esc_html__( 'Forms with the Save & Resume setting turned on save unfinished answers so respondents can continue later.', 'surveyjs' ) . '</p>'
            . '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'surveyjs' ) . ' </strong>'
            . esc_html__( 'Some forms on this site save the answers you have entered but not yet submitted, so you can continue later. If you are not logged in, they are saved only in your browser. If you are logged in, they are saved on this site with your user account, so you can continue on another device. They are deleted when you submit the form or choose Start over. Answers saved on this site are also deleted after 30 days.', 'surveyjs' ) . '</p>';
        wp_add_privacy_policy_content( 'SurveyJS Forms', wp_kses_post( $content ) );
    }

    private static function export_answers( $survey, $data ) {
        if ( ! is_array( $data ) ) {
            return array();
        }
        $questions = $survey ? array_column( SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) ), null, 'name' ) : array();
        $answers = array();
        foreach ( $data as $name => $value ) {
            $question = $questions[ $name ] ?? array( 'name' => (string) $name );
            $answers[] = array(
                'name'  => SurveyJS_Survey_Values::question_title( $question ),
                'value' => SurveyJS_Survey_Values::display_value( $question, $value ),
            );
        }
        return $answers;
    }

    private static function stored_drafts( $user_id = 0 ) {
        global $wpdb;
        $where = $wpdb->prepare( 'meta_key LIKE %s', $wpdb->esc_like( self::meta_key_prefix() ) . '%' );
        if ( $user_id ) {
            $where .= $wpdb->prepare( ' AND user_id = %d', $user_id );
        }
        return $wpdb->get_results( "SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE $where" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    private static function meta_key_prefix() {
        global $wpdb;
        return $wpdb->get_blog_prefix() . self::META_PREFIX;
    }

    private static function decode( $meta_value ) {
        $draft = is_string( $meta_value ) ? json_decode( $meta_value ) : null;
        return $draft instanceof stdClass && isset( $draft->data, $draft->savedAt ) ? $draft : null;
    }

    private static function is_expired( $draft ) {
        return (int) $draft->savedAt < time() - self::MAX_AGE;
    }
}
