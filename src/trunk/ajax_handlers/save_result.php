<?php

include_once("ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            do_action('surveyjs_save_result');

            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_results';

            $wpdb->insert( 
                $table_name, 
                array( 
                 'surveyId' => intval(sanitize_key($_POST['SurveyId'])),
                 'json' => sanitize_text_field($_POST['Json'])
                ) 
            );
        }
    }
}

?>