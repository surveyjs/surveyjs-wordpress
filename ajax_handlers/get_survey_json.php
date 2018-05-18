<?php

include_once("ajax_handler.php");

class WP_GetSurveyJson extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_GetSurveyJson");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $surveyId = $_POST['Id'];
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
            $json =  $wpdb->get_row($query)->json;

            wp_send_json( array('json' => $json) );
        }
    }
}

?>