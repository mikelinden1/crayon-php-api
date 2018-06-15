<?php
require_once('vendor/autoload.php');

use \Firebase\JWT\JWT;

$jwt = $input['jwt'];

if (empty($jwt)) {
    die(json_encode(error_response('Invalid token')));
}

global $jwt_secret_key;
global $jwt_hashing_algorithm;

$jwt_data = JWT::decode($jwt, $jwt_secret_key, array($jwt_hashing_algorithm));

header('Content-Type: application/json');

echo json_encode(array(
    'success' => true,
    'token' => $jwt,
    'user_data' => $jwt_data->data
));

mysqli_close($dbc);