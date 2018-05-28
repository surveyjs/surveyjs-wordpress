<?php

include_once("ajax_handler.php");

class WP_RenameSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_RenameSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = sanitize_key($_POST['Id']);
            $name = sanitize_text_field($_POST['Name']);
            if(!!$name) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'sjs_my_surveys';

                $result = $wpdb->update( 
                    $table_name, 
                    array( 
                        'name' => $name
                    ),
                    array( 
                        'id' => intval($id)
                    )
                );
                wp_send_json( array('IsSuccess' => $result, 'name' => $name, 'id' => intval($id)) );
            }
        }
    }
}

?>