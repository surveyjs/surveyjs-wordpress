<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_WPForms_Importer extends SurveyJS_Importer {

    const FIELD_TAGS = array( 'field_id', 'field_value_id', 'field_html_id' );

    const SKIPPED_ENTRY_STATUSES = array( 'spam', 'trash' );
    const PARTIAL_ENTRY_STATUSES = array( 'partial', 'abandoned' );

    public static function source() {
        return 'wpforms';
    }

    public static function is_available() {
        return function_exists( 'wpforms' );
    }

    public static function forms() {
        $forms = array();
        if ( ! self::is_available() ) {
            return $forms;
        }
        $posts = get_posts( array(
            'post_type'   => 'wpforms',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        ) );
        foreach ( $posts as $post ) {
            $forms[ $post->ID ] = $post->post_title;
        }
        return $forms;
    }

    protected static function convert_form( $wpforms_id ) {
        $post = self::is_available() ? get_post( $wpforms_id ) : null;
        $form = $post && 'wpforms' === $post->post_type ? json_decode( $post->post_content, true ) : null;
        if ( ! is_array( $form ) ) {
            return new WP_Error( 'not_found', __( 'The WPForms form was not found.', 'surveyjs' ) );
        }
        return array( 'name' => $post->post_title ) + self::convert( $form, $post->post_title );
    }

    public static function entries_available() {
        global $wpdb;
        return self::is_available() && (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'wpforms_entries' ) ) );
    }

    protected static function copies_entry_files() {
        return true;
    }

    // WPForms keeps the entry date in UTC. A partial entry is dated by its last save, like partial entries saved by this plugin.
    protected static function entry_dates( $wpforms_id, $entry_ids ) {
        $dates = array();
        foreach ( self::entry_rows( $entry_ids ) as $row ) {
            $dates[ (int) $row->entry_id ] = self::gmt_date( in_array( $row->status, self::PARTIAL_ENTRY_STATUSES, true ) ? $row->date_modified : $row->date );
        }
        return array_filter( $dates );
    }

    protected static function partial_entry_ids( $wpforms_id, $entry_ids ) {
        $ids = array();
        foreach ( self::entry_rows( $entry_ids ) as $row ) {
            if ( in_array( $row->status, self::PARTIAL_ENTRY_STATUSES, true ) ) {
                $ids[] = (int) $row->entry_id;
            }
        }
        return $ids;
    }

    private static function entry_rows( $entry_ids ) {
        global $wpdb;
        $in = implode( ', ', array_fill( 0, count( $entry_ids ), '%d' ) );
        return $wpdb->get_results( $wpdb->prepare( "SELECT entry_id, status, date, date_modified FROM {$wpdb->prefix}wpforms_entries WHERE entry_id IN ( $in )", array_map( 'intval', $entry_ids ) ) );
    }

    protected static function entries( $wpforms_id, $form ) {
        return static::entries_page( $wpforms_id, $form, 0, 0 )['entries'];
    }

    protected static function entries_page( $wpforms_id, $form, $offset, $limit ) {
        global $wpdb;
        $questions = array_column( SurveyJS_Survey_Values::survey_questions( $form['json'] ), null, 'name' );
        $skipped = implode( ', ', array_fill( 0, count( self::SKIPPED_ENTRY_STATUSES ), '%s' ) );
        $partial = implode( ', ', array_fill( 0, count( self::PARTIAL_ENTRY_STATUSES ), '%s' ) );
        $max_age = SurveyJS_Entries::partial_max_age();
        // Partial entries older than the cleanup age would be deleted by the next cleanup, so they are not imported.
        $where = $wpdb->prepare(
            "FROM {$wpdb->prefix}wpforms_entries WHERE form_id = %d AND status NOT IN ( $skipped ) AND ( status NOT IN ( $partial ) OR date_modified >= %s )",
            array_merge( array( $wpforms_id ), self::SKIPPED_ENTRY_STATUSES, self::PARTIAL_ENTRY_STATUSES, array( $max_age > 0 ? gmdate( 'Y-m-d H:i:s', time() - $max_age ) : '0000-00-00 00:00:00' ) )
        );
        $rows = $wpdb->get_results( "SELECT entry_id, fields $where ORDER BY entry_id" . ( $limit ? $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit ) : '' ) );
        $entries = array();
        foreach ( $rows as $row ) {
            $data = array();
            foreach ( (array) json_decode( (string) $row->fields, true ) as $field_id => $field ) {
                $ref = $form['refs'][ (string) $field_id ] ?? null;
                if ( ! $ref || ! is_array( $field ) ) {
                    continue;
                }
                foreach ( $ref['parts'] as $part => $name ) {
                    if ( isset( $questions[ $name ] ) ) {
                        $data += self::entry_field_value( $questions[ $name ], $ref['type'], $field, $part );
                    }
                }
            }
            if ( $data ) {
                $entries[ (int) $row->entry_id ] = $data;
            }
        }
        return array(
            'entries' => $entries,
            'total'   => $limit ? (int) $wpdb->get_var( "SELECT COUNT(*) $where" ) : count( $rows ),
        );
    }

    private static function entry_field_value( $question, $type, $field, $part ) {
        if ( 'value' !== $part ) {
            return self::entry_value( $question, $field[ $part ] ?? '' );
        }
        if ( 'date-time' === $type && is_numeric( $field['unix'] ?? '' ) && '' !== (string) ( $field['value'] ?? '' ) ) {
            $formats = array( 'date' => 'Y-m-d', 'time' => 'H:i' );
            return array( $question['name'] => gmdate( $formats[ $question['inputType'] ?? '' ] ?? 'Y-m-d\TH:i', (int) $field['unix'] ) );
        }
        if ( 'file-upload' === $type ) {
            $files = is_array( $field['value_raw'] ?? null ) ? $field['value_raw'] : array_map( function ( $url ) {
                return array( 'value' => $url );
            }, array_filter( preg_split( '/\r\n|\n/', (string) ( $field['value'] ?? '' ) ) ) );
            $value = array();
            foreach ( $files as $file ) {
                $url = esc_url_raw( (string) ( $file['value'] ?? '' ) );
                if ( '' !== $url ) {
                    $value[] = array(
                        'name'    => (string) ( $file['file_user_name'] ?? $file['file_original'] ?? basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ),
                        'type'    => (string) ( $file['type'] ?? '' ),
                        'content' => $url,
                    );
                }
            }
            return $value ? array( $question['name'] => $value ) : array();
        }
        if ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
            $raw = isset( $field['value_raw'] ) && '' !== $field['value_raw'] ? $field['value_raw'] : ( $field['value'] ?? '' );
            return self::entry_value( $question, is_array( $raw ) ? $raw : preg_split( '/\r\n|\n/', (string) $raw ) );
        }
        return self::entry_value( $question, $field['value'] ?? '' );
    }

    public static function convert( $form, $title ) {
        $json = array();
        $skipped = array();
        $notes = array();
        $taken = array();
        $refs = array();
        $conditions = array();
        $settings = (array) ( $form['settings'] ?? array() );
        $pages = array( array( 'name' => 'page1', 'elements' => array() ) );

        foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }
            $type = (string) ( $field['type'] ?? '' );
            if ( 'pagebreak' === $type ) {
                $position = (string) ( $field['position'] ?? '' );
                if ( 'top' === $position ) {
                    if ( '' !== self::text( $field['title'] ?? '' ) ) {
                        $pages[0]['title'] = self::text( $field['title'] );
                    }
                    if ( 'none' !== ( $field['indicator'] ?? 'progress' ) ) {
                        $json['showProgressBar'] = true;
                    }
                } elseif ( 'bottom' !== $position ) {
                    $page = array( 'name' => 'page' . ( count( $pages ) + 1 ), 'elements' => array() );
                    if ( '' !== self::text( $field['title'] ?? '' ) ) {
                        $page['title'] = self::text( $field['title'] );
                    }
                    $pages[] = $page;
                    if ( ! isset( $json['pageNextText'] ) && '' !== self::text( $field['next'] ?? '' ) ) {
                        $json['pageNextText'] = self::text( $field['next'] );
                    }
                }
                if ( ! isset( $json['pagePrevText'] ) && 'top' !== $position && '' !== self::text( $field['prev'] ?? '' ) ) {
                    $json['pagePrevText'] = self::text( $field['prev'] );
                }
                continue;
            }
            $elements = self::elements( $field, $taken, $refs, $notes );
            if ( null === $elements ) {
                $label = self::text( $field['label'] ?? '' );
                $skipped[] = sprintf( '%s (%s)', '' !== $label ? $label : '#' . ( $field['id'] ?? '' ), $type );
                continue;
            }
            $id = (string) ( $field['id'] ?? '' );
            if ( isset( $refs[ $id ] ) ) {
                $refs[ $id ] += array(
                    'type'     => $type,
                    'choices'  => self::choice_values( $field ),
                    'multiple' => 'checkbox' === $type || ( 'select' === $type && ! empty( $field['multiple'] ) ),
                );
            }
            $page = count( $pages ) - 1;
            if ( ! empty( $field['conditional_logic'] ) && $elements ) {
                $conditions[] = array( $page, count( $pages[ $page ]['elements'] ), count( $elements ), $field );
            }
            array_push( $pages[ $page ]['elements'], ...$elements );
        }

        foreach ( $conditions as list( $page, $start, $count, $field ) ) {
            $expression = self::expression( $field, $refs );
            if ( '' === $expression ) {
                /* translators: %s: field label */
                $notes[] = sprintf( __( 'The conditional logic of "%s" was not imported.', 'surveyjs' ), self::text( $field['label'] ?? '' ) );
                continue;
            }
            for ( $index = $start; $index < $start + $count; $index++ ) {
                $pages[ $page ]['elements'][ $index ]['visibleIf'] = $expression;
            }
        }

        $pages = array_values( array_filter( $pages, function ( $page ) {
            return ! empty( $page['elements'] );
        } ) );
        if ( count( $pages ) > 1 ) {
            $json['pages'] = $pages;
        } else {
            $json['elements'] = $pages ? $pages[0]['elements'] : array();
            unset( $json['showProgressBar'], $json['pageNextText'], $json['pagePrevText'] );
        }

        if ( '' !== self::text( $settings['form_desc'] ?? '' ) ) {
            $json['description'] = self::text( $settings['form_desc'] );
        }
        if ( '' !== self::text( $settings['submit_text'] ?? '' ) ) {
            $json['completeText'] = self::text( $settings['submit_text'] );
        }
        self::confirmation( (array) ( $settings['confirmations'] ?? array() ), $title, $refs, $json, $notes );
        $form_settings = self::notification_settings( $settings, $refs, $notes );
        $source_captcha = ! empty( $settings['recaptcha'] ) ? self::captcha() : '';
        if ( '' !== $source_captcha ) {
            SurveyJS_Captcha::import( $form_settings, $notes, $source_captcha );
        }

        return array(
            'json'     => $json,
            'settings' => $form_settings,
            'skipped'  => $skipped,
            'notes'    => $notes,
            'refs'     => $refs,
        );
    }

    private static function captcha() {
        $provider = function_exists( 'wpforms_setting' ) ? wpforms_setting( 'captcha-provider', 'recaptcha' ) : 'recaptcha';
        $labels = array(
            'recaptcha' => 'Google reCAPTCHA',
            'turnstile' => 'Cloudflare Turnstile',
            'hcaptcha'  => 'hCaptcha',
        );
        return $labels[ $provider ] ?? '';
    }

    private static function elements( $field, &$taken, &$refs, &$notes ) {
        $type = (string) ( $field['type'] ?? '' );
        $id = (string) ( $field['id'] ?? '' );
        $label = self::text( $field['label'] ?? '' );
        $base = '' !== $label ? $label : $type . '_' . $id;
        $common = array( 'title' => $label );
        if ( '' !== self::text( $field['description'] ?? '' ) ) {
            $common['description'] = self::text( $field['description'] );
        }
        if ( ! empty( $field['required'] ) ) {
            $common['isRequired'] = true;
        }
        if ( ! empty( $field['label_hide'] ) ) {
            $common['titleLocation'] = 'hidden';
        }
        $question = function ( $type, $props = array() ) use ( $base, $common, &$taken ) {
            return array_merge( array( 'type' => $type, 'name' => self::unique_name( $base, $taken ) ), $common, $props );
        };
        $html = function ( $html ) use ( $base, &$taken ) {
            return array( array( 'type' => 'html', 'name' => self::unique_name( $base, $taken ), 'html' => wp_kses_post( $html ) ) );
        };

        switch ( $type ) {
            case 'text':
            case 'textarea':
            case 'richtext':
            case 'email':
            case 'number':
            case 'phone':
            case 'url':
            case 'password':
            case 'hidden':
                $input_types = array( 'email' => 'email', 'number' => 'number', 'phone' => 'tel', 'url' => 'url', 'password' => 'password' );
                $props = isset( $input_types[ $type ] ) ? array( 'inputType' => $input_types[ $type ] ) : array();
                $props += self::placeholder_default( $field, 'placeholder', 'default_value', $label, $notes );
                if ( ! empty( $field['limit_enabled'] ) && (int) ( $field['limit_count'] ?? 0 ) > 0 ) {
                    if ( 'words' === ( $field['limit_mode'] ?? 'characters' ) ) {
                        /* translators: %s: field label */
                        $notes[] = sprintf( __( 'The word limit of "%s" was not imported: only a character limit is supported.', 'surveyjs' ), $label );
                    } else {
                        $props['maxLength'] = (int) $field['limit_count'];
                    }
                }
                if ( ! empty( $field['input_mask'] ) ) {
                    /* translators: %s: field label */
                    $notes[] = sprintf( __( 'The input mask of "%s" was not imported.', 'surveyjs' ), $label );
                }
                foreach ( array( 'min', 'max' ) as $limit ) {
                    if ( 'number' === $type && is_numeric( $field[ $limit ] ?? '' ) ) {
                        $props[ $limit ] = (float) $field[ $limit ];
                    }
                }
                if ( 'hidden' === $type ) {
                    $props += array( 'visible' => false, 'clearIfInvisible' => 'none' );
                }
                $element = $question( in_array( $type, array( 'textarea', 'richtext' ), true ) ? 'comment' : 'text', $props );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => 'email' === $type );
                if ( 'email' !== $type ) {
                    return array( $element );
                }
                if ( ! empty( $field['allowlist'] ) || ! empty( $field['denylist'] ) ) {
                    /* translators: %s: field label */
                    $notes[] = sprintf( __( 'The allowlist and denylist of "%s" were not imported.', 'surveyjs' ), $label );
                }
                if ( empty( $field['confirmation'] ) ) {
                    return array( $element );
                }
                $confirm = array(
                    'type'       => 'text',
                    'name'       => self::unique_name( $element['name'] . '_confirm', $taken ),
                    'title'      => __( 'Confirm Email', 'surveyjs' ),
                    'inputType'  => 'email',
                );
                if ( ! empty( $field['required'] ) ) {
                    $confirm['isRequired'] = true;
                }
                if ( '' !== self::text( $field['confirmation_placeholder'] ?? '' ) ) {
                    $confirm['placeholder'] = self::text( $field['confirmation_placeholder'] );
                }
                $confirm['validators'] = array( array(
                    'type'       => 'expression',
                    'expression' => '{' . $confirm['name'] . '} = {' . $element['name'] . '}',
                    'text'       => __( 'The provided emails do not match.', 'surveyjs' ),
                ) );
                return array( $element, $confirm );

            case 'select':
            case 'radio':
            case 'checkbox':
                if ( ! empty( $field['dynamic_choices'] ) ) {
                    /* translators: %s: field label */
                    $notes[] = sprintf( __( 'The field "%s" was not imported: dynamic choices are not supported.', 'surveyjs' ), $label );
                    return array();
                }
                $element = $question( 'dropdown', self::choices( $field, $label, $notes ) );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                return array( $element );

            case 'gdpr-checkbox':
                $choices = (array) ( $field['choices'] ?? array() );
                $choice = reset( $choices );
                $text = is_array( $choice ) ? self::text( $choice['label'] ?? '' ) : '';
                $element = self::agreement( $question( 'boolean', array(
                    'title'      => '' !== $text ? $text : $label,
                    'isRequired' => true,
                ) ) );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                return array( $element );

            case 'number-slider':
                $props = array();
                foreach ( array( 'min', 'max', 'step' ) as $option ) {
                    if ( is_numeric( $field[ $option ] ?? '' ) ) {
                        $props[ $option ] = (float) $field[ $option ];
                    }
                }
                if ( is_numeric( $field['default_value'] ?? '' ) ) {
                    $props['defaultValue'] = (float) $field['default_value'];
                }
                $element = $question( 'slider', $props );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                return array( $element );

            case 'date-time':
                $formats = array( 'date' => 'date', 'time' => 'time' );
                $format = (string) ( $field['format'] ?? 'date-time' );
                $element = $question( 'text', array( 'inputType' => $formats[ $format ] ?? 'datetime-local' ) );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                return array( $element );

            case 'file-upload':
                $props = array( 'storeDataAsText' => false );
                if ( (int) ( $field['max_file_number'] ?? 1 ) > 1 ) {
                    $props['allowMultiple'] = true;
                }
                if ( is_numeric( $field['max_size'] ?? '' ) && (float) $field['max_size'] > 0 ) {
                    $props['maxSize'] = (int) ( (float) $field['max_size'] * MB_IN_BYTES );
                }
                $extensions = array_filter( array_map( 'trim', explode( ',', strtolower( (string) ( $field['extensions'] ?? '' ) ) ) ) );
                if ( $extensions ) {
                    $props['acceptedTypes'] = implode( ',', array_map( function ( $extension ) {
                        return '.' . ltrim( $extension, '.' );
                    }, $extensions ) );
                }
                $element = $question( 'file', $props );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                return array( $element );

            case 'rating':
                $element = $question( 'rating', array( 'rateType' => 'stars', 'rateMax' => max( 2, (int) ( $field['scale'] ?? 5 ) ) ) );
                $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                return array( $element );

            case 'name':
                $format = (string) ( $field['format'] ?? 'first-last' );
                if ( 'simple' === $format ) {
                    $element = $question( 'text', self::placeholder_default( $field, 'simple_placeholder', 'simple_default', $label, $notes ) );
                    $refs[ $id ] = array( 'parts' => array( 'value' => $element['name'] ), 'separator' => '', 'email' => false );
                    return array( $element );
                }
                $parts = array(
                    'first'  => array( __( 'First Name', 'surveyjs' ), true ),
                    'middle' => array( __( 'Middle Name', 'surveyjs' ), false ),
                    'last'   => array( __( 'Last Name', 'surveyjs' ), true ),
                );
                if ( 'first-middle-last' !== $format ) {
                    unset( $parts['middle'] );
                }
                return self::parts( $field, $parts, $base, $common, ' ', $taken, $refs, $notes );

            case 'address':
                $parts = array(
                    'address1' => array( __( 'Address Line 1', 'surveyjs' ), true ),
                    'address2' => array( __( 'Address Line 2', 'surveyjs' ), false ),
                    'city'     => array( __( 'City', 'surveyjs' ), true ),
                    'state'    => array( __( 'State / Province / Region', 'surveyjs' ), true ),
                    'postal'   => array( __( 'Zip / Postal Code', 'surveyjs' ), true ),
                    'country'  => array( __( 'Country', 'surveyjs' ), true ),
                );
                foreach ( array( 'address2', 'postal', 'country' ) as $part ) {
                    if ( ! empty( $field[ $part . '_hide' ] ) ) {
                        unset( $parts[ $part ] );
                    }
                }
                if ( 'us' === ( $field['scheme'] ?? $field['format'] ?? 'us' ) ) {
                    unset( $parts['country'] );
                }
                return self::parts( $field, $parts, $base, $common, ', ', $taken, $refs, $notes );

            case 'html':
                return $html( (string) ( $field['code'] ?? '' ) );

            case 'content':
                return $html( (string) ( $field['content'] ?? '' ) );

            case 'divider':
                $divider = empty( $field['hide_divider_line'] ) ? '<hr>' : '';
                if ( '' !== $label ) {
                    $divider .= '<h3>' . esc_html( $label ) . '</h3>';
                }
                if ( '' !== self::text( $field['description'] ?? '' ) ) {
                    $divider .= '<p>' . esc_html( self::text( $field['description'] ) ) . '</p>';
                }
                return $html( $divider );
        }
        return null;
    }

    private static function parts( $field, $parts, $base, $common, $separator, &$taken, &$refs, &$notes ) {
        $prefix = self::unique_name( $base, $taken );
        $questions = array();
        $names = array();
        foreach ( $parts as $part => list( $title, $required ) ) {
            $question = array( 'type' => 'text', 'name' => self::unique_name( $prefix . '_' . $part, $taken ), 'title' => $title );
            if ( ! $questions && isset( $common['description'] ) ) {
                $question['description'] = $common['description'];
            }
            if ( $required && ! empty( $common['isRequired'] ) ) {
                $question['isRequired'] = true;
            }
            if ( $questions && ! in_array( $part, array( 'address2', 'city', 'postal' ), true ) ) {
                $question['startWithNewLine'] = false;
            }
            $question += self::placeholder_default( $field, $part . '_placeholder', $part . '_default', $common['title'], $notes );
            $questions[] = $question;
            $names[ $part ] = $question['name'];
        }
        $refs[ (string) ( $field['id'] ?? '' ) ] = array( 'parts' => $names, 'separator' => $separator, 'email' => false );
        return $questions;
    }

    private static function choices( $field, $label, &$notes ) {
        $type = (string) $field['type'];
        $images = ! empty( $field['choices_images'] ) && 'select' !== $type;
        $props = array();
        $choices = array();
        $defaults = array();
        foreach ( (array) ( $field['choices'] ?? array() ) as $choice ) {
            if ( ! is_array( $choice ) ) {
                continue;
            }
            $text = self::text( $choice['label'] ?? '' );
            $value = self::choice_value( $field, $choice );
            if ( ! empty( $choice['other'] ) && 'select' !== $type ) {
                $props['showOtherItem'] = true;
                $props['otherText'] = $text;
                if ( '' !== self::text( $field['other_placeholder'] ?? '' ) ) {
                    $props['otherPlaceholder'] = self::text( $field['other_placeholder'] );
                }
                continue;
            }
            $item = $value === $text ? $text : array( 'value' => $value, 'text' => $text );
            if ( $images ) {
                $item = array( 'value' => $value, 'text' => $text, 'imageLink' => esc_url_raw( (string) ( $choice['image'] ?? '' ) ) );
            }
            $choices[] = $item;
            if ( ! empty( $choice['default'] ) ) {
                $defaults[] = $value;
            }
        }

        $multiple = 'checkbox' === $type || ( 'select' === $type && ! empty( $field['multiple'] ) );
        if ( $images ) {
            $props = array( 'type' => 'imagepicker', 'showLabel' => true ) + ( $multiple ? array( 'multiSelect' => true ) : array() ) + $props;
            if ( isset( $props['showOtherItem'] ) ) {
                unset( $props['showOtherItem'], $props['otherText'], $props['otherPlaceholder'] );
                /* translators: %s: field label */
                $notes[] = sprintf( __( 'The "Other" choice of "%s" was not imported: image choices do not support it.', 'surveyjs' ), $label );
            }
        } elseif ( 'select' === $type ) {
            $props['type'] = $multiple ? 'tagbox' : 'dropdown';
            if ( '' !== self::text( $field['placeholder'] ?? '' ) ) {
                $props['placeholder'] = self::text( $field['placeholder'] );
            }
        } else {
            $props['type'] = 'checkbox' === $type ? 'checkbox' : 'radiogroup';
            $columns = (string) ( $field['input_columns'] ?? '' );
            if ( 'inline' === $columns ) {
                $props['colCount'] = 0;
            } elseif ( in_array( $columns, array( '2', '3' ), true ) ) {
                $props['colCount'] = (int) $columns;
            }
        }
        if ( ! empty( $field['choices_icons'] ) ) {
            /* translators: %s: field label */
            $notes[] = sprintf( __( 'The icon choices of "%s" were imported as plain choices.', 'surveyjs' ), $label );
        }
        if ( ! empty( $field['random'] ) ) {
            $props['choicesOrder'] = 'random';
        }
        if ( $multiple && (int) ( $field['choice_limit'] ?? 0 ) > 0 ) {
            $props['maxSelectedChoices'] = (int) $field['choice_limit'];
        }
        $props['choices'] = $choices;
        if ( $defaults ) {
            $props['defaultValue'] = $multiple ? $defaults : $defaults[0];
        }
        return $props;
    }

    private static function choice_value( $field, $choice ) {
        return ! empty( $field['show_values'] ) && '' !== self::text( $choice['value'] ?? '' ) ? self::text( $choice['value'] ) : self::text( $choice['label'] ?? '' );
    }

    private static function choice_values( $field ) {
        $type = (string) ( $field['type'] ?? '' );
        if ( ! in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
            return null;
        }
        $values = array();
        foreach ( array_filter( (array) ( $field['choices'] ?? array() ), 'is_array' ) as $key => $choice ) {
            $values[ (string) $key ] = ! empty( $choice['other'] ) && 'select' !== $type ? 'other' : self::choice_value( $field, $choice );
        }
        return $values;
    }

    private static function placeholder_default( $field, $placeholder_key, $default_key, $label, &$notes ) {
        $props = array();
        if ( '' !== self::text( $field[ $placeholder_key ] ?? '' ) ) {
            $props['placeholder'] = self::text( $field[ $placeholder_key ] );
        }
        $default = self::text( $field[ $default_key ] ?? '' );
        if ( preg_match( '/\{\w+[^}]*\}/', $default ) ) {
            /* translators: 1: field label, 2: default value */
            $notes[] = sprintf( __( 'The default value of "%1$s" (%2$s) uses a smart tag and was not imported.', 'surveyjs' ), $label, $default );
        } elseif ( '' !== $default ) {
            $props['defaultValue'] = $default;
        }
        return $props;
    }

    private static function confirmation( $confirmations, $title, $refs, &$json, &$notes ) {
        $confirmations = array_filter( $confirmations, 'is_array' );
        if ( ! $confirmations ) {
            return;
        }
        ksort( $confirmations, SORT_NUMERIC );
        self::add_confirmation( array_shift( $confirmations ), '', $title, $refs, $json, $notes );
        krsort( $confirmations, SORT_NUMERIC );
        foreach ( $confirmations as $confirmation ) {
            $expression = ! empty( $confirmation['conditional_logic'] ) ? self::expression( $confirmation, $refs ) : '';
            if ( '' === $expression ) {
                /* translators: %s: confirmation name */
                $notes[] = sprintf( __( 'The confirmation "%s" was not imported: its conditional logic is not supported.', 'surveyjs' ), $confirmation['name'] ?? '' );
                continue;
            }
            self::add_confirmation( $confirmation, $expression, $title, $refs, $json, $notes );
        }
    }

    private static function add_confirmation( $confirmation, $expression, $title, $refs, &$json, &$notes ) {
        $type = (string) ( $confirmation['type'] ?? 'message' );
        if ( 'message' === $type ) {
            if ( '' === trim( (string) ( $confirmation['message'] ?? '' ) ) ) {
                return;
            }
            $html = trim( wp_kses_post( wpautop( self::tags( $confirmation['message'], $refs, $title, true, $notes ) ) ) );
            if ( '' === $expression ) {
                $json['completedHtml'] = $html;
            } else {
                $json['completedHtmlOnCondition'][] = array( 'expression' => $expression, 'html' => $html );
            }
            return;
        }
        $url = 'page' === $type ? (string) get_permalink( (int) ( $confirmation['page'] ?? 0 ) ) : trim( (string) ( $confirmation['redirect'] ?? '' ) );
        $url = self::tags( $url, $refs, $title, true, $notes );
        if ( ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) {
            /* translators: %s: confirmation name */
            $notes[] = sprintf( __( 'The redirect of the confirmation "%s" was not imported: it has no valid URL.', 'surveyjs' ), $confirmation['name'] ?? '' );
            return;
        }
        if ( '' === $expression ) {
            $json['navigateToUrl'] = $url;
        } else {
            $json['navigateToUrlOnCondition'][] = array( 'expression' => $expression, 'url' => $url );
        }
    }

    private static function expression( $item, $refs ) {
        $groups = array();
        foreach ( (array) ( $item['conditionals'] ?? array() ) as $group ) {
            $rules = array();
            foreach ( array_filter( (array) $group, 'is_array' ) as $rule ) {
                $rule = self::rule( $rule, $refs );
                if ( '' === $rule ) {
                    return '';
                }
                $rules[] = $rule;
            }
            if ( $rules ) {
                $groups[] = count( $rules ) > 1 ? '(' . implode( ' and ', $rules ) . ')' : $rules[0];
            }
        }
        if ( ! $groups ) {
            return '';
        }
        $expression = implode( ' or ', $groups );
        if ( in_array( $item['conditional_type'] ?? '', array( 'hide', 'stop' ), true ) ) {
            return '!(' . $expression . ')';
        }
        return $expression;
    }

    private static function rule( $rule, $refs ) {
        $ref = $refs[ (string) ( $rule['field'] ?? '' ) ] ?? null;
        if ( ! $ref || ! isset( $ref['parts']['value'] ) ) {
            return '';
        }
        $name = '{' . $ref['parts']['value'] . '}';
        $operator = (string) ( $rule['operator'] ?? '' );
        if ( 'e' === $operator || '!e' === $operator ) {
            return $name . ( 'e' === $operator ? ' empty' : ' notempty' );
        }
        $value = (string) ( $rule['value'] ?? '' );
        if ( isset( $ref['choices'] ) ) {
            if ( ! isset( $ref['choices'][ $value ] ) ) {
                return '';
            }
            $value = $ref['choices'][ $value ];
        }
        if ( in_array( $operator, array( '>', '<' ), true ) ) {
            return is_numeric( $value ) ? $name . ' ' . $operator . ' ' . ( 0 + $value ) : '';
        }
        if ( false === strpos( $value, "'" ) ) {
            $quoted = "'" . $value . "'";
        } elseif ( false === strpos( $value, '"' ) ) {
            $quoted = '"' . $value . '"';
        } else {
            return '';
        }
        $operators = array(
            '=='  => ! empty( $ref['multiple'] ) ? 'contains' : '=',
            '!='  => ! empty( $ref['multiple'] ) ? 'notcontains' : '<>',
            'c'   => 'contains',
            '!c'  => 'notcontains',
        );
        return isset( $operators[ $operator ] ) ? $name . ' ' . $operators[ $operator ] . ' ' . $quoted : '';
    }

    private static function notification_settings( $form_settings, $refs, &$notes ) {
        $notifications = array_filter( (array) ( $form_settings['notifications'] ?? array() ), 'is_array' );
        ksort( $notifications, SORT_NUMERIC );
        $notification = (array) array_shift( $notifications );
        $settings = array(
            'notificationEnabled' => ! empty( $form_settings['notification_enable'] ) && ! empty( $notification['email'] ) && '0' !== (string) ( $notification['enable'] ?? '1' ),
        );
        if ( ! $notification ) {
            return $settings;
        }
        $settings['notificationTo'] = self::tags( $notification['email'] ?? '', $refs, '', false, $notes );
        $settings['notificationSubject'] = self::tags( $notification['subject'] ?? '', $refs, '', false, $notes );
        $settings['notificationFromName'] = self::tags( $notification['sender_name'] ?? '', $refs, '', false, $notes );
        $settings['notificationFromEmail'] = self::tags( $notification['sender_address'] ?? '', $refs, '', false, $notes );
        $settings['notificationMessage'] = self::tags( $notification['message'] ?? '', $refs, '', false, $notes );
        $settings['notificationHtml'] = (bool) preg_match( '/<[a-z][^>]*>/i', $settings['notificationMessage'] );
        if ( ! empty( $notification['carboncopy'] ) ) {
            /* translators: %s: email addresses */
            $notes[] = sprintf( __( 'CC "%s" was not imported: add these addresses to Send To if needed.', 'surveyjs' ), $notification['carboncopy'] );
        }
        if ( ! empty( $notification['conditional_logic'] ) ) {
            $notes[] = __( 'The conditional logic of the notification was not imported.', 'surveyjs' );
        }
        $reply_to = trim( (string) ( $notification['replyto'] ?? '' ) );
        if ( '' !== $reply_to ) {
            $settings['notificationReplyTo'] = self::email_field( $reply_to, $refs );
            if ( '' === $settings['notificationReplyTo'] ) {
                /* translators: %s: Reply-To value */
                $notes[] = sprintf( __( 'Reply-To "%s" was not imported: Reply-To can only be an Email field.', 'surveyjs' ), $reply_to );
            }
        }

        foreach ( $notifications as $other ) {
            $recipient = self::email_field( $other['email'] ?? '', $refs );
            if ( empty( $settings['autoReplyEnabled'] ) && '' !== $recipient && in_array( $settings['notificationReplyTo'] ?? '', array( '', $recipient ), true ) ) {
                $settings['notificationReplyTo'] = $recipient;
                $settings['autoReplyEnabled'] = '0' !== (string) ( $other['enable'] ?? '1' );
                $settings['autoReplySubject'] = self::tags( $other['subject'] ?? '', $refs, '', false, $notes );
                $settings['autoReplyMessage'] = self::tags( $other['message'] ?? '', $refs, '', false, $notes );
                $settings['autoReplyHtml'] = (bool) preg_match( '/<[a-z][^>]*>/i', $settings['autoReplyMessage'] );
                /* translators: %s: notification name */
                $notes[] = sprintf( __( 'The notification "%s" was imported as the auto-reply.', 'surveyjs' ), $other['notification_name'] ?? '' );
                continue;
            }
            /* translators: %s: notification name */
            $notes[] = sprintf( __( 'The notification "%s" was not imported: a form has one email notification and one auto-reply.', 'surveyjs' ), $other['notification_name'] ?? '' );
        }
        return $settings;
    }

    private static function tags( $text, $refs, $title, $html, &$notes ) {
        return preg_replace_callback( '/\{(\w+)(?:="([^"]*)"|\s[^{}]*)?\}/', function ( $match ) use ( $refs, $title, $html, &$notes ) {
            $tag = $match[1];
            if ( in_array( $tag, self::FIELD_TAGS, true ) && isset( $match[2] ) ) {
                $names = self::field_names( $match[2], $refs );
                if ( $names ) {
                    return implode( $refs[ strtok( $match[2], '|' ) ]['separator'], array_map( function ( $name ) use ( $html ) {
                        return $html ? '{' . $name . '}' : '{field:' . $name . '}';
                    }, $names ) );
                }
            } elseif ( ! isset( $match[2] ) && $html && in_array( $tag, array( 'admin_email', 'site_name', 'form_name' ), true ) ) {
                $values = array( 'admin_email' => get_option( 'admin_email' ), 'site_name' => get_bloginfo( 'name' ), 'form_name' => $title );
                return esc_html( $values[ $tag ] );
            } elseif ( $match[0] === '{' . $tag . '}' && ! $html && in_array( $tag, array( 'all_fields', 'admin_email', 'site_name', 'form_name' ), true ) ) {
                return $match[0];
            }
            $notes[] = $html
                /* translators: %s: WPForms smart tag */
                ? sprintf( __( 'Smart tag %s in the confirmation message has no equivalent and was removed.', 'surveyjs' ), $match[0] )
                /* translators: %s: WPForms smart tag */
                : sprintf( __( 'Smart tag %s has no equivalent and was kept as text.', 'surveyjs' ), $match[0] );
            return $html ? '' : $match[0];
        }, (string) $text );
    }

    private static function field_names( $field_id, $refs ) {
        list( $id, $part ) = array_pad( explode( '|', $field_id, 2 ), 2, '' );
        if ( ! isset( $refs[ $id ] ) ) {
            return array();
        }
        $parts = $refs[ $id ]['parts'];
        return isset( $parts[ $part ] ) ? array( $parts[ $part ] ) : array_values( $parts );
    }

    private static function email_field( $text, $refs ) {
        if ( ! preg_match( '/^\s*\{(?:' . implode( '|', self::FIELD_TAGS ) . ')="(\d+)"\}\s*$/', (string) $text, $match ) ) {
            return '';
        }
        return ! empty( $refs[ $match[1] ]['email'] ) ? $refs[ $match[1] ]['parts']['value'] : '';
    }

    private static function unique_name( $text, &$taken ) {
        $base = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( remove_accents( $text ) ) ), '_' );
        $base = '' !== $base ? substr( $base, 0, 40 ) : 'field';
        $name = $base;
        for ( $i = 2; in_array( $name, $taken, true ); $i++ ) {
            $name = $base . '_' . $i;
        }
        $taken[] = $name;
        return $name;
    }

    private static function text( $value ) {
        return is_scalar( $value ) ? trim( wp_strip_all_tags( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) ) ) : '';
    }
}
