<?php
/**
 * This file is part of Elgrow CMS
 * Copyright 2012 Innokenty Sarayev <6319432@gmail.com>
 *
 * Elgrow CMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Elgrow CMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


// error_reporting(E_ALL);

/**
 * Класс контроллера
 */
class Controller {

	/**
	 * Родитель
	 * @var int
	 */
	public $cid;

	/**
	 * Id блока
	 * @var int
	 */
	public $bid;

	/**
	 * Title
	 * @var string
	 */
	public $titleSeo;

	/**
	 * Description
	 * @var string
	 */
	public $descriptionSeo;

	/**
	 * Keywords
	 * @var string
	 */
	public $keywordsSeo;

	/**
	 * Запрашиваемая операция
	 * @var string
	 */
	public $oper;

	/**
	 * Текущая страница при постраничном разбиении
	 * @var int
	 */
	public $page;

	/**
	 * Текущий шаблон
	 * @var string
	 */
	public $template;

	/**
	 * Массив id предков
	 * @var array
	 */
	public $parents;

	/**
	 * Уровень вложенности
	 * @var int
	 */
	public $level;

	/**
	 * Название страницы
	 * @var string
	 */
	public $name;

	/**
	 * Текущий модуль
	 * @var string
	 */
	public $module;

	/**
	 * Блок, привязанный к модулю
	 * @var string
	 */
	public $wrapper;

	/**
	 * @var boolean
	 */
	public $NESTEDSETS = true;


	/**
	 * Путь без query string
	 * @var string
	 */
	private $pathstring;

	/**
	 * Параметры урл (GET и встроенные)
	 * @var string
	 */
	public $urlparams;

