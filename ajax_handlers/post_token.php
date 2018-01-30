<?php

include_once("ajax_handler.php");

define('OAUTH2_CLIENT_ID', 'F034639732E1F6B23A02827A2FCB100');
define('OAUTH2_CLIENT_SECRET', 'abKJHDdrgtkjh3465ergfdsgdLKJDHFLADKFHlsDgfhsgdlKJAHSDFO4twycdef');

function apiRequest($access_token, $url, $post=FALSE, $headers=array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if($post)
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $headers[] = 'Accept: application/json';
    if($access_token != "")
      $headers[] = 'Authorization: Bearer ' . $access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result = json_decode($response);
    return $result;
  }

class WP_PostToken extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_PostToken");  
    }
        
    function callback() {              
        session_start();

        $tokenURL = 'https://surveyjsio-stage.azurewebsites.net/OAuth/Token';
        $apiURLBase = 'https://surveyjsio-stage.azurewebsites.net/api/Resource/Profile';
        $access_token = "";
        $redirectUri = add_query_arg(array('action' => 'WP_SJS_PostToken'), admin_url('admin-ajax.php'));
        
        if($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['code']) {
            // Verify the state matches our stored state
            if(!$_POST['state'] || $_SESSION['state'] != $_POST['state']) {
                echo 'Illegal operation';
            }
            // Exchange the auth code for a token
            $token = apiRequest($access_token, $tokenURL, array(
              'client_id' => OAUTH2_CLIENT_ID,
              'client_secret' => OAUTH2_CLIENT_SECRET,
              'grant_type' => 'authorization_code',
              //'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
              'redirect_uri' => $redirectUri,
              'state' => $_SESSION['state'],
              'code' => $_POST['code']
            ));
            $access_token = $token->access_token;
        }

        if($access_token != "") {
            $profile = apiRequest($access_token, $apiURLBase, array('post' => 1));
            if($profile->AccessKey != '') {
                WP_SJS_SettingsPage::set_access_key($profile->AccessKey);
            }
            echo '<h3>Logged In</h3>';
            echo '<h4>User: ' . $profile->PublicName . '</h4>';
            echo '<h4>AccessKey: ' . $profile->AccessKey . '</h4>';
            echo '<h4>AccessKeyInProfile: ' . WP_SJS_SettingsPage::get_access_key() . '</h4>';
            echo '<pre>';
            print_r($profile);
            echo '</pre>';
            ?>
            <script>
                window.parent.jQuery.find("#sjs-settings\\[access_key\\]")[0].value = '<?php echo $profile->AccessKey ?>';
                window.parent.tb_remove();
            </script>
            <?php
        } else {
            echo '<h3>Not logged in</h3>';
        }
    }
}

?>