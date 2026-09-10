<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_DeleteFile extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_DeleteFile", false);
    }

    function callback() {
        if($_SERVER['REQUEST_METHOD'] !== 'GET') return;

        if(!check_ajax_referer( 'surveyjs-delete-file' )) exit;

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json( array( 'error' => 'forbidden' ), 403 );
        }

        $name = isset( $_GET["name"] ) ? esc_url_raw( wp_unslash( $_GET["name"] ) ) : '';
        if ( '' === $name ) {
            wp_send_json( array( 'error' => 'bad_request' ), 400 );
        }

        $file = $this->resolve_uploaded_file( $name );
        if ( null === $file ) {
            wp_send_json( array( 'error' => 'not_found' ), 404 );
        }

        wp_delete_file( $file );
        wp_send_json( array( 'result' => 'success' ) );
    }

    private function resolve_uploaded_file( $url ) {
        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) ) return null;

        $baseurl = $this->strip_scheme( trailingslashit( $upload_dir['baseurl'] ) );
        $target  = $this->strip_scheme( $url );

        if ( strpos( $target, $baseurl ) !== 0 ) return null;

        $relative = ltrim( substr( $target, strlen( $baseurl ) ), '/' );
        if ( '' === $relative || strpos( $relative, '..' ) !== false ) return null;

        $real_base = realpath( $upload_dir['basedir'] );
        $real_file = realpath( wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . $relative ) );
        if ( false === $real_base || false === $real_file ) return null;

        $real_base = trailingslashit( wp_normalize_path( $real_base ) );
        $real_file = wp_normalize_path( $real_file );

        if ( strpos( $real_file, $real_base ) !== 0 ) return null;
        if ( ! is_file( $real_file ) ) return null;

        return $real_file;
    }

    private function strip_scheme( $url ) {
        return preg_replace( '#^(?:https?:)?//#i', '', $url );
    }
}

?>
