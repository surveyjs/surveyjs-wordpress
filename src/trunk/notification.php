<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Notification {

    public static function send( $survey, $settings, $data ) {
        if ( empty( $settings['notificationEnabled'] ) && empty( $settings['autoReplyEnabled'] ) ) {
            return;
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
        $respondent_email = $data[ $settings['notificationReplyTo'] ] ?? '';
        $respondent_email = is_string( $respondent_email ) && is_email( $respondent_email ) ? $respondent_email : '';

        $from = array();
        $from_email = $value( 'notificationFromEmail' );
        if ( is_email( $from_email ) ) {
            $from[] = sprintf( 'From: %s <%s>', $value( 'notificationFromName' ), $from_email );
        }

        if ( ! empty( $settings['notificationEnabled'] ) && ! empty( $to ) ) {
            $headers = $respondent_email ? array_merge( $from, array( 'Reply-To: ' . $respondent_email ) ) : $from;
            wp_mail( $to, $value( 'notificationSubject' ), $value( 'notificationMessage' ), $headers );
        }

        if ( ! empty( $settings['autoReplyEnabled'] ) && $respondent_email ) {
            $headers = ! empty( $to ) ? array_merge( $from, array( 'Reply-To: ' . implode( ', ', $to ) ) ) : $from;
            wp_mail( $respondent_email, $value( 'autoReplySubject' ), $value( 'autoReplyMessage' ), $headers );
        }
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
