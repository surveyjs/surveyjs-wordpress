<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_CloneSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_CloneSurvey", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-clone-survey', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        global $wpdb;
        $surveyId = absint( wp_unslash( $_POST['SurveyParentId'] ?? 0 ) );
        if ( $surveyId <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid survey ID' ), 400 );
        }
        $table_name = $wpdb->prefix . 'sjs_my_surveys';

        $query = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id=%d", $surveyId );
        $row = $wpdb->get_row( $query );
        if ( ! $row ) {
            wp_send_json_error( array( 'message' => 'Survey not found' ), 404 );
        }

        $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field( 'Copy of ' . (string) $row->name ),
                'json' => (string) $row->json,
                'theme' => isset( $row->theme ) ? (string) $row->theme : null,
            ),
            array( '%s', '%s', '%s' )
        );

        wp_send_json( array( 'Id' => (int) $wpdb->insert_id ) );
    }
}

?>