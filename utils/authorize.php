<?php
require_once('../vendor/autoload.php');
use \Firebase\JWT\JWT;

$authorized_user = has_api_access();

if (!$authorized_user) {
    echo 'Not authorized';
    exit;
}

function has_api_access() {
    $headers = getallheaders();
    $jwt = $headers['Authorization'];

    if ($jwt) {
        try {
            // decode the jwt into a user
            $secretKey  = md5("thesecretkey");
            $user = JWT::decode($jwt, $secretKey, array('HS512'));

            return $user;
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