<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-save-result', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }

        $SurveyId = absint( wp_unslash( $_POST['SurveyId'] ?? 0 ) );
        if ( $SurveyId <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid survey ID' ), 400 );
        }

        $raw_json = wp_unslash( $_POST['Json'] ?? '' );
        $Json = $this->sanitize_result_json( $raw_json );
        if ( null === $Json ) {
            wp_send_json_error(
                array(
                    'message' => 'Invalid survey result payload',
                ),
                400
            );
        }

        $TableName = 'sjs_results';

        if ( function_exists( 'surveyjs_save_result' ) ) {
            do_action( 'wp_surveyjs_save_result', $SurveyId, $Json, $TableName );
        } else {
            global $wpdb;
            $table_name = $wpdb->prefix . $TableName;

            $wpdb->insert(
                $table_name,
                array(
                    'surveyId' => $SurveyId,
                    'json' => $Json,
                ),
                array( '%d', '%s' )
            );
        }

        wp_send_json( array( 'IsSuccess' => true ) );
    }

    private function sanitize_result_json( $raw_json ) {
        if ( ! is_string( $raw_json ) || '' === trim( $raw_json ) ) {
            return null;
        }

        $decoded = json_decode( $raw_json, true );
        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return null;
        }

        $sanitized = $this->sanitize_json_value( $decoded );
        return wp_json_encode( $sanitized );
    }

    private function sanitize_json_value( $value ) {
        if ( is_array( $value ) ) {
            $sanitized = array();
            foreach ( $value as $key => $item ) {
                $sanitized[ $key ] = $this->sanitize_json_value( $item );
            }
            return $sanitized;
        }

        if ( is_string( $value ) ) {
            return sanitize_text_field( wp_kses( $value, array() ) );
        }

        return $value;
    }
}

/**
 * Custom Save Survey Result function
 * file functions.php
 */
/*function surveyjs_save_result($SurveyId=null, $Json=null, $TableName=null) {
	do stuff
}
add_action( 'wp_surveyjs_save_result', 'surveyjs_save_result', 10, 3 );*/

?>