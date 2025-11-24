<?php

include_once("ajax_handler.php");

class SurveyJS_DeleteSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'administrator' )) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $id = sanitize_key($_POST['Id']);
            check_ajax_referer( 'delete-survey-ajax-referer');

            $result = $wpdb->delete( 
                $table_name, 
                array( 
                 'id' => $id
                ) 
            );

            wp_send_json( array('IsSuccess' => $result, 'id' => intval($id)) );
        }
    }
}

?>