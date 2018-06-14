<?php
require_once('utils/authorize.php');
require_once('php-crud-api/class-php-crud-api.php');

$api = new PHP_CRUD_API(array(
	'dbengine' => 'MySQL',
	'hostname' => $db_host,
	'username' => $db_user,
	'password' => $db_pass,
	'database' => $db_name,
	'charset' => 'utf8'
));

$api->executeCommand();
