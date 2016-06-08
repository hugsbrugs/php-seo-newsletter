<?php

$valid_passwords = array ("mario" => "carbonell");
$valid_users = array_keys($valid_passwords);
// error_log(print_r($valid_users, true));
// error_log(print_r($valid_passwords, true));


//set http auth headers for apache+php-cgi work around
/*if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
    $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
}

//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
    $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
}*/

// mod_php
/*if (isset($_SERVER['PHP_AUTH_USER'])) {
    $user = $_SERVER['PHP_AUTH_USER'];
    $pass = $_SERVER['PHP_AUTH_PW'];

// most other servers
} else*/
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {

        if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']),'basic')===0)
          list($user,$pass) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

}

// $user = $_SERVER['PHP_AUTH_USER'];
// $pass = $_SERVER['PHP_AUTH_PW'];
error_log('user : ' . $user);
error_log('pass : ' . $pass);
error_log(print_r($_SERVER, true));

$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

if (!$validated) {
	header('WWW-Authenticate: Basic realm="My Realm"');
	header('HTTP/1.0 401 Unauthorized');
	die ("Not authorized");
}

// If arrives here, is a valid user.
echo "<p>Welcome $user.</p>";
echo "<p>Congratulation, you are into the system.</p>";

echo '<pre>';print_r($_SERVER);echo '</pre>';

$newsletter_file = __DIR__ . '/../json/newsletter.json';
$subscribers = json_decode(file_get_contents($newsletter_file));
echo '<pre>';print_r($subscribers);echo '</pre>';
