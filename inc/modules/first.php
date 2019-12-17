<?php


use Dompdf\Dompdf;

include_once '/home/u67887/u67887.netangels.ru/www/libs/dompdf/autoload.inc.php';
include_once '/home/u67887/u67887.netangels.ru/www/mailtemplates/pdf/mail.php';
include_once '/home/u67887/u67887.netangels.ru/www/mailtemplates/pdf/bill.php';

class first
{

    public function __construct()
    {
        global $control;
        $mailpage = new stdClass();

        if ($_POST['action'] == 'updateCart') {
            $_SESSION['cart'][$_POST['goodId']] = $_POST['count'];
        } else if (!empty($_POST['goood'])) {

            $artranslit = array(
                'company' => 'Организация',
                'name' => 'Имя',
                'phone' => 'Номер телефона',
                'email' => 'E-mail',
                'comment' => 'Примечания к заказу',
            );
            $ids = array_keys($_POST['goood']);
            foreach ($ids as $key => $id) {

                if ($id[0] == 'P') {
                    $parents[] = str_replace('P', '', $id);
                    unset($ids[$key]);
                }
            }

            if (!empty($ids) && empty($parents)) {
                $ids = implode(',', $ids);
                $res = sql::query("SELECT v.id,v.parent,v.blockparent,v.img,v.price,v.time,v.art, c.name_rus as name FROM it_b_variant v inner join it_b_catitem c on c.id = v.blockparent WHERE v.id IN ($ids)");
            } elseif (!empty($ids) && !empty($parents)) {
                $ids = implode(',', $ids);
                $parents = implode(',', $parents);
                $res = sql::query("(SELECT v.id,c.name_rus as name,v.price,v.art FROM it_b_variant v inner join it_b_catitem c on c.id = v.blockparent WHERE v.id IN ({$ids})) UNION (SELECT id,name_rus name,0,art FROM it_b_catitem WHERE id IN ({$parents}))");

            } elseif (empty($ids) && !empty($parents)) {
                $parents = implode(',', $parents);
                $res = sql::query("SELECT id,name_rus name,0,art FROM `prname_b_catitem` WHERE id IN ({$parents})");
            }

            $good = $_POST['goood'];
            unset($_POST['goood']);
            unset($_POST['personal']);
            $send = '<p>Заказ товаров</p>';
            $send .= "<table style='font-size: 12px;'>";
            foreach ($_POST as $key => $val) {

                $data[$key] = mysql_escape_string($val);
                if (strlen($val)) {
                    $send .= "<tr>";
                    $send .= "<td>" . $artranslit[$key] . "<td>";
                    $send .= "<td>" . $val . "<td>";
                    $send .= "</tr>";
                }
            }
            $send .= "</table>";
            $data['date'] = date("Y-m-d");
            $id = all::insert_block('orderinfo', 81, $data, 0);

            $send .= "<table style='font-size: 12px;border: 1px solid black;'>";
            $total = 0;
            $i = 1;
            $pdfGoods = array();
            while ($row = sql::fetch_assoc($res)) {
                if (!empty($good[$row['id']])) {
                    $count = (int)mysql_escape_string($good[$row['id']]);
                } else {
                    $count = (int)mysql_escape_string($good['P' . $row['id']]);
                }

                $pdfGoods[] = array(
                    'name' => $row['name'] . ' ' . $row['art'],
                    'count' => $count,
                    'unit' => 'шт',
                    'price' => $row['price'],
                    'nds' => 20,
                    'art' => $row['art'],
                    'data_name' => $row['name'],
                );

                $row['total'] = $row['price'] * $count;
                $total += $row['total'];
                $write = array(
                    'name' => $row['name'],
                    'art' => $row['art'],
                    'count' => $count,
                    'summ' => $row['total'],
                    'blockparent' => $id,

                );
                $keys = implode(',', array_keys($write));
                $val = "'" . implode("','", $write) . "'";
                sql::query("INSERT INTO prname_b_orderitem ({$keys}) VALUES ({$val})");

                $send .= "<tr style='border: 1px solid black;'>";
                $send .= "<td style='border: 1px solid black;'>" . $i . "</td>";
                $send .= "<td style='border: 1px solid black;'>" . $row['name'] . "</td>";
                $send .= "<td style='border: 1px solid black;'>" . $row['art'] . "</td>";
                $send .= "<td style='border: 1px solid black;'>Цена: " . (float)$row['price'] . "</td>";

                $send .= "<td style='border: 1px solid black;'>Кол-во: " . $count . "</td>";
                $send .= "<td style='border: 1px solid black;'>Итого: " . $row['total'] . "</td>";
                $send .= "</tr>";
                $i++;
            }

            //$from = (array)$control->settings;
            $from = array(
                'bank' => 'БАНК  «НЕЙВА» ООО',
                'bik' => '046577774',
                'schet' => '30101810400000000774',
                'schet_2' => '40702810900280029804',
                'inn' => '6670454561',
                'kpp' => '667001001',
                'ogrn' => '1176658053153',
                'company' => 'ООО “СПЕЦИНТЕР”',
                'index' => '620137',
                'address' => 'г. Екатеринбург, пер. Шоферов, дом №11, офис 4, тел 8 (343) 454 77 88',
                'header' => ' Внимание! Оплата данного счета означает согласие с условиями поставки товара.
    Уведомление об оплате обязательно, в противном случае не гарантируется наличие
    товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика,
    самовывозом, при наличии доверенности и паспорта.',
                'user_1' => 'Машкина М.Ю.',
                'user_2' => 'Машкина М.Ю.',
                'from' => '1000',
                'phone_1' => '8(343)454-77-88',
                'phone_2' => '+7(902)4444-342',
                'email' => 'info@speciter.ru',
            );
            $from['count'] = $id;
            $userDataForPdf = sql::fetch_array(sql::query("SELECT * FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "' and  password = '" . $_SESSION['password'] . "'"));


            if (!empty($userDataForPdf)) {
                $to = array(
                    'company' => $userDataForPdf['form'] . ' ' . $userDataForPdf['organization'],
                    'inn' => $userDataForPdf['inn'],
                    'kpp' => $userDataForPdf['kpp'],
                    'address' => $userDataForPdf['uladress'],
                );
            } else {
                $to = array(
                    'company' => $_REQUEST['company'],
                    'inn' => $_REQUEST['inn'],
                    'kpp' => $_REQUEST['kpp'],
                    'address' => $_REQUEST['address'],
                );
            }

            $arBill = $arPdf = array();

            foreach ($pdfGoods as $good) {
                if ($good['price'] > 0) {
                    $arPdf[] = $good;
                } else {
                    $arBill[] = $good;
                }
            }
            if (!empty($arPdf)) {
                $html = htmlForPdf($from, $to, $arPdf);
                $dompdf = new Dompdf();
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $pdf = $dompdf->output();
                $pdfPath = '/files/pdf/' . $id . '.pdf';
                $xlsPath = saveXls($from, $to, $arPdf, $id);
                file_put_contents('/home/u67887/u67887.netangels.ru/www' . $pdfPath, $pdf);
            }
            if (!empty($arBill)) {
                $from2 = array(
                    'inn' => '6670454561',
                    'kpp' => '667001001',
                    'ogrn' => '1176658053153',
                    'address' => '620137 Екатеринбург, пер. Шоферов дом №11 оф. №4',
                    'phone_1' => '8 (343) 454-77-88',
                    'phone_2' => '+7 (902) 4444-342',
                    'email' => '79024444342@yandex.ru',
                    'user_1' => 'Машкина Марина Юрьевна',
                    'company' => 'ООО «СПЕЦИНТЕР»',
                    'schet_2' => '40702810900280029804',
                    'bank' => 'БАНК «НЕЙВА» ООО',
                    'bik' => '046577774',
                    'schet' => '30101810400000000774',
                );

                if(!empty($userDataForPdf['organization'])){
                    $from2['user_name'] = $userDataForPdf['organization'];
                }else if(!empty($_REQUEST['company'])){
                    $from2['user_name'] = $_REQUEST['company'];
                }else if(!empty($_REQUEST['email'])){
                    $from2['user_name'] = $_REQUEST['email'];
                }else{
                    $from2['user_name'] = str_repeat('_',20);
                }
                
                $html2 = htmlForPdfBill($arBill, $from2);
                $dompdf = new Dompdf();
                $dompdf->loadHtml($html2, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $pdf = $dompdf->output();
                $pdfPath2 = '/files/pdf/' . $id . '_bill.pdf';
                file_put_contents('/home/u67887/u67887.netangels.ru/www' . $pdfPath2, $pdf);
            }


            $send .= "</table>
<table>
<tr>
<td style='font-size: 12px;'>Итого: {$total}</td>
</tr>
</table>";


            $mailpage->text = $send;
            $msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");
            if ($_POST['email']) {
                $bills['items'] = $bills['data'] = array();
                if ($arPdf) {
                    $bills['items'] = array('/home/u67887/u67887.netangels.ru/www' . $pdfPath, $xlsPath);
                    $bills['data'] = array('bill_' . $id . '.pdf', 'bill_' . $id . '.xls');

                }
                if ($arBill) {
                    $bills['items'][] = '/home/u67887/u67887.netangels.ru/www' . $pdfPath2;
                    $bills['data'][] = 'bill_' . $id . '_b.pdf';
                }

                all::send_mail(
                    $_POST['email'],
                    'Счет на оплату ' . $id,
                    $msg,
                    $bills['items'],
                    $bills['data'],
                    $control->settings->sitename
                );
            }

            $send .= "<a style='font-size: 12px;' href='http://specinter.ru/manage/blockedit/_aedit_id{$id}_templateorderinfo_parent81_page0?getsearch=&sort&filter=undefined/'>Заказ № {$id}";
            $mailpage->text = $send;
            $msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");
            all::send_mail($control->settings->email, 'Заказ с сайта ' . $id, $msg, $bills['items'], $bills['data'], $control->settings->sitename);
            unset($_SESSION['cart']);
            echo "Благодарим вас за оформление заказа. Номер вашего заказа: {$id}. Мы свяжемся с вами в ближайшее время.";
            die();

        } elseif ($_POST['name'] && $_POST['phone'] && !$_REQUEST['edit_profile'] && !$_REQUEST['cart'] && !$_POST['fast-order']) {

            foreach ($_POST as $key => $val) {
                $data[$key] = mysql_escape_string($val);
            }

            if (!isset($data['personal'])) {
                $error[] = 'Ошибка не все поля заполнены ';
            }

            if (empty($error)) {
                $data['date'] = date("Y-m-d");
                all::insert_block('call', 48, $data, 0);
                $mailpage->text = "Заказ звонка <br/> Имя: {$data['name']}<br/> Телефон: {$data['phone']}";
                if (strlen($data['$comment']))
                    $mailpage->text .= "<br/>Сообщение: " . $data['$comment'];

                $msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");
                all::send_mail($control->settings->email, 'Заказ звонка с сайта', $msg, false, false, $control->settings->sitename);
                $msg = "Сообщение отправлено, с Вами свяжется менеджер";
            } else {
                $msg = implode("<br/>", $error);
            }

            echo $msg;
            die();

        } elseif ($_POST['login']) {
            foreach ($_POST as $key => $val) {
                $data[$key] = mysql_escape_string($val);
            }

            $pwd = md5($data['password']);

            $result = sql::one_record("SELECT name FROM `prname_b_user3` WHERE email = '" . $data['login'] . "' and  password = '" . $pwd . "' and visible  = 1");
            if (!is_null($result)) {
                $_SESSION['user_name'] = $result;
                $_SESSION['password'] = $pwd;
                $_SESSION['login'] = $data['login'];
                $return['success'] = 'Добро пожаловать ' . $result;
            } else {
                $return['error'] = "Ошибка авторизации: Логин или пароль введен неверно";
            }
            echo json_encode($return);
            die();

        } elseif ($_POST['add_garage'] == "Y") {
            foreach ($_POST as $key => $val) {
                $data[$key] = mysql_escape_string($val);
            }
            $id = sql::one_record("SELECT id FROM `prname_b_user3` WHERE `email` = '" . $_SESSION['login'] . "'");
            $data['blockparent'] = $id;
            $data['visible'] = 1;
            $data['img'] = end(explode('/', $data['img']));
            unset($data['add_garage']);

            $keys = implode(',', array_keys($data));
            $val = "'" . implode("','", $data) . "'";
            sql::query("INSERT INTO prname_b_garage ({$keys}) VALUES ({$val})");
            echo "Добавлено";
            die();

        } elseif ($_REQUEST['edit_profile']) {

            if ($id = sql::one_record("SELECT id FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "'")) {
                unset($_POST['edit_profile']);
                foreach ($_POST as $key => $val) {
                    $val = trim(mysql_escape_string($val));
                    $preSql[] = "`{$key}` = '{$val}'";
                }
                $preSql = implode(', ', $preSql);
                sql::query("UPDATE `prname_b_user3` SET {$preSql} WHERE `id` = {$id}");
                echo "Данные изменены";
            } else {
                echo "Ошибка";
            }

            die();
        } elseif ($_REQUEST['remove_garage'] == 1) {
            $parentId = $id = sql::one_record("SELECT id FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "'");
            if ($_POST['id'] && $parentId) {
                $id = mysql_escape_string($_POST['id']);
                sql::query("delete from prname_b_garage WHERE `id` = '{$id}' AND `blockparent` = '{$parentId}'");
            }
            die();
        } elseif ($_REQUEST['change-pass'] == 1) {
            $pas = md5(mysql_escape_string($_POST["old-password"]));
            if ($id = sql::one_record("SELECT id FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "' and password = '{$pas}'")) {

                unset($_POST['edit_profile']);
                if ($_POST['new-password'] == $_POST['password']) {
                    $password = md5(mysql_escape_string($_POST['password']));
                    $_SESSION['password'] = $password;
                    sql::query("UPDATE `prname_b_user3` SET `password` = '{$password}' WHERE `id` = {$id}");
                    echo "Данные изменены";

                } else {
                    echo "Новые пароли не совпадают";
                }
            } else {
                echo "Ошибка, не верно веден старый пароль";
            }
            die();
        } else if ($_REQUEST['cart']) {
            echo "Ошибка";
            die();
        }
        else if($_POST['fast-order'] && !$_REQUEST['edit_profile'] && !$_REQUEST['cart']){
            foreach ($_POST as $key => $val) {
                $data[$key] = mysql_escape_string($val);
            }

            if (!isset($data['personal'])) {
                $error[] = 'Ошибка не все поля заполнены ';
            }

            if (empty($error)) {
                $data['date'] = date("Y-m-d");
                all::insert_block('call', 48, $data, 0);
                $mailpage->text = "Быстрый заказ товара на сайте specinter.ru  <br/> Имя: {$data['name']}<br/> Телефон: {$data['phone']}<br/> E-mail: {$data['email']}<br/> Комментарий: {$data['comment']}<br/> Название товара: {$data['page-title']} <br/> Ссылка на страницу: {$data['page-url']}";
                if (strlen($data['$comment']))
                    $mailpage->text .= "<br/>Сообщение: " . $data['$comment'];

                $msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");
                all::send_mail($control->settings->email, 'Быстрый заказ товара на сайте', $msg, false, false, $control->settings->sitename);
                $msg = "Сообщение отправлено, с Вами свяжется менеджер";
            } else {
                $msg = implode("<br/>", $error);
            }

            echo $msg;
            die();
        }

        if ($_REQUEST['login']) {
            $this->html['text'] = <<<HERE
    <script>$(document).ready(function (){\$('#overlay').show();$('#login').show();});window.referer = "{$_SESSION["REAL_REFERER"]}"</script>
HERE;
        } else {
            $this->html['text'] = '';
        }

        $this->printList($control->module_parent);

        if (isset($_POST['mode']) && $_POST['mode'] == 'lang') {
            return $this->changeLang();
        }
    }

    private function printList($cid)
    {
        global $control;
    }


}

?>