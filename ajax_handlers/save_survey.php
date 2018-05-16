<?php

include_once("ajax_handler.php");

class WP_SaveSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_SaveSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!!$_POST['Json']) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'my_surveys';

                $result = $wpdb->update( 
                    $table_name, 
                    array( 
                        'json' => $_POST['Json']
                    ),
                    array( 
                        'id' => intval($_POST['Id'])
                    )
                );
                wp_send_json( array('IsSuccess' => $result, 'json' => $_POST['Json'], 'id' => intval($_POST['Id'])) );
            }
        }
    }
}

?>