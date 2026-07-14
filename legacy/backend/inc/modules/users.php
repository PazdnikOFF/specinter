<?php
class users {

	public function __construct() {
		global $control;

		if (isset($_GET['activecode'])) {
			return $this->activation();
		}

		if (isset($_POST['mode'])) {
			if ($_POST['mode'] == 'login') {
				return $this->goLogin();
			}
			if ($_POST['mode'] == 'logout') {
				return $this->logout();
			}
		}

		if (isset($_POST['emailRem'])) {
			return $this->formRem();
		}

		if (isset($_POST['emailReg'])) {
			$this->formReg();
		}

	}

	public function formRem() {
		global $control, $config;

		$formName = "formRem";

		$formconfig = array(
			$formName => array(
				'emailRem' => array(
					'caption' => 'E-mail',
					'noempty' => true
				)
			)
		);


		include_once("libs/formvalidator.php");$_SESSION['langs'] = 'ru';
		$validator = new formvalidator($formconfig);
		$validator->showErrorMethod = "#showErrorsRem";		// div для показа ошибок
		$validator->highlight = 1;							// подсветка полей
		$validator->lastaction = "callback";				// действие при завершении
		$validator->sendMethod = "ajax";					// метод отправки
		$validator->preloaderId = "#preloaderRem";			// id прелоадера
		$validator->capId = "#captchaRem";					// id каптчи
		$validator->callback = "successSend";				// Функция Callback
		$validator->param = 'Rem';							// Параметр в функцию
		

		$r = sql::fetch_assoc(sql::query("SELECT COUNT(*) AS `count` FROM `prname_b_user` WHERE  login='". $_POST['emailRem'] ."'"));
		if($r['count'] == 0){
			$res = json_encode(array('errors'=>'Данный адрес отсутствует в базе'));
			die($res);
		}
		

		
		if (isset($_POST) && count($_POST) > 0) {
			$validator->checkFields($this, $page);
			if($validator->success) {
				$salt = $config['md5'];

				include_once("libs/passgen.php");
				$newPassword = passgen::generatePassword();
				$login = $validator->post['emailRem'];

				$array['password'] = md5($newPassword.$salt);


				if (count($array) > 0) {
					$updateStr = "UPDATE prname_b_user SET ";

					foreach ($array as $key=>$val) {
						$updateStr .= "`".$key."`='".$val."',";
					}

					$updateStr = substr($updateStr, 0, strlen($updateStr)-1);
					$updateStr .= " WHERE login='$login'";

					sql::query($updateStr);


					// отправка уведомления о восстановлении пароля
					$email = $login;
					$sitename = $control->settings->sitename;
					$mailpage->theme = "Восстановление пароля";

					// Урл сайта
					$mailpage->siteUrl = "http://".$_SERVER['HTTP_HOST'];
					// Реквизиты входа
					$mailpage->email = $login;
					$mailpage->password = $newPassword;

					// Генерация текста для письма (задается из админки)
					$mailInfo = all::b_data_all(15, "sitetext");
					$mailInfo->text =  nl2br($mailInfo->text);
					$mailInfo->text = str_replace("{email}", $mailpage->email, $mailInfo->text);
					$mailInfo->text = str_replace("{password}", $mailpage->password, $mailInfo->text);
					$mailpage->text = $mailInfo->text;

					$msg = sprintt($mailpage, 'mailtemplates/touser/password_reset.html');

					all::send_mail($email, $mailpage->theme, $msg, false, false, "$sitename");

				}

				die();
			}
		}


		$script = $validator->getJsArray();

		return $script;
	}

