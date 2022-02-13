<?
require_once(__DIR__ . '/../../libs/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php');

function format_price($value)
{
    return number_format($value, 2, ',', ' ');
}

function str_price($value)
{
    $value = explode('.', number_format($value, 2, '.', ''));

    $f = new NumberFormatter('ru', NumberFormatter::SPELLOUT);
    $str = $f->format($value[0]);

    // Первую букву в верхний регистр.
    $str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));

    // Склонение слова "рубль".
    $num = $value[0] % 100;
    if ($num > 19) {
        $num = $num % 10;
    }
    switch ($num) {
        case 1:
            $rub = 'рубль';
            break;
        case 2:
        case 3:
        case 4:
            $rub = 'рубля';
            break;
        default:
            $rub = 'рублей';
    }

    return $str . ' ' . $rub . ' ' . $value[1] . ' копеек.';
}

function htmlForPdf($from, $to, $items)
{
    global $ar_mon;

    $html = '
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style type="text/css">
        * {
            font-family: arial;
            font-size: 14px;
            line-height: 14px;
        }
        table {
            margin: 0 0 15px 0;
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }
        table td {
            padding: 5px;
        }
        table th {
            padding: 5px;
            font-weight: bold;
        }

        .header {
            margin: 0 0 0 0;
            padding: 0 0 15px 0;
            font-size: 12px;
            line-height: 12px;
            text-align: center;
        }

        /* Реквизиты банка */
        .details td {
            padding: 3px 2px;
            border: 1px solid #000000;
            font-size: 12px;
            line-height: 12px;
            vertical-align: top;
        }

        h1 {
            margin: 0 0 10px 0;
            padding: 10px 0 10px 0;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 20px;
        }

        /* Поставщик/Покупатель */
        .contract th {
            padding: 3px 0;
            vertical-align: top;
            text-align: left;
            font-size: 13px;
            line-height: 15px;
        }
        .contract td {
            padding: 3px 0;
        }

        /* Наименование товара, работ, услуг */
        .list thead, .list tbody  {
            border: 2px solid #000;
        }
        .list thead th {
            padding: 4px 0;
            border: 1px solid #000;
            vertical-align: middle;
            text-align: center;
        }
        .list tbody td {
            padding: 0 2px;
            border: 1px solid #000;
            vertical-align: middle;
            font-size: 11px;
            line-height: 13px;
        }
        .list tfoot th {
            padding: 3px 2px;
            border: none;
            text-align: right;
        }

        /* Сумма */
        .total {
            margin: 0 0 20px 0;
            padding: 0 0 10px 0;
            border-bottom: 2px solid #000;
        }
        .total p {
            margin: 0;
            padding: 0;
        }

        /* Руководитель, бухгалтер */
        .sign {
            position: relative;
        }
        .sign table {
            width: 100%;
        }
        .sign th {
            padding: 40px 0 0 0;
            text-align: left;
        }
        .sign td {
            padding: 40px 0 0 0;
            border-bottom: 1px solid #000;
            text-align: right;
            font-size: 12px;
        }

        .sign-1 {
                position: absolute;
    left: 15%;
    top: 15px;
        }
        .sign-2 {
                position: absolute;
    left: 65%;
    top: 20px;
        }
        .printing {
            position: absolute;
            left: 271px;
            top: 70px;
        }
    </style>
</head>
<body>';
    $html .= '
<h1>Счет на оплату № ' . $from['count'] . ' от  ' . date("d") . ' ' . $ar_mon[date("n")] . ' ' . date("Y") . ' г. ' . date("H:i") . '</h1>
<table class="details">
    <tbody>
    <tr>
        <td colspan="2" style="border-bottom: none;">' . $from["bank"] . '</td>
        <td>БИК</td>
        <td style="border-bottom: none;">' . $from["bik"] . '</td>
    </tr>
    <tr>
        <td colspan="2" style="border-top: none; font-size: 10px;">Банк получателя</td>
        <td>Сч. №</td>
        <td style="border-top: none;">' . $from["schet"] . '</td>
    </tr>
    <tr>
        <td width="25%">ИНН ' . $from["inn"] . '</td>
        <td width="30%">КПП ' . $from["kpp"] . '</td>
        <td width="10%" rowspan="3">Сч. №</td>
        <td width="35%" rowspan="3">' . $from["schet_2"] . '</td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom: none;">' . $from["company"] . '</td>
    </tr>
    <tr>
        <td colspan="2" style="border-top: none; font-size: 10px;">Получатель</td>
    </tr>
    </tbody>
</table>

<table class="contract">
    <tbody>
    <tr>
        <td width="15%">Поставщик:</td>
        <th width="85%">
            ' . $from["company"] . ', ИНН ' . $from["inn"] . ', КПП ' . $from["kpp"] . ', ' . $from["index"] . ', ' . $from["address"] . '
        </th>
    </tr>
    <tr>
        <td>Покупатель:</td>
        <th>
            ' . $to["company"] . ', ИНН ' . $to["inn"] . ', КПП ' . $to["kpp"] . ', ' . $to["address"] . '
        </th>
    </tr>
     <tr>
        <td>Основание:</td>
        <th>
          Основной договор
        </th>
    </tr>
    </tbody>
</table>

<table class="list">
    <thead>
    <tr>
        <th width="5%">№</th>
        <th width="54%">Товары(работы,услуги)</th>
        <th width="8%">Кол-во</th>
        <th width="5%">Ед.</th>
        <th width="14%">Цена</th>
        <th width="14%">Сумма</th>
    </tr>
    </thead>
    <tbody>';

    $total = $nds = 0;
    foreach ($items as $i => $row) {
        $total += $row['price'] * $row['count'];
        //$nds += ($row['price'] * $row['nds'] / 100) * $row['count'];

        $html .= '
    <tr>
        <td align="center">' . (++$i) . '</td>
        <td align="left">' . $row['name'] . '</td>
        <td align="right">' . $row['count'] . '</td>
        <td align="left">' . $row['unit'] . '</td>
        <td align="right">' . format_price($row['price']) . '</td>
        <td align="right">' . format_price($row['price'] * $row['count']) . '</td>
    </tr>';
    }

    $html .= '
    </tbody>
    <tfoot>
    <tr>
        <th colspan="5">Итого:</th>
        <th>' . format_price($total) . '</th>
    </tr>
    <tr>
        <th colspan="5">В том числе НДС 20%:</th>
        <th>' . ((empty($total)) ? '-' : format_price($total / 1.2 * 0.2)) . '</th>
    </tr>
    <tr>
        <th colspan="5">Всего к оплате:</th>
        <th>' . format_price($total) . '</th>
    </tr>

    </tfoot>
</table>

<div class="total">
    <p>Всего наименований ' . count($items) . ', на сумму ' . format_price($total) . ' руб.</p>
    <p><strong>' . str_price($total) . '</strong></p>
</div>';
    if ($from['header']) {
        $html .= '<p class="header">' . $from['header'] . '</p>';
    }
    $html .= '<div class="sign">
    <img class="sign-1" src="/home/c26864/specinter.ru/www/img/podps.png" width="150px">
    <img class="sign-2" src="/home/c26864/specinter.ru/www/img/podps.png" width="150px">
    <img class="printing" src="/home/c26864/specinter.ru/www/img/pechat.png" width="150px">

    <table>
        <tbody>
        <tr>
            <th width="15%">Руководитель</th>
            <td width="34%">' . $from['user_1'] . '</td>
            <th width="2%"></th>
            <th width="15%">Бухгалтер</th>
            <td width="34%">' . $from['user_2'] . '</td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>';
    return $html;
}

