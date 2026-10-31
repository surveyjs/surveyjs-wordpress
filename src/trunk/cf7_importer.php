<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_CF7_Importer {

    public static function is_available() {
        return class_exists( 'WPCF7_ContactForm' );
    }

    public static function forms() {
        $forms = array();
        if ( ! self::is_available() ) {
            return $forms;
        }
        foreach ( WPCF7_ContactForm::find( array( 'posts_per_page' => -1 ) ) as $form ) {
            $forms[ $form->id() ] = $form->title();
        }
        return $forms;
    }

    public static function import( $cf7_id ) {
        global $wpdb;
        $cf7 = self::is_available() ? wpcf7_contact_form( $cf7_id ) : null;
        if ( ! $cf7 ) {
            return new WP_Error( 'not_found', __( 'The Contact Form 7 form was not found.', 'surveyjs' ) );
        }
        $converted = self::convert( $cf7 );
        if ( empty( $converted['json']['elements'] ) ) {
            return new WP_Error( 'no_fields', __( 'The form has no fields to import.', 'surveyjs' ) );
        }
        $wpdb->insert( $wpdb->prefix . 'sjs_my_surveys', array(
            'name'     => sanitize_text_field( $cf7->title() ),
            'json'     => wp_json_encode( $converted['json'] ),
            'settings' => wp_json_encode( SurveyJS_Form_Settings::sanitize( $converted['settings'] ) ),
        ) );
        return array(
            'id'      => $wpdb->insert_id,
            'name'    => $cf7->title(),
            'skipped' => $converted['skipped'],
            'notes'   => $converted['notes'],
        );
    }

    public static function convert( $cf7 ) {
        $json = array( 'elements' => array() );
        $skipped = array();
        $notes = array();
        $form_html = (string) $cf7->prop( 'form' );

        foreach ( $cf7->scan_form_tags() as $tag ) {
            if ( 'submit' === $tag->basetype ) {
                if ( '' !== (string) reset( $tag->values ) ) {
                    $json['completeText'] = (string) reset( $tag->values );
                }
                continue;
            }
            if ( in_array( $tag->basetype, array( 'count', 'response' ), true ) ) {
                continue;
            }
            $question = self::question( $tag, self::label( $form_html, $tag ) );
            if ( $question ) {
                $json['elements'][] = $question;
            } else {
                $skipped[] = sprintf( '%s (%s)', '' !== $tag->name ? $tag->name : $tag->type, $tag->basetype );
            }
        }

        $messages = (array) $cf7->prop( 'messages' );
        if ( ! empty( $messages['mail_sent_ok'] ) ) {
            $json['completedHtml'] = '<p>' . esc_html( $messages['mail_sent_ok'] ) . '</p>';
        }

        $names = wp_list_pluck( $json['elements'], 'name' );
        $settings = self::mail_settings( (array) $cf7->prop( 'mail' ), (array) $cf7->prop( 'mail_2' ), $names, $notes );
        return array( 'json' => $json, 'settings' => $settings, 'skipped' => $skipped, 'notes' => $notes );
    }

    private static function question( $tag, $title ) {
        $question = array( 'name' => $tag->name, 'title' => $title );
        if ( $tag->is_required() ) {
            $question['isRequired'] = true;
        }
        $value = (string) reset( $tag->values );
        $is_placeholder = $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' );

        switch ( $tag->basetype ) {
            case 'text':
            case 'email':
            case 'url':
            case 'tel':
            case 'number':
            case 'range':
            case 'date':
            case 'textarea':
                $question['type'] = 'textarea' === $tag->basetype ? 'comment' : 'text';
                if ( ! in_array( $tag->basetype, array( 'text', 'textarea' ), true ) ) {
                    $question['inputType'] = $tag->basetype;
                }
                if ( '' !== $value ) {
                    $question[ $is_placeholder ? 'placeholder' : 'defaultValue' ] = $value;
                }
                if ( $tag->get_maxlength_option() ) {
                    $question['maxLength'] = (int) $tag->get_maxlength_option();
                }
                foreach ( 'date' === $tag->basetype ? array( 'min', 'max' ) : array( 'min', 'max', 'step' ) as $option ) {
                    $limit = 'date' === $tag->basetype ? $tag->get_date_option( $option ) : $tag->get_option( $option, 'signed_num', true );
                    if ( false !== $limit ) {
                        $question[ $option ] = 'date' === $tag->basetype ? $limit : (float) $limit;
                    }
                }
                return $question;

            case 'select':
            case 'checkbox':
            case 'radio':
                $choices = array_values( array_map( 'strval', $tag->labels ) );
                if ( 'select' === $tag->basetype ) {
                    $question['type'] = $tag->has_option( 'multiple' ) ? 'tagbox' : 'dropdown';
                    if ( $tag->has_option( 'first_as_label' ) && $choices ) {
                        $question['placeholder'] = array_shift( $choices );
                    }
                } else {
                    $question['type'] = 'radio' === $tag->basetype || $tag->has_option( 'exclusive' ) ? 'radiogroup' : 'checkbox';
                    if ( $tag->has_option( 'free_text' ) && $choices ) {
                        $question['showOtherItem'] = true;
                        $question['otherText'] = array_pop( $choices );
                    }
                }
                $question['choices'] = $choices;
                $defaults = array();
                foreach ( explode( '_', (string) $tag->get_option( 'default', '[0-9_]+', true ) ) as $index ) {
                    if ( isset( $tag->labels[ (int) $index - 1 ] ) && in_array( $tag->labels[ (int) $index - 1 ], $choices, true ) ) {
                        $defaults[] = $tag->labels[ (int) $index - 1 ];
                    }
                }
                if ( $defaults ) {
                    $question['defaultValue'] = in_array( $question['type'], array( 'checkbox', 'tagbox' ), true ) ? $defaults : $defaults[0];
                }
                return $question;

            case 'acceptance':
                $text = trim( wp_strip_all_tags( (string) $tag->content ) );
                $question['type'] = 'checkbox';
                $question['title'] = '' !== $text ? $text : $title;
                $question['titleLocation'] = 'hidden';
                $question['choices'] = array( array( 'value' => 'accepted', 'text' => $question['title'] ) );
                if ( ! $tag->has_option( 'optional' ) ) {
                    $question['isRequired'] = true;
                }
                return $question;

            case 'file':
                $question['type'] = 'file';
                $question['storeDataAsText'] = false;
                $question['maxSize'] = (int) $tag->get_limit_option();
                $types = (string) $tag->get_option( 'filetypes', '', true );
                if ( '' !== $types ) {
                    $question['acceptedTypes'] = implode( ',', array_map( function ( $type ) {
                        return false !== strpos( $type, '/' ) ? $type : '.' . ltrim( $type, '.' );
                    }, array_filter( explode( '|', strtolower( $types ) ) ) ) );
                }
                return $question;
        }
        return null;
    }

    private static function label( $form_html, $tag ) {
        $tag_pattern = '\[' . preg_quote( $tag->type, '/' ) . '\s+' . preg_quote( $tag->raw_name, '/' ) . '\b[^\]]*\]';
        if ( preg_match_all( '/<label[^>]*>(.*?)<\/label>/si', $form_html, $labels ) ) {
            foreach ( $labels[1] as $label ) {
                if ( preg_match( '/' . $tag_pattern . '/', $label ) ) {
                    $text = trim( wp_strip_all_tags( preg_replace( '/' . $tag_pattern . '(.*?\[\/' . preg_quote( $tag->type, '/' ) . '\])?/s', '', $label ) ) );
                    if ( '' !== $text ) {
                        return preg_replace( '/\s+/', ' ', $text );
                    }
                }
            }
        }
        return ucfirst( str_replace( array( '-', '_' ), ' ', $tag->name ) );
    }

    private static function mail_settings( $mail, $mail_2, $names, &$notes ) {
        $settings = array( 'notificationEnabled' => true );
        $settings['notificationTo'] = self::tags( $mail['recipient'] ?? '', $names, $notes );
        $settings['notificationSubject'] = self::tags( $mail['subject'] ?? '', $names, $notes );
        $settings['notificationMessage'] = self::tags( $mail['body'] ?? '', $names, $notes );
        $settings['notificationHtml'] = ! empty( $mail['use_html'] );
        $settings['notificationAttachments'] = self::attachment( $mail['attachments'] ?? '', $names );

        $sender = (string) ( $mail['sender'] ?? '' );
        if ( preg_match( '/^(.*?)<([^>]+)>/', $sender, $parts ) ) {
            $settings['notificationFromName'] = trim( self::tags( $parts[1], $names, $notes ) );
            $settings['notificationFromEmail'] = trim( self::tags( $parts[2], $names, $notes ) );
        } elseif ( '' !== trim( $sender ) ) {
            $settings['notificationFromEmail'] = trim( self::tags( $sender, $names, $notes ) );
        }

        if ( preg_match( '/^Reply-To:\s*(.+)$/mi', (string) ( $mail['additional_headers'] ?? '' ), $reply_to ) ) {
            $field = self::field_tag( $reply_to[1], $names );
            if ( $field ) {
                $settings['notificationReplyTo'] = $field;
            } else {
                $notes[] = sprintf( __( 'Reply-To "%s" was not imported: Reply-To can only be a form field.', 'surveyjs' ), trim( $reply_to[1] ) );
            }
        }

        if ( ! empty( $mail_2['active'] ) ) {
            $settings['autoReplyEnabled'] = true;
            $settings['autoReplySubject'] = self::tags( $mail_2['subject'] ?? '', $names, $notes );
            $settings['autoReplyMessage'] = self::tags( $mail_2['body'] ?? '', $names, $notes );
            $settings['autoReplyHtml'] = ! empty( $mail_2['use_html'] );
            $settings['autoReplyAttachments'] = self::attachment( $mail_2['attachments'] ?? '', $names );
            $recipient = self::field_tag( $mail_2['recipient'] ?? '', $names );
            if ( $recipient && empty( $settings['notificationReplyTo'] ) ) {
                $settings['notificationReplyTo'] = $recipient;
            } elseif ( $recipient !== ( $settings['notificationReplyTo'] ?? '' ) ) {
                $notes[] = __( 'The auto-reply is sent to the Reply-To field; check it on the Email Notification tab.', 'surveyjs' );
            }
        }
        $notes = array_values( array_unique( $notes ) );
        return $settings;
    }

    private static function tags( $text, $names, &$notes ) {
        $special = array(
            '[_site_title]'       => '{site_name}',
            '[_site_admin_email]' => '{admin_email}',
            '[_site_url]'         => home_url( '/' ),
            '[_site_description]' => get_bloginfo( 'description' ),
        );
        $text = strtr( (string) $text, $special );
        return preg_replace_callback( '/\[([\w-]+)\]/', function ( $match ) use ( $names, &$notes ) {
            if ( in_array( $match[1], $names, true ) ) {
                return '{field:' . $match[1] . '}';
            }
            $notes[] = sprintf( __( 'Email tag %s has no equivalent and was kept as text.', 'surveyjs' ), $match[0] );
            return $match[0];
        }, $text );
    }

    private static function field_tag( $text, $names ) {
        return preg_match( '/^\s*\[([\w-]+)\]\s*$/', (string) $text, $match ) && in_array( $match[1], $names, true ) ? $match[1] : '';
    }

    private static function attachment( $text, $names ) {
        return preg_match( '/\[([\w-]+)\]/', (string) $text, $match ) && in_array( $match[1], $names, true ) ? $match[1] : '';
    }
}
