<?php
require_once('vendor/autoload.php');
use \Firebase\JWT\JWT;

$authorized_user = has_api_access();

if (!$authorized_user) {
    echo 'Not authorized';
    exit;
}

function has_api_access() {
    $headers = getallheaders();
    $jwt = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];

    if (empty($jwt)) {
        $jwt = $headers['authorization'];
    }

    $jwt = str_replace('Bearer ', '', $jwt);

    if ($jwt) {
        try {
            global $jwt_secret_key;
            global $jwt_hashing_algorithm;

            // decode the jwt into a user
            $user = JWT::decode($jwt, $jwt_secret_key, array($jwt_hashing_algorithm));

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