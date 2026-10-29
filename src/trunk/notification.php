<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Notification {

    public static function send( $survey, $settings, $data ) {
        if ( empty( $settings['notificationEnabled'] ) ) {
            return false;
        }

        $data = is_array( $data ) ? $data : array();
        $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $tags = array(
            '{admin_email}' => get_option( 'admin_email' ),
            '{site_name}'   => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
            '{form_name}'   => $survey->name,
            '{all_fields}'  => self::all_fields( $questions, $data ),
        );

        $value = function ( $key ) use ( $settings, $tags ) {
            return strtr( SurveyJS_Form_Settings::value( $settings, $key ), $tags );
        };

        $to = array_filter( array_map( 'trim', explode( ',', $value( 'notificationTo' ) ) ), 'is_email' );
        if ( empty( $to ) ) {
            return false;
        }

        $headers = array();
        $from_email = $value( 'notificationFromEmail' );
        if ( is_email( $from_email ) ) {
            $headers[] = sprintf( 'From: %s <%s>', $value( 'notificationFromName' ), $from_email );
        }
        $reply_to = $data[ $settings['notificationReplyTo'] ] ?? '';
        if ( is_string( $reply_to ) && is_email( $reply_to ) ) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        return wp_mail( $to, $value( 'notificationSubject' ), $value( 'notificationMessage' ), $headers );
    }

    private static function all_fields( $questions, $data ) {
        $lines = array();
        foreach ( $questions as $question ) {
            if ( array_key_exists( $question['name'], $data ) ) {
                $lines[] = SurveyJS_Survey_Values::question_title( $question ) . ': ' . SurveyJS_Survey_Values::display_value( $question, $data[ $question['name'] ] );
            }
        }
        return implode( "\n", $lines );
    }
}
