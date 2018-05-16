<?php

include_once("ajax_handler.php");

class WP_AddSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_AddSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'my_surveys';

            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => $_POST['Name']
                ) 
            );

            wp_send_json( array('Id' => $wpdb->insert_id) );
        }
    }
}

?>