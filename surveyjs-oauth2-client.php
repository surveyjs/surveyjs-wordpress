<?php
define('OAUTH2_CLIENT_ID', '1234561');
define('OAUTH2_CLIENT_SECRET', 'abcdef');
$authorizeURL = 'https://localhost:44388/OAuth/Authorize';
$tokenURL = 'https://localhost:44388/OAuth/Token';
$apiURLBase = 'https://localhost:44388/api/Resource/Profile';
session_start();
// Start the login process by sending the user to Github's authorization page
if(get('action') == 'login') {
  // Generate a random hash and store in the session for security
  $_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
  unset($_SESSION['access_token']);
  $params = array(
    'client_id' => OAUTH2_CLIENT_ID,
    //'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'redirect_uri' => 'http://localhost:8080/surveyjs-oauth2-client.php',
    'scope' => 'profile',
    'response_mode' => 'form_post',
    'response_type' => 'code',
    'state' => $_SESSION['state']
  );
  // Redirect the user to Github's authorization page
  header('Location: ' . $authorizeURL . '?' . http_build_query($params));
  die();
}
// When Github redirects the user back here, there will be a "code" and "state" parameter in the query string
if($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['code']) {
  // Verify the state matches our stored state
  if(!$_POST['state'] || $_SESSION['state'] != $_POST['state']) {
    header('Location: ' . $_SERVER['PHP_SELF']);
    die();
  }
  // Exchange the auth code for a token
  $token = apiRequest(/*$tokenURL*/'http://localhost:8080', array(
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    //'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'redirect_uri' => 'http://localhost:8080/surveyjs-oauth2-client.php',
    'state' => $_SESSION['state'],
    'code' => $_POST['code']
  ));
  $_SESSION['access_token'] = $token->access_token;

/*
  $ch1 = curl_init($tokenURL);
  curl_setopt($ch1, CURLOPT_POST, 1);
  curl_setopt($ch1,CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch1, CURLOPT_POSTFIELDS, 
    'client_id='.OAUTH2_CLIENT_ID.'&'.
    'client_secret='.OAUTH2_CLIENT_SECRET.'&'.
    'grant_type='.'authorization_code'.'&'.
    'redirect_uri='.'http://localhost:8080/surveyjs-oauth2-client.php'.'&'.
    'state='.$_SESSION['state'].'&'.
    'code='.$_POST['code']
  );
  curl_setopt($ch1, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
  curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
  $server_output = curl_exec($ch1);
  curl_close($ch1);
*/

  header('Location: ' . $_SERVER['PHP_SELF'].'#good'.$token/*->access_token*/);
}
if(session('access_token')) {
  $user = apiRequest($apiURLBase);
  echo '<h3>Logged In</h3>';
  echo '<h4>' . $user->name . '</h4>';
  echo '<pre>';
  print_r($user);
  echo '</pre>';
} else {
  echo '<h3>Not logged in</h3>';
  echo '<p><a href="?action=login">Log In</a></p>';
}
function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  //curl_setopt ($ch, CURLOPT_PORT, 44388); 
  //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
  //$headers[] = 'User-Agent: php-test';
  $headers[] = 'Accept: application/json';
  if(session('access_token'))
    $headers[] = 'Authorization: Bearer ' . session('access_token');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ( $httpCode != 200 ) {
    $response = $httpCode;
  } else {
    $response = 2;
  }
  curl_close($ch);
  //return json_decode($response);
  return $response;
}
function get($key, $default=NULL) {
  return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
  //return isset($_GET['key']) ? $_GET[$key] : $default;
}
function session($key, $default=NULL) {
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
  //return isset($_SESSION['key']) ? $_SESSION[$key] : $default;
}