	/**
	 * Функция парсит урл, выбирает шаблон вывода, устанавливает все переменные класса
	 * @global object of class sql $sql
	 * @global array $config
	 * @param type $rUrl
	 * @return type
	 */
	public function __construct($rUrl) {
		global $sql;
		global $config;
		$this->sql = $sql;

		$this->checkLogin();


		// Парсим урл
		$segments = preg_split("#/#", $rUrl, -1, PREG_SPLIT_NO_EMPTY);
		$lastSeg = end($segments);

		
		if ($lastSeg[0] == "_") {
			$this->urlparams = $urlparams = $lastSeg;
			$this->pathstring = str_replace($lastSeg, "", $rUrl);
			$this->pathstring = substr($this->pathstring, 1);
			$this->pathstring = str_replace("//", "/", $this->pathstring);

			if (strpos($this->pathstring, "?") > -1) {
				$this->pathstring = substr($this->pathstring, 0, strpos($this->pathstring, "?"));
			}
		}
		else {
			$this->urlparams = $urlparams = "";
			$this->pathstring = substr($rUrl, 1);
			if (strpos($this->pathstring, "?") > -1) {
				$this->pathstring = substr($this->pathstring, 0, strpos($this->pathstring, "?"));
			}
		}

		$this->pathstring = urldecode($this->pathstring);

	

		$p = $urlparams ? '_'.$urlparams : '';
		$this->url = $config['server_url'].$this->pathstring.$p;
		$this->all = new All();

		// Имя сайта
		$this->settings = all::c_data_all(12, "settings");
		$config['site_name'] = $this->settings->sitename;


		//Блоковый урл
		$blockUrl = $sql->fetch_assoc($sql->query("SELECT * FROM prname_urls WHERE url='".$this->pathstring."' OR url='".$this->pathstring."/"."' LIMIT 0,1"));

		
		if ($blockUrl != null) {
			$this->pathstring = $blockUrl['realurl'];
			$urlparams = "aview_b".$blockUrl['blockid'];
		}

		// Выбираем из базы строку с таким урлом
		$q = "SELECT * FROM prname_tree WHERE url = '$this->pathstring' OR url = '$this->pathstring/' ";
		$str = $sql->fetch_array($sql->query($q));

		// Если есть - ставим переменные
		if ($str['id']) {
			$this->cid = $str['id'];
			$this->level = $str['level'];
			$this->template = $str['template'];
			$this->name = $str['name'];
			$this->module_url = $config['server_url'].$str['url'];
			$this->parents = tree::getParentsNew($str['left_key'], $str['right_key']);
		}


		// Если нету - ошибка 404 (см. файл inner.php)
		else {
			$this->error = "error404";
			return;
		}




		// Параметры урла
		$arr_param = explode("_", $urlparams);

		// if (isset($_COOKIE['vas-vas'])) {
		// 	var_dump(array(
		// 		$this->cid,
		// 		$this->module_url,
		// 		$this->template,
		// 		$this->name,
		// 		$this->module_url,
		// 		$this->parents
		// 	));
		// }


		if (count($arr_param) > 0) {
			foreach ($arr_param as $arr_one) {
				// Что за параметр?
			
				switch (substr($arr_one, 0, 1)) {
					// Страница
					case "p": {
							$this->page = substr($arr_one, 1);
							break;
						}
					// Режим
					case "a": {
							if (!$this->oper) {
								$this->oper = substr($arr_one, 1);
								if (($getPos = strpos($this->oper, "?")) > -1) {
									$this->oper = substr($this->oper, 0, $getPos);
								}
							}
							break;
						}
					// Номер блока
					case "b": {
							$this->bid = substr($arr_one, 1);
							if (($getPos = strpos($this->bid, "?")) > -1) {
								$this->bid = substr($this->bid, 0, $getPos);
							}
							break;
						}
				}
			}
		}

		if (trim($this->module) == '') {

			
			// Если шаблон есть
			if ($this->template) {
				$mtpl = sql::fetch_assoc(sql::query("select * from prname_ctemplates where `key`='$this->template' and `visible`=1"));
			}
			// Если нету - отсылаем на 404
			else {
				$this->error = "error404";
				return;
			}



			$this->mtmp = $mtpl;

			
			// Настройки модуля
			$mparam = explode("|", $mtpl['alias']);
			$module = strlen($mparam[0]) > 0 ? $mparam[0] : $this->template; // Модуль
			$wrapper = strlen($mparam[1]) > 0 ? $mparam[1] : 'h2.html'; // Файл

			if ($_POST['mode'] == 'ajax') {
				$wrapper = 'h1_ajax.html';
			}

			// Родитель
			if (isset($mparam[3]) && $mparam[3] != "") {
				$this->module_parent = $mparam[3];
			}
			else
				$this->module_parent = $this->cid;

			// Global
			if (isset($mparam[4]) && $mparam[4] != "") {
				$this->module_global = $mparam[4];
			}
			else
				$this->module_global = $this->cid;

			// Шаблон блока
			$this->module_wrap = $this->mtmp['blocktypes'];
			$this->module_wrap = preg_split("# #", $this->module_wrap, null, PREG_SPLIT_NO_EMPTY);
			if ($this->module_wrap != "" && is_array($this->module_wrap) && count($this->module_wrap) > 0) {
				$this->module_wrap = $this->module_wrap[0];
			}


			if (module_prapare($module, $this->template)) {
				$this->module = $module;
				$this->wrapper = $wrapper;
			}
		}
		
		if (isset($this->bid) && $this->oper == 'view' && isset($this->module_wrap) && $this->module_wrap != "" && count($this->module_wrap) > 0) {
			$block = $this->all->b_data_all($this->bid, $this->module_wrap);
			if (!$block) {
				$this->error = 'error404';
				return;
			}
		}


	}

	/**
	 * Генерирует модули, плагины, собирает все вместе и выводит
	 * @global object of class sql $sql
	 * @global array $config
	 */
	public function make() {
		global $sql;
		global $config;

		$this->getSeoFields();

		$path_to_file =str_replace("//", "/",  DOC_ROOT."/inc/modules/".$this->module.".php");
		if (is_file($path_to_file)) {
//			echo ($path_to_file)."start import1213\r\n";
			require_once($path_to_file);
		}
		else {
			$this->error = "error404";
			return;
		}



		$this->html = $this->all->read_file(DOC_ROOT."/templates/".$this->wrapper);
		$this->html = $this->html ? $this->html : 'Отсутствие файла '.$this->wrapper;



		${$this->module} = new $this->module();

		$arr_keys = @array_keys(${$this->module}->html);
		if (count($arr_keys) > 0) {
			foreach ($arr_keys as $one_arr) {
				$this->html = str_replace("<!--control_$one_arr//-->", ${$this->module}->html[$one_arr], $this->html);
			}
		}

		$this->html = str_replace(array('{base_url}', '<!--base_url//-->'), $config['server_url'], $this->html);
		preg_match_all('/<!--(.*?)\/\/-->/Ui', $this->html, $arr_modules);

		if (count($arr_modules) > 0) {
			foreach ($arr_modules[1] as $one_arr) {

				if (!strstr($one_arr, 'control')) {
					if (!is_file("inc/misc/".$one_arr.".php")) {
						$tmp_file_body = $this->all->read_file("inc/misc/_default_.php");
						$tmp_file_body = str_replace('<!--name//-->', $one_arr, $tmp_file_body);
						if ($fp = @fopen("inc/misc/".$one_arr.".php", 'w+')) {
							fputs($fp, $tmp_file_body);
							fclose($fp);
						}
					}
					include_once (DOC_ROOT."/inc/misc/".$one_arr.".php");

					${"tmp_print".$one_arr} = new $one_arr();
					$miskhtml = ${"tmp_print".$one_arr}->Make($one_arr.".html");
				}


				$this->html = str_replace("<!--$one_arr//-->", $miskhtml, $this->html);
			}
		}


		$this->html = str_replace(array('{base_url}', '<!--base_url//-->'), $config['server_url'], $this->html);


		echo $this->html;
	}

