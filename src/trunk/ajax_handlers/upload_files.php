<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class SurveyJS_UploadFiles extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_UploadFiles", false);  
    }

    
    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-upload-files', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( empty( $_FILES ) ) {
            wp_send_json_error( array( 'message' => 'No files uploaded' ), 400 );
        }

        $result = array();
        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) ) {
            wp_send_json_error( array( 'error' => $upload_dir['error'] ), 500 );
        }

        $surveyjs_dir = trailingslashit( $upload_dir['basedir'] ) . 'surveyjs';
        if ( ! wp_mkdir_p( $surveyjs_dir ) ) {
            wp_send_json_error( array( 'error' => 'Cannot create upload directory' ), 500 );
        }

        add_filter( 'upload_dir', array( $this, 'set_upload_subdir' ) );

        foreach ( $_FILES as $value ) {
            if ( ! is_array( $value ) || empty( $value['name'] ) ) {
                remove_filter( 'upload_dir', array( $this, 'set_upload_subdir' ) );
                wp_send_json_error( array( 'error' => 'Invalid file payload' ), 400 );
            }

            $uploadedfile = $value;
            $originalname = (string) $uploadedfile['name'];
            $uploadedfile['name'] = sanitize_file_name( (string) $uploadedfile['name'] );

            $upload_overrides = array( 'test_form' => false );
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

            if ( ! $movefile || isset( $movefile['error'] ) ) {
                remove_filter( 'upload_dir', array( $this, 'set_upload_subdir' ) );
                wp_send_json_error( array( 'error' => isset( $movefile['error'] ) ? $movefile['error'] : 'Upload failed' ), 400 );
            }

            $result[ $originalname ] = $movefile['url'];
        }

        remove_filter( 'upload_dir', array( $this, 'set_upload_subdir' ) );

        wp_send_json( $result );
    }

    public function set_upload_subdir( $dirs ) {
        $dirs['subdir'] = '/surveyjs';
        $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
        $dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];

        return $dirs;
    }
}

?>