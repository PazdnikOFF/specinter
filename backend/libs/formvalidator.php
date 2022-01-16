<?php
/**
 * This file is part of Elgrow CMS
 * Copyright 2012 Innokenty Sarayev <6319432@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


/*Класс для проверки форм - работает как с включенным js, так и без него
**Чтобы воспользоваться нужно:
** 1). Создать конфиг для Fieldй такого вот вида:
		$this->config = array(
			$formName => array(
				'fio' => array(
					'caption' => 'Имя',
					'noempty' => true,
					'nonumber'  => true,
					'maxlength' => 5
				),
				'phone' => array(
					'caption' => 'Контактный телефон',
					'noempty' => true,
					'numberonly' => true,
					'maxlength' => 100
				),
				'eemail' => array(
					'caption' => 'Ваш E-mail',
					'noempty' => true,
					'email' => true,
					'maxlength' => 100
				),
				'text' => array(
					'caption' => 'Текст сообщения',
					'noempty' => true,
					'maxlength' => 200
				)
			)
		);
** caption - обязательное поле, остальные - обработчики (все перечислены внизу класса)

** 2). Создать новый экземпляр класса и передать в конструктор конфиг $this->validator = new formvalidator($this->config);
** 3). Установить необходимые значения переменных, например $this->validator->showErrorMethod = "#showErrors";  //div для показа ошибок
*/

class captcha {
	public static $imagePath = "/libs/imgcode/";
	public static $fontPath = "/libs/fonts/";
	public function __construct() {

	}
	public static function getCaptcha($salt) {
		self::$imagePath = DOC_ROOT."/libs/imgcode/";
		self::$fontPath = DOC_ROOT."/libs/fonts/";

		$x_img = 120; $y_img = 29; //ширина и высота
		$numsymb_img=5; //кол-во символов
		$bgcolor_img = 0x1f4452; //задний фон
		$font_size_from = 14;
		$font_size_dis = 14;
		$fonts = array('a3');
		$im = @ImageCreateTrueColor ($x_img, $y_img) or die ("Ошибка инициализации библиотеки GD");
		ImageFilledRectangle ($im, 0, 0, $x_img - 1, $y_img - 1, $bgcolor_img);
		$tx = 7 + rand(0, 5);
		$s = '';
		for ($i = 0; $i < $numsymb_img; $i++) {
			mt_srand(time());
			$n = self::getRandomSign();
			$nx = $tx;
			$ny = 15; // координаты цифры
			//$ny = 25 + rand(0, 35); // координаты цифры
			$f = rand(5, 10); // размер цифры
			$font = $fonts[rand(0, count($fonts) - 1)] . '.ttf';
			$size = rand($font_size_from,$font_size_dis);
			 $angle = -30 + mt_rand(0, 60);
			//$angle = 10;
			//$color = rand(0, 180) * 65536 + rand(0, 180) * 256 + rand(0, 180);
			$color = 0xFFFFFF;
			$dx = self::imagettftext_cr($im, $size, $angle, $nx, $ny, $color, self::$fontPath.$font, $n );
			$s .= $n;
			$tx += 8 + $dx;
		}
		// Формирование пикселей
	/*	$n = rand(40,90);
		for ($i = 0; $i < $n; $i++) {
			$color = rand(100, 255) * 65536 + rand(100, 255) * 256 + rand(100, 255);
			$nx = rand(1, $x_img - 2);
			$ny = rand(1, $y_img - 2);
			imagesetpixel($im, $nx, $ny, $color);
			imagesetpixel($im, $nx - 1, $ny, $color);
			imagesetpixel($im, $nx + 1, $ny, $color);
			imagesetpixel($im, $nx, $ny - 1, $color);
			imagesetpixel($im, $nx, $ny + 1, $color);
		}*/

		$tmp_cifr_post = md5($salt.$s);
		ImageJpeg ($im, self::$imagePath.$tmp_cifr_post.".jpg", 99);
		@imagedestroy($im);
		foreach (glob(self::$imagePath."*.jpg") as $filename) {
			if(date("YmdHis", filemtime($filename))<date("YmdHis", mktime(date("H"), date("i")-5, date("s"), date("m"), date("d"), date("Y"))))
			unlink($filename);
		}
		return $tmp_cifr_post;
	}
	private function imagettftext_cr (&$im, $size, $angle, $x_img, $y_img, $color, $fontfile, $text) {
		$bbox = imagettfbbox($size, $angle, $fontfile, $text);
		$dx = ($bbox[2]-$bbox[0])/2.0 - ($bbox[2]-$bbox[4])/2.0; // deviation left-right
		$dy = ($bbox[3]-$bbox[1])/2.0 + ($bbox[7]-$bbox[1])/2.0; // deviation top-bottom
		$px = $x_img-$dx;
		$py = $y_img-$dy;
		imagettftext($im, $size, $angle, $px, $py, $color, $fontfile, $text);
		return $bbox[2] - $bbox[0];
	}
	private function getRandomSign() {
		mt_srand();
		$m = mt_rand(48,57);
		return chr($m);
	}
}

