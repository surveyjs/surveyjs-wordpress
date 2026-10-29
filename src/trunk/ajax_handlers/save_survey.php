<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'manage_options' )) {
            if(!check_ajax_referer( 'surveyjs-save-survey' )) exit;
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
            $raw_json = wp_unslash( $_POST['Json'] ?? '' );
            $raw_theme = wp_unslash( $_POST['Theme'] ?? '' );
            $json = current_user_can( 'unfiltered_html' ) ? $raw_json : wp_kses_post( $raw_json );
            $theme = current_user_can( 'unfiltered_html' ) ? $raw_theme : wp_kses_post( $raw_theme );

            // create 'theme' column if not exists
            $row = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = %s AND table_name = %s AND column_name = %s", DB_NAME, $table_name, "theme" ) );
            if(empty($row)){
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}sjs_my_surveys ADD theme LONGTEXT" );
            }
            // EO create 'theme' column if not exists

            $data = array();
            if(!!$json) {
                $data['json'] = $json;
                $data['theme'] = $theme;
            }
            if ( isset( $_POST['Name'] ) && '' !== trim( sanitize_text_field( wp_unslash( $_POST['Name'] ) ) ) ) {
                $data['name'] = sanitize_text_field( wp_unslash( $_POST['Name'] ) );
            }
            if ( isset( $_POST['Settings'] ) ) {
                $data['settings'] = wp_json_encode( SurveyJS_Form_Settings::sanitize( json_decode( wp_unslash( $_POST['Settings'] ), true ) ) );
            }

            if ( ! empty( $data ) ) {
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    array(
                        'id' => $id
                    )
                );
                wp_send_json( array('IsSuccess' => $result === FALSE ? 0 : 1) );
            }
        }
    }
}

?>
