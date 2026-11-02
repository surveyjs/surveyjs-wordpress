<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!check_ajax_referer( 'surveyjs-save-result' )) exit;
            $honeypot = trim( (string) wp_unslash( $_POST['Website'] ?? '' ) );
            if ( '' !== $honeypot ) {
                return;
            }
            $SurveyId = absint( wp_unslash( $_POST['SurveyId'] ?? 0 ) );
            $raw_json = wp_unslash( $_POST['Json'] ?? '' );
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
            if ( SurveyJS_Captcha::is_enabled( $settings ) ) {
                $captcha = SurveyJS_Captcha::verify( wp_unslash( $_POST['CaptchaToken'] ?? '' ) );
                if ( is_wp_error( $captcha ) ) {
                    wp_send_json_error( array( 'message' => $captcha->get_error_message() ), 403 );
                    return;
                }
            }

            $entry_id = 0;
            if ( $settings['storeEntries'] ) {
                $entry_args = array(
                    'user_id' => get_current_user_id(),
                    'ip'      => SurveyJS_Entries::client_ip(),
                );
                $entry_id = SurveyJS_Entries::complete_partial( $survey->id, absint( wp_unslash( $_POST['PartialId'] ?? 0 ) ), (string) wp_unslash( $_POST['PartialToken'] ?? '' ), $Json, $entry_args );
                if ( ! $entry_id ) {
                    $entry_id = SurveyJS_Entries::insert( $survey->id, $Json, $entry_args );
                }
            }

            add_option( 'surveyjs_received_entry', 1 );
            SurveyJS_Drafts::delete( get_current_user_id(), $SurveyId );

            $data = json_decode( $Json, true );
            SurveyJS_Notification::send( $survey, $settings, $data );

            do_action( 'wp_surveyjs_save_result', $survey->id, $Json, 'sjs_results', $entry_id );

            if ( ! $settings['storeEntries'] && is_array( $data ) ) {
                $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
                array_map( 'wp_delete_file', SurveyJS_Uploaded_Files::entry_paths( $questions, $data ) );
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
        return wp_json_encode( $sanitized );
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

?>
