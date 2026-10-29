<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Form_Settings {

    public static function defaults() {
        return array(
            'storeEntries'          => true,
            'notificationEnabled'   => false,
            'notificationTo'        => '',
            'notificationFromName'  => '',
            'notificationFromEmail' => '',
            'notificationReplyTo'   => '',
            'notificationSubject'   => '',
            'notificationMessage'   => '',
        );
    }

    public static function fallbacks() {
        return array(
            'notificationTo'        => get_option( 'admin_email' ),
            'notificationFromName'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
            'notificationFromEmail' => self::default_from_email(),
            'notificationSubject'   => 'New entry: {form_name}',
            'notificationMessage'   => '{all_fields}',
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

    public static function get( $survey ) {
        $stored = json_decode( (string) ( $survey->settings ?? '' ), true );
        $stored = is_array( $stored ) ? array_intersect_key( $stored, self::defaults() ) : array();
        return array_merge( self::defaults(), $stored );
    }

    public static function sanitize( $input ) {
        $input = array_merge( self::defaults(), is_array( $input ) ? $input : array() );
        return array(
            'storeEntries'          => ! empty( $input['storeEntries'] ),
            'notificationEnabled'   => ! empty( $input['notificationEnabled'] ),
            'notificationTo'        => sanitize_text_field( $input['notificationTo'] ),
            'notificationFromName'  => sanitize_text_field( $input['notificationFromName'] ),
            'notificationFromEmail' => sanitize_text_field( $input['notificationFromEmail'] ),
            'notificationReplyTo'   => sanitize_text_field( $input['notificationReplyTo'] ),
            'notificationSubject'   => sanitize_text_field( $input['notificationSubject'] ),
            'notificationMessage'   => sanitize_textarea_field( $input['notificationMessage'] ),
        );
    }
}
