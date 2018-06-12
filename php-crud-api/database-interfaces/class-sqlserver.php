<?php
class SQLServer implements DatabaseInterface {

	protected $db;
	protected $queries;

	public function __construct() {
		$this->queries = array(
			'list_tables'=>'SELECT
					"TABLE_NAME",\'\' as "TABLE_COMMENT"
				FROM
					"INFORMATION_SCHEMA"."TABLES"
				WHERE
					"TABLE_CATALOG" = ?',
			'reflect_table'=>'SELECT
					"TABLE_NAME"
				FROM
					"INFORMATION_SCHEMA"."TABLES"
				WHERE
					"TABLE_NAME" = ? AND
					"TABLE_CATALOG" = ?',
			'reflect_pk'=>'SELECT
					"COLUMN_NAME"
				FROM
					"INFORMATION_SCHEMA"."TABLE_CONSTRAINTS" tc,
					"INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" ku
				WHERE
					tc."CONSTRAINT_TYPE" = \'PRIMARY KEY\' AND
					tc."CONSTRAINT_NAME" = ku."CONSTRAINT_NAME" AND
					ku."TABLE_NAME" = ? AND
					ku."TABLE_CATALOG" = ?',
			'reflect_belongs_to'=>'SELECT
					cu1."TABLE_NAME",cu1."COLUMN_NAME",
					cu2."TABLE_NAME",cu2."COLUMN_NAME"
				FROM
					"INFORMATION_SCHEMA".REFERENTIAL_CONSTRAINTS rc,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cu1,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cu2
				WHERE
					cu1."CONSTRAINT_NAME" = rc."CONSTRAINT_NAME" AND
					cu2."CONSTRAINT_NAME" = rc."UNIQUE_CONSTRAINT_NAME" AND
					cu1."TABLE_NAME" = ? AND
					cu2."TABLE_NAME" IN ? AND
					cu1."TABLE_CATALOG" = ? AND
					cu2."TABLE_CATALOG" = ?',
			'reflect_has_many'=>'SELECT
					cu1."TABLE_NAME",cu1."COLUMN_NAME",
					cu2."TABLE_NAME",cu2."COLUMN_NAME"
				FROM
					"INFORMATION_SCHEMA".REFERENTIAL_CONSTRAINTS rc,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cu1,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cu2
				WHERE
					cu1."CONSTRAINT_NAME" = rc."CONSTRAINT_NAME" AND
					cu2."CONSTRAINT_NAME" = rc."UNIQUE_CONSTRAINT_NAME" AND
					cu1."TABLE_NAME" IN ? AND
					cu2."TABLE_NAME" = ? AND
					cu1."TABLE_CATALOG" = ? AND
					cu2."TABLE_CATALOG" = ?',
			'reflect_habtm'=>'SELECT
					cua1."TABLE_NAME",cua1."COLUMN_NAME",
					cua2."TABLE_NAME",cua2."COLUMN_NAME",
					cub1."TABLE_NAME",cub1."COLUMN_NAME",
					cub2."TABLE_NAME",cub2."COLUMN_NAME"
				FROM
					"INFORMATION_SCHEMA".REFERENTIAL_CONSTRAINTS rca,
					"INFORMATION_SCHEMA".REFERENTIAL_CONSTRAINTS rcb,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cua1,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cua2,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cub1,
					"INFORMATION_SCHEMA".CONSTRAINT_COLUMN_USAGE cub2
				WHERE
					cua1."CONSTRAINT_NAME" = rca."CONSTRAINT_NAME" AND
					cua2."CONSTRAINT_NAME" = rca."UNIQUE_CONSTRAINT_NAME" AND
					cub1."CONSTRAINT_NAME" = rcb."CONSTRAINT_NAME" AND
					cub2."CONSTRAINT_NAME" = rcb."UNIQUE_CONSTRAINT_NAME" AND
					cua1."TABLE_CATALOG" = ? AND
					cub1."TABLE_CATALOG" = ? AND
					cua2."TABLE_CATALOG" = ? AND
					cub2."TABLE_CATALOG" = ? AND
					cua1."TABLE_NAME" = cub1."TABLE_NAME" AND
					cua2."TABLE_NAME" = ? AND
					cub2."TABLE_NAME" IN ?',
			'reflect_columns'=> 'SELECT
					"COLUMN_NAME", "COLUMN_DEFAULT", "IS_NULLABLE", "DATA_TYPE", "CHARACTER_MAXIMUM_LENGTH"
				FROM
					"INFORMATION_SCHEMA"."COLUMNS"
				WHERE
					"TABLE_NAME" LIKE ? AND
					"TABLE_CATALOG" = ?
				ORDER BY
					"ORDINAL_POSITION"'
		);
	}

