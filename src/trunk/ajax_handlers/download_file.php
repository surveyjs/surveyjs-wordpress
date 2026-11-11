<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");

class SurveyJS_DownloadFile extends SurveyJS_AJAX_Handler {

    function __construct() {
        parent::__construct( SurveyJS_Uploaded_Files::DOWNLOAD_ACTION, true );
    }

    function callback() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || ! in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ), true ) ) return;

        SurveyJS_Uploaded_Files::serve();
    }
}