class formvalidator {
	private $config;					// Конфиг полей, загружается в конструкторе
	public $error = array();			// Массив ошибок
	public $post;						// Пост данные

	private $captcha;
	public $formName;					//Имя формы

	private $salt = "has";

	public $showErrorMethod = "alert";	// Метод показа ошибок
	public $highlight = true;			// Подсветка полей с ошибками
	public $sendMethod = "ajax";
	public $capId = "#captcha";			// Id капчи
	public $preloaderId = "#preloader";	// Id преловдера
	public $buttonDisabled = null;	// Id кнопки которую нужно отключить



	public function __construct($config) {
		$this->config = $config;

		foreach ($this->config as $key => $value) {
			$this->formName = $key; break;
		}
	}

	public function checkFields($caller, $object) {
		$this->post = $_POST;
		foreach ($this->post as $key => $value) {
			$this->post[$key] = htmlspecialchars(trim($value));
		}

		foreach ($this->config[$this->formName] as $fieldName => $field) {
			$this->checkField($fieldName, $field, $field['caption']);
		}

		$far = $this->salt.$this->post['cap'];

		// Успешная проверка
		if (count($this->error) < 1) {
			$this->success = true;

			if ($this->sendMethod == "ajax") {
				print '@s@';
			}
			$object->success = true;
			return $object;
		}

		// Обработка ошибок
		elseif (count($this->error) > 0) {

			// Ajax
			if ($this->sendMethod == "ajax") {
				$captcha = $this->getCaptcha();

				$result->captcha = $captcha;
				$result->errors = $this->getErrors();
				die(json_encode($result));
			}

			// No Ajax
			$post = $this->post;
			$post2 = array();
			$i = 0;
			foreach ($post as $key => $value) {
				$post2[$i]->name = $key;
				$post2[$i]->value = $value;
				$i++;
			}
			$object->post = $post2;
			$object->errors = $this->errors = $this->getErrors();

			return $object;
		}

	}

	public function getCaptcha() {
		$this->captcha = captcha::getCaptcha($this->salt);
		$_SESSION[$this->capId] = $this->captcha;
		return $this->captcha;
	}

	// Формирует JavaScript код, все переменные нужно устанавливать до вызова этой функции
	public function getJsArray() {
		$validatorConfig->showErrorMethod = $this->showErrorMethod;
		$validatorConfig->highlight = $this->highlight;
		$validatorConfig->sendId = $this->sendId;
		$validatorConfig->lastaction = $this->lastaction;
		$validatorConfig->sendMethod = $this->sendMethod;
		$validatorConfig->capId = $this->capId;
		$validatorConfig->preloaderId = $this->preloaderId;
		$validatorConfig->callback = $this->callback;
		$validatorConfig->param = $this->param;
		$validatorConfig->buttonDisabled = $this->buttonDisabled;

		$validatorConfig->fields = $this->config[$this->formName];

		return json_encode($validatorConfig);
	}

	public function getErrors() {
		$errorStr = "";
		foreach ($this->error as $fieldName => $error) {

			$value = isset($error['value_'.$error[0]]) ? $error['value_'.$error[0]] : 0;
			$errorStr .= "<p style='color: #c21818; font-size: 0.75rem;'>".$this->getErrorText($error['caption'], $error[0], $value)."</p>";
		}
		return $errorStr;
	}