	private function getSeoFields() {
		global $config;

		// Если блок на выходе, пытаемся извлечь для него данные
		if ($this->oper == "view") {
			$template = $this->module_wrap;

			$a = all::b_data_all($this->bid, $template);

			if ($a->utitle != '') {
				$this->titleSeo = $a->utitle;
			}
			// Иначе - сами генерируем
			else {
				$a = all::b_data_all($this->bid, $template);
				$this->titleSeo = sql::escape_string($a->name." | ".$this->settings->sitename);
				if ($template == 'catitem')
					sql::query("UPDATE prname_b_{$template} SET utitle = '{$this->titleSeo}' WHERE id = {$this->bid}");
			}
			if ($a->udescription) {				
				$this->descriptionSeo = $a->udescription;
			} else {
				$this->descriptionSeo = $this->titleSeo;
				if ($template == 'catitem')
					sql::query("UPDATE prname_b_{$template} SET udescription = '{$this->titleSeo}' WHERE id = {$this->bid}");
			}
			$this->keywordsSeo = $a->ukeywords;
		} elseif ($this->oper == "sub") {
			$template = 'tgroup';

			$a = all::b_data_all($this->bid, $template);

			if ($a->utitle != '') {
				$this->titleSeo = $a->utitle;
			}
			// Иначе - сами генерируем
			else {
				$a = all::b_data_all($this->bid, $template);
				$this->titleSeo = sql::escape_string($a->name." | ".$this->settings->sitename);
				if ($template == 'tgroup')
					sql::query("UPDATE prname_b_{$template} SET utitle = '{$this->titleSeo}' WHERE id = {$this->bid}");
			}
			if ($a->udescription) {				
				$this->descriptionSeo = $a->udescription;
			} else {
				$this->descriptionSeo = $this->titleSeo;
				if ($template == 'tgroup')
					sql::query("UPDATE prname_b_{$template} SET udescription = '{$this->titleSeo}' WHERE id = {$this->bid}");
			}
			$this->keywordsSeo = $a->ukeywords;
		}

		// Если не блок
		else {
			$q = sql::fetch_assoc(sql::query("SELECT * FROM prname_c_".$this->template." where `parent`='".$this->cid."'"));

			if ($q['utitle'] == "") {
				$d = array_reverse($this->parents);
				$i = 0;
				foreach ($d as $f) {
					if($f !== '1') $this->titleSeo .=  sql::fetch_row(sql::query("select name from prname_categories where id='$f'"),0,1)." - ";
					$i++;
				}
				$this->titleSeo .= ' '.$this->settings->sitename;
			}
			else {
				$this->titleSeo = $q['utitle'];
			}



			$this->descriptionSeo = $q['udescription'];
			$this->keywordsSeo = $q['ukeywords'];
		}

	}

	/**
	 * Функция проверяет авторизацию
	 */
	private function checkLogin() {
		global $config, $sql;

		if (isset($_SESSION['uid'])) {
			return;
		}
		else {
			$str = protect::p_encode($_COOKIE['udr_uity']);
			$salt2 = $config['salt'];

			$r = $sql->query("SELECT * FROM prname_b_user");

			while ($res = $sql->fetch_assoc($r)) {
				$md5 = md5($res['login'].$salt2);

				if ($str == $md5) {
					$_SESSION['uid'] = $res['id'];
					return;
				}
			}

			return;
		}
	}
}

?>