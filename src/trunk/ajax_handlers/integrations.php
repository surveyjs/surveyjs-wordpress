<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

abstract class SurveyJS_Integration_AJAX_Handler extends SurveyJS_AJAX_Handler {

    protected function survey() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Not allowed.', 'surveyjs' ) ), 403 );
        }
        if ( ! check_ajax_referer( 'surveyjs-save-survey' ) ) exit;

        $client = new SurveyJS_Client();
        $survey = $client->getSurvey( absint( wp_unslash( $_POST['Id'] ?? 0 ) ) );
        if ( ! $survey ) {
            wp_send_json_error( array( 'message' => __( 'Form not found.', 'surveyjs' ) ), 404 );
        }
        return $survey;
    }

    protected function editor_settings() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the nonce is checked in survey(); SurveyJS_Form_Settings::sanitize() keeps only known keys and sanitizes each value.
        return SurveyJS_Form_Settings::sanitize( json_decode( wp_unslash( $_POST['Settings'] ?? '' ), true ) );
    }
}

class SurveyJS_TestIntegration extends SurveyJS_Integration_AJAX_Handler {

    function __construct() {
        parent::__construct( 'SurveyJS_TestIntegration', false );
    }

    function callback() {
        $survey = $this->survey();
        $settings = $this->editor_settings();
        $feed = SurveyJS_Integrations::find( $settings, sanitize_key( wp_unslash( $_POST['Integration'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in survey().
        if ( ! $feed ) {
            wp_send_json_error( array( 'message' => __( 'Integration not found.', 'surveyjs' ) ) );
        }
        $result = SurveyJS_Integrations::test( $survey, $settings, $feed );
        $log = SurveyJS_Delivery_Queue::client_log( $survey->id );
        if ( ! $result['ok'] ) {
            /* translators: %s: error message */
            wp_send_json_error( array( 'message' => sprintf( __( 'The test was not delivered: %s', 'surveyjs' ), $result['error'] ), 'log' => $log ) );
        }
        wp_send_json_success( array( 'message' => '' !== $result['note'] ? $result['note'] : __( 'Test sent.', 'surveyjs' ), 'log' => $log ) );
    }
}

class SurveyJS_IntegrationTargets extends SurveyJS_Integration_AJAX_Handler {

    function __construct() {
        parent::__construct( 'SurveyJS_IntegrationTargets', false );
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in survey().
    function callback() {
        $this->survey();
        $args = json_decode( sanitize_text_field( wp_unslash( $_POST['Args'] ?? '' ) ), true );
        $targets = SurveyJS_Integrations::targets(
            sanitize_key( wp_unslash( $_POST['Connection'] ?? '' ) ),
            sanitize_key( wp_unslash( $_POST['Target'] ?? '' ) ),
            is_array( $args ) ? array_map( 'sanitize_text_field', array_filter( $args, 'is_scalar' ) ) : array(),
            ! empty( $_POST['Refresh'] )
        );
        if ( is_wp_error( $targets ) ) {
            wp_send_json_error( array( 'message' => $targets->get_error_message() ) );
        }
        wp_send_json_success( array( 'items' => $targets ) );
    }
    // phpcs:enable
}

class SurveyJS_ResendDelivery extends SurveyJS_Integration_AJAX_Handler {

    function __construct() {
        parent::__construct( 'SurveyJS_ResendDelivery', false );
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in survey().
    function callback() {
        $survey = $this->survey();
        $type = sanitize_key( wp_unslash( $_POST['Type'] ?? '' ) );
        $sent = SurveyJS_Delivery_Queue::resend( $survey, $type, sanitize_key( wp_unslash( $_POST['Item'] ?? '' ) ), absint( wp_unslash( $_POST['Entry'] ?? 0 ) ) );
        $log = SurveyJS_Delivery_Queue::client_log( $survey->id );
        if ( is_wp_error( $sent ) ) {
            wp_send_json_error( array( 'message' => $sent->get_error_message(), 'log' => $log ) );
        }
        wp_send_json_success( array( 'message' => __( 'Sent again. See the result below.', 'surveyjs' ), 'log' => $log ) );
    }
    // phpcs:enable
}
