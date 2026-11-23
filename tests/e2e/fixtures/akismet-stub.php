<?php
// Stands in for the Akismet service in the spam E2E tests: the real Akismet plugin runs with a fake key,
// and its requests are answered here, so no Akismet account is needed.

const SURVEYJS_E2E_AKISMET_KEY = 'e2e-akismet-key-0123456789';

add_filter( 'akismet_get_api_key', function () {
    return SURVEYJS_E2E_AKISMET_KEY;
} );

add_filter( 'pre_http_request', function ( $preempt, $args, $url ) {
    if ( false === strpos( (string) wp_parse_url( $url, PHP_URL_HOST ), 'akismet.com' ) ) {
        return $preempt;
    }
    $body = is_string( $args['body'] ?? null ) ? $args['body'] : '';
    $answer = 'valid';
    if ( false !== strpos( $url, 'comment-check' ) ) {
        update_option( 'surveyjs_e2e_akismet_request', $body, false );
        $answer = false !== strpos( $body, 'akismet-guaranteed-spam' ) ? 'true' : 'false';
    }
    return array(
        'headers'  => array(),
        'body'     => $answer,
        'response' => array( 'code' => 200, 'message' => 'OK' ),
        'cookies'  => array(),
        'filename' => null,
    );
}, 10, 3 );
