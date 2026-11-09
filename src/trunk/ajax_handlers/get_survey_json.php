<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_GetSurveyJson extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_GetSurveyJson", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!check_ajax_referer( 'surveyjs-get-survey-json' )) exit;
            $surveyId = intval(sanitize_key($_POST['Id']));
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sjs_my_surveys WHERE id = %d", $surveyId ) );
            if (!$row) {
                wp_send_json_error(array('message' => 'Survey not found'));
                return;
            }
            $decodedJson = SurveyJS_Client::decode_survey_json( isset($row->json) ? $row->json : null );
            $decodedTheme = SurveyJS_Client::decode_survey_json( isset($row->theme) ? $row->theme : null );
            $json = empty( $decodedJson ) ? null : wp_json_encode( $decodedJson );
            $theme = empty( $decodedTheme ) ? null : wp_json_encode( $decodedTheme );

            wp_send_json( array('json' => $json, 'theme' => $theme) );
        }
    }
}

?>