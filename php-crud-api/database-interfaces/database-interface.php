<?php
interface DatabaseInterface {
	public function getSql($name);
	public function connect($hostname,$username,$password,$database,$port,$socket,$charset);
	public function query($sql,$params=array());
	public function fetchAssoc($result);
	public function fetchRow($result);
	public function insertId($result);
	public function affectedRows($result);
	public function close($result);
	public function fetchFields($table);
	public function addLimitToSql($sql,$limit,$offset);
	public function likeEscape($string);
	public function isNumericType($field);
	public function isBinaryType($field);
	public function isGeometryType($field);
	public function isJsonType($field);
	public function getDefaultCharset();
	public function beginTransaction();
	public function commitTransaction();
	public function rollbackTransaction();
	public function jsonEncode($object);
	public function jsonDecode($string);
}

require_once('class-mysql.php');
require_once('class-postgresql.php');
require_once('class-sqlite.php');
require_once('class-sqlserver.php');
?>