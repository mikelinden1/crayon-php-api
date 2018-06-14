<?php
require_once('vendor/autoload.php');

use \Firebase\JWT\JWT;

$jwt = $input['jwt'];

if (empty($jwt)) {
    die(json_encode(error_response('Invalid jwt')));
}

$jwt_data = JWT::decode($jwt, md5("thesecretkey"), array('HS512'));

header('Content-Type: application/json');

echo json_encode(array(
    'success' => true,
    'token' => $jwt,
    'user_data' => $jwt_data->data
));

mysqli_close($dbc);