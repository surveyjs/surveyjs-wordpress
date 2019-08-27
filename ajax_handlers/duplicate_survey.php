<?php

include_once("ajax_handler.php");

class SurveyJS_DuplicateSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DuplicateSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $id = sanitize_key($_POST['Id']);


            $query = "SELECT * FROM " . $table_name . " WHERE id=" . $id;
            $json =  $wpdb->get_row($query)->json;
            $name =  $wpdb->get_row($query)->name;

            $result = $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => $name . " COPY",
                 'json' => $json
                ) 
            );

            wp_send_json( array('IsSuccess' => $result, 'id' => intval($id)) );
        }
    }
}

?>