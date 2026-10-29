<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Notification {

    // Gmail and Outlook reject emails over ~25 MB, and base64 encoding adds ~33%.
    const MAX_ATTACHMENTS_SIZE = 18 * MB_IN_BYTES;

    public static function send( $survey, $settings, $data ) {
        if ( empty( $settings['notificationEnabled'] ) && empty( $settings['autoReplyEnabled'] ) ) {
            return;
        }

        $data = is_array( $data ) ? $data : array();
        $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $text_tags = array(
            '{admin_email}' => get_option( 'admin_email' ),
            '{site_name}'   => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
            '{form_name}'   => $survey->name,
        );
        foreach ( $questions as $question ) {
            $text_tags[ '{field:' . $question['name'] . '}' ] = array_key_exists( $question['name'], $data ) ? SurveyJS_Survey_Values::display_value( $question, $data[ $question['name'] ] ) : '';
        }
        $html_tags = array_map( 'esc_html', $text_tags );
        $text_tags['{all_fields}'] = implode( "\n", self::field_lines( $questions, $data ) );
        $html_tags['{all_fields}'] = implode( "<br>\n", array_map( 'esc_html', self::field_lines( $questions, $data ) ) );

        $value = function ( $key, $html = false ) use ( $settings, $text_tags, $html_tags ) {
            $template = SurveyJS_Form_Settings::value( $settings, $key );
            return $html ? strtr( wpautop( $template ), $html_tags ) : strtr( $template, $text_tags );
        };

        $to = array_filter( array_map( 'trim', explode( ',', $value( 'notificationTo' ) ) ), 'is_email' );
        $respondent_email = $data[ $settings['notificationReplyTo'] ] ?? '';
        $respondent_email = is_string( $respondent_email ) && is_email( $respondent_email ) ? $respondent_email : '';

        $from = array();
        $from_email = $value( 'notificationFromEmail' );
        if ( is_email( $from_email ) ) {
            $from[] = sprintf( 'From: %s <%s>', $value( 'notificationFromName' ), $from_email );
        }

        $notify = ! empty( $settings['notificationEnabled'] ) && ! empty( $to );
        if ( $notify ) {
            $html = ! empty( $settings['notificationHtml'] );
            $headers = self::headers( $from, $respondent_email, $html );
            wp_mail( $to, $value( 'notificationSubject' ), $value( 'notificationMessage', $html ), $headers, self::attachments( $settings['notificationAttachments'], $data ) );
        }

        if ( ! empty( $settings['autoReplyEnabled'] ) && $respondent_email ) {
            $html = ! empty( $settings['autoReplyHtml'] );
            $headers = self::headers( $from, $notify ? implode( ', ', $to ) : get_option( 'admin_email' ), $html );
            wp_mail( $respondent_email, $value( 'autoReplySubject' ), $value( 'autoReplyMessage', $html ), $headers, self::attachments( $settings['autoReplyAttachments'], $data ) );
        }
    }

    private static function attachments( $question_name, $data ) {
        $attachments = array();
        $total = 0;
        foreach ( SurveyJS_Uploaded_Files::paths( $data[ $question_name ] ?? null ) as $name => $path ) {
            $size = (int) filesize( $path );
            if ( $total + $size > self::MAX_ATTACHMENTS_SIZE ) {
                continue;
            }
            $total += $size;
            $attachments[ $name ] = $path;
        }
        return $attachments;
    }

    private static function headers( $from, $reply_to, $html ) {
        $headers = $from;
        if ( '' !== $reply_to ) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        if ( $html ) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        return $headers;
    }

    private static function field_lines( $questions, $data ) {
        $lines = array();
        foreach ( $questions as $question ) {
            if ( array_key_exists( $question['name'], $data ) ) {
                $lines[] = SurveyJS_Survey_Values::question_title( $question ) . ': ' . SurveyJS_Survey_Values::display_value( $question, $data[ $question['name'] ] );
            }
        }
        return $lines;
    }
}
