<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'administrator' )) {
            if(!check_ajax_referer( 'surveyjs-save-survey' )) exit;
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $id = absint( wp_unslash( $_POST['Id'] ?? 0 ) );
            $raw_json = wp_unslash( $_POST['Json'] ?? '' );
            $raw_theme = wp_unslash( $_POST['Theme'] ?? '' );
            $json = current_user_can( 'unfiltered_html' ) ? $raw_json : wp_kses_post( $raw_json );
            $theme = current_user_can( 'unfiltered_html' ) ? $raw_theme : wp_kses_post( $raw_theme );

            // create 'theme' column if not exists
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = $table_name AND column_name = 'theme'"  );
            if(empty($row)){
                $wpdb->query("ALTER TABLE $table_name ADD theme LONGTEXT");
            }
            // EO create 'theme' column if not exists

            if(!!$json) {
                $result = $wpdb->update(
                    $table_name,
                    array(
                        'json' => $json,
                        'theme' => $theme
                    ),
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
