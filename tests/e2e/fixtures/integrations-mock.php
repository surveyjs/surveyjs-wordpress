<?php
/*
 * Test double for Mailchimp, installed as a mu-plugin. Only requests to the fake Mailchimp data center us0 are answered here, so real keys entered on the same site still reach the services.
 * A key of 32 "f" characters in data center us0 is rejected like a revoked key.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

const SURVEYJS_MOCK_OPTION = 'surveyjs_e2e_integrations_mock';
const SURVEYJS_MOCK_MAILCHIMP = 'https://us0.api.mailchimp.com/3.0';
define( 'SURVEYJS_MOCK_REVOKED_KEY', str_repeat( 'f', 32 ) . '-us0' );

function surveyjs_mock_state() {
    $state = get_option( SURVEYJS_MOCK_OPTION, array() );
    return array_merge( array( 'members' => array(), 'tags' => array() ), is_array( $state ) ? $state : array() );
}

function surveyjs_mock_response( $code, $body ) {
    return array(
        'headers'  => array( 'content-type' => 'application/json' ),
        'body'     => is_string( $body ) ? $body : wp_json_encode( $body ),
        'response' => array( 'code' => $code, 'message' => get_status_header_desc( $code ) ),
        'cookies'  => array(),
        'filename' => null,
    );
}

function surveyjs_mock_mailchimp( $method, $path, $body, $state ) {
    $list = array( 'id' => 'demo01', 'name' => 'Newsletter (mock)', 'stats' => array( 'member_count' => count( $state['members'] ) ) );
    if ( '/' === $path || '' === $path ) {
        return array( 200, array( 'account_name' => 'Sandbox Mailchimp (mock)', 'email' => 'owner@example.com' ) );
    }
    if ( '/lists' === $path ) {
        return array( 200, array( 'lists' => array( $list ) ) );
    }
    if ( ! preg_match( '#^/lists/([^/]+)(/merge-fields|/members/([a-f0-9]{32})(/tags)?)?$#', $path, $m ) ) {
        return array( 404, array( 'title' => 'Resource Not Found', 'detail' => 'The requested resource could not be found.' ) );
    }
    if ( 'demo01' !== $m[1] ) {
        return array( 404, array( 'title' => 'Resource Not Found', 'detail' => 'The requested resource could not be found.' ) );
    }
    if ( empty( $m[2] ) ) {
        return array( 200, $list );
    }
    if ( '/merge-fields' === $m[2] ) {
        return array( 200, array( 'merge_fields' => array(
            array( 'tag' => 'FNAME', 'name' => 'First Name', 'type' => 'text', 'required' => false ),
            array( 'tag' => 'LNAME', 'name' => 'Last Name', 'type' => 'text', 'required' => false ),
            array( 'tag' => 'COMPANY', 'name' => 'Company', 'type' => 'text', 'required' => false ),
            array( 'tag' => 'ADDRESS', 'name' => 'Address', 'type' => 'address', 'required' => false ),
            array( 'tag' => 'BIRTHDAY', 'name' => 'Birthday', 'type' => 'birthday', 'required' => false ),
        ) ) );
    }
    $hash = $m[3];
    if ( ! empty( $m[4] ) ) {
        $state['tags'][ $hash ] = array_values( array_unique( array_merge( $state['tags'][ $hash ] ?? array(), array_column( (array) ( $body['tags'] ?? array() ), 'name' ) ) ) );
        update_option( SURVEYJS_MOCK_OPTION, $state, false );
        return array( 204, '' );
    }
    if ( 'GET' === $method ) {
        return isset( $state['members'][ $hash ] ) ? array( 200, $state['members'][ $hash ] ) : array( 404, array( 'title' => 'Resource Not Found', 'detail' => 'The requested resource could not be found.' ) );
    }
    if ( 'PUT' === $method ) {
        if ( str_ends_with( (string) ( $body['email_address'] ?? '' ), '@compliance.test' ) ) {
            return array( 400, array( 'title' => 'Member In Compliance State', 'detail' => 'The contact is in a compliance state and cannot be subscribed.' ) );
        }
        $existing = $state['members'][ $hash ] ?? null;
        $state['members'][ $hash ] = array(
            'email_address' => $body['email_address'] ?? '',
            'status'        => $existing ? $existing['status'] : ( $body['status_if_new'] ?? 'subscribed' ),
            'merge_fields'  => array_merge( $existing['merge_fields'] ?? array(), (array) ( $body['merge_fields'] ?? array() ) ),
            'updated'       => gmdate( 'c' ),
        );
        update_option( SURVEYJS_MOCK_OPTION, $state, false );
        return array( 200, $state['members'][ $hash ] );
    }
    return array( 405, array( 'title' => 'Method Not Allowed', 'detail' => $method ) );
}

add_filter( 'pre_http_request', function ( $preempt, $args, $url ) {
    $state = surveyjs_mock_state();
    $body = json_decode( (string) ( $args['body'] ?? '' ), true );
    if ( 0 === strpos( $url, SURVEYJS_MOCK_MAILCHIMP ) ) {
        $auth = (string) ( $args['headers']['Authorization'] ?? '' );
        if ( 'Bearer ' . SURVEYJS_MOCK_REVOKED_KEY === $auth ) {
            return surveyjs_mock_response( 401, array( 'title' => 'API Key Invalid', 'detail' => 'Your API key may be invalid, or you\'ve attempted to access the wrong datacenter.' ) );
        }
        $path = (string) wp_parse_url( substr( $url, strlen( SURVEYJS_MOCK_MAILCHIMP ) ), PHP_URL_PATH );
        list( $code, $response ) = surveyjs_mock_mailchimp( strtoupper( (string) ( $args['method'] ?? 'GET' ) ), $path, is_array( $body ) ? $body : array(), $state );
        return surveyjs_mock_response( $code, $response );
    }
    return $preempt;
}, 10, 3 );
