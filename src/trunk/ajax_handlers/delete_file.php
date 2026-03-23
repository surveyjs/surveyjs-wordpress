<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class SurveyJS_DeleteFile extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteFile", false);  
    }
        
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-delete-file', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        $file_url = esc_url_raw( wp_unslash( $_POST['name'] ?? '' ) );
        if ( '' === $file_url ) {
            wp_send_json_error( array( 'message' => 'File URL is required' ), 400 );
        }

        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) ) {
            wp_send_json_error( array( 'message' => $upload_dir['error'] ), 500 );
        }

        $base_url_path = wp_normalize_path( (string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_PATH ) );
        $file_url_path = wp_normalize_path( (string) wp_parse_url( $file_url, PHP_URL_PATH ) );

        if ( '' === $base_url_path || 0 !== strpos( $file_url_path, $base_url_path . '/surveyjs/' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid file location' ), 400 );
        }

        $relative_path = ltrim( substr( $file_url_path, strlen( $base_url_path ) ), '/' );
        $full_path = wp_normalize_path( path_join( $upload_dir['basedir'], $relative_path ) );
        $surveyjs_base = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'surveyjs' ) . '/';

        if ( 0 !== strpos( $full_path, $surveyjs_base ) ) {
            wp_send_json_error( array( 'message' => 'Invalid file path' ), 400 );
        }

        if ( ! file_exists( $full_path ) ) {
            wp_send_json_error( array( 'message' => 'File not found' ), 404 );
        }

        wp_delete_file( $full_path );

        if ( file_exists( $full_path ) ) {
            wp_send_json_error( array( 'message' => 'Failed to delete file' ), 500 );
        }

        wp_send_json( array( 'IsSuccess' => true ) );
    }
}

?>