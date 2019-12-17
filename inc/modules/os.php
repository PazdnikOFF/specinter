<?php
class os {

	public function __construct() {
		global $control;
		$this->printList($control->module_parent);
	}



	private function printList($cid) {
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
		$validator->showErrorMethod = "#showErrorsCall";	//div для показа ошибок
		$validator->highlight = 1;							//подсветка полей
		$validator->lastaction = "callback";				// действие при завершении
		$validator->sendMethod = "ajax";					//метод отправки
		$validator->preloaderId = "#preloaderCall";			//id прелоадера
		$validator->capId = "#captchaCall";					// id каптчи
		$validator->callback = "successSend";				// Функция Callback
		$validator->param = 'Call';							// Параметр в функцию


		if (isset($_POST) && count($_POST) > 0) {

			$validator->checkFields($this, $page);
			if($validator->success) {
				header("Content-type: text/html; charset=utf-8");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);


				$fields = array();
				$fields['name'] = "Контактное лицо";
				$fields['phone'] = "Телефон";

				$array = array();


				$array['name'] = $validator->post['nameCall'];
				$array['phone'] = $validator->post['phoneCall'];
				$array['date'] = date("Y-m-d");

				all::insert_block('os', 14, $array, 0);


				// отправка на почту уведомления
				$mailpage->theme = "Заказ звонка с сайта Olli Tours";
				$mailpage->name = $array['name'];
				$mailpage->phone = $array['phone'];

				$email = $control->settings->email;
				$sitename = $control->settings->sitename;
				$msg = sprintt($mailpage, "mailtemplates/toadmin/call.html");

				all::send_mail($email, $mailpage->theme, $msg, false, false, "$sitename robot");

				die();
			}

		}
		$page->script = $validator->getJsArray();
		$page->cap = $validator->getCaptcha();

		$page->name = $control->name;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}
}
?>