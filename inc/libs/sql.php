<?php
class Sql {
	function connect() {
		global $config;
		global $texts;
		if (!$aaa = @mysql_connect($config['dbhost'], $config['dbuser'], $config['dbpass'])) {
			echo $texts['sql_connection_error'];
			exit;
		}

		if (!$aaa = @mysql_select_db($config['dbname'])) {
			echo $texts['sql_db_selection_error'];
			exit;
		}
		mysql_query("SET NAMES 'utf8'");
	}

	function query($q) {
		global $prname;
		$q = str_replace('prname', $prname, $q);
//		echo $q."\r\n";
		$res = mysql_query($q) OR die(mysql_error());
//		echo "complete query\r\n";
		return $res;
	}

	function fetch_row($res, $n=-1) {
		$str = mysql_fetch_row($res);
		if ($n == -1) {
			return $str;
		}
		else {
			return $str[$n];
		}
	}

	function one_record($q)	{
		return  sql::fetch_row(sql::query($q), 0);
	}

	function fetch_array($res, $key='')	{
		$str = mysql_fetch_array($res);
		if ($key == '') {
			return $str;
		}
		else {
			return $str[$key];
		}
	}

	function fetch_assoc($res, $key = '') {
		return  mysql_fetch_assoc($res);
	}

	function fetch_object($res, $key='') {
		return  mysql_fetch_object($res);
	}

	function num_rows($res) {
		return mysql_num_rows($res);
	}

	function insert_id() {
		return mysql_insert_id();
	}

	function escape_string($data) {
		return mysql_real_escape_string($data);
	}

	function close() {
		mysql_close();
	}
}
?>