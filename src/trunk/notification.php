<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Notification {

    // Gmail and Outlook reject emails over ~25 MB, and base64 encoding adds ~33%.
    const MAX_ATTACHMENTS_SIZE = 18 * MB_IN_BYTES;

    public static function send( $survey, $settings, $data, $entry_id = 0 ) {
        if ( empty( $settings['notificationEnabled'] ) && empty( $settings['autoReplyEnabled'] ) ) {
            return;
        }

        $data = is_array( $data ) ? $data : array();
        $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $value_tags = self::common_tags( $survey );
        foreach ( $questions as $question ) {
            $value_tags[ '{field:' . $question['name'] . '}' ] = self::raw_value( $data[ $question['name'] ] ?? null );
        }
        $respondent_tags = self::field_tags( $survey, $questions, $data, false );
        // Without stored entries the files are deleted a day after sending, so a link would soon be dead.
        $owner_tags = empty( $settings['storeEntries'] ) ? $respondent_tags : self::field_tags( $survey, $questions, $data, true );
        $respondent_tags = apply_filters( 'surveyjs_notification_tags', $respondent_tags, 'respondent', $survey, $data, (int) $entry_id );
        $owner_tags = apply_filters( 'surveyjs_notification_tags', $owner_tags, 'owner', $survey, $data, (int) $entry_id );
        $attachments = function ( $key, $recipient ) use ( $settings, $data, $survey, $entry_id ) {
            return apply_filters( 'surveyjs_notification_attachments', self::attachments( $settings[ $key ], $data ), $recipient, $survey, $settings, $data, (int) $entry_id );
        };

        $value = function ( $key, $tags, $html = false ) use ( $settings ) {
            $template = SurveyJS_Form_Settings::value( $settings, $key );
            if ( ! $html ) {
                return strtr( $template, $tags['text'] );
            }
            $body = strtr( wpautop( $template ), $tags['html'] );
            return is_rtl() ? '<div dir="rtl">' . $body . '</div>' : $body;
        };

        $to = self::recipients( strtr( SurveyJS_Form_Settings::value( $settings, 'notificationTo' ), $value_tags ) );
        $respondent_email = $data[ $settings['notificationReplyTo'] ] ?? '';
        $respondent_email = is_string( $respondent_email ) && is_email( $respondent_email ) ? $respondent_email : '';
        $from = self::from( $value( 'notificationFromName', $respondent_tags ), $value( 'notificationFromEmail', $respondent_tags ) );

        $notify = ! empty( $settings['notificationEnabled'] ) && ! empty( $to );
        if ( $notify ) {
            $html = ! empty( $settings['notificationHtml'] );
            $headers = self::headers( $from, $respondent_email, $html );
            wp_mail( $to, $value( 'notificationSubject', $respondent_tags ), $value( 'notificationMessage', $owner_tags, $html ), $headers, $attachments( 'notificationAttachments', 'owner' ) );
        }

        if ( ! empty( $settings['autoReplyEnabled'] ) && $respondent_email ) {
            $html = ! empty( $settings['autoReplyHtml'] );
            $headers = self::headers( $from, $notify ? implode( ', ', $to ) : get_option( 'admin_email' ), $html );
            wp_mail( $respondent_email, $value( 'autoReplySubject', $respondent_tags ), $value( 'autoReplyMessage', $respondent_tags, $html ), $headers, $attachments( 'autoReplyAttachments', 'respondent' ) );
        }
    }

    private static function field_tags( $survey, $questions, $data, $file_links ) {
        $text_tags = self::common_tags( $survey );
        $html_tags = array_map( 'esc_html', $text_tags );
        $text_lines = array();
        $html_lines = array();
        foreach ( $questions as $question ) {
            $tag = '{field:' . $question['name'] . '}';
            $text_tags[ $tag ] = '';
            $html_tags[ $tag ] = '';
            if ( ! array_key_exists( $question['name'], $data ) ) {
                continue;
            }
            $links = $file_links ? SurveyJS_Uploaded_Files::links( $data[ $question['name'] ] ) : array();
            $text_tags[ $tag ] = $links ? SurveyJS_Uploaded_Files::links_text( $links ) : SurveyJS_Survey_Values::display_value( $question, $data[ $question['name'] ] );
            $html_tags[ $tag ] = $links ? SurveyJS_Uploaded_Files::links_html( $links ) : SurveyJS_Survey_Values::display_html( $question, $data[ $question['name'] ] );
            $title = SurveyJS_Survey_Values::question_title( $question );
            $text_lines[] = $title . ': ' . $text_tags[ $tag ];
            $html_lines[] = esc_html( $title ) . ': ' . $html_tags[ $tag ];
        }
        $text_tags['{all_fields}'] = implode( "\n", $text_lines );
        $html_tags['{all_fields}'] = implode( "<br>\n", $html_lines );
        return array( 'text' => $text_tags, 'html' => $html_tags );
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
            /* translators: %s: site name */
            sprintf( __( 'Test email from %s', 'surveyjs' ), $tags['{site_name}'] ),
            /* translators: %s: form name */
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

    private static function raw_value( $value ) {
        if ( is_scalar( $value ) ) {
            return (string) $value;
        }
        return is_array( $value ) ? implode( ', ', array_filter( $value, 'is_scalar' ) ) : '';
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
}
