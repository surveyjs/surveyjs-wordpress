<?php

include_once("ajax_handler.php");

class WP_RenameSurvey extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_RenameSurvey");  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!!$_POST['Name']) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'sjs_my_surveys';

                $result = $wpdb->update( 
                    $table_name, 
                    array( 
                        'name' => $_POST['Name']
                    ),
                    array( 
                        'id' => intval($_POST['Id'])
                    )
                );
                wp_send_json( array('IsSuccess' => $result, 'name' => $_POST['Name'], 'id' => intval($_POST['Id'])) );
            }
        }
    }
}

?>