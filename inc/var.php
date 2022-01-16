<?php
error_reporting(0);
ini_set('memory_limit', '-1');

	//Род падеж месяцев
	$ar_mon[1] = 'января';
	$ar_mon[2] = 'февраля';
	$ar_mon[3] = 'марта';
	$ar_mon[4] = 'апреля';
	$ar_mon[5] = 'мая';
	$ar_mon[6] = 'июня';
	$ar_mon[7] = 'июля';
	$ar_mon[8] = 'августа';
	$ar_mon[9] = 'сентября';
	$ar_mon[10] = 'октября';
	$ar_mon[11] = 'ноября';
	$ar_mon[12] = 'декабря';

	//Количество дней в месяцах
	$ar_mon_count[1] = 31;
	$ar_mon_count[2] = 28;
	$ar_mon_count[3] = 31;
	$ar_mon_count[4] = 30;
	$ar_mon_count[5] = 31;
	$ar_mon_count[6] = 30;
	$ar_mon_count[7] = 31;
	$ar_mon_count[8] = 31;
	$ar_mon_count[9] = 30;
	$ar_mon_count[10] = 31;
	$ar_mon_count[11] = 30;
	$ar_mon_count[12] = 31;
	$substep=5;

	//Префикс базы данных
	$prname = "it";
	$config = array();
	$config['dbhost'] = 'localhost';
	$config['dbname'] = 'c26864_specinter_ru';
	$config['dbuser'] = 'c26864_specinter_ru';
	$config['dbpass'] = 'HaWpoXujponiy12';
	$config['site_name'] = '';
	$config['md5'] = 'siteactiv';
	$config['server_url'] = 'http://'.$_SERVER['SERVER_NAME'].'/';
	$texts['sql_connection_error'] = 'Невозможно подключиться к серверу баз данных.';
	$texts['sql_db_selection_error'] = 'Невозможно выбрать базу данных.';
	$docroot = $_SERVER['DOCUMENT_ROOT'];
	if (substr($docroot, -1) == "/") {
		$docroot = substr($docroot, 0, strlen($docroot)-1);
	}
	if (!defined("DOC_ROOT")) {
		define("DOC_ROOT", $docroot);
	}
?>