<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Form_Variables {

    const USER = 'wpUser';
    const QUERY = 'urlParams';
    const USER_PROPERTIES = array( 'id', 'login', 'email', 'url', 'display_name', 'first_name', 'last_name', 'full_name', 'nickname' );

    public static function client_config( $json ) {
        $user = self::user( $json );
        return array(
            'query'  => self::QUERY,
            'values' => (object) ( $user ? array( self::USER => $user ) : array() ),
        );
    }

    // Only the properties the form uses are put on the page, and only for a signed-in visitor.
    private static function user( $json ) {
        if ( ! is_user_logged_in() || ! preg_match_all( '/\{#?' . self::USER . '\.(\w+)\}/i', (string) $json, $matches ) ) {
            return array();
        }
        $user = wp_get_current_user();
        $values = array(
            'id'           => (int) $user->ID,
            'login'        => $user->user_login,
            'email'        => $user->user_email,
            'url'          => $user->user_url,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'full_name'    => trim( $user->first_name . ' ' . $user->last_name ),
            'nickname'     => $user->nickname,
        );
        return array_intersect_key( $values, array_flip( array_map( 'strtolower', $matches[1] ) ) );
    }
}
