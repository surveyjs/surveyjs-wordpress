<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Client {
    private $accessKey;

    function __construct() {
    }

    public function getSurveys() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sjs_my_surveys" );
    }

    public function getSurvey( $id ) {
        global $wpdb;
        $id = absint( $id );
        if ( ! $id ) {
            return null;
        }
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sjs_my_surveys WHERE id = %d", $id ) );
    }

    public static function decode_survey_json( $raw_json, $assoc = true ) {
        if ( ! is_string( $raw_json ) || '' === trim( $raw_json ) ) {
            return array();
        }

        $candidates = array(
            $raw_json,
            wp_specialchars_decode( $raw_json, ENT_QUOTES ),
            stripslashes( $raw_json ),
            wp_specialchars_decode( stripslashes( $raw_json ), ENT_QUOTES ),
        );

        foreach ( $candidates as $candidate ) {
            $decoded = json_decode( $candidate, $assoc );
            if ( JSON_ERROR_NONE !== json_last_error() ) {
                continue;
            }

            // Some legacy records store survey JSON as a JSON string (double encoded).
            if ( is_string( $decoded ) ) {
                $decoded_twice = json_decode( $decoded, $assoc );
                if ( JSON_ERROR_NONE === json_last_error() && ( is_array( $decoded_twice ) || is_object( $decoded_twice ) ) ) {
                    return $decoded_twice;
                }
                continue;
            }

            if ( is_array( $decoded ) || is_object( $decoded ) ) {
                return $decoded;
            }
        }

        return array();
    }

    public static function survey_has_file_question( $node ) {
        if ( ! is_array( $node ) ) {
            return false;
        }

        if ( isset( $node['type'] ) && is_string( $node['type'] ) && 'file' === strtolower( $node['type'] ) ) {
            return true;
        }

        foreach ( $node as $child ) {
            if ( is_array( $child ) && self::survey_has_file_question( $child ) ) {
                return true;
            }
        }

        return false;
    }

}

?>
