<?php

class WP_Service_Client {
    private $accessKey;

    function __construct() {
        $this->accessKey = WP_SJS_SettingsPage::get_access_key();
    }

    public function getSurveys() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, "https://surveyjs.io/api/MySurveys/getActive?ownerId=null&accessKey=".$this->accessKey);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }

}

?>