<?php

include_once("ajax_handler.php");

class WP_SaveSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_SaveSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = sanitize_key($_POST['Id']);
            $json = sanitize_text_field($_POST['Json']);

            if(!!$json) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'sjs_my_surveys';

                $result = $wpdb->update( 
                    $table_name, 
                    array( 
                        'json' => $json
                    ),
                    array( 
                        'id' => intval($id)
                    )
                );
                wp_send_json( array('IsSuccess' => $result) );
            }
        }
    }
}

?>