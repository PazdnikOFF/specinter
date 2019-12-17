<?php

use Dompdf\Dompdf;
include_once __DIR__ . '/libs/dompdf/autoload.inc.php';
include_once __DIR__ . '/mailtemplates/pdf/mail.php';

$from = array(
    'bank' => 'ЗАО "БАНК", г.Москва',
    'bik' => '000000001',
    'schet' => '000000001001011',
    'inn' => '000110110001',
    'kpp' => '000110110002',
    'company' => 'ООО “СПЕЦИНТЕР”',
    'index' => '620000',
    'address' => 'г. Екатеринбург ул. Шоферов, 11',
    'header' => ' Внимание! Оплата данного счета означает согласие с условиями поставки товара.
    Уведомление об оплате обязательно, в противном случае не гарантируется наличие
    товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика,
    самовывозом, при наличии доверенности и паспорта.',
    'user_1' => 'Иванов А.А.',
    'user_2' => 'Иванова Я.Я',
    'from' => '1000',
);

$to = array(
    'company' => 'ООО "Покупатель"',
    'inn' => '000110110001',
    'kpp' => '000110110002',
    'index' => '119019',
    'address' => 'Москва г, Новый Арбат ул, дом № 10',
);

$items = array(
    array(
        'name'  => 'Бульдозер Shantui SD16',
        'count' => 5,
        'unit'  => 'шт',
        'price' => 1210,
        'nds'   => 18,
    ),
    array(
        'name'  => 'Бульдозер Shantui SD16',
        'count' => 3,
        'unit'  => 'шт',
        'price' => 111,
        'nds'   => 18,
    ),
    array(
        'name'  => 'Бульдозер Shantui SD16',
        'count' => 1,
        'unit'  => 'шт',
        'price' => 312312,
        'nds'   => 18,
    ),
);
$html = htmlForPdf($from,$to,$items);

$dompdf = new Dompdf();

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Вывод файла в браузер:
$dompdf->stream('schet-10');

// Или сохранение на сервере:
//$pdf = $dompdf->output();
//file_put_contents(__DIR__ . '/schet-10.pdf', $pdf);