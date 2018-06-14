<?php
require_once('../vendor/autoload.php');
use \Firebase\JWT\JWT;

$authorized = has_api_access();

if (!$authorized) {
    echo 'Not authorized';
    exit;
}

function has_api_access() {
    $headers = getallheaders();
    $jwt = $headers['Authorization'];

    if ($jwt) {
        try {
            // decode the jwt into a user
            $secretKey  = md5("Kinetek1!!@#$");
            JWT::decode($jwt, $secretKey, array('HS512'));

            return true;
        } catch(Exception $e) {
            header('HTTP/1.0 401 Unauthorized');
            return false;
        }
    } else {
        header('HTTP/1.0 401 Unauthorized');
        return false;
    }
}
?>