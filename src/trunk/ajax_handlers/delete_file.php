<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_DeleteFile extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct( 'SurveyJS_DeleteFile', true );
    }

    function callback() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;

        if ( ! check_ajax_referer( 'surveyjs-delete-file' ) ) exit;

        $key = SurveyJS_Uploaded_Files::key( sanitize_text_field( wp_unslash( $_POST['file'] ?? '' ) ) );
        if ( null === $key ) {
            wp_send_json( array( 'error' => 'bad_request' ), 400 );
        }

        $expires = absint( wp_unslash( $_POST['expires'] ?? 0 ) );
        $token = sanitize_key( wp_unslash( $_POST['token'] ?? '' ) );
        if ( ! SurveyJS_Uploaded_Files::has_valid_delete_token( $key, $expires, $token ) ) {
            wp_send_json( array( 'error' => 'forbidden' ), 403 );
        }

        $result = SurveyJS_Uploaded_Files::delete_unattached( $key );
        if ( 'not_found' === $result ) {
            wp_send_json( array( 'error' => 'not_found' ), 404 );
        }
        if ( 'attached' === $result ) {
            wp_send_json( array( 'error' => 'in_use' ), 409 );
        }
        wp_send_json( array( 'result' => 'success' ) );
    }
}
