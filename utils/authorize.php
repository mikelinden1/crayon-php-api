<?php
require_once('vendor/autoload.php');
use \Firebase\JWT\JWT;

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, strlen('HTTP_')) === 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

$authorized_user = has_api_access();

if (!$authorized_user) {
    echo 'Not authorized';
    exit;
}

function has_api_access() {
    $headers = getallheaders();
    $jwt = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    
    if ($jwt) {
        $jwt = str_replace('Bearer ', '', $jwt);
    } else if (isset($_COOKIE['usr'])) {
        $jwt = $_COOKIE['usr'];
    }

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