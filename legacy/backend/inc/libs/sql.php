<?php
class Sql {
    public static $connection;

	function connect() {
		global $config;
		global $texts;
		if (!$aaa = mysqli_connect($config['dbhost'], $config['dbuser'], $config['dbpass'])) {
			echo $texts['sql_connection_error'];
			exit;
		}
        self::$connection = $aaa;
		if (!$aaa = mysqli_select_db(self::$connection, $config['dbname'])) {
			echo $texts['sql_db_selection_error'];
			exit;
		}
		mysqli_query(self::$connection, "SET NAMES 'utf8'");
	}


	public static function log($logString) {
		$handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/log/sql-' . date('Y-m-d') . '.log', 'a+');
		$logString = "[" . date("Y-m-d H:i:s") . "] " . $logString . "\n";
		fwrite($handle, $logString);
		fclose($handle);
	}

	function query($q) {
		global $prname;
		$q = str_replace('prname', $prname, $q);
//		echo $q."\r\n";

		if (isset($_COOKIE['vas-vas'])) {
			Sql::log($q);
		}

		$res = mysqli_query(self::$connection, $q);
        if (!$res) {
//            echo '<!-- error dump: ';
//            var_dump(mysqli_error(self::$connection));
//            var_dump(debug_backtrace());
//            echo '-->';
            die(mysqli_error(self::$connection));
        }
//		echo "complete query\r\n";
		return $res;
	}

	function prepare($q) {
		global $prname;
		$q = str_replace('prname', $prname, $q);
//		echo $q."\r\n";

		if (isset($_COOKIE['vas-vas'])) {
			Sql::log($q);
		}

		$res = mysqli_prepare(self::$connection, $q);
        if (!$res) {
//            echo '<!-- error dump: ';
//            var_dump(mysqli_error(self::$connection));
//            var_dump(debug_backtrace());
//            echo '-->';
            die(mysqli_error(self::$connection));
        }
//		echo "complete query\r\n";
		return $res;
	}

	function fetch_row($res, $n=-1) {
		$str = mysqli_fetch_row($res);
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
		$str = mysqli_fetch_array($res);
		if ($key == '') {
			return $str;
		}
		else {
			return $str[$key];
		}
	}

	function fetch_assoc($res, $key = '') {
		return  mysqli_fetch_assoc($res);
	}

	function fetch_object($res, $key='') {
		return  mysqli_fetch_object($res);
	}

	function num_rows($res) {
		return mysqli_num_rows($res);
	}

	function insert_id() {
		return mysqli_insert_id(self::$connection);
	}

	function escape_string($data) {
		return mysqli_real_escape_string(self::$connection, $data);
	}

	function close() {
		mysqli_close(self::$connection);
	}
}

function mysqli_result($mysql_result) {
    return mysqli_fetch_row($mysql_result)[0];
}
?>