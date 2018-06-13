<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once('vendor/autoload.php');

use \Firebase\JWT\JWT;

$input = json_decode(file_get_contents('php://input'),true);

$jwt = $input['jwt'];


if (empty($jwt)) {
    die(json_encode(error_response('Invalid jwt')));
}

$jwt_data = JWT::decode($jwt, md5("Kinetek1!!@#$"), array('HS512'));

header('Content-Type: application/json');

echo json_encode(array(
    'success' => true,
    'token' => $jwt,
    'user_data' => $jwt_data->data
));

mysqli_close($dbc);

function error_response($msg) {
    return array('success' => false, 'msg' => $msg);
}


?>