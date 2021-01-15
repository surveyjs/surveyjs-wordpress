<?php

include_once('ajax_handler.php');

class SurveyJS_CloneSurvey extends SurveyJS_AJAX_Handler {
    function __construct() {
        parent::__construct('SurveyJS_CloneSurvey', false);  
    }
        
    function callback() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $surveyId = sanitize_key($_POST['SurveyParentId']);
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
            $json =  $wpdb->get_row($query)->json;
            $name =  $wpdb->get_row($query)->name;

            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => sanitize_text_field('Copy of ' . $name),
                 'json' => $json
                ) 
            );

            wp_send_json(array('Id' => $wpdb->insert_id));
        }
    }
}
?>