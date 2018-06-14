<?php
/*
error_reporting(-1);
ini_set('display_errors', 'On');
*/

$request = $_GET['request'];

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
    case 'users':
        require_once('routes/users.php');
        break;
    default:
        require_once('routes/api.php');
        break;
}