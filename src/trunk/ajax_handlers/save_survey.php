<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && current_user_can( 'manage_options' ) ) {
            if(!check_ajax_referer( 'surveyjs-save-survey' )) exit;

            $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
            $survey = SurveyJS_Forms::find( $id );
            if ( ! $survey ) {
                wp_send_json( array('IsSuccess' => 0) );
            }
            $raw_json = wp_unslash( $_POST['Json'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- kept raw only for users with unfiltered_html, as core does for post content; everyone else gets wp_kses_post() below.
            $raw_theme = wp_unslash( $_POST['Theme'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- kept raw only for users with unfiltered_html, as core does for post content; everyone else gets wp_kses_post() below.
            $json = current_user_can( 'unfiltered_html' ) ? $raw_json : wp_kses_post( $raw_json );
            $theme = current_user_can( 'unfiltered_html' ) ? $raw_theme : wp_kses_post( $raw_theme );

            $data = array();
            if(!!$json) {
                $data['json'] = $json;
                $data['theme'] = $theme;
            }
            if ( isset( $_POST['Name'] ) && '' !== trim( sanitize_text_field( wp_unslash( $_POST['Name'] ) ) ) ) {
                $data['name'] = sanitize_text_field( wp_unslash( $_POST['Name'] ) );
            }
            if ( isset( $_POST['Settings'] ) ) {
                $data['settings'] = wp_json_encode( SurveyJS_Form_Settings::sanitize( json_decode( wp_unslash( $_POST['Settings'] ), true ), $survey ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- SurveyJS_Form_Settings::sanitize() keeps only known keys and sanitizes each value.
            }

            if ( ! empty( $data ) ) {
                wp_send_json( array('IsSuccess' => SurveyJS_Forms::update( $id, $data ) ? 1 : 0) );
            }
        }
    }
}
