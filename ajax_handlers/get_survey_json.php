<?php

include_once("ajax_handler.php");

class SurveyJS_GetSurveyJson extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_GetSurveyJson", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $surveyId = sanitize_key($_POST['Id']);
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
            $json =  $wpdb->get_row($query)->json;

            wp_send_json( array('json' => $json) );
        }
    }
}

?>