<?php
function surveyjs_onComplete__callback() {
    $widgetId = $_POST['widgetId'];
    $jsonValue =  $_POST['jsonValue'];
    if(!isset($widgetId, $jsonValue)) {
        echo json_encode("Incorrect widgetId and/or jsonValue.", true);
        die();
        exit;
    }
    $dummy = new wp_surveyjs_widget();
    $settings = $dummy->get_settings();
    $widget_settings = $settings[$widgetId];
    if(isset($widget_settings)) {
        $postId = $widget_settings['postId'];
        sendSurveyResult($postId, $jsonValue);
    } else {
        echo json_encode("Incorrect widgetId.", true);
    }
    die();
}

function sendSurveyResult($postId, $jsonValue) {
    
    $jsonValue = str_replace('\"', '"', $jsonValue);
    $data = array("PostId" => $postId, "SurveyResult" => $jsonValue);
    $data_string = json_encode($data);

    //$ch = curl_init('https://dxsurveyapi.azurewebsites.net/api/Survey/post/');
    $ch = curl_init('http://localhost:50488/api/Survey/post/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8')
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    //execute post
    $result = curl_exec($ch);

    //close connection
    curl_close($ch);
    
}

add_action( 'wp_ajax_nopriv_surveyjs_onComplete', 'surveyjs_onComplete__callback' );
add_action( 'wp_ajax_surveyjs_onComplete', 'surveyjs_onComplete__callback' );

function load_surveyjs_wp_admin() {
        wp_register_script( 'surveyjs_admin', plugin_dir_url( __FILE__ ) . '_inc/wp_survey_admin.js', array( 'jquery' ), null);
        wp_enqueue_script( 'surveyjs_admin' );
}
add_action( 'admin_enqueue_scripts', 'load_surveyjs_wp_admin' );