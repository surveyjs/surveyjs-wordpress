<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_GetSurveyJson extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_GetSurveyJson", true);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-get-survey-json', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }

        $surveyId = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
        if ( $surveyId <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid survey ID' ), 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id=%d", $surveyId );
        $row = $wpdb->get_row( $query );
        if ( ! $row ) {
            wp_send_json_error( array( 'message' => 'Survey not found' ), 404 );
        }

        $json = isset( $row->json ) ? $row->json : null;
        $theme = isset( $row->theme ) ? $row->theme : null;

        wp_send_json( array( 'json' => $json, 'theme' => $theme ) );
    }
}

?>