<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_RenameSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_RenameSurvey", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-rename-survey', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
        $name = sanitize_text_field( wp_unslash( $_POST['Name'] ?? '' ) );
        if ( $id <= 0 || '' === $name ) {
            wp_send_json_error( array( 'message' => 'Invalid data provided' ), 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';

        $result = $wpdb->update(
            $table_name,
            array(
                'name' => $name,
            ),
            array(
                'id' => $id,
            ),
            array( '%s' ),
            array( '%d' )
        );
        wp_send_json( array( 'IsSuccess' => false !== $result, 'name' => $name, 'id' => $id ) );
    }
}

?>