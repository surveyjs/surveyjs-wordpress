<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class SurveyJS_UploadFile extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_UploadFile", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-upload-file', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }
        if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
            wp_send_json_error( array( 'message' => 'No file uploaded' ), 400 );
        }

        $uploadedfile = $_FILES['file'];

        $upload_overrides = array( 'test_form' => false );

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            wp_send_json( array( 'url' => $movefile['url'] ) );
        }

        wp_send_json_error( array( 'error' => isset( $movefile['error'] ) ? $movefile['error'] : 'Upload failed' ), 400 );
    }
}

?>