function saveXls($from, $to, $items, $id)
{
    //error_reporting( E_ALL );
    global $ar_mon;
    $xls = PHPExcel_IOFactory::load('/home/c26864/specinter.ru/www/backend/mailtemplates/pdf/bill.xlsx');
    $xls->setActiveSheetIndex(0);
    $sheet = $xls->getActiveSheet();

    $sheet->setCellValueByColumnAndRow(1, 10, 'Счет на оплату № ' . $from['count'] . ' от  ' . date("d") . ' ' . $ar_mon[date("n")] . ' ' . date("Y") . ' г. ' . date("H:i") . '');
    $sheet->setCellValueByColumnAndRow(1, 2, $from['bank']);
    $sheet->setCellValueByColumnAndRow(29, 2, $from['bik']);
    $sheet->setCellValueByColumnAndRow(29, 3, $from['schet']);
    $sheet->setCellValueByColumnAndRow(29, 5, $from['schet_2']);
    $sheet->setCellValueByColumnAndRow(4, 5, $from['inn']);
    $sheet->setCellValueByColumnAndRow(4, 15, $from['kpp']);
    $sheet->setCellValueByColumnAndRow(1, 6, $from['company']);
    $sheet->setCellValueByColumnAndRow(6, 14, $from["company"] . ', ИНН ' . $from["inn"] . ', КПП ' . $from["kpp"] . ', ' . $from["index"] . ', ' . $from["address"]);
    $sheet->setCellValueByColumnAndRow(6, 17, $to["company"] . ', ИНН ' . $to["inn"] . ', КПП ' . $to["kpp"] . ', ' . $to["address"]);
    $sheet->setCellValueByColumnAndRow(6, 20, 'Основной договор');

    $total = $nds = 0;
    foreach ($items as $i => $row) {
        $count = $i;
        $total += $row['price'] * $row['count'];
        $nds += ($row['price'] * $row['nds'] / 100) * $row['count'];
        if ($i > 0) {
            $sheet->insertNewRowBefore(23 + $i, 1);
            $sheet->mergeCells("D" . (23 + $i) . ":X" . (23 + $i));
            $sheet->mergeCells("B" . (23 + $i) . ":C" . (23 + $i));
            $sheet->mergeCells("AF" . (23 + $i) . ":AJ" . (23 + $i));
            $sheet->mergeCells("AK" . (23 + $i) . ":AQ" . (23 + $i));
        }
        $sheet->setCellValueByColumnAndRow(1, (23 + $count), ++$i);
        $sheet->setCellValueByColumnAndRow(3, (23 + $count), $row['name']);
        $sheet->setCellValueByColumnAndRow(24, (23 + $count), $row['count']);
        $sheet->setCellValueByColumnAndRow(29, (23 + $count), $row['unit']);
        $sheet->setCellValueByColumnAndRow(31, (23 + $count), format_price($row['price']));
        $sheet->setCellValueByColumnAndRow(36, (23 + $count), format_price($row['price'] * $row['count']));

    }

    $sheet->setCellValueByColumnAndRow(37, 25 + $count, format_price($total));
    $sheet->setCellValueByColumnAndRow(37, 27 + $count, format_price($total));
    $sheet->setCellValueByColumnAndRow(1, 28 + $count, 'Всего наименований ' . count($items) . ', на сумму ' . format_price($total) . ' руб.');
    $sheet->setCellValueByColumnAndRow(37, 26 + $count, ((empty($nds)) ? '-' : format_price($nds)));
    $sheet->setCellValueByColumnAndRow(1, 29 + $count, str_price($total));
    $objWriter = new PHPExcel_Writer_Excel5($xls);
    $objWriter->save('/home/c26864/specinter.ru/www/files/pdf/bill_' . $id . '.xls');
    return '/home/c26864/specinter.ru/www/files/pdf/bill_' . $id . '.xls';
}