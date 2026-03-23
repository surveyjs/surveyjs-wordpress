<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
        }
        if ( ! check_ajax_referer( 'surveyjs-save-survey', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';

        $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
        $json = wp_unslash( $_POST['Json'] ?? '' );
        $theme = wp_unslash( $_POST['Theme'] ?? '' );

        if ( $id <= 0 || ! is_string( $json ) || '' === trim( $json ) ) {
            wp_send_json_error( array( 'message' => 'Invalid survey payload' ), 400 );
        }

        $decoded_json = json_decode( $json, true );
        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded_json ) ) {
            wp_send_json_error( array( 'message' => 'Invalid survey JSON' ), 400 );
        }

        $decoded_theme = null;
        if ( '' !== trim( (string) $theme ) ) {
            $decoded_theme = json_decode( $theme, true );
            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded_theme ) ) {
                wp_send_json_error( array( 'message' => 'Invalid theme payload' ), 400 );
            }
        }

        $theme_column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$table_name}` LIKE %s", 'theme' ) );
        if ( empty( $theme_column ) ) {
            $wpdb->query( "ALTER TABLE `{$table_name}` ADD `theme` LONGTEXT" );
        }

        $result = $wpdb->update(
            $table_name,
            array(
                'json' => wp_json_encode( $decoded_json ),
                'theme' => is_array( $decoded_theme ) ? wp_json_encode( $decoded_theme ) : '',
            ),
            array(
                'id' => $id,
            ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        wp_send_json( array( 'IsSuccess' => false !== $result ? 1 : 0 ) );
    }
}

?>