	public function getSql($name) {
		return isset($this->queries[$name])?$this->queries[$name]:false;
	}

	public function connect($hostname,$username,$password,$database,$port,$socket,$charset) {
		$connectionInfo = array();
		if ($port) $hostname.=','.$port;
		if ($username) $connectionInfo['UID']=$username;
		if ($password) $connectionInfo['PWD']=$password;
		if ($database) $connectionInfo['Database']=$database;
		if ($charset) $connectionInfo['CharacterSet']=$charset;
		$connectionInfo['QuotedId']=1;
		$connectionInfo['ReturnDatesAsStrings']=1;

		$db = sqlsrv_connect($hostname, $connectionInfo);
		if (!$db) {
			throw new \Exception('Connect failed. '.print_r( sqlsrv_errors(), true));
		}
		if ($socket) {
			throw new \Exception('Socket connection is not supported.');
		}
		$this->db = $db;
	}

	public function query($sql,$params=array()) {
		$args = array();
		$db = $this->db;
		$sql = preg_replace_callback('/\!|\?/', function ($matches) use (&$db,&$params,&$args) {
			static $i=-1;
			$i++;
			$param = $params[$i];
			if ($matches[0]=='!') {
				$key = preg_replace('/[^a-zA-Z0-9\-_=<> ]/','',is_object($param)?$param->key:$param);
				if (is_object($param) && $param->type=='hex') {
					return "CONVERT(varchar(max), \"$key\", 2) as \"$key\"";
				}
				if (is_object($param) && $param->type=='wkt') {
					return "\"$key\".STAsText() as \"$key\"";
				}
				return '"'.$key.'"';
			} else {
				// This is workaround because SQLSRV cannot accept NULL in a param
				if ($matches[0]=='?' && is_null($param)) {
					return 'NULL';
				}
				if (is_array($param)) {
					$args = array_merge($args,$param);
					return '('.implode(',',str_split(str_repeat('?',count($param)))).')';
				}
				if (is_object($param) && $param->type=='hex') {
					$args[] = $param->value;
					return 'CONVERT(VARBINARY(MAX),?,2)';
				}
				if (is_object($param) && $param->type=='wkt') {
					$args[] = $param->value;
					return 'geometry::STGeomFromText(?,0)';
				}
				$args[] = $param;
				return '?';
			}
		}, $sql);
		//var_dump($params);
		//echo "\n$sql\n";
		//var_dump($args);
		//file_put_contents('sql.txt',"\n$sql\n".var_export($args,true)."\n",FILE_APPEND);
		if (strtoupper(substr($sql,0,6))=='INSERT') {
			$sql .= ';SELECT SCOPE_IDENTITY()';
		}
		return sqlsrv_query($db,$sql,$args)?:null;
	}

	public function fetchAssoc($result) {
		return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
	}

