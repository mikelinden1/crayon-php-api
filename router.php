<?php

// error_reporting(-1);
// ini_set('display_errors', 'On');


require_once('utils/preflight-check.php');
require_once('utils/database-connection.php');

// required for PHP versions <5.5
require_once('utils/password-forward-compat.php');

require_once('utils/jwt-config.php');
require_once('utils/helper-functions.php');

header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (empty($_GET['request'])) {
    $request = $_SERVER['PATH_INFO'];
} else {
    $request = $_GET['request'];
}

// remove slashes from beginning and end
$request = ltrim($request, '/');
$request = rtrim($request, '/');

$request_components = explode('/', $request);
$request_route = $request_components[0];

switch ($request_route) {
    case 'login':
        require_once('routes/login.php');
        break;
    case 'validate-jwt':
        require_once('routes/validate-jwt.php');
        break;
    case 'upload':
        require_once('routes/upload.php');
        break;
    case 'ck-upload':
        require_once('routes/ck-upload.php');
        break;
    case 'users':
        require_once('routes/users.php');
        break;
    case 'media':
        require_once('routes/media.php');
        break;
    default:
        require_once('routes/api.php');
        break;
}

function error_response($msg) {
    return array('success' => false, 'msg' => $msg);
}
