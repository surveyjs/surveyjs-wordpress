<?php

include_once("ajax_handler.php");

define('OAUTH2_CLIENT_ID', '1234561');

class WP_GetAccessKeyHandler extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_GetAccessKey");  
    }
        
    function callback() {
        $authorizeURL = 'https://surveyjsio-stage.azurewebsites.net/OAuth/Authorize';

        session_start();
        $_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
        unset($_SESSION['access_token']);

        $redirectUri = add_query_arg(array('action' => 'WP_SJS_PostToken'), admin_url('admin-ajax.php'));

        $params = array(
          'client_id' => OAUTH2_CLIENT_ID,
          //'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
          'redirect_uri' => $redirectUri,
          'scope' => 'profile',
          'response_mode' => 'form_post',
          'response_type' => 'code',
          'state' => $_SESSION['state']
        );
        $url = $authorizeURL . '?' . http_build_query($params);
               
        ?>
            <script>
                window.location = '<?php echo $url ?>';
            </script>
            <button onclick="window.parent.tb_remove();">Close</button>
        <?php
    }
}

?>