	public function fetchRow($result) {
		return sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC);
	}

	public function insertId($result) {
		sqlsrv_next_result($result);
		sqlsrv_fetch($result);
		return (int)sqlsrv_get_field($result, 0);
	}

	public function affectedRows($result) {
		return sqlsrv_rows_affected($result);
	}

	public function close($result) {
		return sqlsrv_free_stmt($result);
	}

	public function fetchFields($table) {
		$result = $this->query('SELECT * FROM ! WHERE 1=2;',array($table));
		//var_dump(sqlsrv_field_metadata($result));
		return array_map(function($a){
			$p = array();
			foreach ($a as $k=>$v) {
				$p[strtolower($k)] = $v;
			}
			return (object)$p;
		},sqlsrv_field_metadata($result));
	}

	public function addLimitToSql($sql,$limit,$offset) {
		return "$sql OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
	}

	public function likeEscape($string) {
		return str_replace(array('%','_'),array('[%]','[_]'),$string);
	}

	public function convertFilter($field, $comparator, $value) {
		$comparator = strtolower($comparator);
		if ($comparator[0]!='n') {
			switch ($comparator) {
				case 'sco': return array('!.STContains(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'scr': return array('!.STCrosses(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'sdi': return array('!.STDisjoint(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'seq': return array('!.STEquals(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'sin': return array('!.STIntersects(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'sov': return array('!.STOverlaps(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'sto': return array('!.STTouches(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'swi': return array('!.STWithin(geometry::STGeomFromText(?,0))=1',$field,$value);
				case 'sic': return array('!.STIsClosed()=1',$field);
				case 'sis': return array('!.STIsSimple()=1',$field);
				case 'siv': return array('!.STIsValid()=1',$field);
			}
		} else {
			switch ($comparator) {
				case 'nsco': return array('!.STContains(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nscr': return array('!.STCrosses(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nsdi': return array('!.STDisjoint(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nseq': return array('!.STEquals(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nsin': return array('!.STIntersects(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nsov': return array('!.STOverlaps(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nsto': return array('!.STTouches(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nswi': return array('!.STWithin(geometry::STGeomFromText(?,0))=0',$field,$value);
				case 'nsic': return array('!.STIsClosed()=0',$field);
				case 'nsis': return array('!.STIsSimple()=0',$field);
				case 'nsiv': return array('!.STIsValid()=0',$field);
			}
		}
		return false;
	}

	public function isNumericType($field) {
		return in_array($field->type,array(-6,-5,4,5,2,6,7));
	}

	public function isBinaryType($field) {
		return ($field->type>=-4 && $field->type<=-2);
	}

	public function isGeometryType($field) {
		return ($field->type==-151);
	}

	public function isJsonType($field) {
		return ($field->type==-152);
	}

	public function getDefaultCharset() {
		return 'UTF-8';
	}

	public function beginTransaction() {
		return sqlsrv_begin_transaction($this->db);
	}

	public function commitTransaction() {
		return sqlsrv_commit($this->db);
	}

	public function rollbackTransaction() {
		return sqlsrv_rollback($this->db);
	}

	public function jsonEncode($object) {
		$a = $object;
		$d = new DOMDocument();
		$c = $d->createElement("root");
		$d->appendChild($c);
		$t = function($v) {
			$type = gettype($v);
			switch($type) {
				case 'integer': return 'number';
				case 'double':  return 'number';
				default: return strtolower($type);
			}
		};
		$f = function($f,$c,$a,$s=false) use ($t,$d) {
			$c->setAttribute('type', $t($a));
			if ($t($a) != 'array' && $t($a) != 'object') {
				if ($t($a) == 'boolean') {
					$c->appendChild($d->createTextNode($a?'true':'false'));
				} else {
					$c->appendChild($d->createTextNode($a));
				}
			} else {
				foreach($a as $k=>$v) {
					if ($k == '__type' && $t($a) == 'object') {
						$c->setAttribute('__type', $v);
					} else {
						if ($t($v) == 'object') {
							$ch = $c->appendChild($d->createElementNS(null, $s ? 'item' : $k));
							$f($f, $ch, $v);
						} else if ($t($v) == 'array') {
							$ch = $c->appendChild($d->createElementNS(null, $s ? 'item' : $k));
							$f($f, $ch, $v, true);
						} else {
							$va = $d->createElementNS(null, $s ? 'item' : $k);
							if ($t($v) == 'boolean') {
								$va->appendChild($d->createTextNode($v?'true':'false'));
							} else {
								$va->appendChild($d->createTextNode($v));
							}
							$ch = $c->appendChild($va);
							$ch->setAttribute('type', $t($v));
						}
					}
				}
			}
		};
		$f($f,$c,$a,$t($a)=='array');
		return $d->saveXML($d->documentElement);
	}

	public function jsonDecode($string) {
		$a = dom_import_simplexml(simplexml_load_string($string));
		$t = function($v) {
			return $v->getAttribute('type');
		};
		$f = function($f,$a) use ($t) {
			$c = null;
			if ($t($a)=='null') {
				$c = null;
			} else if ($t($a)=='boolean') {
				$b = substr(strtolower($a->textContent),0,1);
				$c = in_array($b,array('1','t'));
			} else if ($t($a)=='number') {
				$c = $a->textContent+0;
			} else if ($t($a)=='string') {
				$c = $a->textContent;
			} else if ($t($a)=='object') {
				$c = array();
				if ($a->getAttribute('__type')) {
					$c['__type'] = $a->getAttribute('__type');
				}
				for ($i=0;$i<$a->childNodes->length;$i++) {
					$v = $a->childNodes[$i];
					$c[$v->nodeName] = $f($f,$v);
				}
				$c = (object)$c;
			} else if ($t($a)=='array') {
				$c = array();
				for ($i=0;$i<$a->childNodes->length;$i++) {
					$v = $a->childNodes[$i];
					$c[$i] = $f($f,$v);
				}
			}
			return $c;
		};
		$c = $f($f,$a);
		return $c;
	}
}
?>