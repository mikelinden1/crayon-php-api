<?php
require_once('php-crud-api/class-php-crud-api.php');

// require 'auth.php'; // from the PHP-API-AUTH project, see: https://github.com/mevdschee/php-api-auth

// uncomment the lines below for token+session based authentication (see "login_token.html" + "login_token.php"):

// $auth = new PHP_API_AUTH(array(
// 	'secret'=>'someVeryLongPassPhraseChangeMe',
// ));
// if ($auth->executeCommand()) exit(0);
// if (empty($_SESSION['user']) || !$auth->hasValidCsrfToken()) {
//	header('HTTP/1.0 401 Unauthorized');
//	exit(0);
// }

// uncomment the lines below for form+session based authentication (see "login.html"):

// $auth = new PHP_API_AUTH(array(
// 	'authenticator'=>function($user,$pass){ $_SESSION['user']=($user=='admin' && $pass=='admin'); }
// ));
// if ($auth->executeCommand()) exit(0);
// if (empty($_SESSION['user']) || !$auth->hasValidCsrfToken()) {
//	header('HTTP/1.0 401 Unauthorized');
//	exit(0);
// }

// uncomment the lines below when running in stand-alone mode:

$api = new PHP_CRUD_API(array(
	'dbengine'=>'MySQL',
	'hostname'=>'localhost',
	'username'=>'root',
	'password'=>'root',
	'database'=>'php_crud_api',
	'charset'=>'utf8'
));
$api->executeCommand();

// For Microsoft SQL Server 2012 use:

// $api = new PHP_CRUD_API(array(
// 	'dbengine'=>'SQLServer',
// 	'hostname'=>'(local)',
// 	'username'=>'',
// 	'password'=>'',
// 	'database'=>'xxx',
// 	'charset'=>'UTF-8'
// ));
// $api->executeCommand();

// For PostgreSQL 9 use:

// $api = new PHP_CRUD_API(array(
// 	'dbengine'=>'PostgreSQL',
// 	'hostname'=>'localhost',
// 	'username'=>'xxx',
// 	'password'=>'xxx',
// 	'database'=>'xxx',
// 	'charset'=>'UTF8'
// ));
// $api->executeCommand();

// For SQLite 3 use:

// $api = new PHP_CRUD_API(array(
// 	'dbengine'=>'SQLite',
// 	'database'=>'data/blog.db',
// ));
// $api->executeCommand();
