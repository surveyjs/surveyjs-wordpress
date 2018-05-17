<?php

include_once("ajax_handler.php");

class WP_DeleteSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_DeleteSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'my_surveys';

            $result = $wpdb->delete( 
                $table_name, 
                array( 
                 'id' => $_POST['Id']
                ) 
            );

            wp_send_json( array('IsSuccess' => $result, 'id' => intval($_POST['Id'])) );
        }
    }
}

?>