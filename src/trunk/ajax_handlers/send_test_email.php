<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_SendTestEmail extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SendTestEmail", false);
    }

    function callback() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Not allowed.', 'surveyjs' ) ), 403 );
        }
        if ( ! check_ajax_referer( 'surveyjs-save-survey' ) ) exit;

        $client = new SurveyJS_Client();
        $survey = $client->getSurvey( absint( wp_unslash( $_POST['Id'] ?? 0 ) ) );
        if ( ! $survey ) {
            wp_send_json_error( array( 'message' => __( 'Form not found.', 'surveyjs' ) ), 404 );
        }

        $settings = SurveyJS_Form_Settings::sanitize( json_decode( wp_unslash( $_POST['Settings'] ?? '' ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- SurveyJS_Form_Settings::sanitize() keeps only known keys and sanitizes each value.
        $result = SurveyJS_Notification::send_test( $survey, $settings );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        /* translators: %s: comma-separated list of recipient email addresses */
        wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent to %s. If it does not arrive, check the spam folder or set up an SMTP plugin.', 'surveyjs' ), implode( ', ', $result ) ) ) );
    }
}
