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
        $text_tags = self::common_tags( $survey );
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

        $to = self::recipients( $value( 'notificationTo' ) );
        $respondent_email = $data[ $settings['notificationReplyTo'] ] ?? '';
        $respondent_email = is_string( $respondent_email ) && is_email( $respondent_email ) ? $respondent_email : '';
        $from = self::from( $value( 'notificationFromName' ), $value( 'notificationFromEmail' ) );

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

    public static function send_test( $survey, $settings ) {
        $tags = self::common_tags( $survey );
        $value = function ( $key ) use ( $settings, $tags ) {
            return strtr( SurveyJS_Form_Settings::value( $settings, $key ), $tags );
        };

        $to = self::recipients( $value( 'notificationTo' ) );
        if ( empty( $to ) ) {
            return new WP_Error( 'no_recipients', __( 'Enter a valid email address in Send To.', 'surveyjs' ) );
        }

        $error = null;
        $catch_error = function ( $wp_error ) use ( &$error ) {
            $error = $wp_error;
        };
        add_action( 'wp_mail_failed', $catch_error );
        $sent = wp_mail(
            $to,
            sprintf( __( 'Test email from %s', 'surveyjs' ), $tags['{site_name}'] ),
            sprintf( __( "This is a test email from the form \"%s\". If you received it, emails from this form are delivered.", 'surveyjs' ), $survey->name ),
            self::from( $value( 'notificationFromName' ), $value( 'notificationFromEmail' ) )
        );
        remove_action( 'wp_mail_failed', $catch_error );

        if ( $sent ) {
            return $to;
        }
        return $error instanceof WP_Error ? $error : new WP_Error( 'mail_failed', __( 'The email could not be sent.', 'surveyjs' ) );
    }

    private static function common_tags( $survey ) {
        return array(
            '{admin_email}' => get_option( 'admin_email' ),
            '{site_name}'   => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
            '{form_name}'   => $survey->name,
        );
    }

    private static function recipients( $list ) {
        return array_values( array_filter( array_map( 'trim', explode( ',', $list ) ), 'is_email' ) );
    }

    private static function from( $name, $email ) {
        return is_email( $email ) ? array( sprintf( 'From: %s <%s>', $name, $email ) ) : array();
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
