<?php
class SQLite implements DatabaseInterface {

	protected $db;
	protected $queries;

	public function __construct() {
		$this->queries = array(
			'list_tables'=>'SELECT
					"name", ""
				FROM
					"sys/tables"',
			'reflect_table'=>'SELECT
					"name"
				FROM
					"sys/tables"
				WHERE
					"name"=?',
			'reflect_pk'=>'SELECT
					"name"
				FROM
					"sys/columns"
				WHERE
					"pk"=1 AND
					"self"=?',
			'reflect_belongs_to'=>'SELECT
					"self", "from",
					"table", "to"
				FROM
					"sys/foreign_keys"
				WHERE
					"self" = ? AND
					"table" IN ? AND
					? like "%" AND
					? like "%"',
			'reflect_has_many'=>'SELECT
					"self", "from",
					"table", "to"
				FROM
					"sys/foreign_keys"
				WHERE
					"self" IN ? AND
					"table" = ? AND
					? like "%" AND
					? like "%"',
			'reflect_habtm'=>'SELECT
					k1."self", k1."from",
					k1."table", k1."to",
					k2."self", k2."from",
					k2."table", k2."to"
				FROM
					"sys/foreign_keys" k1,
					"sys/foreign_keys" k2
				WHERE
					? like "%" AND
					? like "%" AND
					? like "%" AND
					? like "%" AND
					k1."self" = k2."self" AND
					k1."table" = ? AND
					k2."table" IN ?',
			'reflect_columns'=> 'SELECT
					"name", "dflt_value", case when "notnull"==1 then \'no\' else \'yes\' end as "nullable", "type", 2147483647
				FROM
					"sys/columns"
				WHERE
					"self"=?
				ORDER BY
					"cid"'
		);
	}

	public function getSql($name) {
		return isset($this->queries[$name])?$this->queries[$name]:false;
	}

	public function connect($hostname,$username,$password,$database,$port,$socket,$charset) {
		$this->db = new SQLite3($database);
		// optimizations
		$this->db->querySingle('PRAGMA synchronous = NORMAL');
		$this->db->querySingle('PRAGMA foreign_keys = on');
		$reflection = $this->db->querySingle('SELECT name FROM sqlite_master WHERE type = "table" and name like "sys/%"');
		if (!$reflection) {
			//create reflection tables
			$this->query('CREATE table "sys/version" ("version" integer)');
			$this->query('CREATE table "sys/tables" ("name" text)');
			$this->query('CREATE table "sys/columns" ("self" text,"cid" integer,"name" text,"type" integer,"notnull" integer,"dflt_value" integer,"pk" integer)');
			$this->query('CREATE table "sys/foreign_keys" ("self" text,"id" integer,"seq" integer,"table" text,"from" text,"to" text,"on_update" text,"on_delete" text,"match" text)');
		}
		$version = $this->db->querySingle('pragma schema_version');
		if ($version != $this->db->querySingle('SELECT "version" from "sys/version"')) {
			// reflection may take a while
			set_time_limit(3600);
			// update version data
			$this->query('DELETE FROM "sys/version"');
			$this->query('INSERT into "sys/version" ("version") VALUES (?)',array($version));
			// update tables data
			$this->query('DELETE FROM "sys/tables"');
			$result = $this->query('SELECT * FROM sqlite_master WHERE (type = "table" or type = "view") and name not like "sys/%" and name<>"sqlite_sequence"');
			$tables = array();
			while ($row = $this->fetchAssoc($result)) {
				$tables[] = $row['name'];
				$this->query('INSERT into "sys/tables" ("name") VALUES (?)',array($row['name']));
			}
			// update columns and foreign_keys data
			$this->query('DELETE FROM "sys/columns"');
			$this->query('DELETE FROM "sys/foreign_keys"');
			foreach ($tables as $table) {
				$result = $this->query('pragma table_info(!)',array($table));
				while ($row = $this->fetchRow($result)) {
					array_unshift($row, $table);
					$this->query('INSERT into "sys/columns" ("self","cid","name","type","notnull","dflt_value","pk") VALUES (?,?,?,?,?,?,?)',$row);
				}
				$result = $this->query('pragma foreign_key_list(!)',array($table));
				while ($row = $this->fetchRow($result)) {
					array_unshift($row, $table);
					$this->query('INSERT into "sys/foreign_keys" ("self","id","seq","table","from","to","on_update","on_delete","match") VALUES (?,?,?,?,?,?,?,?,?)',$row);
				}
			}
		}
	}

	public function query($sql,$params=array()) {
		$db = $this->db;
		$sql = preg_replace_callback('/\!|\?/', function ($matches) use (&$db,&$params) {
			$param = array_shift($params);
			if ($matches[0]=='!') {
				$key = preg_replace('/[^a-zA-Z0-9\-_=<> ]/','',is_object($param)?$param->key:$param);
				return '"'.$key.'"';
			} else {
				if (is_array($param)) return '('.implode(',',array_map(function($v) use (&$db) {
					return "'".$db->escapeString($v)."'";
				},$param)).')';
				if (is_object($param) && $param->type=='hex') {
					return "'".$db->escapeString($param->value)."'";
				}
				if (is_object($param) && $param->type=='wkt') {
					return "'".$db->escapeString($param->value)."'";
				}
				if ($param===null) return 'NULL';
				return "'".$db->escapeString($param)."'";
			}
		}, $sql);
		//echo "\n$sql\n";
		try {	$result=$db->query($sql); } catch(\Exception $e) { $result=null; }
		return $result;
	}

	public function fetchAssoc($result) {
		return $result->fetchArray(SQLITE3_ASSOC);
	}

	public function fetchRow($result) {
		return $result->fetchArray(SQLITE3_NUM);
	}

	public function insertId($result) {
		return $this->db->lastInsertRowID();
	}

	public function affectedRows($result) {
		return $this->db->changes();
	}

	public function close($result) {
		return $result->finalize();
	}

	public function fetchFields($table) {
		$result = $this->query('SELECT * FROM "sys/columns" WHERE "self"=?;',array($table));
		$fields = array();
		while ($row = $this->fetchAssoc($result)){
			$fields[strtolower($row['name'])] = (object)$row;
		}
		return $fields;
	}

	public function addLimitToSql($sql,$limit,$offset) {
		return "$sql LIMIT $limit OFFSET $offset";
	}

	public function likeEscape($string) {
		return addcslashes($string,'%_');
	}

	public function convertFilter($field, $comparator, $value) {
		return false;
	}

	public function isNumericType($field) {
		return in_array($field->type,array('integer','real'));
	}

	public function isBinaryType($field) {
		return (substr($field->type,0,4)=='data');
	}

	public function isGeometryType($field) {
		return in_array($field->type,array('geometry'));
	}

	public function isJsonType($field) {
		return in_array($field->type,array('json','jsonb'));
	}

	public function getDefaultCharset() {
		return 'utf8';
	}

	public function beginTransaction() {
		return $this->query('BEGIN');
	}

	public function commitTransaction() {
		return $this->query('COMMIT');
	}

	public function rollbackTransaction() {
		return $this->query('ROLLBACK');
	}

	public function jsonEncode($object) {
		return json_encode($object);
	}

	public function jsonDecode($string) {
		return json_decode($string);
	}
}
?>