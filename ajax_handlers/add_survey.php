<?php

include_once("ajax_handler.php");

class SurveyJS_AddSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_AddSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => sanitize_text_field($_POST['Name'])
                ) 
            );

            wp_send_json( array('Id' => $wpdb->insert_id) );
        }
    }
}

?>