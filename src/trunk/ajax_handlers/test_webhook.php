<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_TestWebhook extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct( 'SurveyJS_TestWebhook', false );
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
        $webhook = SurveyJS_Webhooks::find( $settings, sanitize_key( wp_unslash( $_POST['Webhook'] ?? '' ) ) );
        if ( ! $webhook || '' === $webhook['url'] ) {
            wp_send_json_error( array( 'message' => __( 'Enter the webhook URL first.', 'surveyjs' ) ) );
        }

        $result = SurveyJS_Webhooks::test( $survey, $settings, $webhook );
        $log = SurveyJS_Delivery_Queue::client_log( $survey->id );
        if ( ! $result['ok'] ) {
            /* translators: %s: error message or HTTP status, for example "404 Not Found" */
            wp_send_json_error( array( 'message' => sprintf( __( 'The test was not delivered: %s', 'surveyjs' ), $result['error'] ), 'log' => $log ) );
        }
        /* translators: %d: HTTP status code, for example 200 */
        wp_send_json_success( array( 'message' => sprintf( __( 'Test sent. The URL answered with HTTP %d.', 'surveyjs' ), $result['code'] ), 'log' => $log ) );
    }
}
