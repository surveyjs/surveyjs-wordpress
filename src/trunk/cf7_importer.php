<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_CF7_Importer extends SurveyJS_Importer {

    const TITLE_MAX_LENGTH = 150;

    public static function source() {
        return 'cf7';
    }

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

    protected static function convert_form( $cf7_id ) {
        $cf7 = self::is_available() ? wpcf7_contact_form( $cf7_id ) : null;
        if ( ! $cf7 ) {
            return new WP_Error( 'not_found', __( 'The Contact Form 7 form was not found.', 'surveyjs' ) );
        }
        return array( 'name' => $cf7->title() ) + self::convert( $cf7 );
    }

    public static function convert( $cf7 ) {
        $json = array( 'elements' => array() );
        $skipped = array();
        $notes = array();
        $form_html = (string) $cf7->prop( 'form' );
        $messages = (array) $cf7->prop( 'messages' );
        $tags = $cf7->scan_form_tags();
        $segments = self::segments( $form_html, count( $tags ) );
        $taken = array_filter( wp_list_pluck( $tags, 'name' ), 'strlen' );

        foreach ( $tags as $index => $tag ) {
            $text = $segments[ $index ] ?? '';
            $title = self::label( $form_html, $tag );
            if ( '' === $title && '' !== $tag->name && 'hidden' !== $tag->basetype ) {
                list( $text, $title ) = self::title_line( $text );
            }
            self::add_html( $json, $text, $taken );
            if ( 'submit' === $tag->basetype ) {
                if ( '' !== (string) reset( $tag->values ) ) {
                    $json['completeText'] = (string) reset( $tag->values );
                }
                continue;
            }
            if ( in_array( $tag->basetype, array( 'count', 'response', 'recaptcha', 'turnstile' ), true ) ) {
                continue;
            }
            $question = self::question( $tag, $title, $messages, $notes );
            if ( $question ) {
                $json['elements'][] = $question;
            } else {
                $skipped[] = sprintf( '%s (%s)', '' !== $tag->name ? $tag->name : $tag->type, $tag->basetype );
            }
        }
        self::add_html( $json, $segments[ count( $tags ) ] ?? '', $taken );

        if ( ! empty( $messages['mail_sent_ok'] ) ) {
            $json['completedHtml'] = '<p>' . esc_html( $messages['mail_sent_ok'] ) . '</p>';
        }

        $names = wp_list_pluck( $json['elements'], 'name' );
        $settings = self::mail_settings( (array) $cf7->prop( 'mail' ), (array) $cf7->prop( 'mail_2' ), $names, $notes );
        if ( $cf7->is_true( 'skip_mail' ) || $cf7->in_demo_mode() ) {
            $settings['notificationEnabled'] = false;
            $settings['autoReplyEnabled'] = false;
            $notes[] = __( 'Emails are turned off because the form skips mail (skip_mail or demo_mode).', 'surveyjs' );
        }
        $source_captcha = self::captcha();
        if ( $source_captcha ) {
            SurveyJS_Captcha::import( $settings, $notes, $source_captcha );
        }
        if ( $cf7->is_true( 'subscribers_only' ) ) {
            $notes[] = __( 'The subscribers_only setting was not imported: the form is open to all visitors.', 'surveyjs' );
        }
        return array( 'json' => $json, 'settings' => $settings, 'skipped' => $skipped, 'notes' => $notes );
    }

    private static function captcha() {
        if ( class_exists( 'WPCF7_Turnstile' ) && WPCF7_Turnstile::get_instance()->is_active() ) {
            return 'Cloudflare Turnstile';
        }
        if ( class_exists( 'WPCF7_RECAPTCHA' ) && WPCF7_RECAPTCHA::get_instance()->is_active() ) {
            return 'Google reCAPTCHA';
        }
        return '';
    }

    private static function question( $tag, $title, $messages, &$notes ) {
        $label = '' !== $title ? $title : ucfirst( str_replace( array( '-', '_' ), ' ', $tag->name ) );
        $question = array( 'name' => $tag->name, 'title' => $label );
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
                if ( $tag->get_minlength_option() ) {
                    $question['validators'] = array( array( 'type' => 'text', 'minLength' => (int) $tag->get_minlength_option() ) );
                }
                if ( 'textarea' === $tag->basetype && $tag->get_rows_option() ) {
                    $question['rows'] = (int) $tag->get_rows_option();
                }
                $autocomplete = $tag->get_option( 'autocomplete', '[-0-9a-zA-Z]+', true );
                if ( $autocomplete && 'textarea' !== $tag->basetype ) {
                    $question['autocomplete'] = $autocomplete;
                }
                if ( 'number' === $tag->basetype ) {
                    foreach ( array( 'min', 'max', 'step' ) as $option ) {
                        $limit = $tag->get_option( $option, 'signed_num', true );
                        if ( false !== $limit ) {
                            $question[ $option ] = (float) $limit;
                        }
                    }
                } elseif ( 'date' === $tag->basetype ) {
                    foreach ( array( 'min', 'max' ) as $option ) {
                        if ( false !== $tag->get_date_option( $option ) ) {
                            $question[ $option ] = $tag->get_date_option( $option );
                        }
                    }
                }
                return $question;

            case 'range':
                $question['type'] = 'slider';
                foreach ( array( 'min', 'max', 'step' ) as $option ) {
                    $limit = $tag->get_option( $option, 'signed_num', true );
                    if ( false !== $limit ) {
                        $question[ $option ] = (float) $limit;
                    }
                }
                if ( is_numeric( $value ) && ! $is_placeholder ) {
                    $question['defaultValue'] = (float) $value;
                }
                return $question;

            case 'hidden':
                $question['type'] = 'text';
                $question['visible'] = false;
                $question['clearIfInvisible'] = 'none';
                if ( '' !== $value ) {
                    $question['defaultValue'] = $value;
                }
                return $question;

            case 'quiz':
                $pairs = $tag->pipes instanceof WPCF7_Pipes ? $tag->pipes->to_array() : array();
                if ( ! $pairs || '' === trim( (string) $pairs[0][1] ) ) {
                    return null;
                }
                if ( count( $pairs ) > 1 ) {
                    /* translators: %s: field name */
                    $notes[] = sprintf( __( 'Only the first question of the quiz "%s" was imported.', 'surveyjs' ), $tag->name );
                }
                $question = array( 'type' => 'text', 'name' => $tag->name, 'title' => trim( (string) $pairs[0][0] ), 'isRequired' => true );
                if ( '' !== $title ) {
                    $question['description'] = $title;
                }
                $question['validators'] = array( array(
                    'type'            => 'regex',
                    'regex'           => '^\s*' . preg_quote( trim( (string) $pairs[0][1] ) ) . '\s*$',
                    'caseInsensitive' => true,
                    'text'            => ! empty( $messages['quiz_answer_not_correct'] ) ? $messages['quiz_answer_not_correct'] : __( 'The answer to the quiz is incorrect.', 'surveyjs' ),
                ) );
                return $question;

            case 'select':
            case 'checkbox':
            case 'radio':
                $labels = array_values( array_map( 'strval', $tag->labels ) );
                $afters = $tag->pipes instanceof WPCF7_Pipes ? array_map( 'trim', $tag->pipes->collect_afters() ) : array();
                $items = array();
                foreach ( $labels as $index => $text ) {
                    $item_value = count( $afters ) === count( $labels ) ? (string) $afters[ $index ] : $text;
                    $items[ $index ] = $item_value === $text ? $text : array( 'value' => $item_value, 'text' => $text );
                }
                if ( 'select' === $tag->basetype ) {
                    $question['type'] = $tag->has_option( 'multiple' ) ? 'tagbox' : 'dropdown';
                    if ( $tag->has_option( 'first_as_label' ) && $items ) {
                        $question['placeholder'] = $labels[0];
                        unset( $items[0] );
                    }
                } else {
                    $question['type'] = 'radio' === $tag->basetype || $tag->has_option( 'exclusive' ) ? 'radiogroup' : 'checkbox';
                    if ( $tag->has_option( 'free_text' ) && $items ) {
                        $question['showOtherItem'] = true;
                        $question['otherText'] = end( $labels );
                        array_pop( $items );
                    }
                }
                $question['choices'] = array_values( $items );
                $defaults = array();
                foreach ( explode( '_', (string) $tag->get_option( 'default', '[0-9_]+', true ) ) as $index ) {
                    if ( isset( $items[ (int) $index - 1 ] ) ) {
                        $item = $items[ (int) $index - 1 ];
                        $defaults[] = is_array( $item ) ? $item['value'] : $item;
                    }
                }
                if ( $defaults ) {
                    $question['defaultValue'] = in_array( $question['type'], array( 'checkbox', 'tagbox' ), true ) ? $defaults : $defaults[0];
                }
                return $question;

            case 'acceptance':
                $text = trim( wp_strip_all_tags( (string) $tag->content ) );
                $question['title'] = '' !== $text ? $text : $label;
                if ( ! $tag->has_option( 'optional' ) ) {
                    $question['isRequired'] = true;
                }
                if ( $tag->has_option( 'default:on' ) ) {
                    $question['defaultValue'] = true;
                }
                return self::agreement( $question, (string) ( $messages['accept_terms'] ?? '' ) );

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
        return '';
    }

    private static function tag_regex() {
        $types = implode( '|', array_map( function ( $type ) {
            return preg_quote( $type, '/' );
        }, WPCF7_FormTagsManager::get_instance()->collect_tag_types() ) );
        $whitespaces = wpcf7_get_unicode_whitespaces();
        return '/(\[?)\[(' . $types . ')(?:[' . $whitespaces . ']+(.*?))?(?:[' . $whitespaces . ']+(\/))?\](?:([^[]*?)\[\/\2\])?(\]?)/su';
    }

    private static function segments( $form_html, $count ) {
        $regex = self::tag_regex();
        $html = preg_replace_callback( '/<label\b[^>]*>(.*?)<\/label>/si', function ( $label ) use ( $regex ) {
            return preg_match_all( $regex, $label[1], $tags ) ? implode( ' ', $tags[0] ) : $label[0];
        }, $form_html );
        preg_match_all( $regex, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
        $segments = array();
        $offset = 0;
        foreach ( $matches as $match ) {
            if ( '[' === $match[1][0] && ']' === ( $match[6][0] ?? '' ) ) {
                continue;
            }
            $segments[] = substr( $html, $offset, $match[0][1] - $offset );
            $offset = $match[0][1] + strlen( $match[0][0] );
        }
        $segments[] = substr( $html, $offset );
        return count( $segments ) === $count + 1 ? $segments : array();
    }

    private static function title_line( $html ) {
        $lines = preg_split( '/(\r\n|\r|\n|<br\s*\/?>|<\/p>|<\/div>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
        for ( $i = count( $lines ) - 1; $i >= 0; $i -= 2 ) {
            $text = preg_replace( '/\s+/', ' ', trim( wp_strip_all_tags( $lines[ $i ] ) ) );
            if ( '' === $text ) {
                continue;
            }
            if ( strlen( $text ) > self::TITLE_MAX_LENGTH ) {
                break;
            }
            $lines[ $i ] = '';
            return array( implode( '', $lines ), $text );
        }
        return array( $html, '' );
    }

    private static function add_html( &$json, $html, &$taken ) {
        $html = trim( preg_replace( '/<p>\s*<\/p>/i', '', wp_kses_post( wpautop( force_balance_tags( $html ) ) ) ) );
        if ( '' === trim( wp_strip_all_tags( $html ) ) && ! preg_match( '/<(img|hr|iframe)\b/i', $html ) ) {
            return;
        }
        $name = 'html1';
        for ( $i = 2; in_array( $name, $taken, true ); $i++ ) {
            $name = 'html' . $i;
        }
        $taken[] = $name;
        $json['elements'][] = array( 'type' => 'html', 'name' => $name, 'html' => $html );
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
                $notes[] = __( 'The auto-reply is sent to the Reply-To field; check it on the Email tab.', 'surveyjs' );
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
        return preg_replace_callback( '/\[(?:_raw_)?([\w-]+)\]/', function ( $match ) use ( $names, &$notes ) {
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
