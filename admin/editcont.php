<?php
include_once("../includes.php");
mb_internal_encoding("UTF-8");
if (!isset($_SESSION['admin_name'])) {
	die("Нет прав");
}

if (isset($_POST['value'])) {
	$type = ($_POST['type']=="cat")?"c":"b";
	$datatype = $_POST['datatype'];
	$field = $_POST['field'];
	$template = $_POST['template'];
	$blockid = $_POST['id'];
	$texthtml = $_POST['value'];

	if ($type == 'c') {
		$whereField = 'parent';
	}
	else {
		$whereField = 'id';
	}

	if ($datatype == 'text') $texthtml = strip_tags($texthtml);
	
	$texthtml = htmlentities($texthtml, ENT_COMPAT, "UTF-8");
	
	$texthtml = str_replace('&shy;', '', $texthtml);
	
	$texthtml = html_entity_decode($texthtml, ENT_COMPAT, "UTF-8");
	$texthtml = trim($texthtml);
	
	
	$sql = new Sql();
	$sql->connect();
	$sql->query("UPDATE prname_".$type."_".$template." SET `".$field."`='".$texthtml."' WHERE ".$whereField."=".$blockid);
	
	
	$sql->close();
	die("ok");
	
	
	
}
?>