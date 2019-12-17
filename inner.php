<?php
ob_start();
ini_set("display_errors", "1");
mb_internal_encoding("UTF-8");
session_start();


if (isset($_COOKIE['vas-vas'])) {
	# enabled all error reporting and stricts
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
}

define("CMS_VERSION", 0.9);

$url = $_SERVER['REQUEST_URI'];

$lastS = substr($url, -1, 1);

if ($lastS != "/" && empty($_REQUEST['search-request'])) {
	$url .= "/";
	header("Location: $url", true, 301);
}

include "includes.php";
require_once 'libs/cache/php_fast_cache.php';
phpFastCache::cleanup();

	$sql = new Sql();
	$sql->connect();
	$control = new Controller($url);

	if (defined('ENVIRONMENT') && ENVIRONMENT == 'development') {

	}

	if (isset($control->error)) {
		$control = new Controller("/".$control->error);
	}
	$control->make();
	$sql->close();

ob_get_contents();

?>