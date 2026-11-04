<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Form_Settings {

    public static function defaults() {
        return apply_filters( 'surveyjs_form_settings_defaults', self::base_defaults() );
    }

    public static function base_defaults() {
        return array(
            'storeEntries'          => true,
            'saveResume'            => false,
            'savePartialEntries'    => false,
            'notificationEnabled'   => false,
            'notificationTo'        => '',
            'notificationFromName'  => '',
            'notificationFromEmail' => '',
            'notificationReplyTo'   => '',
            'notificationSubject'   => '',
            'notificationMessage'   => '',
            'notificationHtml'      => false,
            'notificationAttachments' => '',
            'autoReplyEnabled'      => false,
            'autoReplySubject'      => '',
            'autoReplyMessage'      => '',
            'autoReplyHtml'         => false,
            'autoReplyAttachments'  => '',
            'themeMode'             => 'adaptive',
            'transparentBackground' => false,
            'captchaEnabled'        => false,
        );
    }

    public static function theme_modes() {
        return array( 'adaptive', 'surveyjs' );
    }

    public static function fallbacks() {
        return array(
            'notificationTo'        => get_option( 'admin_email' ),
            'notificationFromName'  => '{site_name}',
            'notificationFromEmail' => self::default_from_email(),
            'notificationSubject'   => 'New entry: {form_name}',
            'notificationMessage'   => '{all_fields}',
            'autoReplySubject'      => 'Thanks for contacting {site_name}',
            'autoReplyMessage'      => "Thanks for your message. We received it and will get back to you soon.\n\nYour answers:\n{all_fields}",
        );
    }

    public static function default_from_email() {
        $host = strtolower( (string) wp_parse_url( network_home_url(), PHP_URL_HOST ) );
        return 'wordpress@' . preg_replace( '#^www\.#', '', $host );
    }

    public static function value( $settings, $key ) {
        $value = trim( (string) ( $settings[ $key ] ?? '' ) );
        return '' !== $value ? $value : ( self::fallbacks()[ $key ] ?? '' );
    }

    private static function stored( $survey ) {
        $stored = json_decode( (string) ( $survey->settings ?? '' ), true );
        return is_array( $stored ) ? $stored : array();
    }

    public static function get( $survey ) {
        $stored = array_intersect_key( self::stored( $survey ), self::defaults() );
        if ( ! isset( $stored['themeMode'] ) && ! empty( array_filter( SurveyJS_Client::decode_survey_json( $survey->theme ?? '' ) ) ) ) {
            // Forms styled before the adaptive theme existed keep their SurveyJS theme.
            $stored['themeMode'] = 'surveyjs';
        }
        return array_merge( self::defaults(), $stored );
    }

    // Pass $survey when the result replaces its stored settings: keys no plugin declares right now
    // (e.g. Pro's while Pro is inactive) are kept instead of being dropped.
    public static function sanitize( $input, $survey = null ) {
        $input = array_merge( self::defaults(), is_array( $input ) ? $input : array() );
        $sanitized = apply_filters( 'surveyjs_form_settings_sanitize', self::sanitize_base( $input ), $input );
        $sanitized = array_intersect_key( is_array( $sanitized ) ? $sanitized : array(), self::defaults() );
        return $survey ? $sanitized + array_diff_key( self::stored( $survey ), self::defaults() ) : $sanitized;
    }

    private static function sanitize_base( $input ) {
        return array(
            'storeEntries'          => ! empty( $input['storeEntries'] ),
            'saveResume'            => ! empty( $input['saveResume'] ),
            'savePartialEntries'    => ! empty( $input['savePartialEntries'] ),
            'notificationEnabled'   => ! empty( $input['notificationEnabled'] ),
            'notificationTo'        => sanitize_text_field( $input['notificationTo'] ),
            'notificationFromName'  => sanitize_text_field( $input['notificationFromName'] ),
            'notificationFromEmail' => sanitize_text_field( $input['notificationFromEmail'] ),
            'notificationReplyTo'   => sanitize_text_field( $input['notificationReplyTo'] ),
            'notificationSubject'   => sanitize_text_field( $input['notificationSubject'] ),
            'notificationMessage'   => self::sanitize_message( $input['notificationMessage'], $input['notificationHtml'] ),
            'notificationHtml'      => ! empty( $input['notificationHtml'] ),
            'notificationAttachments' => sanitize_text_field( $input['notificationAttachments'] ),
            'autoReplyEnabled'      => ! empty( $input['autoReplyEnabled'] ),
            'autoReplySubject'      => sanitize_text_field( $input['autoReplySubject'] ),
            'autoReplyMessage'      => self::sanitize_message( $input['autoReplyMessage'], $input['autoReplyHtml'] ),
            'autoReplyHtml'         => ! empty( $input['autoReplyHtml'] ),
            'autoReplyAttachments'  => sanitize_text_field( $input['autoReplyAttachments'] ),
            'themeMode'             => in_array( $input['themeMode'], self::theme_modes(), true ) ? $input['themeMode'] : 'adaptive',
            'transparentBackground' => ! empty( $input['transparentBackground'] ),
            'captchaEnabled'        => ! empty( $input['captchaEnabled'] ),
        );
    }

    private static function sanitize_message( $message, $html ) {
        return ! empty( $html ) ? wp_kses_post( $message ) : sanitize_textarea_field( $message );
    }
}
