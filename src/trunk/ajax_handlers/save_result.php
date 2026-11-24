<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            if(!check_ajax_referer( 'surveyjs-save-result' )) exit;
            $honeypot = sanitize_text_field( wp_unslash( $_POST['Website'] ?? '' ) );
            if ( '' !== $honeypot ) {
                return;
            }
            $SurveyId = absint( wp_unslash( $_POST['SurveyId'] ?? 0 ) );
            $raw_json = wp_unslash( $_POST['Json'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_result_json() on the next line decodes it and sanitizes every value.
            $Json = self::sanitize_result_json( $raw_json );
            if ( null === $Json ) {
                wp_send_json_error(
                    array(
                        'message' => 'Invalid form result payload',
                    ),
                    400
                );
                return;
            }
            $client = new SurveyJS_Client();
            $survey = $client->getSurvey( $SurveyId );
            if ( ! $survey ) {
                wp_send_json_error( array( 'message' => 'Form not found' ), 404 );
                return;
            }
            $settings = SurveyJS_Form_Settings::get( $survey );
            SurveyJS_Restrictions::reject_if_closed( $survey, $settings );
            if ( SurveyJS_Submit_Time::is_enabled( $settings ) ) {
                $submit_time = SurveyJS_Submit_Time::verify( $survey->id, $settings, sanitize_text_field( wp_unslash( $_POST['SubmitToken'] ?? '' ) ) );
                if ( is_wp_error( $submit_time ) ) {
                    wp_send_json_error( array( 'message' => $submit_time->get_error_message() ), 403 );
                    return;
                }
            }
            if ( SurveyJS_Captcha::is_enabled( $settings ) ) {
                $captcha = SurveyJS_Captcha::verify( sanitize_text_field( wp_unslash( $_POST['CaptchaToken'] ?? '' ) ) );
                if ( is_wp_error( $captcha ) ) {
                    wp_send_json_error( array( 'message' => $captcha->get_error_message() ), 403 );
                    return;
                }
            }

            $partial_id = absint( wp_unslash( $_POST['PartialId'] ?? 0 ) );
            $partial_token = sanitize_text_field( wp_unslash( $_POST['PartialToken'] ?? '' ) );
            $partial_id = $settings['storeEntries'] ? SurveyJS_Entries::unfinished_id( $survey->id, $partial_id, $partial_token ) : 0;
            $data = SurveyJS_Uploaded_Files::filter_submitted( json_decode( $Json, true ), $survey->id, $partial_id );
            $Json = wp_json_encode( $data );
            $is_spam = SurveyJS_Akismet::is_enabled( $settings ) && SurveyJS_Akismet::is_spam( $survey, $settings, $data );

            $entry_id = 0;
            if ( $settings['storeEntries'] ) {
                $entry_args = array(
                    'status'  => $is_spam ? 'spam' : 'unread',
                    'user_id' => get_current_user_id(),
                    'ip'      => SurveyJS_Entries::client_ip(),
                    'ip_hash' => SurveyJS_Restrictions::entry_ip_hash( $settings ),
                );
                $entry_id = SurveyJS_Entries::complete_partial( $survey->id, $partial_id, $partial_token, $Json, $entry_args );
                if ( ! $entry_id ) {
                    $entry_id = SurveyJS_Entries::insert( $survey->id, $Json, $entry_args );
                }
            }
            if ( $entry_id ) {
                SurveyJS_Uploaded_Files::attach( $survey->id, $entry_id, SurveyJS_Uploaded_Files::referenced_names( $data ) );
            }

            SurveyJS_Drafts::delete( get_current_user_id(), $SurveyId );

            if ( $is_spam ) {
                if ( ! $entry_id ) {
                    SurveyJS_Uploaded_Files::delete_later( SurveyJS_Uploaded_Files::referenced_names( $data ) );
                }
                return;
            }

            SurveyJS_Notification::send( $survey, $settings, $data, $entry_id );
            SurveyJS_Webhooks::dispatch( $survey, $settings, $data, $entry_id );

            do_action( 'wp_surveyjs_save_result', $survey->id, $Json, 'sjs_results', $entry_id );

            if ( ! $entry_id ) {
                SurveyJS_Uploaded_Files::delete_later( SurveyJS_Uploaded_Files::referenced_names( $data ) );
            }

            $response = apply_filters( 'surveyjs_save_result_response', array(), $survey, $entry_id, is_array( $data ) ? $data : array() );
            if ( ! empty( $response ) && is_array( $response ) ) {
                wp_send_json_success( $response );
            }
        }
    }

    public static function sanitize_result_json( $raw_json ) {
        if ( ! is_string( $raw_json ) || '' === trim( $raw_json ) ) {
            return null;
        }

        $decoded = json_decode( $raw_json, true );
        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return null;
        }

        $sanitized = self::sanitize_json_value( $decoded );
        return wp_json_encode( SurveyJS_Uploaded_Files::normalize_contents( $sanitized ) );
    }

    private static function sanitize_json_value( $value ) {
        if ( is_array( $value ) ) {
            $sanitized = array();
            foreach ( $value as $key => $item ) {
                $sanitized[ $key ] = self::sanitize_json_value( $item );
            }
            return $sanitized;
        }

        if ( is_string( $value ) ) {
            return sanitize_text_field( wp_kses( $value, array() ) );
        }

        return $value;
    }
}
