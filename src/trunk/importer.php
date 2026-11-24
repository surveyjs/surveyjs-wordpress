<?php
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class SurveyJS_Importer {

    const MAP_OPTION = 'surveyjs_imported_forms';
    const FILE_SOURCE_DIRS = array( 'wpforms', 'fluentform', 'ninja-forms', 'gravity_forms' );

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
        $form['json'] = self::with_variables( $form['json'] );
        $id = static::imported_id( $source_id );
        $updated = (bool) $id;
        $settings = $form['settings'];
        if ( $id ) {
            $survey = SurveyJS_Forms::find( $id );
            $current = SurveyJS_Form_Settings::get( $survey );
            $settings += array_intersect_key( $current, array_flip( array( 'storeEntries', 'themeMode', 'transparentBackground', 'captchaEnabled', 'akismetEnabled', 'minSubmitTimeEnabled', 'minSubmitTime', 'webhooks' ) ) );
            $settings += array_diff_key( $current, SurveyJS_Form_Settings::base_defaults() );
            SurveyJS_Forms::update( $id, array(
                'json'     => wp_json_encode( $form['json'] ),
                'settings' => wp_json_encode( SurveyJS_Form_Settings::sanitize( $settings, $survey ) ),
            ) );
        } else {
            $settings += array( 'minSubmitTimeEnabled' => true );
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

    // survey.js sets these variables at runtime. A calculated value without an expression is never computed, so it keeps
    // the runtime value and only declares the name for SurveyJS tools that check references.
    private static function with_variables( $json ) {
        $encoded = (string) wp_json_encode( $json );
        $declared = array_map( 'strtolower', array_column( array_filter( (array) ( $json['calculatedValues'] ?? array() ), 'is_array' ), 'name' ) );
        foreach ( array( SurveyJS_Form_Variables::QUERY, SurveyJS_Form_Variables::USER ) as $variable ) {
            if ( preg_match( '/\{#?' . $variable . '\./i', $encoded ) && ! in_array( strtolower( $variable ), $declared, true ) ) {
                $json['calculatedValues'][] = array( 'name' => $variable );
            }
        }
        return $json;
    }

    private static function save_entries( $survey_id, $source_id, $form, $entries ) {
        global $wpdb;
        $table = SurveyJS_Entries::table();
        $key = static::source() . ':' . $source_id;
        $map = self::map();
        $imported = (array) ( $map[ $key ]['entries'] ?? array() );
        $files = (array) ( $map[ $key ]['files'] ?? array() );
        $existing = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE form_id = %d', $table, $survey_id ) ) );
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
            $data = self::copy_entry_files( $data, $file_questions, $survey_id, $files, $counts );
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
                $result_id = SurveyJS_Entries::insert( $survey_id, $json, array( 'status' => $is_partial ? SurveyJS_Entries::PARTIAL : 'read' ) + $date_field );
                if ( $result_id ) {
                    $imported[ $entry_id ] = $result_id;
                    $counts['added']++;
                }
            }
            if ( $result_id ) {
                SurveyJS_Uploaded_Files::attach( $survey_id, $result_id, SurveyJS_Uploaded_Files::referenced_names( $data ), false );
            }
        }
        $map[ $key ] = array( 'id' => $survey_id, 'entries' => $imported, 'files' => $files );
        update_option( self::MAP_OPTION, $map, false );
        return $counts;
    }

    private static function copy_entry_files( $data, $file_questions, $survey_id, &$files, &$counts ) {
        foreach ( $file_questions as $name ) {
            if ( empty( $data[ $name ] ) || ! is_array( $data[ $name ] ) ) {
                continue;
            }
            foreach ( $data[ $name ] as $index => $file ) {
                $source = is_array( $file ) && is_string( $file['content'] ?? null ) ? $file['content'] : '';
                if ( '' === $source ) {
                    continue;
                }
                $original_name = is_string( $file['name'] ?? null ) ? $file['name'] : '';
                $copied = isset( $files[ $source ] ) ? SurveyJS_Uploaded_Files::key( $files[ $source ] ) : null;
                if ( null !== $copied && SurveyJS_Uploaded_Files::storage()->exists( $copied ) ) {
                    SurveyJS_Uploaded_Files::insert_records( array( array( 'file_key' => $copied, 'form_id' => $survey_id, 'original_name' => $original_name ) ) );
                    $data[ $name ][ $index ]['content'] = SurveyJS_Uploaded_Files::content_url( $copied );
                    continue;
                }
                $copy = self::copy_file( $source, $survey_id, $original_name );
                if ( null === $copy ) {
                    $counts['files_not_copied']++;
                    continue;
                }
                $files[ $source ] = $copy;
                $data[ $name ][ $index ]['content'] = SurveyJS_Uploaded_Files::content_url( $copy );
                $counts['files_copied']++;
            }
        }
        return $data;
    }

    private static function copy_file( $url, $survey_id, $original_name ) {
        $source = self::uploaded_file_path( $url );
        $ext = strtolower( (string) pathinfo( (string) $source, PATHINFO_EXTENSION ) );
        if ( null === $source || ! in_array( $ext, SurveyJS_UploadFiles::allowed_extensions(), true ) ) {
            return null;
        }
        $type = wp_check_filetype( $source );
        $size = filesize( $source );
        $key = SurveyJS_Uploaded_Files::store_upload(
            $source,
            $ext,
            array(
                'form_id'       => (int) $survey_id,
                'original_name' => $original_name,
                'mime'          => (string) $type['type'],
                'size'          => false === $size ? 0 : $size,
            )
        );
        return is_wp_error( $key ) ? null : $key;
    }

    // Read-only access to the folders where the source plugins keep entry files, or to Media Library files.
    // Files of SurveyJS itself are not a source, and realpath() is not used: it fails on stream wrappers such as vip://.
    private static function uploaded_file_path( $url ) {
        $upload_dir = wp_upload_dir( null, false );
        if ( ! empty( $upload_dir['error'] ) ) {
            return null;
        }
        // Only the path is compared: the site address may have changed since the entry was submitted.
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        $base = untrailingslashit( (string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_PATH ) ) . '/';
        if ( 0 !== strpos( $path, $base ) ) {
            return null;
        }
        $relative = rawurldecode( substr( $path, strlen( $base ) ) );
        if ( '' === $relative || 0 !== validate_file( $relative ) || str_contains( $relative, '\\' ) ) {
            return null;
        }
        $segments = explode( '/', $relative );
        foreach ( $segments as $segment ) {
            if ( '' === $segment || '.' === $segment[0] ) {
                return null;
            }
        }
        if ( ltrim( SurveyJS_Uploads_Storage::SUBDIR, '/' ) === $segments[0] || ( ! in_array( $segments[0], self::FILE_SOURCE_DIRS, true ) && ! self::attachment_id( $url ) ) ) {
            return null;
        }
        $file = $upload_dir['basedir'];
        foreach ( $segments as $segment ) {
            $file .= '/' . $segment;
            // A symlink inside uploads could point outside it. Stream wrappers have no symlinks.
            if ( ! wp_is_stream( $file ) && is_link( $file ) ) {
                return null;
            }
        }
        return is_file( $file ) && is_readable( $file ) ? $file : null;
    }

    private static function attachment_id( $url ) {
        if ( function_exists( 'wpcom_vip_attachment_url_to_postid' ) ) {
            return (int) wpcom_vip_attachment_url_to_postid( $url );
        }
        return (int) attachment_url_to_postid( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- the cached VIP version above is used where it exists.
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

    protected static function user_variable( $property ) {
        return in_array( $property, SurveyJS_Form_Variables::USER_PROPERTIES, true ) ? SurveyJS_Form_Variables::USER . '.' . $property : '';
    }

    protected static function query_variable( $param ) {
        return preg_match( '/^[A-Za-z0-9_-]+$/', (string) $param ) ? SurveyJS_Form_Variables::QUERY . '.' . $param : '';
    }

    // The first variable with a value wins; when none has one, SurveyJS falls back to defaultValue.
    protected static function variables_expression( $variables ) {
        $variables = array_values( array_unique( array_filter( $variables, 'strlen' ) ) );
        $expression = '';
        foreach ( array_reverse( $variables ) as $variable ) {
            // "#" keeps the text as is: without it SurveyJS turns "01234" into the number 1234.
            $value = '{#' . $variable . '}';
            $expression = '' === $expression ? $value : 'iif(' . $value . ' notempty, ' . $value . ', ' . $expression . ')';
        }
        return $expression;
    }

    protected static function quoted( $value ) {
        $value = (string) $value;
        if ( false === strpos( $value, "'" ) ) {
            return "'" . $value . "'";
        }
        return false === strpos( $value, '"' ) ? '"' . $value . '"' : '';
    }

    // SurveyJS expressions have no startsWith/endsWith, so the rule compares a substring of the answer's length in UTF-16 units, as JavaScript counts it.
    // The ending starts at sum({q.length}, -N): "{q.length} - N" is arithmetic on a text question, which SurveyJS expression checks report.
    protected static function affix_rule( $name, $starts, $value ) {
        $value = (string) $value;
        $quoted = self::quoted( $value );
        if ( '' === $value || '' === $quoted ) {
            return '';
        }
        $length = (int) ( strlen( mb_convert_encoding( $value, 'UTF-16LE', 'UTF-8' ) ) / 2 );
        return 'substring({#' . $name . '}, ' . ( $starts ? '0, ' . $length : 'sum({' . $name . '.length}, -' . $length . ')' ) . ') = ' . $quoted;
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
