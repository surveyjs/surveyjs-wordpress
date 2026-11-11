<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Captcha {

    const DEFAULT_THRESHOLD = 0.5;
    const V3_ACTION = 'surveyjs_submit';

    public static function types() {
        return array(
            'none'         => __( 'None', 'surveyjs' ),
            'recaptcha_v2' => __( 'Google reCAPTCHA v2 (checkbox)', 'surveyjs' ),
            'recaptcha_v3' => __( 'Google reCAPTCHA v3 (invisible)', 'surveyjs' ),
            'turnstile'    => __( 'Cloudflare Turnstile', 'surveyjs' ),
        );
    }

    public static function settings() {
        $settings = (array) get_option( 'surveyjs-settings' );
        $type = (string) ( $settings['captcha_type'] ?? 'none' );
        return array(
            'type'      => array_key_exists( $type, self::types() ) ? $type : 'none',
            'siteKey'   => (string) ( $settings['captcha_site_key'] ?? '' ),
            'secretKey' => (string) ( $settings['captcha_secret_key'] ?? '' ),
            'threshold' => isset( $settings['captcha_threshold'] ) ? (float) $settings['captcha_threshold'] : self::DEFAULT_THRESHOLD,
        );
    }

    public static function sanitize_threshold( $value ) {
        if ( ! is_numeric( $value ) ) {
            return self::DEFAULT_THRESHOLD;
        }
        return round( min( 1, max( 0, (float) $value ) ), 1 );
    }

    public static function is_configured() {
        $captcha = self::settings();
        return 'none' !== $captcha['type'] && '' !== $captcha['siteKey'] && '' !== $captcha['secretKey'];
    }

    public static function is_enabled( $form_settings ) {
        return ! empty( $form_settings['captchaEnabled'] ) && self::is_configured();
    }

    public static function type_label() {
        return self::types()[ self::settings()['type'] ];
    }

    public static function settings_url() {
        return add_query_arg( array( 'page' => 'surveyjs-settings' ), admin_url( 'admin.php' ) );
    }

    public static function script_url() {
        $captcha = self::settings();
        switch ( $captcha['type'] ) {
            case 'recaptcha_v2':
                return 'https://www.google.com/recaptcha/api.js?render=explicit';
            case 'recaptcha_v3':
                return add_query_arg( 'render', rawurlencode( $captcha['siteKey'] ), 'https://www.google.com/recaptcha/api.js' );
            case 'turnstile':
                return 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        }
        return '';
    }

    public static function enqueue_script() {
        $url = self::script_url();
        if ( '' !== $url ) {
            wp_enqueue_script( 'wps-front--captcha', $url, array(), null, array( 'strategy' => 'async' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        }
    }

    public static function client_config() {
        $captcha = self::settings();
        return array(
            'type'     => $captcha['type'],
            'siteKey'  => $captcha['siteKey'],
            'action'   => self::V3_ACTION,
            'messages' => array(
                'required' => __( 'Please complete the CAPTCHA.', 'surveyjs' ),
                'failed'   => __( 'The form could not be submitted. Please try again.', 'surveyjs' ),
            ),
        );
    }

    public static function verify( $token ) {
        $captcha = self::settings();
        $token = sanitize_text_field( (string) $token );
        if ( '' === $token ) {
            return new WP_Error( 'captcha_missing', __( 'Please complete the CAPTCHA.', 'surveyjs' ) );
        }
        $url = 'turnstile' === $captcha['type']
            ? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            : 'https://www.google.com/recaptcha/api/siteverify';
        $response = wp_remote_post( $url, array(
            'timeout' => 3,
            'body'    => array(
                'secret'   => $captcha['secretKey'],
                'response' => $token,
            ),
        ) );
        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return new WP_Error( 'captcha_unavailable', __( 'The CAPTCHA could not be verified. Please try again later.', 'surveyjs' ) );
        }
        $result = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $result ) || empty( $result['success'] ) ) {
            return new WP_Error( 'captcha_failed', __( 'CAPTCHA verification failed. Please try again.', 'surveyjs' ) );
        }
        if ( 'recaptcha_v3' === $captcha['type'] ) {
            $score = (float) ( $result['score'] ?? 0 );
            if ( $score < $captcha['threshold'] || self::V3_ACTION !== ( $result['action'] ?? '' ) ) {
                return new WP_Error( 'captcha_low_score', __( 'The submission looks like spam and was not accepted.', 'surveyjs' ) );
            }
        }
        return true;
    }

    public static function import( &$settings, &$notes, $source_captcha ) {
        if ( self::is_configured() ) {
            $settings['captchaEnabled'] = true;
            return;
        }
        /* translators: %s: CAPTCHA service used by the imported form, for example "Google reCAPTCHA" */
        $notes[] = sprintf( __( 'The form used %s. CAPTCHA is off: set up the CAPTCHA keys in SurveyJS Settings, then turn on CAPTCHA on the Spam Protection tab.', 'surveyjs' ), $source_captcha );
    }
}
