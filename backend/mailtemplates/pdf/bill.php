<?php
function htmlForPdfBill($items, $from)
{
    $html = '<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
        @font-face {
            font-family: Calibri;
            src: url("/home/c26864/specinter.ru/www/img/pdf/8277.ttf");
            
            
        }
        @font-face {
            font-family: Calibri;
            src: url("/home/c26864/specinter.ru/www/img/pdf/calibrib.ttf");
            font-weight: bold;
        }
        * {
            font-family: Calibri;
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
        .logo{
            position: absolute;
            left: 0;
            top: 0;
            width: 140px;
        }
        .right{
            position: absolute;
            right: 0;
            top: 0;
            width: 140px;
        }
        .head{
            text-align: center;
            font-size: 12px;
        }
        .head b{
            font-size: 11px;
        }
        .wrapper_head{
            border-bottom: 2px solid #000000;
        }
        .text_right
        {
            text-align: right;
        }
        .text_center{
         text-align: center;
        }
        .text_line{
            text-align: justify;
        }
        .text_line span{
            display: inline-block;
            border-bottom: 1px solid #000;
        }
        .black{
            background-color: #466ac9;
            color: #FFFFFF;
            position: absolute;
            bottom: 30px;
            padding: 5px;
            font-size: 20px;
            font-weight: bold;
        }
        .printing {
            position: absolute;
            left: 350px;
            top: -80px;
        }
        .sign-1 {
            position: absolute;
            left: 25%;
            top: -50px;
        }
        .relative{
        position: relative;
        }
        .name_item{
        font-size: 20px;
        font-weight: bold;
        }
        .ooo_name_item{
            font-size: 14px;
            font-weight: bold;
        }
        .item{
            position:absolute;
            top:50%;
            left: 50%;
            margin-top: -250px;
            margin-left: -250px;
        }
</style>
    </head>
    <body>
    <img src="/home/c26864/specinter.ru/www//img/newlogo.jpg"  class="logo">
    <img src="/home/c26864/specinter.ru/www/img/pdf/item.png"  class="item">
        <table>
            <tr class="wrapper_head">
                <td></td>
                <td class="head">
                    <p class="name_item">«СПЕЦИНТЕР»</p>
                    <p class="ooo_name_item">Общество с ограниченной ответственностью</p> 
                    ИНН: <b>' . $from['inn'] . ' </b> КПП: <b>' . $from['kpp'] . ' </b>   ОГРН: <b>' . $from['ogrn'] . ' </b> <br/>
                    Адрес: <b>' . $from['address'] . ' </b><br/>
                    Телефон: <b>' . $from['phone_1'] . '</b>;<b>' . $from['phone_2'] . '</b> email:' . $from['email'] . '
                     
                </td>
                
            </tr>
        </table>
        <img src="/home/c26864/specinter.ru/www/img/pdf/right.png"  class="right">
        <hr>
        <table>
            <tr>
                <td class="text_right">от: ' . $from['user_name'] . '</td>
            </tr>
        </table>
        <h1 class="text_center">Заявка на запрос наличия и цены</h1>
        <p class="text_center">  Данной заявкой уведомляем Вас о необходимости поставки в Наш адрес следующих позиций</p>
        <br>
        <table border="1">
            <tr>
                <td class="text_center">№</td>
                <td class="text_center">Артикул</td>
                <td class="text_center">Наименование</td>
                <td class="text_center">Кол-во</td>
            </tr>';
    foreach ($items as $i => $item) {
        $j = $i + 1;
        $html .= "
                <tr>
                <td class=\"text_center\">{$j}</td>
                <td class=\"text_center\">{$item['art']}</td>
                <td class=\"text_center\">{$item['data_name']}</td>
                <td class=\"text_center\">{$item['count']}</td>
            </tr>';
            ";
    }
    $html .= '</table>
        Дополнительные условия:<br/>
        <ol>
            <li>Данная заявка является запросом и не является основанием для поставки и резервированием указанных позиций.<br/></li>
            <li>Цены на товар в заявке указать на период до 5-ти рабочих дней. По истечении данного срока необходимо уточнить наличие.<br/></li>
            <li>Условия поставки:<br/> 
                Самовывоз!! Просьба заблаговременно согласовать с менеджером дату приезда за товаром <br/><br/>
                Отправка транспортной!! Просьба указать точный адрес в личном кабинете или согласовать с менеджером транспортную компанию и/или точный адрес доставки  
            </li>
        </ol>
        
        <p class="text_line">
            <span>' . $from['email'] . '</span> <span>' . $from['phone_1'] . '</span>  <span>' . $from['phone_2'] . '</span> <span>Whats App / Viber </span> <span>www.specinter.ru</span>
           
        </p>
        <p>
            Директор ООО "Специнтер" ________________________________________ ' . $from['user_1'] . '
        </p>
        <div class="relative">
            <img class="sign-1" src="/home/c26864/specinter.ru/www/img/podps.png" width="150px">
            <img class="printing" src="/home/c26864/specinter.ru/www/img/pechat.png" width="150px">
        </div>
        
        <br/>
        <br/><br/>
        <br/><br/>
        <br/>
        <table class="black">
            <tr>
                <td>Р\Счет:&nbsp;&nbsp;&nbsp;&nbsp;' . $from['schet_2'] . '&nbsp;&nbsp;&nbsp;' . $from['bank'] . '</td>
                <td rowspan="2" class="text_right">' . $from['company'] . '</td>
            </tr>
            
            <tr>
                   <td>БИК:&nbsp;' . $from['bik'] . '&nbsp;&nbsp;&nbsp;КОР.СЧЕТ:&nbsp;' . $from['schet'] . '</td>
                  
            </tr>
        </table>
    </body>
</html>
';
    return $html;
}
