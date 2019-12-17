<?php

class profile
{

    public function __construct()
    {
        global $control;

        if ($_SERVER["REQUEST_URI"] == '/profile/_recovery/') {
            return $this->_recovery();
        } elseif (preg_match('/^\/profile\/_recovery=(.*)\/$/i', $_SERVER["REQUEST_URI"], $math)) {
            return $this->_recover($math[1]);
        }
        if ($_REQUEST['act']) {
            return $this->_act();
        }
        if ($_REQUEST['logout'] == "Y") {
            return $this->_logout();
        }
        if (!empty($_POST)) {
            return $this->_reg();
        }

        if ($this->_auth()) {
            $this->_profile();
        } else {
            $this->_register();
        }

    }

    private function _recover($hash)
    {
        $page = new stdClass();
        $hash = explode('&', $hash);
        $code = $hash[0];

        $email = $hash[1];
        if ($id = sql::one_record("SELECT id FROM `prname_b_user3` WHERE email = '" . $email . "' AND `code` = '" . $code . "'")) {

            $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
            $max = 8;
            $size = strlen($chars) - 1;
            $password = null;

            while ($max--) {
                $password .= $chars[rand(0, $size)];
            }

            $page->password = $password;
            $password = md5($password);
            sql::query("UPDATE  `prname_b_user3` SET  `password` =  '{$password}', `code`= NULL WHERE `id` = {$id}");

        } else {
            $page->error = "Пользователь не найден";
        }
        $this->html['text'] = sprintt($page, 'templates/cabinet/recover.html');

    }

    private function _recovery()
    {
        global $control;

        $page = new stdClass();
        $page->recover = false;

        if ($_REQUEST['email'] && filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
            $page->email = mysql_escape_string($_REQUEST['email']);
            $page->recover = true;
        } elseif ($_REQUEST['email']) {
            $page->email = $_REQUEST['email'];
            $page->error = 'Не вилдиный email';
            $page->recover = true;
        }

        if (!$page->error && $page->recover) {
            $page->user_id = sql::one_record("SELECT id FROM `prname_b_user3` WHERE email = '" . $page->email . "'");

            if (!$page->user_id && $page->recover) {
                $page->error = 'Неверный email';
            }
        }


        if ($page->user_id) {
            $page->send = true;
            $page->code = md5($page->user_id . $page->email . time());
			
            sql::query("UPDATE  `prname_b_user3` SET  `code` =  '{$page->code}' WHERE `id` = {$page->user_id}");
			
            $page->text = "Перейдите по <a href='{$control->module_url}_recovery={$page->code}&{$page->email}'>ссылке</a> для восстановление пароля";
            $msg = sprintt($page, "mailtemplates/toadmin/call.html");
			
            all::send_mail($page->email, 'Востановление пароля', $msg, false, false, $control->settings->sitename);
        }


        $this->html['text'] = sprintt($page, 'templates/cabinet/recovery.html');
    }

    private function _logout()
    {

        unset($_SESSION['user_name']);
        unset($_SESSION['password']);
        unset($_SESSION['login']);
        echo '/';
        die();
    }

    private function _act()
    {
        $code = mb_substr(mysql_escape_string($_REQUEST['act']), 0, -1);
        $result = sql::one_record("SELECT id FROM `prname_b_user3` WHERE code = '" . $code . "'");
        if ($result) {
            sql::one_record("UPDATE `prname_b_user3` SET  `visible` =  '1', `code` = '' WHERE id = '" . $result . "'");
            $this->html['text'] = "Пользователь активирован";
        } else {
            $this->html['text'] = "Не верный код";
        }

    }

    private function _reg()
    {

        foreach ($_POST as $k => $v) {
            if (strlen($v))
                $data[$k] = mysql_escape_string($v);
        }
        if (!strlen($data['password'])) {
            $return['error'][] = 'Не знаполнен пароль';
        }
        if (!strlen($data['email'])) {
            $return['error'][] = 'Не знаполнен email';

        }
        if ($data['password'] != $data['password2']) {
            $return['error'][] = 'Пароли не совпали';
        }
        if (empty($return['error'])) {
            $result = sql::one_record("SELECT id FROM `prname_b_user3` WHERE email = '" . $data['email'] . "'");
            unset($data['politic']);
            if ($result) {
                $return['error'][] = "Данный адрес уже используется";
            }
            if (empty($return['error'])) {

                $data['code'] = md5(time() . $data['email']);
                $data['password'] = md5($data['password']);
                $data['parent'] = 80;
                $data['visible'] = 0;

                unset($data['password2']);

                $keys = implode(',', array_keys($data));
                $val = "'" . implode("','", $data) . "'";

                sql::query("INSERT INTO prname_b_user3 ({$keys}) VALUES ({$val})");

                $mailpage = new stdClass();
                $mailpage->text = "Ссылка активанции пользователя <a href='http://{$_SERVER['SERVER_NAME']}/profile/?act={$data['code']}'>Активировать</a>";
                $msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");
                all::send_mail($data['email'], 'Активация пользователя', $msg, false, false, "$sitename");
                $return['register'] = "Вы зарегестрированы, активируйте акаунт. Письмо Вам отправлено.";
            }
            echo json_encode($return);
            die();
        }
        die();
    }

    private function _profile()
    {
        global $control;
        $result = sql::fetch_array(sql::query("SELECT * FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "' and  password = '" . $_SESSION['password'] . "'"));


        $page = all::c_data_all($control->cid,'profile');

        $list = new Listing("garage", "items", $result["id"]);
        $list->getList();
        $list->getItem();
        $page->garage = $list->item;

        $list = new Listing("catalog", "cats", 40);
        $list->getList();
        $list->getItem();

        $page->items = $list->item;
        $id = $result["id"];
        $list = new Listing("user3", "blocks", 'all',' id= '.$id.' and ');
        $list->getList();
        $list->getItem();
        $page->user = $list->item;

        $this->html['text'] = sprintt($page, 'templates/cabinet/cabinet.html');
    }

    private function _register()
    {
        global $control;
        $control->name = 'Регистрация';
        $page = all::c_data_all($control->cid,'profile');
        $this->html['text'] = sprintt($page, 'templates/cabinet/register.html');
    }

    private function _auth()
    {
        $result = sql::one_record("SELECT name FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "' and  password = '" . $_SESSION['password'] . "'");
        if (is_null($result)) {
            return false;
        } else {
            return true;
        }
        die();
    }

    public static function userData()
    {
        $result = sql::one_record("SELECT name FROM `prname_b_user3` WHERE email = '" . $_SESSION['login'] . "' and  password = '" . $_SESSION['password'] . "'");
        return $result;
    }
}

?>