<?php
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class SurveyJS_Importer {

    const MAP_OPTION = 'surveyjs_imported_forms';

    abstract public static function source();

    abstract public static function is_available();

    abstract public static function forms();

    abstract protected static function convert_form( $source_id );

    public static function entries_available() {
        return false;
    }

    protected static function entries( $source_id, $form ) {
        return array();
    }

    protected static function entries_page( $source_id, $form, $offset, $limit ) {
        $entries = static::entries( $source_id, $form );
        return array(
            'entries' => $limit ? array_slice( $entries, $offset, $limit, true ) : $entries,
            'total'   => count( $entries ),
        );
    }

    protected static function copies_entry_files() {
        return false;
    }

    protected static function entry_dates( $source_id, $entry_ids ) {
        return array();
    }

    protected static function partial_entry_ids( $source_id, $entry_ids ) {
        return array();
    }

    protected static function gmt_date( $date, $is_gmt = true ) {
        $date = (string) $date;
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date ) || 0 === strpos( $date, '0000' ) ) {
            return '';
        }
        return $is_gmt ? $date : get_gmt_from_date( $date );
    }

    public static function import( $source_id, $with_entries = false ) {
        return static::import_part( $source_id, $with_entries, 0, 0 );
    }

    public static function import_part( $source_id, $with_entries, $offset, $limit ) {
        $form = static::convert_form( $source_id );
        if ( is_wp_error( $form ) ) {
            return $form;
        }
        if ( $offset ) {
            $id = static::imported_id( $source_id );
            if ( ! $id ) {
                return new WP_Error( 'not_imported', __( 'The SurveyJS form was deleted during the import.', 'surveyjs' ) );
            }
            $result = array( 'id' => $id, 'notes' => array() );
        } else {
            $result = self::save( $source_id, $form );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }
        if ( $with_entries && static::entries_available() ) {
            $page = static::entries_page( $source_id, $form, $offset, $limit );
            $result['entries'] = self::save_entries( $result['id'], $source_id, $form, $page['entries'] ) + array(
                'total' => $page['total'],
                'next'  => $limit && $offset + $limit < $page['total'] ? $offset + $limit : null,
            );
        }
        return $result;
    }

    public static function imported_id( $source_id ) {
        $id = (int) ( self::map()[ static::source() . ':' . $source_id ]['id'] ?? 0 );
        return $id && SurveyJS_Forms::find( $id ) ? $id : 0;
    }

    public static function forget( $survey_ids ) {
        $survey_ids = array_map( 'intval', (array) $survey_ids );
        $map = self::map();
        $links = array_filter( $map, function ( $link ) use ( $survey_ids ) {
            return ! in_array( (int) ( $link['id'] ?? 0 ), $survey_ids, true );
        } );
        if ( count( $links ) !== count( $map ) ) {
            update_option( self::MAP_OPTION, $links, false );
        }
    }

    private static function map() {
        $map = get_option( self::MAP_OPTION, array() );
        return is_array( $map ) ? $map : array();
    }

    private static function save( $source_id, $form ) {
        if ( empty( $form['json']['elements'] ) && empty( $form['json']['pages'] ) ) {
            return new WP_Error( 'no_fields', __( 'The form has no fields to import.', 'surveyjs' ) );
        }
        $id = static::imported_id( $source_id );
        $updated = (bool) $id;
        $settings = $form['settings'];
        if ( $id ) {
            $survey = SurveyJS_Forms::find( $id );
            $current = SurveyJS_Form_Settings::get( $survey );
            $settings += array_intersect_key( $current, array_flip( array( 'storeEntries', 'themeMode', 'transparentBackground', 'captchaEnabled' ) ) );
            $settings += array_diff_key( $current, SurveyJS_Form_Settings::base_defaults() );
            SurveyJS_Forms::update( $id, array(
                'json'     => wp_json_encode( $form['json'] ),
                'settings' => wp_json_encode( SurveyJS_Form_Settings::sanitize( $settings, $survey ) ),
            ) );
        } else {
            $id = SurveyJS_Forms::create( array(
                'name'     => sanitize_text_field( $form['name'] ),
                'json'     => wp_json_encode( $form['json'] ),
                'settings' => wp_json_encode( SurveyJS_Form_Settings::sanitize( $settings ) ),
            ) );
            if ( ! $id ) {
                return new WP_Error( 'not_saved', __( 'The SurveyJS form could not be saved.', 'surveyjs' ) );
            }
            $map = self::map();
            $map[ static::source() . ':' . $source_id ] = array( 'id' => $id, 'entries' => array() );
            update_option( self::MAP_OPTION, $map, false );
        }
        return array(
            'id'      => $id,
            'name'    => $form['name'],
            'updated' => $updated,
            'skipped' => $form['skipped'],
            'notes'   => array_values( array_unique( $form['notes'] ) ),
        );
    }

    private static function save_entries( $survey_id, $source_id, $form, $entries ) {
        global $wpdb;
        $table = SurveyJS_Entries::table();
        $key = static::source() . ':' . $source_id;
        $map = self::map();
        $imported = (array) ( $map[ $key ]['entries'] ?? array() );
        $files = (array) ( $map[ $key ]['files'] ?? array() );
        $existing = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $table WHERE form_id = %d", $survey_id ) ) );
        $dates = $entries ? static::entry_dates( $source_id, array_keys( $entries ) ) : array();
        $partial = $entries ? array_map( 'intval', static::partial_entry_ids( $source_id, array_keys( $entries ) ) ) : array();
        $file_questions = array();
        if ( static::copies_entry_files() ) {
            foreach ( SurveyJS_Survey_Values::survey_questions( $form['json'] ) as $question ) {
                if ( 'file' === ( $question['type'] ?? '' ) ) {
                    $file_questions[] = $question['name'];
                }
            }
        }
        $counts = array( 'added' => 0, 'updated' => 0, 'files_copied' => 0, 'files_not_copied' => 0 );
        foreach ( $entries as $entry_id => $data ) {
            $result_id = isset( $imported[ $entry_id ] ) ? (int) $imported[ $entry_id ] : 0;
            if ( $result_id && ! in_array( $result_id, $existing, true ) ) {
                continue;
            }
            $data = self::copy_entry_files( $data, $file_questions, $files, $counts );
            $json = wp_json_encode( self::sanitize_entry( $data ) );
            $date = $dates[ $entry_id ] ?? '';
            $date_field = '' !== $date ? array( 'created_at' => $date ) : array();
            $is_partial = in_array( (int) $entry_id, $partial, true );
            if ( $result_id ) {
                $wpdb->update( $table, array( 'json' => $json ) + $date_field, array( 'id' => $result_id ) );
                if ( ! $is_partial ) {
                    SurveyJS_Entries::mark_finished( $result_id );
                }
                $counts['updated']++;
            } else {
                $new_id = SurveyJS_Entries::insert( $survey_id, $json, array( 'status' => $is_partial ? SurveyJS_Entries::PARTIAL : 'read' ) + $date_field );
                if ( $new_id ) {
                    $imported[ $entry_id ] = $new_id;
                    $counts['added']++;
                }
            }
        }
        $map[ $key ] = array( 'id' => $survey_id, 'entries' => $imported, 'files' => $files );
        update_option( self::MAP_OPTION, $map, false );
        return $counts;
    }

    private static function copy_entry_files( $data, $file_questions, &$files, &$counts ) {
        foreach ( $file_questions as $name ) {
            if ( empty( $data[ $name ] ) || ! is_array( $data[ $name ] ) ) {
                continue;
            }
            foreach ( $data[ $name ] as $index => $file ) {
                $source = is_array( $file ) && is_string( $file['content'] ?? null ) ? $file['content'] : '';
                if ( '' === $source ) {
                    continue;
                }
                if ( isset( $files[ $source ] ) && self::stored_file_exists( $files[ $source ] ) ) {
                    $data[ $name ][ $index ]['content'] = $files[ $source ];
                    continue;
                }
                $copy = self::copy_file( $source );
                if ( '' === $copy ) {
                    $counts['files_not_copied']++;
                    continue;
                }
                $files[ $source ] = $copy;
                $data[ $name ][ $index ]['content'] = $copy;
                $counts['files_copied']++;
            }
        }
        return $data;
    }

    private static function stored_file_exists( $url ) {
        $upload_dir = wp_upload_dir( null, false );
        return empty( $upload_dir['error'] ) && is_file( $upload_dir['basedir'] . SurveyJS_UploadFiles::SUBDIR . '/' . basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
    }

    private static function copy_file( $url ) {
        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) ) {
            return '';
        }
        $source = self::uploaded_file_path( $url, $upload_dir );
        $ext = strtolower( (string) pathinfo( $source, PATHINFO_EXTENSION ) );
        if ( '' === $source || ! in_array( $ext, SurveyJS_UploadFiles::allowed_extensions(), true ) ) {
            return '';
        }
        SurveyJS_UploadFiles::protect_upload_dir();
        $dir = $upload_dir['basedir'] . SurveyJS_UploadFiles::SUBDIR;
        $name = uniqid( (string) wp_rand(), true ) . '.' . $ext;
        if ( ! is_dir( $dir ) || ! @copy( $source, $dir . '/' . $name ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return '';
        }
        $stat = stat( $dir );
        chmod( $dir . '/' . $name, $stat['mode'] & 0000666 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        return $upload_dir['baseurl'] . SurveyJS_UploadFiles::SUBDIR . '/' . $name;
    }

    private static function uploaded_file_path( $url, $upload_dir ) {
        // Only the path is compared: the site address may have changed since the entry was submitted.
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        $base = untrailingslashit( (string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_PATH ) ) . '/';
        if ( 0 !== strpos( $path, $base ) ) {
            return '';
        }
        $root = realpath( $upload_dir['basedir'] );
        $file = realpath( $upload_dir['basedir'] . '/' . rawurldecode( substr( $path, strlen( $base ) ) ) );
        return $root && $file && 0 === strpos( $file, $root . DIRECTORY_SEPARATOR ) && is_file( $file ) ? $file : '';
    }

    private static function sanitize_entry( $value ) {
        if ( is_array( $value ) ) {
            return array_map( array( __CLASS__, 'sanitize_entry' ), $value );
        }
        return is_string( $value ) ? sanitize_textarea_field( $value ) : $value;
    }

    // A required boolean also accepts false (checked, then unchecked), so the validator demands true.
    protected static function agreement( $question, $message = '' ) {
        unset( $question['titleLocation'] );
        $question = array( 'type' => 'boolean', 'displayMode' => 'checkbox' ) + $question;
        if ( ! empty( $question['isRequired'] ) ) {
            $question['validators'] = array( array(
                'type'       => 'expression',
                'expression' => '{' . $question['name'] . '} = true',
                'text'       => '' !== $message ? $message : __( 'Please give your consent to submit the form.', 'surveyjs' ),
            ) );
        }
        return $question;
    }

    protected static function entry_value( $question, $value ) {
        $values = array_values( array_filter( array_map( 'trim', array_map( 'strval', array_filter( (array) $value, 'is_scalar' ) ) ), 'strlen' ) );
        $name = $question['name'];
        $type = $question['type'] ?? '';
        if ( ! $values ) {
            return array();
        }
        if ( 'boolean' === $type ) {
            return array( $name => true );
        }
        if ( in_array( $type, array( 'checkbox', 'radiogroup', 'dropdown', 'tagbox', 'imagepicker' ), true ) ) {
            $choices = array();
            foreach ( (array) ( $question['choices'] ?? array() ) as $choice ) {
                $choice_value = (string) ( is_array( $choice ) ? ( $choice['value'] ?? '' ) : $choice );
                $choices[ $choice_value ] = (string) ( is_array( $choice ) ? ( $choice['text'] ?? $choice_value ) : $choice );
            }
            $selected = array();
            $other = array();
            foreach ( $values as $item ) {
                $match = array_key_exists( $item, $choices ) ? $item : array_search( $item, $choices, true );
                if ( false === $match ) {
                    $other[] = $item;
                } else {
                    $selected[] = (string) $match;
                }
            }
            $result = array();
            if ( $other && ! empty( $question['showOtherItem'] ) ) {
                $selected[] = 'other';
                $prefix = (string) ( $question['otherText'] ?? '' );
                $result[ $name . '-Comment' ] = implode( ', ', array_map( function ( $item ) use ( $prefix ) {
                    return '' !== $prefix && 0 === strpos( $item, $prefix ) ? trim( substr( $item, strlen( $prefix ) ) ) : $item;
                }, $other ) );
            } else {
                $selected = array_merge( $selected, $other );
            }
            $multiple = in_array( $type, array( 'checkbox', 'tagbox' ), true ) || ! empty( $question['multiSelect'] );
            $result[ $name ] = $multiple ? $selected : $selected[0];
            return $result;
        }
        if ( in_array( $type, array( 'rating', 'slider' ), true ) || in_array( $question['inputType'] ?? '', array( 'number', 'range' ), true ) ) {
            return is_numeric( $values[0] ) ? array( $name => 0 + $values[0] ) : array();
        }
        return array( $name => implode( ', ', $values ) );
    }
}