	public function formReg() {
		global $control, $config;

		$formName = "formReg";

		$formconfig = array(
			$formName => array(
				'emailReg' => array(
					'caption' => 'E-mail',
					'login' => 'user_login',
					'email' => true,
					'noempty' => true
				),
				'passwordReg' => array(
					'caption' => 'Пароль',
					'noempty' => true
				)
			)
		);

		include_once("libs/formvalidator.php");$_SESSION['langs'] = 'ru';
		$validator = new formvalidator($formconfig);
		$validator->showErrorMethod = "#showErrorsReg";		//div для показа ошибок
		$validator->highlight = 1;							//подсветка полей
		$validator->lastaction = "callback";				// действие при завершении
		$validator->sendMethod = "ajax";					//метод отправки
		$validator->preloaderId = "#preloaderReg";			//id прелоадера
		$validator->capId = "#captchaReg";					// id каптчи
		$validator->callback = "successSend";				// Функция Callback
		$validator->param = 'Reg';							// Параметр в функцию


		if (isset($_POST) && count($_POST) > 0) {
			$validator->checkFields($this, $page);
			if($validator->success) {
				header("Content-type: text/html; charset=utf-8");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);


				$array = array();

				$array['login'] = $validator->post['emailReg'];
				$array['email'] = $validator->post['emailReg'];
				$array['password'] = md5($validator->post['passwordReg'].$config['md5']);
				$array['subscribe'] = (int)isset($validator->post['subscribeReg']);
				$array['active'] = 0;
				$array['discountinet'] = 0;
				$array['discountshop'] = 0;

				$newUserId = all::insert_block("user", 29, $array, 1);


				// Отправка письма зарегистрированному пользователю
					$mailpage->theme = "Регистрация на сайте $sitename";
					$sitename = $control->settings->sitename;
					// Урл сайта
					$mailpage->siteUrl = "http://".$_SERVER['HTTP_HOST'];
					// Код активации
					$activecode = md5($array['email']."#@#@#".$config['salt']);
					$mailpage->activecode = $mailpage->siteUrl."/users/?activecode=".$activecode."&login=".$newUserId."/";
					// Реквизиты входа
					$mailpage->email = $array['login'];
					$mailpage->password = $validator->post['passwordReg'];
					$mailpage->name = $array['name'];

					// Генерация текста для письма (задается из админки)
					$mailInfo = all::b_data_all(16, "sitetext");
					$mailInfo->text =  nl2br($mailInfo->text);
					$mailInfo->text = str_replace("{name}", $mailpage->name, $mailInfo->text);
					$mailInfo->text = str_replace("{email}", $mailpage->email, $mailInfo->text);
					$mailInfo->text = str_replace("{password}", $mailpage->password, $mailInfo->text);
					$mailInfo->text = str_replace("{activecode}", $mailpage->activecode, $mailInfo->text);
					$mailpage->text = $mailInfo->text;

					$msg = sprintt($mailpage, 'mailtemplates/touser/registration_activation.html');

					all::send_mail($array['login'], $mailpage->theme, $msg, false, false, "$sitename");


				die();
			}

		}
		$script = $validator->getJsArray();
		return $script;
	}

	private function activation() {
		global $control, $config;

		$activecode = trim($_GET['activecode'], "/");
		$login = trim($_GET['login'], "/");

		$login = $login + 1 - 1;

		$user = all::b_data_all($login, "user");
		$str = md5($user->email."#@#@#".$config['salt']);

		// Защита от многоразовой активации аккаунта
		$active = sql::one_record("SELECT active FROM prname_b_user WHERE id=$login");
		if ($active == 1) {
			header("Location: /");
			return;
		}

		if ($activecode == $str) {
			sql::query("UPDATE prname_b_user SET active=1 WHERE id=$login");
			$this->goLogin($user->email, $user->password, false);

			// отправка уведомления на почту администратора
				$mailpage->theme = "Новый пользователь ";

				// Урл сайта
				$mailpage->siteUrl = "http://".$_SERVER['HTTP_HOST'];
				// Реквизиты входа
				$mailpage->email = $user->login;

				$email = $control->settings->email;
				$sitename = $control->settings->sitename;

				// Генерация текста для письма (задается из админки)
				$mailInfo = all::b_data_all(17, "sitetext");
				$mailInfo->text =  nl2br($mailInfo->text);
				$mailInfo->text = str_replace("{email}", $mailpage->email, $mailInfo->text);
				$mailpage->text = $mailInfo->text;

				$msg = sprintt($mailpage, 'mailtemplates/toadmin/registration.html');

				all::send_mail($email, $mailpage->theme, $msg, false, false, "$sitename");

			header("Location: /cabinet/");
			return;
		}


		return;
	}

	private function goLogin($email = null, $password = null, $md5 = true) {
		global $config;
		$salt = $config['md5'];
		$salt2 = $config['salt'];

		// Авторизация при активации
		if ($email != null && $password != null) {
			if ($md5) {
				$password = md5($password.$salt);
			}
		}
		else {
			$email = sql::escape_string($_POST['login']);
			$password = md5($_POST['password'].$salt);
		}

		$r = sql::fetch_assoc(sql::query("SELECT * FROM prname_b_user WHERE `login`='".$email."'"));

		if ($r != null) {

			if ($r['active'] < 1) {
				die("<p>Неправильные e-mail/пароль</p>");
			}
			if ($r['password'] == $password) {
				$_SESSION['uid'] = $r['id'];

				$newEmail = md5($email.$salt2);
				$str = protect::p_code($newEmail);
				setcookie('udr_uity', $str , time() + 2592000, "/");
				if (!$md5) {
					return true;
				}
				else {
					die("ok");
				}
			}
			else {
				if (!$md5) {
					return  false;
				}
				else {
					die("<p>Неправильные e-mail/пароль</p>");
				}
			}
		}
		if (!$md5) {
			return false;
		}
		else {
			die("<p>Неправильные e-mail/пароль</p>");
		}
	}

	function logout() {
		unset($_SESSION['uid']);
		setcookie('udr_uity', '' , time() - 36000, "/");
		die("ok");
	}


}
?>