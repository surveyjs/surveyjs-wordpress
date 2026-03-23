<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_DeleteSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteSurvey", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'delete-survey-ajax-referer', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid survey ID' ), 400 );
        }

        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $id,
            ),
            array( '%d' )
        );

        wp_send_json( array( 'IsSuccess' => false !== $result, 'id' => $id ) );
    }
}

?>