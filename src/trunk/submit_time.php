<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Submit_Time {

    const DEFAULT_SECONDS = 3;
    const MAX_SECONDS = 60;

    public static function is_enabled( $settings ) {
        return ! empty( $settings['minSubmitTimeEnabled'] );
    }

    public static function sanitize_seconds( $value ) {
        return min( self::MAX_SECONDS, max( 1, absint( $value ) ) );
    }

    public static function client_config( $form_id, $settings ) {
        $time = time();
        return array(
            'token' => $time . '.' . self::signature( $form_id, $time ),
            'wait'  => self::sanitize_seconds( $settings['minSubmitTime'] ?? self::DEFAULT_SECONDS ) * 1000,
        );
    }

    // The token carries the time the form was rendered, signed by the site, so a script that posts the form
    // right after loading the page cannot claim it was opened earlier. On a cached page the time is older and the check passes.
    public static function verify( $form_id, $settings, $token ) {
        $parts = explode( '.', (string) $token, 2 );
        $time = absint( $parts[0] );
        $signed = 2 === count( $parts ) && $time > 0 && hash_equals( self::signature( $form_id, $time ), $parts[1] );
        if ( $signed && time() - $time >= self::sanitize_seconds( $settings['minSubmitTime'] ?? self::DEFAULT_SECONDS ) ) {
            return true;
        }
        return new WP_Error( 'submitted_too_fast', __( 'The form was submitted too quickly. Please wait a few seconds and try again.', 'surveyjs' ) );
    }

    private static function signature( $form_id, $time ) {
        return wp_hash( 'surveyjs-submit-time|' . absint( $form_id ) . '|' . absint( $time ), 'nonce' );
    }
}
