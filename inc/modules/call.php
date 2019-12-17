<?php
class call {

	public function __construct() {
		global $control;
		if (isset($_POST['nameCall'])) {
			return $this->formCall();
		}
	}

	public function formCall() {
		global $control;
		$formName = "formCall";

		$formconfig = array(
			$formName => array(
				'nameCall' => array(
					'caption' => 'Ваше имя',
					'noempty' => true
				),
				'phoneCall' => array(
					'caption' => 'Телефон',
					'noempty' => true
				)
			)
		);

		include_once("libs/formvalidator.php");$_SESSION['langs'] = 'ru';
		$validator = new formvalidator($formconfig);
		$validator->showErrorMethod = "#showErrorsCall";	// div для показа ошибок
		$validator->highlight = 1;							// подсветка полей
		$validator->lastaction = "callback";				// действие при завершении
		$validator->sendMethod = "ajax";					// метод отправки
		$validator->preloaderId = "#preloaderCall";			// id прелоадера
		$validator->capId = "#captchaCall";					// id каптчи
		$validator->callback = "successSend";				// Функция Callback
		$validator->param = 'Call';							// Параметр в функцию


		if (isset($_POST) && count($_POST) > 0) {

			$validator->checkFields($this, $page);
			if($validator->success) {
				header("Content-type: text/html; charset=utf-8");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);


				$array = array();

				$array['name'] = $validator->post['nameCall'];
				$array['phone'] = $validator->post['phoneCall'];
				$array['date'] = date("Y-m-d");


				all::insert_block('call', 48, $array, 0);

				// отправка на почту уведомления
				$mailpage->theme = "Заказ звонка";
				$mailpage->name = $array['name'];
				$mailpage->phone = $array['phone'];

				// Генерация текста для письма админу (задается из админки)
				$mailInfo = all::b_data_all(13, "sitetext");
				$mailInfo->text =  nl2br($mailInfo->text);
				$mailInfo->text = str_replace("{name}", $mailpage->name, $mailInfo->text);
				$mailInfo->text = str_replace("{phone}", $mailpage->phone, $mailInfo->text);
				$mailpage->text = $mailInfo->text;

				$email = $control->settings->email;

				preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $email, $matches);
				$email = $matches[0][0];
	
				$sitename = $control->settings->sitename;
				
				$msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");
				
				all::send_mail($email, $mailpage->theme, $msg, false, false, "$sitename");

				die();
			}

		}
		$script = $validator->getJsArray();
		return $script;
	}
}
?>