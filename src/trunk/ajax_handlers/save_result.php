<?php

include_once("ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_ajax_referer( 'surveyjs-save-result' );
            $SurveyId = intval(sanitize_key($_POST['SurveyId']));
            $Json =  sanitize_text_field($_POST['Json']);
            $TableName = 'sjs_results';
            
            if (function_exists('surveyjs_save_result'))
            {
                do_action('wp_surveyjs_save_result', $SurveyId, $Json, $TableName);
            } else {
                global $wpdb;
                $table_name = $wpdb->prefix . $TableName;
    
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

/**
 * Custom Save Survey Result function
 * file functions.php
 */
/*function surveyjs_save_result($SurveyId=null, $Json=null, $TableName=null) {
	do stuff
}
add_action( 'wp_surveyjs_save_result', 'surveyjs_save_result', 10, 3 );*/

?>