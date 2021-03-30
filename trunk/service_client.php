<?php

class SurveyJS_Client {
    private $accessKey;

    function __construct() {
    }

    public function getSurveys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name;
        
        return $wpdb->get_results( $query );
    }

}

?>