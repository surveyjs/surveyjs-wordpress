<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_CloneSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_CloneSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'administrator' )) {
            if(!check_ajax_referer( 'surveyjs-clone-survey' )) exit;
            global $wpdb;
            $surveyId = intval(sanitize_key($_POST['SurveyParentId']));
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $query = $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE id=%d", $surveyId);
            $row = $wpdb->get_row($query);
            if (!$row) {
                wp_send_json_error(array('message' => 'Survey not found'));
                return;
            }
            $json = $row->json;
            $name = $row->name;

            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => sanitize_text_field('Copy of ' . $name),
                 'json' => $json
                ) 
            );

            wp_send_json( array('Id' => $wpdb->insert_id) );
        }
    }
}

?>