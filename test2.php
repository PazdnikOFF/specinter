<?php
var_dump(date("n"));die();
require_once ('/home/u67887/u67887.netangels.ru/www/libs/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php');

$xls = PHPExcel_IOFactory::load('/home/u67887/u67887.netangels.ru/www/mailtemplates/pdf/bill.xlsx');
$xls->setActiveSheetIndex(0);
$sheet = $xls->getActiveSheet();

$sheet->setCellValueByColumnAndRow(1,10 , 'fuck');

header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
header ( "Cache-Control: no-cache, must-revalidate" );
header ( "Pragma: no-cache" );
header ( "Content-type: application/vnd.ms-excel" );
header ( "Content-Disposition: attachment; filename=data.xls" );

// Выводим содержимое файла
$objWriter = new PHPExcel_Writer_Excel5($xls);
$objWriter->save('php://output');