	private function getErrorText($fieldName, $errorType, $value) {

		switch ($errorType) {
			case "maxlength": $string = "У поля `" . $fieldName . "` превышена максимальная длина - " . $value; break;
			case "minlength": $string = "Поле `" . $fieldName . "` - недостаточно символов"; break;
			case "email": $string = "Поле `" . $fieldName . "` заполнено неверно"; break;
			case "noempty": $string = "Поле `" . $fieldName . "` обязательно для заполнения"; break;
			case "nonumber": $string = "Поле `" . $fieldName . "` не должно содержать цифры"; break;
			case "numberonly": $string = "Поле `" . $fieldName . "` должно содержать только цифры"; break;
			case "selectinarray": $string = "Выберите пожалуйста пункт `" . $fieldName . "`"; break;
			case "login": $string = "Пользователь с таким адресом E-mail уже имеется в нашей базе. Пожалуйста введите другое значение."; break;
			case "captcha": $string = "В поле `" . $fieldName . "` введен неверный код"; break;
			case "sameas": $string = "Пароли не совпадают"; break;
			default: $string = "Ошибка";
		};
		return $string;
	}

	private function getFormName() {
		if (isset($this->post['formname'])) {
			$this->formName = $this->post['formname'];
			return;
		}
		foreach ($this->post as $key => $value) {
			foreach ($this->config as $formName => $b) {
				if ($formName == $key) {
					$this->formName = $formName;
					return;
				}
			}
		}
	}

	private function checkField($fieldName, $field, $caption) {
		foreach ($field as $crit => $value) {
			if ($crit != "caption") {
				$methodName = "check_".$crit;
				$this->$methodName($fieldName, $value, $caption);
			}

			if (is_array($this->error[$fieldName])) {
				$this->error[$fieldName]['caption'] = $caption;
			}
		}
	}



	// Max-lenght validation
	private function check_maxlength($field, $value) {
		if (strlen($this->post[$field]) > $value) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'maxlength';
			$this->error[$field]['value_maxlength'] = $value;
		}
	}

	// captcha validation
	private function check_captcha($field, $value) {
		if (md5($this->salt.$this->post[$field]) != $_SESSION[$this->capId]) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'captcha';
		}
	}

	// Min-lenght validation
	private function check_minlength($field, $value) {
		if (strlen($this->post[$field]) < $value) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'minlength';
			$this->error[$field]['value_minlength'] = $value;

		}
	}

	// Only numbers validation
	private function check_numberonly($field, $value) {
		$string = $this->post[$field];
		if (preg_match("#[^0-9+]#", $string)) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'numberonly';
		}
	}

	// number in array validation
	private function check_selectinarray($field, $value) {
		$number = $this->post[$field];
		if (!in_array($number, $value)) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
            $this->error[$field][] = 'selectinarray';
		}
	}

	// No numbers validation
	private function check_nonumber($field, $value) {
		$string = $this->post[$field];
		if (preg_match("#[0-9]#", $string)) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'nonumber';
		}
	}

	// Only numbers and symbols validation
	private function check_numberandsimbols($field, $value) {
		$string = $this->post[$field];
		if (preg_match("#[^0-9-_+@\s]#", $string)) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'numberandsimbols';
		}
	}

	// E-mail validation
	private function check_email($field, $value) {
		$string = $this->post[$field];
		$result = filter_var($string, FILTER_VALIDATE_EMAIL);
		if (!$result) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'email';
		}
	}

	// No empty validation
	private function check_noempty($field, $value) {
		if (strlen($value) > 4) {
			if ($this->post[$field] == $value) {
				if (!is_array($this->error[$field])) {
					$this->error[$field] = array();
				}
				$this->error[$field][] = 'noempty';
			}
		}

		if (strlen($this->post[$field]) < 1) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'noempty';
		}
	}

	// SQL validation
	private function check_login($field, $value) {
		$email = mysql_real_escape_string($this->post[$field]);
		$array = explode("_", $value);
		$table = $array[0];
		$field = $array[1];
	 	$r = sql::one_record('SELECT * FROM prname_b_'.$table.' WHERE `'.$field.'`="'.$email.'"');
		if ($r != null) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'login';
		}
	}

	// sameAs (password reply) validation
	private function check_sameas($field, $value) {
		if ($this->post[$value] != $this->post[$field]) {
			if (!is_array($this->error[$field])) {
				$this->error[$field] = array();
			}
			$this->error[$field][] = 'sameas';
		}
	}
}

?>