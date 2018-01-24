<?php

class WP_Service_Client {
    private $accessKey;

    function __construct() {
        $settings = (array) get_option( 'sjs-settings' );
		if ( isset( $settings['access_key'] ) ) {
			$this->accessKey = esc_js( $settings['access_key'] );
		}
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