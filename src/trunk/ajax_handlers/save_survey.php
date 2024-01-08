<?php

include_once("ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $id = sanitize_key($_POST['Id']);
            $json = current_user_can( 'unfiltered_html' ) ? $_POST['Json'] : wp_kses_post( $_POST['Json'] );
            $theme = current_user_can( 'unfiltered_html' ) ? $_POST['Theme'] : wp_kses_post( $_POST['Theme'] );

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
                        'id' => intval($id)
                    )
                );
                wp_send_json( array('IsSuccess' => $result === FALSE ? 0 : 1) );
            }
        }
    }
}

?>
