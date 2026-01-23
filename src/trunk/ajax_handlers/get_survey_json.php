<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_GetSurveyJson extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_GetSurveyJson", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!check_ajax_referer( 'surveyjs-get-survey-json' )) exit;
            $surveyId = intval(sanitize_key($_POST['Id']));
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $query = $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE id=%d", $surveyId);
            $row = $wpdb->get_row($query);
            if (!$row) {
                wp_send_json_error(array('message' => 'Survey not found'));
                return;
            }
            $json = isset($row->json) ? $row->json : null;
            $theme = isset($row->theme) ? $row->theme : null;

            wp_send_json( array('json' => $json, 'theme' => $theme) );
        }
    }
}

?>