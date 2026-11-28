<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_JSON_Importer {

    const EXPORT_TYPE = 'surveyjs-forms';
    const EXPORT_VERSION = 1;
    const MAX_BYTES = 5 * MB_IN_BYTES;

    // Webhooks and integrations point to services and connections of the site the form came from.
    const SITE_SETTINGS = array( 'webhooks', 'integrations' );

    public static function is_available() {
        return true;
    }

    public static function forms() {
        return array();
    }

    public static function max_bytes() {
        $limit = (int) apply_filters( 'surveyjs_import_json_max_bytes', self::MAX_BYTES );
        $post_max = wp_convert_hr_to_bytes( (string) ini_get( 'post_max_size' ) );
        if ( $post_max > 0 ) {
            $limit = min( $limit, (int) floor( $post_max / 2 ) );
        }
        return max( KB_IN_BYTES, $limit );
    }

    public static function import( $name, $raw_json, $raw_theme = '', $raw_settings = '' ) {
        $raw_json = (string) $raw_json;
        $raw_theme = (string) $raw_theme;
        if ( strlen( $raw_json ) + strlen( $raw_theme ) > self::max_bytes() ) {
            return new WP_Error( 'too_large', __( 'The form JSON is too large to import.', 'surveyjs' ) );
        }
        $json = json_decode( $raw_json, true );
        if ( ! is_array( $json ) ) {
            return new WP_Error( 'invalid_json', __( 'The file is not valid JSON.', 'surveyjs' ) );
        }
        if ( ! self::is_survey( $json ) ) {
            return new WP_Error( 'not_a_form', __( 'This is not a SurveyJS form JSON: it has no pages or questions.', 'surveyjs' ) );
        }
        $notes = array();
        $theme = '' !== trim( $raw_theme ) ? json_decode( $raw_theme, true ) : null;
        if ( null !== $theme && ( ! is_array( $theme ) || wp_is_numeric_array( $theme ) ) ) {
            $theme = null;
            $notes[] = __( 'The theme was not imported: it is not a SurveyJS theme JSON.', 'surveyjs' );
        }
        $settings = '' !== trim( (string) $raw_settings ) ? json_decode( (string) $raw_settings, true ) : null;
        $settings = is_array( $settings ) && ! wp_is_numeric_array( $settings ) ? $settings : array( 'minSubmitTimeEnabled' => true );
        if ( array_intersect_key( $settings, array_flip( self::SITE_SETTINGS ) ) ) {
            $notes[] = __( 'Webhooks and integrations were not imported: set them up again on this site.', 'surveyjs' );
        }
        $defaults = SurveyJS_Form_Settings::defaults();
        $settings = array_filter( array_diff_key( array_intersect_key( $settings, $defaults ), array_flip( self::SITE_SETTINGS ) ), function ( $value, $key ) use ( $defaults ) {
            return is_array( $defaults[ $key ] ) ? is_array( $value ) : is_scalar( $value );
        }, ARRAY_FILTER_USE_BOTH );
        if ( $theme && ! isset( $settings['themeMode'] ) ) {
            $settings['themeMode'] = 'surveyjs';
        }
        $settings = SurveyJS_Form_Settings::sanitize( $settings );
        if ( ! empty( $settings['notificationEnabled'] ) || ! empty( $settings['autoReplyEnabled'] ) ) {
            $notes[] = __( 'The email notifications are on: check their recipients and sender on the Notifications tab.', 'surveyjs' );
        }
        $name = self::name( $name, $json );
        $unfiltered = current_user_can( 'unfiltered_html' );
        $id = SurveyJS_Forms::create( array(
            'name'     => $name,
            'json'     => $unfiltered ? $raw_json : wp_kses_post( $raw_json ),
            'theme'    => $theme ? ( $unfiltered ? $raw_theme : wp_kses_post( $raw_theme ) ) : '',
            'settings' => wp_json_encode( $settings ),
        ) );
        if ( ! $id ) {
            return new WP_Error( 'not_saved', __( 'The SurveyJS form could not be saved.', 'surveyjs' ) );
        }
        return array(
            'id'      => $id,
            'name'    => $name,
            'updated' => false,
            'skipped' => array(),
            'notes'   => $notes,
        );
    }

    public static function is_survey( $json ) {
        if ( ! is_array( $json ) || wp_is_numeric_array( $json ) ) {
            return false;
        }
        foreach ( array( 'pages', 'elements', 'questions' ) as $key ) {
            if ( ! empty( $json[ $key ] ) && is_array( $json[ $key ] ) ) {
                return true;
            }
        }
        return false;
    }

    private static function name( $name, $json ) {
        $name = sanitize_text_field( (string) $name );
        if ( '' === $name ) {
            $title = $json['title'] ?? '';
            if ( is_array( $title ) ) {
                $title = $title['default'] ?? reset( $title );
            }
            $name = sanitize_text_field( is_string( $title ) ? $title : '' );
        }
        $name = '' !== $name ? $name : __( 'Imported form', 'surveyjs' );
        return function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 200 ) : substr( $name, 0, 200 );
    }

    public static function export( $ids ) {
        $forms = array();
        foreach ( $ids as $id ) {
            $form = SurveyJS_Forms::find( $id );
            if ( ! $form ) {
                continue;
            }
            $settings = json_decode( $form->settings, true );
            $theme = SurveyJS_Client::decode_survey_json( $form->theme );
            $forms[] = array(
                'name'     => $form->name,
                'json'     => SurveyJS_Client::decode_survey_json( $form->json, false ),
                'theme'    => $theme ? SurveyJS_Client::decode_survey_json( $form->theme, false ) : null,
                'settings' => is_array( $settings ) ? (object) array_diff_key( $settings, array_flip( self::SITE_SETTINGS ) ) : new stdClass(),
            );
        }
        return array(
            'type'     => self::EXPORT_TYPE,
            'version'  => self::EXPORT_VERSION,
            'exported' => gmdate( 'c' ),
            'forms'    => $forms,
        );
    }

    public static function send_export( $ids ) {
        $file = sanitize_file_name( 'surveyjs-forms-' . wp_parse_url( home_url(), PHP_URL_HOST ) . '-' . gmdate( 'Y-m-d' ) . '.json' );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $file . '"' );
        header( 'X-Content-Type-Options: nosniff' );
        echo wp_json_encode( self::export( $ids ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a JSON file download, not HTML.
        exit;
    }
}
