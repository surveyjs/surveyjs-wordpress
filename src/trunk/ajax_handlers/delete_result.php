<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_DeleteResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteResult", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-delete-result', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        $result_id = absint( wp_unslash( $_POST['ResultId'] ?? 0 ) );
        if ( $result_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid result ID' ), 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_results';

        $deleted = $wpdb->delete(
            $table_name,
            array(
                'id' => $result_id,
            ),
            array( '%d' )
        );

        wp_send_json( array( 'IsSuccess' => false !== $deleted ) );
    }
}

?>