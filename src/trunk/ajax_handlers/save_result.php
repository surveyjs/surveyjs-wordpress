<?php

include_once("ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        $SurveyId = intval(sanitize_key($_POST['SurveyId']));
        $Json =  sanitize_text_field($_POST['Json']);

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (function_exists('surveyjs_save_result'))
            {
                do_action('surveyjs_save_result', $SurveyId, $Json);
            } else {
                global $wpdb;
                $table_name = $wpdb->prefix . 'sjs_results';
    
                $wpdb->insert( 
                    $table_name, 
                    array( 
                     'surveyId' => $SurveyId,
                     'json' => $Json
                    ) 
                );
            }
        }
    }
}

?>