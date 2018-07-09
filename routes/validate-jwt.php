<?php
require_once('vendor/autoload.php');

use \Firebase\JWT\JWT;

$jwt = $input['jwt'];

if (empty($jwt)) {
    die(json_encode(error_response('Invalid token')));
}

global $jwt_secret_key;
global $jwt_hashing_algorithm;

try {
    $jwt_data = JWT::decode($jwt, $jwt_secret_key, array($jwt_hashing_algorithm));
    $user_data = $jwt_data->data;

    // verify the user in the JWT is still a user in the db
    $dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    $stmt = $dbc->stmt_init();
    $stmt->prepare('select id from users where id=?');
    $stmt->bind_param('i', $user_data->userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die(json_encode(error_response('Invalid token')));
    }

    $stmt->close();

    header('Content-Type: application/json');

    echo json_encode(array(
        'success' => true,
        'token' => $jwt,
        'user_data' => $user_data
    ));

    mysqli_close($dbc);
} catch (Exception $e) {
    die(json_encode(error_response('Invalid token')));
}