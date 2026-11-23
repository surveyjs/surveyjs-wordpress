<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// The linter that finds JSON issues runs only in the browser, so the form editor reports its findings with the JSON
// they belong to; AI tools get them while the JSON they read is still the same.
class SurveyJS_AI_Issues {

    const META = '_sjs_json_issues';
    const MAX_ISSUES = 30;
    const SEVERITIES = array( 'error', 'warning', 'info' );

    public static function sanitize( $raw ) {
        if ( ! is_array( $raw ) ) {
            return array();
        }
        $issues = array();
        foreach ( array_slice( array_values( $raw ), 0, self::MAX_ISSUES ) as $item ) {
            if ( ! is_array( $item ) || ! isset( $item['message'] ) ) {
                continue;
            }
            $severity = (string) ( $item['severity'] ?? '' );
            $issues[] = array(
                'rule'     => substr( preg_replace( '/[^a-z0-9\/-]/', '', strtolower( (string) ( $item['ruleId'] ?? '' ) ) ), 0, 64 ),
                'severity' => in_array( $severity, self::SEVERITIES, true ) ? $severity : 'warning',
                'path'     => substr( sanitize_text_field( (string) ( $item['path'] ?? '' ) ), 0, 300 ),
                'message'  => substr( sanitize_text_field( (string) $item['message'] ), 0, 600 ),
            );
        }
        return $issues;
    }

    public static function remember( $form_id, $json_text, $issues ) {
        $hash = self::hash( $json_text );
        if ( null === $hash ) {
            return;
        }
        update_post_meta( (int) $form_id, self::META, wp_slash( (string) wp_json_encode( array(
            'hash'   => $hash,
            'issues' => self::sanitize( $issues ),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
    }

    // Null when the editor has not checked this JSON: it changed after the last check, or nobody opened it.
    public static function for_json( $form_id, $json_text ) {
        $raw = get_post_meta( (int) $form_id, self::META, true );
        $stored = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;
        if ( ! is_array( $stored ) || ! isset( $stored['hash'], $stored['issues'] ) || ! is_array( $stored['issues'] ) ) {
            return null;
        }
        $hash = self::hash( $json_text );
        return null !== $hash && hash_equals( (string) $stored['hash'], $hash ) ? $stored['issues'] : null;
    }

    public static function describe( $issue ) {
        return ( '' !== $issue['path'] ? $issue['path'] . ': ' : '' ) . $issue['message'] . ( '' !== $issue['rule'] ? ' (' . $issue['rule'] . ', ' . $issue['severity'] . ')' : '' );
    }

    // Key order and the escaping of the text differ between the browser, the draft and the live form, so the JSON is
    // compared by its structure.
    private static function hash( $json_text ) {
        $json = json_decode( (string) $json_text, true );
        if ( ! is_array( $json ) ) {
            return null;
        }
        return md5( (string) wp_json_encode( self::canonical( $json ) ) );
    }

    private static function canonical( $value ) {
        if ( ! is_array( $value ) ) {
            return is_int( $value ) || is_float( $value ) ? (float) $value : $value;
        }
        if ( ! array_is_list( $value ) ) {
            ksort( $value, SORT_STRING );
        }
        return array_map( array( __CLASS__, 'canonical' ), $value );
    }
}
