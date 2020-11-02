<?php

// error_reporting(-1);
// ini_set('display_errors', 'On');


require_once('utils/preflight-check.php');
require_once('utils/database-connection.php');

require_once('utils/jwt-config.php');
require_once('utils/helper-functions.php');

header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$input = json_decode(file_get_contents('php://input'), true);
$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (empty($_GET['request'])) {
    $request = $_SERVER['PATH_INFO'];

    $request_components = explode('/', $request);
    $request = $request_components[1];
} else {
    $request = $_GET['request'];
}

if (substr($request, -1) === '/') {
    $request = substr($request, 0, -1);
}

switch ($request) {
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
