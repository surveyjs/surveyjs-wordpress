<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_AddSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_AddSurvey", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-add-survey', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $name = sanitize_text_field( wp_unslash( $_POST['Name'] ?? 'New Survey' ) );

        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
            ),
            array( '%s' )
        );

        wp_send_json( array( 'Id' => (int) $wpdb->insert_id ) );
    }
}

?>