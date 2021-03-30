<?php

include_once("ajax_handler.php");

class SurveyJS_DeleteResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteResult", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_results';

            $wpdb->delete( 
                $table_name, 
                array( 
                 'id' => intval(sanitize_key($_POST['ResultId']))
                ) 
            );
        }
    }
}

?>