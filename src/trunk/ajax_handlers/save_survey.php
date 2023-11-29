<?php

include_once("ajax_handler.php");

class SurveyJS_SaveSurvey extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct("SurveyJS_SaveSurvey", false);
    }

    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = sanitize_key($_POST['Id']);
            $json = current_user_can( 'unfiltered_html' ) ? $_POST['Json'] : wp_kses_post( $_POST['Json'] );
            $theme = current_user_can( 'unfiltered_html' ) ? $_POST['Theme'] : wp_kses_post( $_POST['Theme'] );

            if(!!$json) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'sjs_my_surveys';

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
