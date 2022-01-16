<?php

use Dompdf\Dompdf;

include_once __DIR__ . '/libs/dompdf/autoload.inc.php';
include_once __DIR__ . '/mailtemplates/pdf/bill.php';

$from2 = array(
    'address' => '620137 Екатеринбург, пер. Шоферов дом №11 оф. №4',
    'phone_1' => '8 (343) 454-77-88',
    'phone_2' => '+7 (902) 4444-342',
    'email' => '79024444342@yandex.ru',
    'user_1' => 'Машкина Марина Юрьевна',
    'company' => 'ООО «СПЕЦИНТЕР»',
    'schet_2' => '40702810406020000350',
    'bank' => 'Филиал «Центральный» Банка ВТБ (ПАО)',
    'bik' => '044525411',
    'schet' => '30101810145250000411',
);

$html = htmlForPdfBill(array(), $from2);


$dompdf = new Dompdf();

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Вывод файла в браузер:
$dompdf->stream('schet-10');