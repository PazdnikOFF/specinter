<?php

class formos {
	function Make($wrapper) {

		$formName = "formCall";

		$config = array(
			$formName => array(
				'nameCall' => array(
					'caption' => 'Ваше имя',
					'noempty' => true,
					'minlength' => 10,
					'maxlength' => 20
				),
				'phoneCall' => array(
					'caption' => 'Телефон',
					'noempty' => true
				)
			)
		);


		include_once("libs/formvalidator.php");$_SESSION['langs'] = 'ru';
		$validator = new formvalidator($config);
		$validator->showErrorMethod = "#showErrorsCall";	//div для показа ошибок
		$validator->highlight = true;						//подсветка полей
		$validator->lastaction = "callback";				// действие при завершении
		$validator->sendMethod = "ajax";					//метод отправки
		$validator->preloaderId = "#preloaderCall";			//id прелоадера
		$validator->capId = "#captchaCall";					// id каптчи
		$validator->callback = "successSend";				// Функция Callback
		$validator->param = 'Call';							// Параметр в функцию


		$page->script = $validator->getJsArray();
		$page->cap = $validator->getCaptcha();


		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>