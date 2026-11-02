<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Fluent_Forms_Importer extends SurveyJS_Importer {

    const SKIPPED_ENTRY_STATUSES = array( 'spam', 'trashed' );

    const DEFAULT_REQUIRED_MESSAGE = 'This field is required';

    const NAME_PARTS = array( 'first_name' => 'first', 'middle_name' => 'middle', 'last_name' => 'last' );

    const ADDRESS_PARTS = array( 'address_line_1' => 'line1', 'address_line_2' => 'line2', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' );

    const DATE_TOKENS = array( 'H' => 'H', 'h' => 'g', 'G' => 'h', 'i' => 'i', 'S' => 's', 's' => 's', 'K' => 'A', 'd' => 'd', 'j' => 'j', 'm' => 'm', 'n' => 'n', 'Y' => 'Y', 'y' => 'y', 'F' => 'F', 'M' => 'M', 'D' => 'D', 'l' => 'l' );

    private $title = '';
    private $taken = array();
    private $refs = array();
    private $conditions = array();
    private $skipped = array();
    private $notes = array();
    private $json = array();
    private $titles = array();

    public static function source() {
        return 'fluentform';
    }

    public static function is_available() {
        return defined( 'FLUENTFORM_VERSION' ) && self::table_exists( 'fluentform_forms' );
    }

    public static function forms() {
        global $wpdb;
        $forms = array();
        if ( ! self::is_available() ) {
            return $forms;
        }
        foreach ( $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}fluentform_forms ORDER BY id" ) as $row ) {
            $forms[ (int) $row->id ] = $row->title;
        }
        return $forms;
    }

    protected static function convert_form( $form_id ) {
        global $wpdb;
        $row = self::is_available() ? $wpdb->get_row( $wpdb->prepare( "SELECT id, title, form_fields FROM {$wpdb->prefix}fluentform_forms WHERE id = %d", $form_id ) ) : null;
        $fields = $row ? json_decode( (string) $row->form_fields, true ) : null;
        if ( ! is_array( $fields ) ) {
            return new WP_Error( 'not_found', __( 'The Fluent Forms form was not found.', 'surveyjs' ) );
        }
        $metas = array();
        foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, value FROM {$wpdb->prefix}fluentform_form_meta WHERE form_id = %d ORDER BY id", $form_id ) ) as $meta ) {
            $value = json_decode( (string) $meta->value, true );
            $metas[ $meta->meta_key ][] = null === $value ? $meta->value : $value;
        }
        return array( 'name' => $row->title ) + ( new self() )->convert( $fields, $metas, $row->title );
    }

    public static function entries_available() {
        return self::is_available() && self::table_exists( 'fluentform_submissions' );
    }

    protected static function entries( $form_id, $form ) {
        global $wpdb;
        $questions = array_column( SurveyJS_Survey_Values::survey_questions( $form['json'] ), null, 'name' );
        $statuses = implode( ', ', array_fill( 0, count( self::SKIPPED_ENTRY_STATUSES ), '%s' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, response FROM {$wpdb->prefix}fluentform_submissions WHERE form_id = %d AND ( status IS NULL OR status NOT IN ( $statuses ) ) ORDER BY id",
            array_merge( array( $form_id ), self::SKIPPED_ENTRY_STATUSES )
        ) );
        $entries = array();
        foreach ( $rows as $row ) {
            $data = array();
            foreach ( (array) json_decode( (string) $row->response, true ) as $input => $value ) {
                $ref = $form['refs'][ (string) $input ] ?? null;
                if ( ! $ref ) {
                    continue;
                }
                foreach ( $ref['parts'] as $part => $name ) {
                    if ( isset( $questions[ $name ] ) ) {
                        $data += self::entry_field_value( $questions[ $name ], $ref, 'value' === $part ? $value : ( is_array( $value ) ? ( $value[ $part ] ?? '' ) : '' ) );
                    }
                }
            }
            if ( $data ) {
                $entries[ (int) $row->id ] = $data;
            }
        }
        return $entries;
    }

    private static function entry_field_value( $question, $ref, $value ) {
        $name = $question['name'];
        switch ( $ref['kind'] ) {
            case 'date':
                $value = is_scalar( $value ) ? trim( (string) $value ) : '';
                $date = '' !== $value && '' !== $ref['format'] ? date_create_from_format( '!' . $ref['format'], $value ) : false;
                $formats = array( 'date' => 'Y-m-d', 'time' => 'H:i', 'datetime-local' => 'Y-m-d\TH:i' );
                return '' === $value ? array() : array( $name => $date ? $date->format( $formats[ $question['inputType'] ] ) : $value );

            case 'file':
                $files = array();
                foreach ( array_filter( (array) $value, 'is_string' ) as $url ) {
                    $url = esc_url_raw( $url );
                    if ( '' !== $url ) {
                        $files[] = array(
                            'name'    => basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
                            'type'    => (string) ( wp_check_filetype( $url )['type'] ?? '' ),
                            'content' => $url,
                        );
                    }
                }
                return $files ? array( $name => $files ) : array();

            case 'grid':
                $rows = array_column( $question['rows'] ?? array(), 'value' );
                $value = array_intersect_key( array_filter( (array) $value, 'is_scalar' ), array_flip( $rows ) );
                return $value ? array( $name => array_map( 'strval', $value ) ) : array();

            case 'ranking':
                $value = array_values( array_map( 'strval', array_filter( (array) $value, 'is_scalar' ) ) );
                return $value ? array( $name => $value ) : array();

            case 'repeater':
                $rows = array();
                foreach ( array_filter( (array) $value, 'is_array' ) as $row ) {
                    $item = array();
                    foreach ( array_values( $row ) as $index => $cell ) {
                        if ( isset( $ref['columns'][ $index ] ) && is_scalar( $cell ) && '' !== trim( (string) $cell ) ) {
                            $item[ $ref['columns'][ $index ] ] = (string) $cell;
                        }
                    }
                    if ( $item ) {
                        $rows[] = $item;
                    }
                }
                return $rows ? array( $name => $rows ) : array();

            case 'choice':
                if ( '' !== $ref['other_prefix'] ) {
                    $value = array_map( function ( $item ) use ( $ref ) {
                        return is_string( $item ) && 0 === strpos( $item, $ref['other_prefix'] ) ? substr( $item, strlen( $ref['other_prefix'] ) ) : $item;
                    }, (array) $value );
                }
                return self::entry_value( $question, $value );
        }
        return self::entry_value( $question, $value );
    }

    public function convert( $fields, $metas, $title ) {
        $this->title = $title;
        $pages = array( array( 'name' => 'page1', 'elements' => array() ) );
        $steps = (array) ( $fields['stepsWrapper'] ?? array() );

        foreach ( (array) ( $fields['fields'] ?? array() ) as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }
            if ( 'form_step' === ( $field['element'] ?? '' ) ) {
                $pages[] = array( 'name' => 'page' . ( count( $pages ) + 1 ), 'elements' => array() );
                $this->button_text( 'pageNextText', $field['settings']['next_btn'] ?? null );
                $this->button_text( 'pagePrevText', $field['settings']['prev_btn'] ?? null );
                continue;
            }
            array_push( $pages[ count( $pages ) - 1 ]['elements'], ...$this->elements( $field ) );
        }
        $this->button_text( 'completeText', $fields['submitButton']['settings']['button_ui'] ?? null );

        if ( count( $pages ) > 1 && $steps ) {
            foreach ( array_values( (array) ( $steps['stepStart']['settings']['step_titles'] ?? array() ) ) as $index => $step_title ) {
                if ( isset( $pages[ $index ] ) && '' !== self::text( $step_title ) ) {
                    $pages[ $index ]['title'] = self::text( $step_title );
                }
            }
            $indicator = (string) ( $steps['stepStart']['settings']['progress_indicator'] ?? '' );
            if ( in_array( $indicator, array( 'progress-bar', 'steps' ), true ) ) {
                $this->json['showProgressBar'] = true;
                if ( 'steps' === $indicator ) {
                    $this->json['progressBarType'] = 'buttons';
                }
            }
            $this->button_text( 'pagePrevText', $steps['stepEnd']['settings']['prev_btn'] ?? null );
        }

        $pages = array_values( array_filter( $pages, function ( $page ) {
            return ! empty( $page['elements'] );
        } ) );
        foreach ( $pages as &$page ) {
            $page['elements'] = $this->apply_conditions( $page['elements'] );
        }
        unset( $page );
        $json = array();
        if ( count( $pages ) > 1 ) {
            $json['pages'] = $pages;
        } else {
            $json['elements'] = $pages ? $pages[0]['elements'] : array();
            unset( $this->json['showProgressBar'], $this->json['progressBarType'], $this->json['pageNextText'], $this->json['pagePrevText'] );
        }
        $json += $this->json;

        $form_settings = (array) ( $metas['formSettings'][0] ?? array() );
        $placements = array( 'left' => 'left', 'bottom' => 'bottom' );
        $placement = (string) ( $form_settings['layout']['labelPlacement'] ?? '' );
        if ( isset( $placements[ $placement ] ) ) {
            $json['questionTitleLocation'] = $placements[ $placement ];
        }
        $this->confirmation( $form_settings, (array) ( $metas['confirmations'] ?? array() ), $json );
        foreach ( (array) ( $form_settings['restrictions'] ?? array() ) as $key => $restriction ) {
            if ( is_array( $restriction ) && ! empty( $restriction['enabled'] ) ) {
                /* translators: %s: Fluent Forms restriction key */
                $this->notes[] = sprintf( __( 'The form restriction "%s" was not imported.', 'surveyjs' ), $key );
            }
        }
        if ( 'yes' === ( $metas['is_conversion_form'][0] ?? '' ) ) {
            $this->notes[] = __( 'The conversational form was imported as a regular form.', 'surveyjs' );
        }

        return array(
            'json'     => $json,
            'settings' => $this->notification_settings( array_filter( (array) ( $metas['notifications'] ?? array() ), 'is_array' ) ),
            'skipped'  => $this->skipped,
            'notes'    => $this->notes,
            'refs'     => $this->refs,
        );
    }

    private function button_text( $property, $button ) {
        if ( ! isset( $this->json[ $property ] ) && is_array( $button ) && '' !== self::text( $button['text'] ?? '' ) ) {
            $this->json[ $property ] = self::text( $button['text'] );
        }
    }

    private function elements( $field ) {
        $type = (string) ( $field['element'] ?? '' );
        $settings = (array) ( $field['settings'] ?? array() );
        $attributes = (array) ( $field['attributes'] ?? array() );
        $input = (string) ( $attributes['name'] ?? '' );
        $label = self::text( $settings['label'] ?? '' );
        $title = '' !== $label ? $label : self::text( $settings['admin_field_label'] ?? '' );
        $title = '' !== $title ? $title : $input;
        $common = array( 'title' => $title );
        if ( '' === $label || 'hide_label' === ( $settings['label_placement'] ?? '' ) ) {
            $common['titleLocation'] = 'hidden';
        } elseif ( 'left' === ( $settings['label_placement'] ?? '' ) ) {
            $common['titleLocation'] = 'left';
        }
        if ( '' !== self::text( $settings['help_message'] ?? '' ) ) {
            $common['description'] = self::text( $settings['help_message'] );
        }
        $common += self::required( $settings );
        $question = function ( $survey_type, $props = array() ) use ( $title, $common, $field ) {
            $element = array_merge( array( 'type' => $survey_type, 'name' => $this->unique_name( $title ) ), $common, $props );
            $this->titles[ $element['name'] ] = $title;
            $this->condition( $element['name'], $field );
            return $element;
        };
        $html = function ( $markup ) use ( $title, $field ) {
            $element = array( 'type' => 'html', 'name' => $this->unique_name( '' !== $title ? $title : 'html' ), 'html' => wp_kses_post( $markup ) );
            $this->condition( $element['name'], $field );
            return array( $element );
        };

        switch ( $type ) {
            case 'input_text':
            case 'input_email':
            case 'input_url':
            case 'input_password':
            case 'phone':
            case 'color-picker':
            case 'input_number':
                $input_types = array( 'input_email' => 'email', 'input_url' => 'url', 'input_password' => 'password', 'phone' => 'tel', 'color-picker' => 'color', 'input_number' => 'number' );
                $props = isset( $input_types[ $type ] ) ? array( 'inputType' => $input_types[ $type ] ) : array();
                $props += $this->placeholder_default( $attributes, $title );
                if ( (int) ( $attributes['maxlength'] ?? 0 ) > 0 ) {
                    $props['maxLength'] = (int) $attributes['maxlength'];
                }
                if ( 'input_number' === $type ) {
                    foreach ( array( 'min', 'max' ) as $limit ) {
                        if ( is_numeric( $settings['validation_rules'][ $limit ]['value'] ?? '' ) ) {
                            $props[ $limit ] = 0 + $settings['validation_rules'][ $limit ]['value'];
                        }
                    }
                    if ( is_numeric( $props['defaultValue'] ?? '' ) ) {
                        $props['defaultValue'] = 0 + $props['defaultValue'];
                    }
                    if ( is_numeric( $settings['number_step'] ?? '' ) ) {
                        $props['step'] = 0 + $settings['number_step'];
                    }
                    if ( ! empty( $settings['calculation_settings']['status'] ) ) {
                        /* translators: %s: field label */
                        $this->notes[] = sprintf( __( 'The calculation of "%s" was not imported.', 'surveyjs' ), $title );
                    }
                }
                $props += $this->mask( (string) ( $attributes['data-mask'] ?? '' ), $title );
                if ( '' !== self::text( $settings['prefix_label'] ?? '' ) || '' !== self::text( $settings['suffix_label'] ?? '' ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The prefix and suffix of "%s" were not imported.', 'surveyjs' ), $title );
                }
                if ( 'yes' === ( $settings['is_unique'] ?? '' ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The unique value check of "%s" was not imported.', 'surveyjs' ), $title );
                }
                $element = $question( 'text', $props );
                $this->ref( $input, array( 'value' => $element['name'] ), 'input_number' === $type ? 'number' : 'text', array( 'email' => 'input_email' === $type ) );
                return array( $element );

            case 'textarea':
            case 'rich_text_input':
                $props = $this->placeholder_default( $attributes, $title );
                if ( (int) ( $attributes['maxlength'] ?? 0 ) > 0 ) {
                    $props['maxLength'] = (int) $attributes['maxlength'];
                }
                if ( (int) ( $attributes['rows'] ?? 0 ) > 0 ) {
                    $props['rows'] = (int) $attributes['rows'];
                }
                $element = $question( 'comment', $props );
                $this->ref( $input, array( 'value' => $element['name'] ), 'text' );
                return array( $element );

            case 'input_hidden':
                $props = array( 'visible' => false, 'clearIfInvisible' => 'none' ) + $this->placeholder_default( $attributes, $title );
                $element = $question( 'text', $props );
                unset( $element['titleLocation'] );
                $this->ref( $input, array( 'value' => $element['name'] ), 'text' );
                return array( $element );

            case 'input_date':
                $format = (string) ( $settings['date_format'] ?? 'd/m/Y' );
                $has_time = (bool) preg_match( '/[HhGiK]/', $format );
                $has_date = (bool) preg_match( '/[djmnYyFM]/', $format );
                $element = $question( 'text', array( 'inputType' => $has_time ? ( $has_date ? 'datetime-local' : 'time' ) : 'date' ) + $this->placeholder_default( array( 'placeholder' => $attributes['placeholder'] ?? '' ), $title ) );
                $this->ref( $input, array( 'value' => $element['name'] ), 'date', array( 'format' => strtr( $format, self::DATE_TOKENS ) ) );
                return array( $element );

            case 'select':
            case 'input_radio':
            case 'input_checkbox':
            case 'multi_select':
                $props = $this->choices( $field, $title );
                $element = $question( $props['type'], $props );
                $this->ref( $input, array( 'value' => $element['name'] ), 'choice', array(
                    'multiple'     => in_array( $element['type'], array( 'checkbox', 'tagbox' ), true ) || ! empty( $element['multiSelect'] ),
                    'other_prefix' => empty( $element['showOtherItem'] ) ? '' : self::other_prefix( $settings ),
                ) );
                return array( $element );

            case 'select_country':
                $element = $question( 'dropdown', $this->country_props( $field ) );
                $this->ref( $input, array( 'value' => $element['name'] ), 'choice' );
                return array( $element );

            case 'input_name':
            case 'address':
                return array( $this->panel( $field, $title, $common ) );

            case 'custom_html':
                return $html( (string) ( $settings['html_codes'] ?? '' ) );

            case 'section_break':
                $markup = '' !== $label ? '<h3>' . esc_html( $label ) . '</h3>' : '';
                if ( '' !== trim( (string) ( $settings['description'] ?? '' ) ) ) {
                    $markup .= wpautop( (string) $settings['description'] );
                }
                return $html( $markup . '<hr>' );

            case 'ratings':
                $options = (array) ( $field['options'] ?? array() );
                $element = $question( 'rating', array( 'rateType' => 'stars', 'rateMax' => max( 2, count( $options ) ) ) );
                $this->ref( $input, array( 'value' => $element['name'] ), 'number' );
                return array( $element );

            case 'net_promoter_score':
                $props = array( 'rateMin' => 0, 'rateMax' => 10 );
                if ( '' !== self::text( $settings['start_text'] ?? '' ) ) {
                    $props['minRateDescription'] = self::text( $settings['start_text'] );
                }
                if ( '' !== self::text( $settings['end_text'] ?? '' ) ) {
                    $props['maxRateDescription'] = self::text( $settings['end_text'] );
                }
                $element = $question( 'rating', $props );
                $this->ref( $input, array( 'value' => $element['name'] ), 'number' );
                return array( $element );

            case 'rangeslider':
                $props = array();
                foreach ( array( 'min' => $attributes['min'] ?? '', 'max' => $attributes['max'] ?? '', 'step' => $settings['number_step'] ?? '' ) as $option => $value ) {
                    if ( is_numeric( $value ) ) {
                        $props[ $option ] = 0 + $value;
                    }
                }
                if ( is_numeric( $attributes['value'] ?? '' ) ) {
                    $props['defaultValue'] = 0 + $attributes['value'];
                }
                $element = $question( 'slider', $props );
                $this->ref( $input, array( 'value' => $element['name'] ), 'number' );
                return array( $element );

            case 'input_file':
            case 'input_image':
                $element = $question( 'file', $this->file_props( $type, $settings ) );
                $this->ref( $input, array( 'value' => $element['name'] ), 'file' );
                return array( $element );

            case 'terms_and_condition':
            case 'gdpr_agreement':
                $markup = (string) ( $settings['tnc_html'] ?? '' );
                if ( isset( $settings['has_checkbox'] ) && empty( $settings['has_checkbox'] ) ) {
                    return $html( $markup );
                }
                if ( preg_match( '/<a\s/i', $markup ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The links in the agreement text of "%s" were imported as plain text.', 'surveyjs' ), $title );
                }
                $text = self::text( $markup );
                $element = $question( 'checkbox', array( 'choices' => array( array( 'value' => 'accepted', 'text' => '' !== $text ? $text : $title ) ) ) );
                $this->ref( $input, array( 'value' => $element['name'] ), 'agreement' );
                return array( $element );

            case 'tabular_grid':
                if ( 'radio' !== ( $settings['tabular_field_type'] ?? 'checkbox' ) ) {
                    break;
                }
                $props = array( 'columns' => self::grid_items( $settings['grid_columns'] ?? array() ), 'rows' => self::grid_items( $settings['grid_rows'] ?? array() ) );
                if ( ! empty( $common['isRequired'] ) && ! empty( $settings['validation_rules']['required']['per_row'] ) ) {
                    $props['eachRowRequired'] = true;
                }
                $element = $question( 'matrix', $props );
                $this->ref( $input, array( 'value' => $element['name'] ), 'grid' );
                return array( $element );

            case 'input_ranking':
                $props = $this->choices( $field, $title );
                $element = $question( 'ranking', array( 'choices' => $props['choices'] ) );
                $this->ref( $input, array( 'value' => $element['name'] ), 'ranking' );
                return array( $element );

            case 'repeater_field':
                return $this->repeater( $field, $question, $input );

            case 'container':
                return $this->container( $field );

            case 'custom_submit_button':
                $this->button_text( 'completeText', $settings['button_ui'] ?? null );
                if ( self::condition_active( $settings['conditional_logics'] ?? null ) ) {
                    $this->notes[] = __( 'The conditional logic of the submit button was not imported.', 'surveyjs' );
                }
                return array();
        }
        $label = '' !== $label ? $label : self::text( $settings['admin_field_label'] ?? '' );
        $this->skipped[] = sprintf( '%s (%s)', '' !== $label ? $label : $type, $type );
        return array();
    }

    private function container( $field ) {
        $columns = array_values( array_filter( (array) ( $field['columns'] ?? array() ), 'is_array' ) );
        $groups = array();
        foreach ( $columns as $column ) {
            $elements = array();
            foreach ( array_filter( (array) ( $column['fields'] ?? array() ), 'is_array' ) as $child ) {
                array_push( $elements, ...$this->elements( $child ) );
            }
            $groups[] = array( 'elements' => $elements, 'width' => $column['width'] ?? '' );
        }
        $groups = array_values( array_filter( $groups, function ( $group ) {
            return ! empty( $group['elements'] );
        } ) );
        $widths = array_unique( array_map( 'strval', array_column( $groups, 'width' ) ) );
        $nested = count( $groups ) > 1 && max( array_map( function ( $group ) {
            return count( $group['elements'] );
        }, $groups ) ) > 1;
        $result = array();
        foreach ( $groups as $index => $group ) {
            $items = $group['elements'];
            if ( $nested ) {
                $items = array( array( 'type' => 'panel', 'name' => $this->unique_name( 'column' ), 'elements' => $items ) );
            }
            foreach ( $items as $position => $item ) {
                if ( $index > 0 && 0 === $position ) {
                    $item['startWithNewLine'] = false;
                }
                if ( count( $widths ) > 1 && is_numeric( $group['width'] ) && ( $nested || 1 === count( $group['elements'] ) ) ) {
                    $item['width'] = round( (float) $group['width'], 2 ) . '%';
                }
                $this->condition( $item['name'], $field );
                $result[] = $item;
            }
        }
        return $result;
    }

    private function panel( $field, $title, $common ) {
        $type = (string) $field['element'];
        $parts = 'address' === $type ? self::ADDRESS_PARTS : self::NAME_PARTS;
        $sub_fields = (array) ( $field['fields'] ?? array() );
        if ( 'address' === $type ) {
            $order = array_column( array_filter( (array) ( $field['settings']['field_order'] ?? array() ), 'is_array' ), 'value' );
            $sub_fields = array_merge( array_intersect_key( array_flip( $order ), $sub_fields ), $sub_fields );
        }
        $label = self::text( $field['settings']['label'] ?? '' );
        $panel = array( 'type' => 'panel', 'name' => $this->unique_name( $title ) );
        if ( '' !== $label && 'hidden' !== ( $common['titleLocation'] ?? '' ) ) {
            $panel['title'] = $label;
        }
        if ( isset( $common['description'] ) ) {
            $panel['description'] = $common['description'];
        }
        $panel['elements'] = array();
        $names = array();
        foreach ( $sub_fields as $key => $sub ) {
            if ( ! isset( $parts[ $key ] ) || ! is_array( $sub ) || ( isset( $sub['settings']['visible'] ) && ! $sub['settings']['visible'] ) ) {
                continue;
            }
            $sub_label = self::text( $sub['settings']['label'] ?? '' );
            $question = array( 'type' => 'country' === $key ? 'dropdown' : 'text', 'name' => $this->unique_name( $panel['name'] . '_' . $parts[ $key ] ), 'title' => '' !== $sub_label ? $sub_label : ucfirst( str_replace( '_', ' ', $key ) ) );
            if ( '' === $sub_label || 'hide_label' === ( $sub['settings']['label_placement'] ?? '' ) ) {
                $question['titleLocation'] = 'hidden';
            }
            if ( '' !== self::text( $sub['settings']['help_message'] ?? '' ) ) {
                $question['description'] = self::text( $sub['settings']['help_message'] );
            }
            $question += self::required( (array) ( $sub['settings'] ?? array() ) );
            if ( $panel['elements'] && ( 'input_name' === $type || in_array( $key, array( 'state', 'country' ), true ) ) ) {
                $question['startWithNewLine'] = false;
            }
            if ( 'country' === $key ) {
                $question += $this->country_props( $sub );
            } else {
                $question += $this->placeholder_default( (array) ( $sub['attributes'] ?? array() ), $question['title'] );
            }
            $panel['elements'][] = $question;
            $this->titles[ $question['name'] ] = $question['title'];
            $names[ $key ] = $question['name'];
        }
        $this->condition( $panel['name'], $field );
        $this->ref( (string) ( $field['attributes']['name'] ?? '' ), $names, 'text', array( 'separator' => 'address' === $type ? ', ' : ' ' ) );
        return $panel;
    }

    private function repeater( $field, $question, $input ) {
        $columns = array();
        $names = array();
        foreach ( array_values( array_filter( (array) ( $field['fields'] ?? array() ), 'is_array' ) ) as $index => $sub ) {
            $sub_type = (string) ( $sub['element'] ?? '' );
            $title = self::text( $sub['settings']['label'] ?? '' );
            $title = '' !== $title ? $title : (string) ( $sub['attributes']['name'] ?? 'column' . ( $index + 1 ) );
            $column = array( 'name' => $this->column_name( $title, $names ), 'title' => $title );
            if ( 'select' === $sub_type ) {
                $column['cellType'] = 'dropdown';
                $column['choices'] = $this->choices( $sub, $title )['choices'];
            } else {
                $column['cellType'] = 'text';
                $input_types = array( 'input_email' => 'email', 'input_number' => 'number' );
                if ( isset( $input_types[ $sub_type ] ) ) {
                    $column['inputType'] = $input_types[ $sub_type ];
                }
            }
            $column += self::required( (array) ( $sub['settings'] ?? array() ) );
            $columns[ $index ] = $column;
        }
        $props = array( 'columns' => array_values( $columns ), 'rowCount' => 1, 'minRowCount' => 1 );
        if ( (int) ( $field['settings']['max_repeat_field'] ?? 0 ) > 0 ) {
            $props['maxRowCount'] = (int) $field['settings']['max_repeat_field'];
        }
        $element = $question( 'matrixdynamic', $props );
        $this->ref( $input, array( 'value' => $element['name'] ), 'repeater', array( 'columns' => array_column( $columns, 'name' ) ) );
        return array( $element );
    }

    private function column_name( $title, &$names ) {
        $base = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( remove_accents( $title ) ) ), '_' );
        $base = '' !== $base ? substr( $base, 0, 40 ) : 'column';
        $name = $base;
        for ( $i = 2; in_array( $name, $names, true ); $i++ ) {
            $name = $base . '_' . $i;
        }
        $names[] = $name;
        return $name;
    }

    private function choices( $field, $label ) {
        $type = (string) $field['element'];
        $settings = (array) ( $field['settings'] ?? array() );
        $attributes = (array) ( $field['attributes'] ?? array() );
        $multiple = 'input_checkbox' === $type || 'multi_select' === $type || ( 'select' === $type && ! empty( $attributes['multiple'] ) );
        $images = ! empty( $settings['enable_image_input'] ) && in_array( $type, array( 'input_radio', 'input_checkbox' ), true );
        $props = array();
        $choices = array();
        foreach ( (array) ( $settings['advanced_options'] ?? array() ) as $option ) {
            if ( ! is_array( $option ) ) {
                continue;
            }
            $text = self::text( $option['label'] ?? '' );
            $value = isset( $option['value'] ) && '' !== (string) $option['value'] ? (string) $option['value'] : $text;
            $item = $value === $text ? $text : array( 'value' => $value, 'text' => $text );
            if ( $images ) {
                $item = array( 'value' => $value, 'text' => $text, 'imageLink' => esc_url_raw( (string) ( $option['image'] ?? '' ) ) );
            }
            $choices[] = $item;
        }

        if ( $images ) {
            $props = array( 'type' => 'imagepicker', 'showLabel' => true ) + ( $multiple ? array( 'multiSelect' => true ) : array() );
        } elseif ( in_array( $type, array( 'select', 'multi_select' ), true ) ) {
            $props['type'] = $multiple ? 'tagbox' : 'dropdown';
            $placeholder = self::text( $settings['placeholder'] ?? '' );
            $placeholder = '' !== $placeholder ? $placeholder : self::text( $attributes['placeholder'] ?? '' );
            if ( '' !== $placeholder ) {
                $props['placeholder'] = $placeholder;
            }
        } else {
            $props['type'] = $multiple ? 'checkbox' : 'radiogroup';
            if ( preg_match( '/\bff_list_(inline|(\d)col)\b/', (string) ( $settings['layout_class'] ?? '' ), $layout ) ) {
                $props['colCount'] = 'inline' === $layout[1] ? 0 : (int) $layout[2];
            }
        }
        if ( 'yes' === ( $settings['enable_other_option'] ?? '' ) && in_array( $type, array( 'input_radio', 'input_checkbox' ), true ) ) {
            if ( $images ) {
                /* translators: %s: field label */
                $this->notes[] = sprintf( __( 'The "Other" choice of "%s" was not imported: image choices do not support it.', 'surveyjs' ), $label );
            } else {
                $props['showOtherItem'] = true;
                $props['otherText'] = self::text( $settings['other_option_label'] ?? '' ) ?: __( 'Other', 'surveyjs' );
                if ( '' !== self::text( $settings['other_option_placeholder'] ?? '' ) ) {
                    $props['otherPlaceholder'] = self::text( $settings['other_option_placeholder'] );
                }
            }
        }
        if ( 'yes' === ( $settings['randomize_options'] ?? '' ) ) {
            $props['choicesOrder'] = 'random';
        }
        if ( 'yes' === ( $settings['enable_option_groups'] ?? '' ) ) {
            /* translators: %s: field label */
            $this->notes[] = sprintf( __( 'The option groups of "%s" were imported as a flat list.', 'surveyjs' ), $label );
        }
        if ( $multiple ) {
            $rules = (array) ( $settings['validation_rules'] ?? array() );
            $max = $rules['max_selection']['value'] ?? $settings['max_selection'] ?? '';
            if ( (int) $max > 0 ) {
                $props['maxSelectedChoices'] = (int) $max;
            }
            if ( (int) ( $rules['min_selection']['value'] ?? 0 ) > 0 ) {
                $props['minSelectedChoices'] = (int) $rules['min_selection']['value'];
            }
        }
        $props['choices'] = $choices;
        $default = $attributes['value'] ?? '';
        $default = array_values( array_filter( array_map( 'strval', array_filter( (array) $default, 'is_scalar' ) ), 'strlen' ) );
        if ( $default ) {
            $props['defaultValue'] = $multiple ? $default : $default[0];
        }
        if ( '' !== trim( (string) ( $settings['dynamic_default_value'] ?? '' ) ) ) {
            /* translators: %s: field label */
            $this->notes[] = sprintf( __( 'The dynamic default value of "%s" was not imported.', 'surveyjs' ), $label );
        }
        return $props;
    }

    private function country_props( $field ) {
        $countries = function_exists( 'getFluentFormCountryList' ) ? (array) getFluentFormCountryList() : array();
        $list = (array) ( $field['settings']['country_list'] ?? array() );
        $active = (string) ( $list['active_list'] ?? 'all' );
        $selected = array_map( 'strval', (array) ( $list[ $active ] ?? array() ) );
        if ( 'visible_list' === $active ) {
            $countries = array_intersect_key( $countries, array_flip( $selected ) );
        } elseif ( 'hidden_list' === $active ) {
            $countries = array_diff_key( $countries, array_flip( $selected ) );
        } elseif ( 'priority_based' === $active ) {
            $countries = array_replace( array_flip( array_intersect( $selected, array_keys( $countries ) ) ), $countries );
        }
        $props = array( 'choices' => array() );
        foreach ( $countries as $code => $name ) {
            $props['choices'][] = array( 'value' => (string) $code, 'text' => self::text( $name ) );
        }
        $placeholder = self::text( $field['attributes']['placeholder'] ?? '' );
        if ( '' !== $placeholder ) {
            $props['placeholder'] = $placeholder;
        }
        if ( '' !== (string) ( $field['attributes']['value'] ?? '' ) ) {
            $props['defaultValue'] = (string) $field['attributes']['value'];
        }
        return $props;
    }

    private function file_props( $type, $settings ) {
        $rules = (array) ( $settings['validation_rules'] ?? array() );
        $props = array( 'storeDataAsText' => false );
        if ( (int) ( $rules['max_file_count']['value'] ?? 1 ) > 1 ) {
            $props['allowMultiple'] = true;
        }
        if ( (int) ( $rules['max_file_size']['value'] ?? 0 ) > 0 ) {
            $props['maxSize'] = (int) $rules['max_file_size']['value'];
        }
        $types = (array) ( $rules['input_image' === $type ? 'allowed_image_types' : 'allowed_file_types']['value'] ?? array() );
        $extensions = array();
        foreach ( array_filter( $types, 'is_string' ) as $group ) {
            foreach ( explode( '|', strtolower( $group ) ) as $extension ) {
                if ( '' !== trim( $extension ) ) {
                    $extensions[] = '.' . ltrim( trim( $extension ), '.' );
                }
            }
        }
        if ( $extensions ) {
            $props['acceptedTypes'] = implode( ',', array_unique( $extensions ) );
        } elseif ( 'input_image' === $type ) {
            $props['acceptedTypes'] = 'image/*';
        }
        return $props;
    }

    private function mask( $mask, $label ) {
        if ( '' === $mask ) {
            return array();
        }
        if ( preg_match( '/[9#*]/', $mask ) ) {
            /* translators: 1: field label, 2: input mask */
            $this->notes[] = sprintf( __( 'The input mask of "%1$s" (%2$s) was not imported: it has optional or repeating parts.', 'surveyjs' ), $label, $mask );
            return array();
        }
        $pattern = preg_replace_callback( '/./u', function ( $match ) {
            $tokens = array( '0' => '9', 'S' => 'a', 'A' => '#' );
            if ( isset( $tokens[ $match[0] ] ) ) {
                return $tokens[ $match[0] ];
            }
            return in_array( $match[0], array( 'a', '\\' ), true ) ? '\\' . $match[0] : $match[0];
        }, $mask );
        return array( 'maskType' => 'pattern', 'maskSettings' => array( 'pattern' => $pattern, 'saveMaskedValue' => true ) );
    }

    private static function grid_items( $items ) {
        $result = array();
        foreach ( (array) $items as $key => $label ) {
            $result[] = array( 'value' => (string) $key, 'text' => self::text( $label ) );
        }
        return $result;
    }

    private static function required( $settings ) {
        $rule = (array) ( $settings['validation_rules']['required'] ?? array() );
        if ( empty( $rule['value'] ) ) {
            return array();
        }
        $props = array( 'isRequired' => true );
        $message = self::text( $rule['message'] ?? '' );
        if ( empty( $rule['global'] ) && '' !== $message && self::DEFAULT_REQUIRED_MESSAGE !== $message ) {
            $props['requiredErrorText'] = $message;
        }
        return $props;
    }

    private static function other_prefix( $settings ) {
        $label = trim( (string) ( $settings['other_option_label'] ?? '' ) );
        $label = '' !== $label ? $label : 'Other';
        return ':' === substr( $label, -1 ) ? $label . ' ' : $label . ': ';
    }

    private function placeholder_default( $attributes, $label ) {
        $props = array();
        if ( '' !== self::text( $attributes['placeholder'] ?? '' ) ) {
            $props['placeholder'] = self::text( $attributes['placeholder'] );
        }
        $default = is_scalar( $attributes['value'] ?? '' ) ? trim( (string) ( $attributes['value'] ?? '' ) ) : '';
        if ( preg_match( '/\{[\w.]+[^}]*\}/', $default ) ) {
            /* translators: 1: field label, 2: default value */
            $this->notes[] = sprintf( __( 'The default value of "%1$s" (%2$s) uses a smart code and was not imported.', 'surveyjs' ), $label, $default );
        } elseif ( '' !== $default ) {
            $props['defaultValue'] = $default;
        }
        return $props;
    }

    private function ref( $input, $parts, $kind, $extra = array() ) {
        if ( '' === $input || ! $parts ) {
            return;
        }
        $this->refs[ $input ] = $extra + array( 'parts' => $parts, 'kind' => $kind, 'separator' => '', 'email' => false, 'multiple' => false, 'other_prefix' => '', 'format' => '' );
    }

    private function condition( $name, $field ) {
        $logic = $field['settings']['conditional_logics'] ?? null;
        if ( self::condition_active( $logic ) ) {
            $this->conditions[ $name ][] = array( $logic, self::text( $field['settings']['label'] ?? $field['settings']['admin_field_label'] ?? $name ) );
        }
    }

    private static function condition_active( $logic ) {
        return is_array( $logic ) && ! empty( $logic['status'] ) && 'false' !== $logic['status'] && ( ! empty( $logic['conditions'] ) || ! empty( $logic['condition_groups'] ) );
    }

    private function apply_conditions( $elements ) {
        foreach ( $elements as &$element ) {
            if ( ! empty( $element['elements'] ) ) {
                $element['elements'] = $this->apply_conditions( $element['elements'] );
            }
            $expressions = array();
            foreach ( $this->conditions[ $element['name'] ] ?? array() as list( $logic, $label ) ) {
                $expression = $this->expression( $logic );
                if ( '' === $expression ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The conditional logic of "%s" was not imported.', 'surveyjs' ), '' !== $label ? $label : $element['name'] );
                    continue;
                }
                $expressions[] = $expression;
            }
            if ( $expressions ) {
                $element['visibleIf'] = count( $expressions ) > 1 ? '(' . implode( ') and (', $expressions ) . ')' : $expressions[0];
            }
        }
        return $elements;
    }

    private function expression( $logic ) {
        if ( ! self::condition_active( $logic ) ) {
            return '';
        }
        $type = (string) ( $logic['type'] ?? 'any' );
        $groups = 'group' === $type
            ? array_column( array_filter( (array) ( $logic['condition_groups'] ?? array() ), 'is_array' ), 'rules' )
            : array( (array) ( $logic['conditions'] ?? array() ) );
        $parts = array();
        foreach ( $groups as $rules ) {
            $expressions = array();
            foreach ( array_filter( (array) $rules, 'is_array' ) as $rule ) {
                if ( '' === (string) ( $rule['field'] ?? '' ) || '' === (string) ( $rule['operator'] ?? '' ) ) {
                    continue;
                }
                $expression = $this->rule( $rule );
                if ( '' === $expression ) {
                    return '';
                }
                $expressions[] = $expression;
            }
            if ( ! $expressions ) {
                continue;
            }
            $joined = implode( 'group' === $type || 'all' === $type ? ' and ' : ' or ', $expressions );
            $parts[] = count( $expressions ) > 1 && count( $groups ) > 1 ? '(' . $joined . ')' : $joined;
        }
        return implode( ' or ', $parts );
    }

    private function rule( $rule ) {
        $names = $this->field_names( (string) $rule['field'] );
        if ( 1 !== count( $names ) ) {
            return '';
        }
        $ref = $this->refs[ strtok( (string) $rule['field'], '[.' ) ];
        $name = '{' . $names[0] . '}';
        $operator = (string) $rule['operator'];
        $value = is_scalar( $rule['value'] ?? '' ) ? (string) ( $rule['value'] ?? '' ) : '';
        if ( 'agreement' === $ref['kind'] && in_array( $operator, array( '=', '!=' ), true ) ) {
            return $name . ( ( '=' === $operator ) === ( '' !== $value ) ? ' notempty' : ' empty' );
        }
        if ( '' === $value && in_array( $operator, array( '=', '!=' ), true ) ) {
            return $name . ( '=' === $operator ? ' empty' : ' notempty' );
        }
        if ( preg_match( '/^\{inputs\.([^}]+)\}$/', $value, $match ) ) {
            $other = $this->field_names( $match[1] );
            if ( 1 !== count( $other ) ) {
                return '';
            }
            $quoted = '{' . $other[0] . '}';
        } elseif ( false !== strpos( $value, '{' ) ) {
            return '';
        } elseif ( in_array( $ref['kind'], array( 'number' ), true ) && is_numeric( $value ) ) {
            $quoted = (string) ( 0 + $value );
        } elseif ( false === strpos( $value, "'" ) ) {
            $quoted = "'" . $value . "'";
        } elseif ( false === strpos( $value, '"' ) ) {
            $quoted = '"' . $value . '"';
        } else {
            return '';
        }
        if ( in_array( $operator, array( '>', '<', '>=', '<=' ), true ) ) {
            return is_numeric( $value ) ? $name . ' ' . $operator . ' ' . ( 0 + $value ) : '';
        }
        $operators = array(
            '='             => $ref['multiple'] ? 'contains' : '=',
            '!='            => $ref['multiple'] ? 'notcontains' : '<>',
            'contains'      => 'contains',
            'doNotContains' => 'notcontains',
        );
        return isset( $operators[ $operator ] ) ? $name . ' ' . $operators[ $operator ] . ' ' . $quoted : '';
    }

    private function field_names( $field ) {
        $field = preg_replace( '/\.(label|value)$/', '', $field );
        if ( preg_match( '/^([^\[.]+)(?:\[([^\]]+)\]|\.(.+))?$/', $field, $match ) !== 1 || ! isset( $this->refs[ $match[1] ] ) ) {
            return array();
        }
        $parts = $this->refs[ $match[1] ]['parts'];
        $part = '' !== ( $match[2] ?? '' ) ? $match[2] : ( $match[3] ?? '' );
        if ( '' !== $part ) {
            return isset( $parts[ $part ] ) ? array( $parts[ $part ] ) : array();
        }
        return array_values( $parts );
    }

    private function confirmation( $form_settings, $confirmations, &$json ) {
        $default = (array) ( $form_settings['confirmation'] ?? array() );
        $conditional = array();
        foreach ( array_filter( $confirmations, 'is_array' ) as $confirmation ) {
            if ( empty( $confirmation['active'] ) ) {
                continue;
            }
            if ( ! self::condition_active( $confirmation['conditionals'] ?? null ) ) {
                $default = $confirmation;
                break;
            }
            $conditional[] = $confirmation;
        }
        $messages = array();
        foreach ( $conditional as $confirmation ) {
            $expression = $this->expression( $confirmation['conditionals'] );
            if ( '' === $expression ) {
                /* translators: %s: confirmation name */
                $this->notes[] = sprintf( __( 'The confirmation "%s" was not imported: its conditional logic is not supported.', 'surveyjs' ), $confirmation['name'] ?? '' );
                continue;
            }
            $this->add_confirmation( $confirmation, $expression, $messages, $json );
        }
        if ( $default ) {
            $this->add_confirmation( $default, '', $messages, $json );
        }
    }

    private function add_confirmation( $confirmation, $expression, &$messages, &$json ) {
        $type = (string) ( $confirmation['redirectTo'] ?? 'samePage' );
        if ( 'samePage' === $type ) {
            if ( '' === trim( (string) ( $confirmation['messageToShow'] ?? '' ) ) ) {
                return;
            }
            $html = trim( wp_kses_post( wpautop( $this->tags( $confirmation['messageToShow'], true ) ) ) );
            if ( '' === $expression ) {
                $json['completedHtml'] = $html;
                if ( 'reset_form' === ( $confirmation['samePageFormBehavior'] ?? '' ) ) {
                    $this->notes[] = __( 'The confirmation resets the form in Fluent Forms; the imported form shows the message instead.', 'surveyjs' );
                }
            } else {
                $json['completedHtmlOnCondition'][] = array( 'expression' => $expression, 'html' => $html );
                $messages[] = $expression;
            }
            return;
        }
        $url = 'customPage' === $type ? (string) get_permalink( (int) ( $confirmation['customPage'] ?? 0 ) ) : trim( (string) ( $confirmation['customUrl'] ?? '' ) );
        $url = $this->tags( $url, true );
        if ( ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) {
            /* translators: %s: confirmation name */
            $this->notes[] = sprintf( __( 'The redirect of the confirmation "%s" was not imported: it has no valid URL.', 'surveyjs' ), $confirmation['name'] ?? __( 'Default', 'surveyjs' ) );
            return;
        }
        // SurveyJS redirects before it shows any completed page, so a redirect must not match when an earlier conditional message does.
        $conditions = '' !== $expression ? array( '(' . $expression . ')' ) : array();
        if ( $messages ) {
            $conditions[] = '!((' . implode( ') or (', $messages ) . '))';
        }
        if ( ! $conditions ) {
            $json['navigateToUrl'] = $url;
        } else {
            $json['navigateToUrlOnCondition'][] = array( 'expression' => implode( ' and ', $conditions ), 'url' => $url );
        }
    }

    private function notification_settings( $notifications ) {
        $admin = null;
        $auto_reply = null;
        $auto_reply_field = '';
        $others = array();
        foreach ( $notifications as $notification ) {
            $field = $this->recipient_field( $notification );
            if ( '' !== $field && ! $auto_reply ) {
                $auto_reply = $notification;
                $auto_reply_field = $field;
            } elseif ( '' === $field && ( ! $admin || ( empty( $admin['enabled'] ) && ! empty( $notification['enabled'] ) ) ) ) {
                if ( $admin ) {
                    $others[] = $admin;
                }
                $admin = $notification;
            } else {
                $others[] = $notification;
            }
        }

        $settings = array( 'notificationEnabled' => false );
        if ( $admin ) {
            $send_to = (array) ( $admin['sendTo'] ?? array() );
            if ( 'routing' === ( $send_to['type'] ?? '' ) ) {
                $to = '';
                /* translators: %s: notification name */
                $this->notes[] = sprintf( __( 'The conditional routing of the notification "%s" was not imported: it is sent to the site admin.', 'surveyjs' ), $admin['name'] ?? '' );
            } else {
                $to = $this->tags( $send_to['email'] ?? '', false );
            }
            $settings = array(
                'notificationEnabled'   => ! empty( $admin['enabled'] ),
                'notificationTo'        => $to,
                'notificationSubject'   => $this->tags( $admin['subject'] ?? '', false ),
                'notificationFromName'  => $this->tags( $admin['fromName'] ?? '', false ),
                'notificationFromEmail' => $this->tags( $admin['fromEmail'] ?? '', false ),
            ) + $this->message_settings( $admin, 'notification' );
            foreach ( array( 'cc' => 'CC', 'bcc' => 'BCC' ) as $key => $header ) {
                if ( '' !== trim( (string) ( $admin[ $key ] ?? '' ) ) ) {
                    /* translators: 1: CC or BCC, 2: email addresses */
                    $this->notes[] = sprintf( __( '%1$s "%2$s" was not imported: add these addresses to Send To if needed.', 'surveyjs' ), $header, $admin[ $key ] );
                }
            }
            $reply_to = trim( (string) ( $admin['replyTo'] ?? '' ) );
            if ( '' !== $reply_to ) {
                $settings['notificationReplyTo'] = $this->email_field( $reply_to );
                if ( '' === $settings['notificationReplyTo'] ) {
                    /* translators: %s: Reply-To value */
                    $this->notes[] = sprintf( __( 'Reply-To "%s" was not imported: Reply-To can only be an Email field.', 'surveyjs' ), $reply_to );
                }
            }
            $this->notification_notes( $admin );
        }

        if ( $auto_reply ) {
            if ( in_array( $settings['notificationReplyTo'] ?? '', array( '', $auto_reply_field ), true ) ) {
                $settings['notificationReplyTo'] = $auto_reply_field;
                $settings['autoReplyEnabled'] = ! empty( $auto_reply['enabled'] );
                $settings['autoReplySubject'] = $this->tags( $auto_reply['subject'] ?? '', false );
                $settings += $this->message_settings( $auto_reply, 'autoReply' );
                /* translators: %s: notification name */
                $this->notes[] = sprintf( __( 'The notification "%s" was imported as the auto-reply.', 'surveyjs' ), $auto_reply['name'] ?? '' );
                $this->notification_notes( $auto_reply );
            } else {
                $others[] = $auto_reply;
            }
        }
        foreach ( $others as $other ) {
            /* translators: %s: notification name */
            $this->notes[] = sprintf( __( 'The notification "%s" was not imported: a form has one email notification and one auto-reply.', 'surveyjs' ), $other['name'] ?? '' );
        }
        return $settings;
    }

    private function message_settings( $notification, $prefix ) {
        $message = $this->tags( $notification['message'] ?? '', false );
        $html = 'yes' !== ( $notification['asPlainText'] ?? 'no' ) && (bool) preg_match( '/<[a-z][^>]*>/i', $message );
        $settings = array(
            $prefix . 'Message' => $html ? $message : wp_strip_all_tags( $message ),
            $prefix . 'Html'    => $html,
        );
        $attachments = array();
        foreach ( (array) ( $notification['attachments'] ?? array() ) as $input ) {
            $names = is_string( $input ) && 'file' === ( $this->refs[ $input ]['kind'] ?? '' ) ? $this->field_names( $input ) : array();
            if ( $names ) {
                $attachments[] = $names[0];
            }
        }
        if ( $attachments ) {
            $settings[ $prefix . 'Attachments' ] = $attachments[0];
        }
        return $settings;
    }

    private function notification_notes( $notification ) {
        if ( self::condition_active( $notification['conditionals'] ?? null ) ) {
            /* translators: %s: notification name */
            $this->notes[] = sprintf( __( 'The conditional logic of the notification "%s" was not imported.', 'surveyjs' ), $notification['name'] ?? '' );
        }
        $files = count( (array) ( $notification['attachments'] ?? array() ) ) + count( (array) ( $notification['media_attachments'] ?? array() ) );
        if ( $files > 1 || ! empty( $notification['media_attachments'] ) ) {
            /* translators: %s: notification name */
            $this->notes[] = sprintf( __( 'Only one file field of the notification "%s" is attached; other attachments were not imported.', 'surveyjs' ), $notification['name'] ?? '' );
        }
    }

    private function recipient_field( $notification ) {
        $send_to = (array) ( $notification['sendTo'] ?? array() );
        if ( 'field' === ( $send_to['type'] ?? '' ) ) {
            $input = (string) ( $send_to['field'] ?? '' );
            return ! empty( $this->refs[ $input ]['email'] ) ? $this->refs[ $input ]['parts']['value'] : '';
        }
        return 'email' === ( $send_to['type'] ?? 'email' ) ? $this->email_field( (string) ( $send_to['email'] ?? '' ) ) : '';
    }

    private function email_field( $text ) {
        if ( ! preg_match( '/^\s*\{inputs\.([^}.\[]+)\}\s*$/', $text, $match ) ) {
            return '';
        }
        return ! empty( $this->refs[ $match[1] ]['email'] ) ? $this->refs[ $match[1] ]['parts']['value'] : '';
    }

    private function tags( $text, $html ) {
        return preg_replace_callback( '/\{([^{}\s]+)\}/', function ( $match ) use ( $html ) {
            $code = $match[1];
            if ( 0 === strpos( $code, 'inputs.' ) ) {
                $input = substr( $code, strlen( 'inputs.' ) );
                $names = $this->field_names( $input );
                if ( $names ) {
                    $ref = $this->refs[ strtok( $input, '[.' ) ];
                    return implode( $ref['separator'], array_map( function ( $name ) use ( $html ) {
                        return $html ? '{' . $name . '}' : '{field:' . $name . '}';
                    }, $names ) );
                }
            } elseif ( 0 === strpos( $code, 'labels.' ) ) {
                $names = $this->field_names( substr( $code, strlen( 'labels.' ) ) );
                if ( $names ) {
                    return esc_html( $this->titles[ $names[0] ] ?? $names[0] );
                }
            } else {
                $values = array(
                    'wp.admin_email' => array( '{admin_email}', get_option( 'admin_email' ) ),
                    'admin_email'    => array( '{admin_email}', get_option( 'admin_email' ) ),
                    'wp.site_title'  => array( '{site_name}', get_bloginfo( 'name' ) ),
                    'form_title'     => array( '{form_name}', $this->title ),
                    'wp.site_url'    => array( home_url(), home_url() ),
                    'all_data'       => array( '{all_fields}', null ),
                    'all_data_without_hidden_fields' => array( '{all_fields}', null ),
                );
                if ( isset( $values[ $code ] ) && ( ! $html || null !== $values[ $code ][1] ) ) {
                    return $html ? esc_html( $values[ $code ][1] ) : $values[ $code ][0];
                }
            }
            $this->notes[] = $html
                /* translators: %s: Fluent Forms smart code */
                ? sprintf( __( 'Smart code %s in the confirmation has no equivalent and was removed.', 'surveyjs' ), $match[0] )
                /* translators: %s: Fluent Forms smart code */
                : sprintf( __( 'Smart code %s has no equivalent and was kept as text.', 'surveyjs' ), $match[0] );
            return $html ? '' : $match[0];
        }, (string) $text );
    }

    private function unique_name( $text ) {
        $base = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( remove_accents( $text ) ) ), '_' );
        $base = '' !== $base ? substr( $base, 0, 40 ) : 'field';
        $name = $base;
        for ( $i = 2; in_array( $name, $this->taken, true ); $i++ ) {
            $name = $base . '_' . $i;
        }
        $this->taken[] = $name;
        return $name;
    }

    private static function table_exists( $table ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . $table ) ) );
    }

    private static function text( $value ) {
        return is_scalar( $value ) ? trim( wp_strip_all_tags( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) ) ) : '';
    }
}
