<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'manage_options' )) {
            if(!check_ajax_referer( 'surveyjs-save-survey' )) exit;

            $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
            $survey = SurveyJS_Forms::find( $id );
            if ( ! $survey ) {
                wp_send_json( array('IsSuccess' => 0) );
            }
            $raw_json = wp_unslash( $_POST['Json'] ?? '' );
            $raw_theme = wp_unslash( $_POST['Theme'] ?? '' );
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
                $data['settings'] = wp_json_encode( SurveyJS_Form_Settings::sanitize( json_decode( wp_unslash( $_POST['Settings'] ), true ), $survey ) );
            }

            if ( ! empty( $data ) ) {
                wp_send_json( array('IsSuccess' => SurveyJS_Forms::update( $id, $data ) ? 1 : 0) );
            }
        }
    }
}

?>
