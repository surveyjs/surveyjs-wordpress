<?php

include_once("ajax_handler.php");

class WP_SaveSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_SaveSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!!$_POST['Json']) {
                $table_name = $wpdb->prefix . 'my_surveys';

                $wpdb->insert( 
                    $table_name, 
                    array( 
                        'time' => current_time( 'mysql' ), 
                        'name' => $welcome_name, 
                        'text' => $welcome_text, 
                    ) 
                );
                echo "save survey: " . $_POST['Json']; 
            }
        }
    }
}

?>