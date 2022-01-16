<?php
if (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'Development Server') !== false) {
    $file_path = __DIR__ . '/' . ltrim($_SERVER['SCRIPT_NAME'], '/');
    if (is_file($file_path)) {
        switch (pathinfo($file_path, PATHINFO_EXTENSION)) {
            case 'css':
                $content_type = 'text/css';
                break;
            case 'js':
                $content_type = 'text/js';
                break;
            default:
                $content_type = mime_content_type($file_path);
        }
        header('Content-Type: ' . $content_type);
        echo file_get_contents($file_path);
        exit();
    }
}
ob_start();
ini_set("display_errors", 0);
mb_internal_encoding("UTF-8");
session_start();

define("CMS_VERSION", 0.9);

$url = $_SERVER['REQUEST_URI'];

$lastS = substr($url, -1, 1);

if ($lastS != "/" && empty($_REQUEST['search-request'])) {
    $url .= "/";
    header("Location: $url", true, 301);
}

include "backend/includes.php";
require_once 'backend/libs/cache/php_fast_cache.php';
phpFastCache::cleanup();

$sql = new Sql();
$sql->connect();
$control = new Controller($url);

if (defined('ENVIRONMENT') && ENVIRONMENT == 'development') {

}

if (isset($control->error)) {
    $control = new Controller("/" . $control->error);
}
$control->make();
$sql->close();

ob_end_flush();
