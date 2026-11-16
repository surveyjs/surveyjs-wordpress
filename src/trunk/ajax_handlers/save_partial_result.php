<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_SavePartialResult extends SurveyJS_AJAX_Handler {

    const MAX_BYTES = MB_IN_BYTES;

    function __construct() {
        parent::__construct( 'SurveyJS_SavePartialResult', true );
    }

    function callback() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
        check_ajax_referer( 'surveyjs-save-result' );

        if ( '' !== sanitize_text_field( wp_unslash( $_POST['Website'] ?? '' ) ) ) {
            wp_send_json_success();
        }

        $survey = ( new SurveyJS_Client() )->getSurvey( absint( wp_unslash( $_POST['SurveyId'] ?? 0 ) ) );
        if ( ! $survey || ! SurveyJS_Entries::partial_enabled( SurveyJS_Form_Settings::get( $survey ) ) ) {
            wp_send_json_error( array( 'message' => 'Form not found' ), 404 );
        }

        $raw_json = (string) wp_unslash( $_POST['Json'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only measured here; sanitize_result_json() below decodes it and sanitizes every value.
        if ( strlen( $raw_json ) > self::MAX_BYTES ) {
            wp_send_json_error( array( 'message' => 'Form result is too large' ), 413 );
        }
        $json = SurveyJS_SaveResult::sanitize_result_json( $raw_json );
        if ( null === $json ) {
            wp_send_json_error( array( 'message' => 'Invalid form result payload' ), 400 );
        }

        $partial_id = absint( wp_unslash( $_POST['PartialId'] ?? 0 ) );
        $partial_token = sanitize_text_field( wp_unslash( $_POST['PartialToken'] ?? '' ) );
        $data = SurveyJS_Uploaded_Files::filter_submitted( json_decode( $json, true ), $survey->id, SurveyJS_Entries::unfinished_id( $survey->id, $partial_id, $partial_token ) );
        $saved = SurveyJS_Entries::save_partial(
            $survey->id,
            $partial_id,
            $partial_token,
            wp_json_encode( $data ),
            absint( wp_unslash( $_POST['Page'] ?? 0 ) ),
            array(
                'user_id' => get_current_user_id(),
                'ip'      => SurveyJS_Entries::client_ip(),
            )
        );
        if ( null === $saved ) {
            wp_send_json_error( array( 'message' => 'Could not save the answers' ), 500 );
        }
        SurveyJS_Uploaded_Files::attach( $survey->id, $saved['id'], SurveyJS_Uploaded_Files::referenced_names( $data ) );
        wp_send_json_success( $saved );
    }
}
