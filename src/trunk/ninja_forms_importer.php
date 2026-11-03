<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Ninja_Forms_Importer extends SurveyJS_Importer {

    const DATE_FORMATS = array(
        'MM/DD/YYYY'        => 'm/d/Y',
        'MM-DD-YYYY'        => 'm-d-Y',
        'MM.DD.YYYY'        => 'm.d.Y',
        'DD/MM/YYYY'        => 'd/m/Y',
        'DD-MM-YYYY'        => 'd-m-Y',
        'DD.MM.YYYY'        => 'd.m.Y',
        'YYYY-MM-DD'        => 'Y-m-d',
        'YYYY/MM/DD'        => 'Y/m/d',
        'YYYY.MM.DD'        => 'Y.m.d',
        'dddd, MMMM D YYYY' => 'l, F d Y',
    );

    const AUTOCOMPLETE = array(
        'firstname' => 'given-name',
        'lastname'  => 'family-name',
        'email'     => 'email',
        'phone'     => 'tel',
        'address'   => 'address-line1',
        'address2'  => 'address-line2',
        'city'      => 'address-level2',
        'zip'       => 'postal-code',
    );

    const IGNORED_TYPES = array( 'submit', 'note' );

    public static function source() {
        return 'ninja-forms';
    }

    public static function is_available() {
        return function_exists( 'Ninja_Forms' );
    }

    public static function forms() {
        $forms = array();
        if ( ! self::is_available() ) {
            return $forms;
        }
        foreach ( Ninja_Forms()->form()->get_forms() as $form ) {
            $forms[ (int) $form->get_id() ] = (string) $form->get_setting( 'title' );
        }
        ksort( $forms );
        return $forms;
    }

    protected static function convert_form( $form_id ) {
        $forms = self::forms();
        if ( ! isset( $forms[ $form_id ] ) ) {
            return new WP_Error( 'not_found', __( 'The Ninja Forms form was not found.', 'surveyjs' ) );
        }
        $model = Ninja_Forms()->form( $form_id );
        $form = array(
            'settings' => (array) $model->get()->get_settings(),
            'fields'   => array(),
            'actions'  => array(),
        );
        foreach ( $model->get_fields() as $field ) {
            $form['fields'][] = array( 'id' => $field->get_id() ) + (array) $field->get_settings();
        }
        foreach ( Ninja_Forms()->form( $form_id )->get_actions() as $action ) {
            $form['actions'][] = array( 'id' => $action->get_id() ) + (array) $action->get_settings();
        }
        return array( 'name' => $forms[ $form_id ] ) + self::convert( $form, $forms[ $form_id ] );
    }

    public static function entries_available() {
        return self::is_available();
    }

    protected static function entry_dates( $form_id, $entry_ids ) {
        global $wpdb;
        $in = implode( ', ', array_fill( 0, count( $entry_ids ), '%d' ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_date_gmt FROM {$wpdb->posts} WHERE ID IN ( $in )", array_map( 'intval', $entry_ids ) ) );
        $dates = array();
        foreach ( $rows as $row ) {
            $dates[ (int) $row->ID ] = self::gmt_date( $row->post_date_gmt );
        }
        return array_filter( $dates );
    }

    protected static function entries( $form_id, $form ) {
        $questions = array_column( SurveyJS_Survey_Values::survey_questions( $form['json'] ), null, 'name' );
        $ids = get_posts( array(
            'post_type'   => 'nf_sub',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
            'fields'      => 'ids',
            'meta_key'    => '_form_id',
            'meta_value'  => (string) $form_id,
        ) );
        $entries = array();
        foreach ( $ids as $id ) {
            $meta = get_post_meta( $id );
            $data = array();
            foreach ( $form['refs'] as $field_id => $ref ) {
                if ( ! isset( $meta[ '_field_' . $field_id ][0], $questions[ $ref['name'] ] ) ) {
                    continue;
                }
                $value = self::decode( maybe_unserialize( $meta[ '_field_' . $field_id ][0] ) );
                $data += self::entry_field_value( $questions[ $ref['name'] ], $ref, $value );
            }
            if ( $data ) {
                $entries[ (int) $id ] = $data;
            }
        }
        return $entries;
    }

    private static function decode( $value ) {
        if ( is_array( $value ) ) {
            return array_map( array( __CLASS__, 'decode' ), $value );
        }
        return is_string( $value ) ? html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $value;
    }

    private static function entry_field_value( $question, $ref, $value ) {
        $name = $question['name'];
        switch ( $ref['type'] ) {
            case 'checkbox':
                $checked = is_scalar( $value ) ? trim( (string) $value ) : '';
                if ( '' === $checked ) {
                    return array();
                }
                return array( $name => ! in_array( strtolower( $checked ), array( '0', 'unchecked', strtolower( $ref['unchecked_value'] ) ), true ) );

            case 'date':
                $date = self::entry_date( $value, $ref );
                return '' !== $date ? array( $name => $date ) : array();

            case 'signature':
                $signature = is_string( $value ) ? json_decode( $value, true ) : $value;
                $image = is_array( $signature ) ? (string) ( $signature['signature_data'] ?? '' ) : '';
                return 0 === strpos( $image, 'data:image/' ) ? array( $name => $image ) : array();

            case 'file_upload':
                $files = array();
                foreach ( array_filter( (array) $value, 'is_scalar' ) as $url ) {
                    $url = esc_url_raw( (string) $url );
                    if ( '' !== $url ) {
                        $files[] = array( 'name' => wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ), 'type' => '', 'content' => $url );
                    }
                }
                return $files ? array( $name => $files ) : array();

            case 'repeater':
                $rows = array();
                foreach ( (array) $value as $key => $item ) {
                    if ( ! preg_match( '/^\d+\.(\d+)_(\d+)$/', (string) $key, $match ) || ! isset( $ref['children'][ $match[1] ] ) ) {
                        continue;
                    }
                    $child = $ref['children'][ $match[1] ];
                    $item = is_array( $item ) && array_key_exists( 'value', $item ) ? $item['value'] : $item;
                    $rows[ (int) $match[2] ] = ( $rows[ (int) $match[2] ] ?? array() ) + self::entry_field_value( $child['question'], $child, $item );
                }
                ksort( $rows );
                $rows = array_values( array_filter( $rows ) );
                return $rows ? array( $name => $rows ) : array();
        }
        return self::entry_value( $question, $value );
    }

    private static function entry_date( $value, $ref ) {
        $parts = is_array( $value ) ? $value : array( 'date' => $value );
        $date = trim( (string) ( is_scalar( $parts['date'] ?? '' ) ? $parts['date'] ?? '' : '' ) );
        $time = '';
        if ( is_numeric( $parts['hour'] ?? '' ) && is_numeric( $parts['minute'] ?? '' ) ) {
            $hour = (int) $parts['hour'] % 12;
            $ampm = strtolower( (string) ( $parts['ampm'] ?? '' ) );
            $hour = '' === $ampm ? (int) $parts['hour'] : $hour + ( 'pm' === $ampm ? 12 : 0 );
            $time = sprintf( '%02d:%02d', $hour, (int) $parts['minute'] );
        }
        if ( 'time_only' === $ref['date_mode'] ) {
            return '' !== $time ? $time : $date;
        }
        if ( '' === $date ) {
            return '';
        }
        $parsed = DateTime::createFromFormat( '!' . $ref['date_format'], $date );
        if ( ! $parsed && false !== strtotime( $date ) ) {
            $parsed = new DateTime( '@' . strtotime( $date ) );
        }
        if ( ! $parsed ) {
            return $date;
        }
        if ( 'date_and_time' === $ref['date_mode'] ) {
            return $parsed->format( 'Y-m-d' ) . 'T' . ( '' !== $time ? $time : '00:00' );
        }
        return $parsed->format( 'Y-m-d' );
    }

    public static function convert( $form, $title ) {
        $json = array();
        $skipped = array();
        $notes = array();
        $taken = array();
        $refs = array();
        $settings = (array) ( $form['settings'] ?? array() );
        $fields = array_values( array_filter( (array) ( $form['fields'] ?? array() ), 'is_array' ) );
        usort( $fields, function ( $a, $b ) {
            return (int) ( $a['order'] ?? 0 ) - (int) ( $b['order'] ?? 0 );
        } );
        $fields_by_key = array();
        foreach ( $fields as $field ) {
            $fields_by_key[ self::field_key( $field ) ] = $field;
        }
        unset( $fields_by_key[''] );

        $pages = array();
        foreach ( self::layout( $settings['formContentData'] ?? array(), $fields ) as $layout_page ) {
            $page = array( 'name' => 'page' . ( count( $pages ) + 1 ), 'elements' => array() );
            if ( '' !== $layout_page['title'] ) {
                $page['title'] = $layout_page['title'];
            }
            foreach ( $layout_page['rows'] as $row ) {
                $in_row = 0;
                foreach ( $row as $key ) {
                    $field = $fields_by_key[ $key ];
                    $type = (string) ( $field['type'] ?? '' );
                    if ( 'submit' === $type && '' !== self::text( $field['label'] ?? '' ) ) {
                        $json['completeText'] = self::text( $field['label'] );
                    }
                    if ( in_array( $type, self::IGNORED_TYPES, true ) ) {
                        continue;
                    }
                    $elements = self::elements( $field, $taken, $refs, $notes );
                    if ( null === $elements ) {
                        $label = self::text( $field['label'] ?? '' );
                        $skipped[] = sprintf( '%s (%s)', '' !== $label ? $label : (string) ( $field['key'] ?? '' ), $type );
                        continue;
                    }
                    if ( $in_row++ > 0 && $elements ) {
                        $elements[0]['startWithNewLine'] = false;
                    }
                    array_push( $page['elements'], ...$elements );
                }
            }
            $pages[] = $page;
        }

        $by_key = array();
        foreach ( $refs as $ref ) {
            $by_key[ $ref['key'] ] = $ref;
        }
        $visibility = self::visibility( (array) ( $settings['conditions'] ?? array() ), $by_key, $notes );
        foreach ( $pages as &$page ) {
            foreach ( $page['elements'] as &$element ) {
                if ( isset( $element['confirmOf'] ) ) {
                    self::confirm_validator( $element, $by_key, $notes );
                }
                if ( isset( $visibility[ $element['name'] ] ) ) {
                    $element['visibleIf'] = $visibility[ $element['name'] ];
                }
            }
            unset( $element );
        }
        unset( $page );

        $pages = array_values( array_filter( $pages, function ( $page ) {
            return ! empty( $page['elements'] );
        } ) );
        if ( count( $pages ) > 1 ) {
            $json['pages'] = $pages;
            $json['showProgressBar'] = true;
        } else {
            $json['elements'] = $pages ? $pages[0]['elements'] : array();
        }

        $actions = array_values( array_filter( (array) ( $form['actions'] ?? array() ), 'is_array' ) );
        self::completion( $actions, $title, $by_key, $json, $notes );
        foreach ( $actions as $action ) {
            $type = (string) ( $action['type'] ?? '' );
            if ( ! in_array( $type, array( 'save', 'email', 'successmessage', 'redirect' ), true ) && ! empty( $action['active'] ) ) {
                /* translators: 1: action name, 2: action type */
                $notes[] = sprintf( __( 'The action "%1$s" (%2$s) was not imported.', 'surveyjs' ), self::text( $action['label'] ?? '' ), $type );
            }
        }
        self::form_notes( $settings, $notes );

        return array(
            'json'     => $json,
            'settings' => self::notification_settings( $actions, $by_key, $notes ),
            'skipped'  => $skipped,
            'notes'    => $notes,
            'refs'     => $refs,
        );
    }

    private static function layout( $content, $fields ) {
        $aliases = array();
        foreach ( $fields as $field ) {
            $aliases[ (string) ( $field['id'] ?? '' ) ] = self::field_key( $field );
        }
        foreach ( $fields as $field ) {
            $aliases[ self::field_key( $field ) ] = self::field_key( $field );
        }
        unset( $aliases[''] );
        $content = array_values( array_filter( (array) $content, function ( $item ) {
            return is_array( $item ) || is_scalar( $item );
        } ) );
        $parts = $content && is_array( $content[0] ) && 'part' === ( $content[0]['type'] ?? '' )
            ? self::sorted( $content )
            : array( array( 'formContentData' => $content ) );
        $pages = array();
        $placed = array();
        foreach ( $parts as $part ) {
            $rows = array();
            foreach ( self::rows( (array) ( $part['formContentData'] ?? array() ) ) as $row ) {
                $keys = array();
                foreach ( $row as $reference ) {
                    $key = $aliases[ $reference ] ?? '';
                    if ( '' !== $key && ! isset( $placed[ $key ] ) ) {
                        $keys[] = $key;
                        $placed[ $key ] = true;
                    }
                }
                if ( $keys ) {
                    $rows[] = $keys;
                }
            }
            $pages[] = array( 'title' => self::text( $part['title'] ?? '' ), 'rows' => $rows );
        }
        foreach ( $fields as $field ) {
            $key = self::field_key( $field );
            if ( '' !== $key && ! isset( $placed[ $key ] ) ) {
                $pages[ count( $pages ) - 1 ]['rows'][] = array( $key );
                $placed[ $key ] = true;
            }
        }
        return $pages;
    }

    private static function rows( $items ) {
        $rows = array();
        foreach ( self::sorted( $items ) as $item ) {
            if ( is_scalar( $item ) ) {
                $rows[] = array( (string) $item );
                continue;
            }
            if ( ! is_array( $item ) || ! isset( $item['cells'] ) ) {
                continue;
            }
            $cells = array_map( function ( $cell ) {
                return array_values( array_map( 'strval', array_filter( (array) ( $cell['fields'] ?? array() ), 'is_scalar' ) ) );
            }, self::sorted( array_filter( (array) $item['cells'], 'is_array' ) ) );
            $cells = array_values( array_filter( $cells ) );
            if ( count( $cells ) > 1 && 1 === max( array_map( 'count', $cells ) ) ) {
                $rows[] = array_merge( ...$cells );
                continue;
            }
            foreach ( $cells as $keys ) {
                foreach ( $keys as $key ) {
                    $rows[] = array( $key );
                }
            }
        }
        return $rows;
    }

    private static function field_key( $field ) {
        $key = (string) ( $field['key'] ?? '' );
        return '' !== $key ? $key : (string) ( $field['id'] ?? '' );
    }

    private static function sorted( $items ) {
        $items = array_values( $items );
        $order = array_map( function ( $item, $index ) {
            return is_array( $item ) && is_numeric( $item['order'] ?? '' ) ? (float) $item['order'] : $index;
        }, $items, array_keys( $items ) );
        array_multisort( $order, SORT_NUMERIC, array_keys( $items ), $items );
        return $items;
    }

    private static function elements( $field, &$taken, &$refs, &$notes ) {
        $type = (string) ( $field['type'] ?? '' );
        $id = (string) ( $field['id'] ?? '' );
        $key = (string) ( $field['key'] ?? '' );
        $label = self::text( $field['label'] ?? '' );
        $base = '' !== $label ? $label : ( '' !== $key ? $key : $type . '_' . $id );
        $common = array( 'title' => $label );
        $description = trim( implode( "\n", array_filter( array( self::text( $field['desc_text'] ?? '' ), self::text( $field['help_text'] ?? '' ) ), 'strlen' ) ) );
        if ( '' !== $description ) {
            $common['description'] = $description;
        }
        if ( ! empty( $field['required'] ) ) {
            $common['isRequired'] = true;
        }
        $positions = array( 'hidden' => 'hidden', 'below' => 'bottom', 'left' => 'left' );
        if ( isset( $positions[ $field['label_pos'] ?? '' ] ) ) {
            $common['titleLocation'] = $positions[ $field['label_pos'] ];
        }
        $question = function ( $question_type, $props = array() ) use ( $base, $common, &$taken ) {
            return array_merge( array( 'type' => $question_type, 'name' => self::unique_name( $base, $taken ) ), $common, $props );
        };
        $html = function ( $html ) use ( $base, &$taken ) {
            return array( array( 'type' => 'html', 'name' => self::unique_name( $base, $taken ), 'html' => wp_kses_post( $html ) ) );
        };
        $ref = function ( $element, $extra = array() ) use ( $type, $id, $key, &$refs ) {
            $refs[ $id ] = array_merge( array( 'name' => $element['name'], 'key' => '' !== $key ? $key : $id, 'type' => $type, 'question' => $element ), $extra );
            return array( $element );
        };

        switch ( $type ) {
            case 'textbox':
            case 'firstname':
            case 'lastname':
            case 'address':
            case 'address2':
            case 'city':
            case 'zip':
            case 'email':
            case 'phone':
            case 'textarea':
            case 'number':
            case 'hidden':
            case 'password':
            case 'confirm':
            case 'passwordconfirm':
                $input_types = array( 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'password' => 'password', 'passwordconfirm' => 'password' );
                $props = isset( $input_types[ $type ] ) ? array( 'inputType' => $input_types[ $type ] ) : array();
                if ( isset( self::AUTOCOMPLETE[ $type ] ) ) {
                    $props['autocomplete'] = self::AUTOCOMPLETE[ $type ];
                }
                $props += self::placeholder_default( $field, $label, $notes );
                if ( (int) ( $field['input_limit'] ?? 0 ) > 0 ) {
                    if ( 'words' === ( $field['input_limit_type'] ?? 'characters' ) ) {
                        /* translators: %s: field label */
                        $notes[] = sprintf( __( 'The word limit of "%s" was not imported: only a character limit is supported.', 'surveyjs' ), $label );
                    } else {
                        $props['maxLength'] = (int) $field['input_limit'];
                    }
                }
                if ( ! empty( $field['mask'] ) ) {
                    /* translators: %s: field label */
                    $notes[] = sprintf( __( 'The input mask of "%s" was not imported.', 'surveyjs' ), $label );
                }
                if ( 'number' === $type ) {
                    foreach ( array( 'min' => 'num_min', 'max' => 'num_max', 'step' => 'num_step' ) as $prop => $option ) {
                        if ( is_numeric( $field[ $option ] ?? '' ) ) {
                            $props[ $prop ] = 0 + $field[ $option ];
                        }
                    }
                    if ( 1 === ( $props['step'] ?? null ) ) {
                        unset( $props['step'] );
                    }
                }
                if ( ! empty( $field['disable_input'] ) ) {
                    $props['readOnly'] = true;
                }
                if ( 'hidden' === $type ) {
                    $props += array( 'visible' => false, 'clearIfInvisible' => 'none' );
                }
                if ( in_array( $type, array( 'confirm', 'passwordconfirm' ), true ) ) {
                    $props['confirmOf'] = (string) ( $field['confirm_field'] ?? '' );
                }
                return $ref( $question( 'textarea' === $type ? 'comment' : 'text', $props ) );

            case 'date':
                $mode = (string) ( $field['date_mode'] ?? 'date_only' );
                $input_types = array( 'time_only' => 'time', 'date_and_time' => 'datetime-local' );
                $props = array( 'inputType' => $input_types[ $mode ] ?? 'date' );
                if ( 'date_only' === $mode && ! empty( $field['date_default'] ) ) {
                    $props['defaultValueExpression'] = 'today()';
                }
                foreach ( array( 'min' => array( 'year_range_start', '-01-01' ), 'max' => array( 'year_range_end', '-12-31' ) ) as $prop => list( $option, $suffix ) ) {
                    if ( 'date' === $props['inputType'] && preg_match( '/^\d{4}$/', (string) ( $field[ $option ] ?? '' ) ) ) {
                        $props[ $prop ] = $field[ $option ] . $suffix;
                    }
                }
                $format = (string) ( $field['date_format'] ?? 'default' );
                if ( ! isset( self::DATE_FORMATS[ $format ] ) ) {
                    $format = self::is_available() ? (string) Ninja_Forms()->get_setting( 'date_format' ) : '';
                } else {
                    $format = self::DATE_FORMATS[ $format ];
                }
                return $ref( $question( 'text', $props ), array( 'date_mode' => $mode, 'date_format' => '' !== $format ? $format : 'm/d/Y' ) );

            case 'listselect':
            case 'liststate':
            case 'listcountry':
            case 'listradio':
            case 'listcheckbox':
            case 'listmultiselect':
            case 'listimage':
                return $ref( $question( 'dropdown', self::choices( $field ) ) );

            case 'checkbox':
                $props = array();
                if ( 'checked' === ( $field['checkbox_default_value'] ?? '' ) ) {
                    $props['defaultValue'] = true;
                }
                return $ref( self::agreement( $question( 'boolean', $props ) ), array( 'unchecked_value' => (string) ( $field['unchecked_value'] ?? '' ) ) );

            case 'starrating':
                $props = array( 'rateType' => 'stars', 'rateMax' => max( 2, (int) ( $field['number_of_stars'] ?? 5 ) ) );
                if ( (int) ( $field['default'] ?? 0 ) > 0 ) {
                    $props['defaultValue'] = (int) $field['default'];
                }
                return $ref( $question( 'rating', $props ) );

            case 'signature':
                return $ref( $question( 'signaturepad' ) );

            case 'file_upload':
                $props = array( 'storeDataAsText' => false );
                if ( (int) ( $field['upload_multi_count'] ?? 1 ) > 1 ) {
                    $props['allowMultiple'] = true;
                }
                if ( is_numeric( $field['max_file_size'] ?? '' ) && (float) $field['max_file_size'] > 0 ) {
                    $props['maxSize'] = (int) ( (float) $field['max_file_size'] * MB_IN_BYTES );
                }
                $extensions = array_filter( array_map( 'trim', explode( ',', strtolower( (string) ( $field['upload_types'] ?? '' ) ) ) ) );
                if ( $extensions ) {
                    $props['acceptedTypes'] = implode( ',', array_map( function ( $extension ) {
                        return '.' . ltrim( $extension, '.' );
                    }, $extensions ) );
                }
                return $ref( $question( 'file', $props ) );

            case 'repeater':
                $children = array();
                $child_taken = array();
                $templates = array();
                foreach ( array_filter( (array) ( $field['fields'] ?? array() ), 'is_array' ) as $child ) {
                    $child_id = substr( (string) strrchr( '.' . ( $child['id'] ?? '' ), '.' ), 1 );
                    $child_refs = array();
                    $child['id'] = $child_id;
                    $elements = in_array( $child['type'] ?? '', array( 'repeater', 'confirm', 'passwordconfirm' ), true ) ? null : self::elements( $child, $child_taken, $child_refs, $notes );
                    if ( null === $elements ) {
                        /* translators: 1: field label, 2: repeatable fieldset label */
                        $notes[] = sprintf( __( 'The field "%1$s" in "%2$s" was not imported: it has no equivalent in a dynamic panel.', 'surveyjs' ), self::text( $child['label'] ?? '' ), $label );
                        continue;
                    }
                    array_push( $templates, ...$elements );
                    $children += $child_refs;
                }
                if ( ! $templates ) {
                    return null;
                }
                return $ref( $question( 'paneldynamic', array( 'templateElements' => $templates, 'panelCount' => 1 ) ), array( 'children' => $children ) );

            case 'html':
                return $html( (string) ( $field['default'] ?? '' ) );

            case 'hr':
                return $html( '<hr>' );
        }
        return null;
    }

    private static function choices( $field ) {
        $type = (string) $field['type'];
        $options = 'listimage' === $type ? ( $field['image_options'] ?? array() ) : ( $field['options'] ?? array() );
        if ( 'listcountry' === $type && self::is_available() ) {
            $options = array();
            foreach ( (array) Ninja_Forms()->config( 'CountryList' ) as $country => $code ) {
                $options[] = array( 'label' => $country, 'value' => $code, 'selected' => (string) ( $field['default'] ?? '' ) === (string) $code );
            }
        } elseif ( 'liststate' === $type && empty( $options ) && self::is_available() ) {
            foreach ( (array) Ninja_Forms()->config( 'StateList' ) as $state => $code ) {
                $options[] = array( 'label' => $state, 'value' => $code );
            }
        }
        $multiple = in_array( $type, array( 'listcheckbox', 'listmultiselect' ), true ) || ( 'listimage' === $type && ! empty( $field['allow_multi_select'] ) );
        $props = array();
        $choices = array();
        $defaults = array();
        foreach ( self::sorted( array_filter( (array) $options, 'is_array' ) ) as $option ) {
            $text = self::text( $option['label'] ?? '' );
            $value = self::text( $option['value'] ?? '' );
            if ( '' === $value ) {
                if ( '' !== $text && ! in_array( $type, array( 'listcheckbox', 'listradio', 'listimage' ), true ) ) {
                    $props['placeholder'] = $text;
                }
                continue;
            }
            $item = $value === $text ? $text : array( 'value' => $value, 'text' => $text );
            if ( 'listimage' === $type ) {
                $item = array( 'value' => $value, 'text' => $text, 'imageLink' => esc_url_raw( (string) ( $option['image'] ?? '' ) ) );
            }
            $choices[] = $item;
            if ( ! empty( $option['selected'] ) ) {
                $defaults[] = $value;
            }
        }
        $types = array( 'listradio' => 'radiogroup', 'listcheckbox' => 'checkbox', 'listmultiselect' => 'tagbox', 'listimage' => 'imagepicker' );
        $props = array( 'type' => $types[ $type ] ?? 'dropdown' ) + ( in_array( $type, array( 'listradio', 'listcheckbox', 'listimage' ), true ) ? array() : $props );
        if ( 'listimage' === $type ) {
            $props['showLabel'] = ! isset( $field['show_option_labels'] ) || ! empty( $field['show_option_labels'] );
            if ( $multiple ) {
                $props['multiSelect'] = true;
            }
            if ( (int) ( $field['num_columns'] ?? 0 ) > 0 ) {
                $props['colCount'] = (int) $field['num_columns'];
            }
        } elseif ( in_array( $type, array( 'listradio', 'listcheckbox' ), true ) && 'horizontal' === ( $field['list_orientation'] ?? '' ) ) {
            $props['colCount'] = 0;
        }
        $props['choices'] = $choices;
        if ( $defaults ) {
            $props['defaultValue'] = $multiple ? $defaults : $defaults[0];
        }
        return $props;
    }

    private static function placeholder_default( $field, $label, &$notes ) {
        $props = array();
        if ( '' !== self::text( $field['placeholder'] ?? '' ) ) {
            $props['placeholder'] = self::text( $field['placeholder'] );
        }
        $default = self::text( $field['default'] ?? '' );
        if ( preg_match( '/\{[\w-]+:[^{}]*\}/', $default ) ) {
            /* translators: 1: field label, 2: default value */
            $notes[] = sprintf( __( 'The default value of "%1$s" (%2$s) uses a merge tag and was not imported.', 'surveyjs' ), $label, $default );
        } elseif ( '' !== $default ) {
            $props['defaultValue'] = $default;
        }
        return $props;
    }

    private static function confirm_validator( &$element, $by_key, &$notes ) {
        $target = $by_key[ $element['confirmOf'] ] ?? null;
        unset( $element['confirmOf'] );
        if ( ! $target ) {
            /* translators: %s: field label */
            $notes[] = sprintf( __( 'The confirm field "%s" was imported without its check: the field it confirms was not found.', 'surveyjs' ), $element['title'] );
            return;
        }
        if ( isset( $target['question']['inputType'] ) ) {
            $element['inputType'] = $target['question']['inputType'];
        }
        $element['validators'] = array( array(
            'type'       => 'expression',
            'expression' => '{' . $element['name'] . '} = {' . $target['name'] . '}',
            /* translators: %s: field label */
            'text'       => sprintf( __( 'The value does not match %s.', 'surveyjs' ), SurveyJS_Survey_Values::question_title( $target['question'] ) ),
        ) );
    }

    private static function visibility( $conditions, $by_key, &$notes ) {
        $shown = array();
        $hidden = array();
        foreach ( array_filter( $conditions, 'is_array' ) as $condition ) {
            $expression = self::expression( $condition, $by_key );
            $then = array_filter( (array) ( $condition['then'] ?? array() ), function ( $effect ) {
                return is_array( $effect ) && '' !== (string) ( $effect['key'] ?? '' );
            } );
            $targets = array();
            foreach ( $then as $effect ) {
                $trigger = (string) ( $effect['trigger'] ?? '' );
                $target = $by_key[ (string) $effect['key'] ] ?? null;
                if ( ! $target || ! in_array( $trigger, array( 'show_field', 'hide_field' ), true ) || '' === $expression ) {
                    /* translators: %s: field label */
                    $notes[] = sprintf( __( 'The conditional logic of "%s" was not imported.', 'surveyjs' ), $target ? $target['question']['title'] : (string) $effect['key'] );
                    continue;
                }
                $targets[ (string) $effect['key'] ] = $trigger;
                if ( 'show_field' === $trigger ) {
                    $shown[ $target['name'] ][] = $expression;
                } else {
                    $hidden[ $target['name'] ][] = $expression;
                }
            }
            foreach ( (array) ( $condition['else'] ?? array() ) as $effect ) {
                if ( ! is_array( $effect ) || '' === (string) ( $effect['key'] ?? '' ) ) {
                    continue;
                }
                $opposite = array( 'show_field' => 'hide_field', 'hide_field' => 'show_field' );
                $key = (string) $effect['key'];
                if ( ! isset( $targets[ $key ] ) || $opposite[ $targets[ $key ] ] !== ( $effect['trigger'] ?? '' ) ) {
                    $target = $by_key[ $key ] ?? null;
                    /* translators: %s: field label */
                    $notes[] = sprintf( __( 'The conditional logic of "%s" was not imported.', 'surveyjs' ), $target ? $target['question']['title'] : $key );
                }
            }
        }
        $visibility = array();
        foreach ( array_unique( array_merge( array_keys( $shown ), array_keys( $hidden ) ) ) as $name ) {
            $parts = array();
            if ( isset( $shown[ $name ] ) ) {
                $parts[] = implode( ' or ', count( $shown[ $name ] ) > 1 ? array_map( array( __CLASS__, 'group' ), $shown[ $name ] ) : $shown[ $name ] );
            }
            foreach ( $hidden[ $name ] ?? array() as $expression ) {
                $parts[] = '!(' . $expression . ')';
            }
            $visibility[ $name ] = implode( ' and ', count( $parts ) > 1 ? array_map( array( __CLASS__, 'group' ), $parts ) : $parts );
        }
        return $visibility;
    }

    private static function group( $expression ) {
        return preg_match( '/\s(and|or)\s/', $expression ) && 0 !== strpos( $expression, '!(' ) ? '(' . $expression . ')' : $expression;
    }

    private static function expression( $condition, $by_key ) {
        $rules = array();
        foreach ( (array) ( $condition['when'] ?? array() ) as $rule ) {
            if ( ! is_array( $rule ) || '' === (string) ( $rule['key'] ?? '' ) ) {
                continue;
            }
            $rule = self::rule( $rule, $by_key );
            if ( '' === $rule ) {
                return '';
            }
            $rules[] = $rule;
        }
        if ( ! $rules ) {
            return '';
        }
        return implode( 'any' === ( $condition['connector'] ?? 'all' ) ? ' or ' : ' and ', $rules );
    }

    private static function rule( $rule, $by_key ) {
        $ref = $by_key[ (string) $rule['key'] ] ?? null;
        if ( ! $ref || 'field' !== ( $rule['type'] ?? 'field' ) || in_array( $ref['type'], array( 'repeater', 'signature', 'file_upload' ), true ) ) {
            return '';
        }
        $name = '{' . $ref['name'] . '}';
        $comparator = (string) ( $rule['comparator'] ?? '' );
        $value = (string) ( $rule['value'] ?? '' );
        if ( 'checkbox' === $ref['type'] ) {
            $checked = in_array( strtolower( $value ), array( 'checked', '1', 'on', 'true' ), true );
            if ( in_array( $comparator, array( 'equal', 'notequal' ), true ) ) {
                return $name . ( $checked === ( 'equal' === $comparator ) ? ' = true' : ' <> true' );
            }
            return '';
        }
        if ( in_array( $comparator, array( 'empty', 'notempty' ), true ) ) {
            return $name . ' ' . $comparator;
        }
        if ( in_array( $comparator, array( 'greater', 'less' ), true ) ) {
            return is_numeric( $value ) ? $name . ( 'greater' === $comparator ? ' > ' : ' < ' ) . ( 0 + $value ) : '';
        }
        if ( false === strpos( $value, "'" ) ) {
            $quoted = "'" . $value . "'";
        } elseif ( false === strpos( $value, '"' ) ) {
            $quoted = '"' . $value . '"';
        } else {
            return '';
        }
        $multiple = in_array( $ref['question']['type'], array( 'checkbox', 'tagbox' ), true ) || ! empty( $ref['question']['multiSelect'] );
        $operators = array(
            'equal'       => $multiple ? 'contains' : '=',
            'notequal'    => $multiple ? 'notcontains' : '<>',
            'contains'    => 'contains',
            'notcontains' => 'notcontains',
        );
        return isset( $operators[ $comparator ] ) ? $name . ' ' . $operators[ $comparator ] . ' ' . $quoted : '';
    }

    private static function action_expression( $action, $by_key ) {
        $when = array_filter( (array) ( $action['conditions']['when'] ?? array() ), function ( $rule ) {
            return is_array( $rule ) && '' !== (string) ( $rule['key'] ?? '' );
        } );
        if ( ! $when ) {
            return null;
        }
        $expression = self::expression( $action['conditions'], $by_key );
        if ( '' !== $expression && isset( $action['conditions']['process'] ) && empty( $action['conditions']['process'] ) ) {
            $expression = '!(' . $expression . ')';
        }
        return $expression;
    }

    private static function completion( $actions, $title, $by_key, &$json, &$notes ) {
        $messages = array();
        foreach ( $actions as $action ) {
            $type = (string) ( $action['type'] ?? '' );
            if ( empty( $action['active'] ) || ! in_array( $type, array( 'successmessage', 'redirect' ), true ) ) {
                continue;
            }
            $name = self::text( $action['label'] ?? '' );
            $expression = self::action_expression( $action, $by_key );
            if ( '' === $expression ) {
                /* translators: %s: action name */
                $notes[] = sprintf( __( 'The action "%s" was not imported: its conditional logic is not supported.', 'surveyjs' ), $name );
                continue;
            }
            if ( 'successmessage' === $type ) {
                $html = trim( wp_kses_post( wpautop( self::tags( (string) ( $action['success_msg'] ?? '' ), $by_key, $title, true, $notes ) ) ) );
                if ( '' === $html ) {
                    continue;
                }
                if ( null === $expression ) {
                    $messages[] = $html;
                } else {
                    $json['completedHtmlOnCondition'][] = array( 'expression' => $expression, 'html' => $html );
                }
                continue;
            }
            $url = trim( self::tags( (string) ( $action['redirect_url'] ?? '' ), $by_key, $title, true, $notes ) );
            if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
                $url = home_url( $url );
            }
            if ( ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) {
                /* translators: %s: action name */
                $notes[] = sprintf( __( 'The redirect "%s" was not imported: it has no valid URL.', 'surveyjs' ), $name );
                continue;
            }
            if ( null === $expression ) {
                $json['navigateToUrl'] = $url;
            } else {
                $json['navigateToUrlOnCondition'][] = array( 'expression' => $expression, 'url' => $url );
            }
        }
        if ( $messages ) {
            $json['completedHtml'] = implode( "\n", $messages );
        }
    }

    private static function notification_settings( $actions, $by_key, &$notes ) {
        $emails = array_values( array_filter( $actions, function ( $action ) {
            return 'email' === ( $action['type'] ?? '' );
        } ) );
        usort( $emails, function ( $a, $b ) {
            return (int) empty( $a['active'] ) - (int) empty( $b['active'] );
        } );
        $notification = null;
        $auto_reply = null;
        $others = array();
        foreach ( $emails as $email ) {
            $recipient = self::email_field( $email['to'] ?? '', $by_key );
            if ( '' === $recipient && ! $notification ) {
                $notification = $email;
            } elseif ( '' !== $recipient && ! $auto_reply ) {
                $auto_reply = $email + array( 'recipient' => $recipient );
            } else {
                $others[] = $email;
            }
        }

        $settings = array( 'notificationEnabled' => $notification && ! empty( $notification['active'] ) );
        if ( $notification ) {
            $html = 'plain' !== ( $notification['email_format'] ?? 'html' );
            $settings['notificationTo'] = self::tags( $notification['to'] ?? '', $by_key, '', false, $notes );
            $settings['notificationSubject'] = self::tags( $notification['email_subject'] ?? '', $by_key, '', false, $notes );
            $settings['notificationFromName'] = self::tags( $notification['from_name'] ?? '', $by_key, '', false, $notes );
            $settings['notificationFromEmail'] = self::tags( $notification['from_address'] ?? '', $by_key, '', false, $notes );
            $settings['notificationMessage'] = self::tags( $notification[ $html ? 'email_message' : 'email_message_plain' ] ?? '', $by_key, '', false, $notes );
            $settings['notificationHtml'] = $html;
            $reply_to = trim( (string) ( $notification['reply_to'] ?? '' ) );
            if ( '' !== $reply_to ) {
                $settings['notificationReplyTo'] = self::email_field( $reply_to, $by_key );
                if ( '' === $settings['notificationReplyTo'] ) {
                    /* translators: %s: Reply-To value */
                    $notes[] = sprintf( __( 'Reply-To "%s" was not imported: Reply-To can only be an Email field.', 'surveyjs' ), $reply_to );
                }
            }
            self::email_notes( $notification, $by_key, $notes );
        }
        if ( $auto_reply && in_array( $settings['notificationReplyTo'] ?? '', array( '', $auto_reply['recipient'] ), true ) ) {
            $html = 'plain' !== ( $auto_reply['email_format'] ?? 'html' );
            $settings['notificationReplyTo'] = $auto_reply['recipient'];
            $settings['autoReplyEnabled'] = ! empty( $auto_reply['active'] );
            $settings['autoReplySubject'] = self::tags( $auto_reply['email_subject'] ?? '', $by_key, '', false, $notes );
            $settings['autoReplyMessage'] = self::tags( $auto_reply[ $html ? 'email_message' : 'email_message_plain' ] ?? '', $by_key, '', false, $notes );
            $settings['autoReplyHtml'] = $html;
            /* translators: %s: email action name */
            $notes[] = sprintf( __( 'The email action "%s" was imported as the auto-reply.', 'surveyjs' ), self::text( $auto_reply['label'] ?? '' ) );
            foreach ( array( 'from_name', 'from_address', 'reply_to' ) as $option ) {
                if ( '' !== trim( (string) ( $auto_reply[ $option ] ?? '' ) ) && trim( (string) $auto_reply[ $option ] ) !== trim( (string) ( $notification[ $option ] ?? '' ) ) ) {
                    /* translators: %s: email action name */
                    $notes[] = sprintf( __( 'The sender and Reply-To of the email action "%s" were not imported: the auto-reply uses the sender of the email notification.', 'surveyjs' ), self::text( $auto_reply['label'] ?? '' ) );
                    break;
                }
            }
            self::email_notes( $auto_reply, $by_key, $notes );
        } elseif ( $auto_reply ) {
            $others[] = $auto_reply;
        }
        foreach ( $others as $other ) {
            /* translators: %s: email action name */
            $notes[] = sprintf( __( 'The email action "%s" was not imported: a form has one email notification and one auto-reply.', 'surveyjs' ), self::text( $other['label'] ?? '' ) );
        }
        return $settings;
    }

    private static function email_notes( $email, $by_key, &$notes ) {
        $name = self::text( $email['label'] ?? '' );
        foreach ( array( 'cc' => 'CC', 'bcc' => 'BCC' ) as $option => $header ) {
            if ( '' !== trim( (string) ( $email[ $option ] ?? '' ) ) ) {
                /* translators: 1: CC or BCC, 2: email addresses, 3: email action name */
                $notes[] = sprintf( __( '%1$s "%2$s" of the email action "%3$s" was not imported: add these addresses to Send To if needed.', 'surveyjs' ), $header, $email[ $option ], $name );
            }
        }
        if ( ! empty( $email['attach_csv'] ) || ! empty( $email['file_attachment'] ) ) {
            /* translators: %s: email action name */
            $notes[] = sprintf( __( 'The attachments of the email action "%s" were not imported.', 'surveyjs' ), $name );
        }
        if ( null !== self::action_expression( $email, $by_key ) ) {
            /* translators: %s: email action name */
            $notes[] = sprintf( __( 'The conditional logic of the email action "%s" was not imported.', 'surveyjs' ), $name );
        }
    }

    private static function form_notes( $settings, &$notes ) {
        if ( ! empty( $settings['logged_in'] ) ) {
            $notes[] = __( 'The "Require user to be logged in" restriction was not imported: the form is open to all visitors.', 'surveyjs' );
        }
        if ( (int) ( $settings['sub_limit_number'] ?? 0 ) > 0 ) {
            /* translators: %d: submission limit */
            $notes[] = sprintf( __( 'The submission limit (%d) was not imported.', 'surveyjs' ), (int) $settings['sub_limit_number'] );
        }
        if ( ! empty( $settings['unique_field'] ) ) {
            $notes[] = __( 'The unique field restriction was not imported.', 'surveyjs' );
        }
        if ( ! empty( array_filter( (array) ( $settings['calculations'] ?? array() ) ) ) ) {
            $notes[] = __( 'Calculations were not imported.', 'surveyjs' );
        }
    }

    private static function tags( $text, $by_key, $title, $html, &$notes ) {
        return preg_replace_callback( '/\{([a-z_]+:[^{}\s]+|all_fields_table|fields_table)\}/', function ( $match ) use ( $by_key, $title, $html, &$notes ) {
            $tag = $match[1];
            $field = 0 === strpos( $tag, 'field:' ) ? substr( $tag, 6 ) : null;
            if ( null !== $field && isset( $by_key[ $field ] ) && ! in_array( $by_key[ $field ]['type'], array( 'repeater' ), true ) ) {
                return $html ? '{' . $by_key[ $field ]['name'] . '}' : '{field:' . $by_key[ $field ]['name'] . '}';
            }
            $tags = array(
                'all_fields_table'   => '{all_fields}',
                'fields_table'       => '{all_fields}',
                'field:all_fields'   => '{all_fields}',
                'system:admin_email' => '{admin_email}',
                'wp:admin_email'     => '{admin_email}',
                'site:title'         => '{site_name}',
                'wp:site_title'      => '{site_name}',
                'form:title'         => '{form_name}',
            );
            if ( in_array( $tag, array( 'site:url', 'wp:site_url' ), true ) ) {
                return $html ? esc_url( home_url() ) : home_url();
            }
            if ( isset( $tags[ $tag ] ) && ! $html ) {
                return $tags[ $tag ];
            }
            if ( isset( $tags[ $tag ] ) && '{all_fields}' !== $tags[ $tag ] ) {
                $values = array( '{admin_email}' => get_option( 'admin_email' ), '{site_name}' => get_bloginfo( 'name' ), '{form_name}' => $title );
                return esc_html( $values[ $tags[ $tag ] ] );
            }
            $notes[] = $html
                /* translators: %s: Ninja Forms merge tag */
                ? sprintf( __( 'Merge tag %s in the success message has no equivalent and was removed.', 'surveyjs' ), $match[0] )
                /* translators: %s: Ninja Forms merge tag */
                : sprintf( __( 'Merge tag %s has no equivalent and was kept as text.', 'surveyjs' ), $match[0] );
            return $html ? '' : $match[0];
        }, (string) $text );
    }

    private static function email_field( $text, $by_key ) {
        if ( ! preg_match( '/^\s*\{field:([^{}\s]+)\}\s*$/', (string) $text, $match ) || ! isset( $by_key[ $match[1] ] ) ) {
            return '';
        }
        return 'email' === $by_key[ $match[1] ]['type'] ? $by_key[ $match[1] ]['name'] : '';
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
