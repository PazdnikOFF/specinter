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


/**
 * Разный функционал
 */
class All {

	function __construct() {

	}

	/**
	 * Функция для изменения формата даты
	 * @global type $ar_mon массив месяцев в родительном падеже
	 * @param type $date дата в формате YYYY-mm-dd
	 * @param type $type - режим обработки
	 * @return string
	 */
    function get_name($cid)
    {
        global $sql;
        return $sql->fetch_row($sql->query("select name from prname_tree where `id`='$cid'"), 0, 1);
    }

    public static function googleReCaptcha()
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => '6LehYeQUAAAAAME41f1RZV7yk9JlGsr7Bf2UCP88',
            'response' => $_POST["g-recaptcha-response"]
        );
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $verify = file_get_contents($url, false, $context);
        $captcha_success = json_decode($verify);
        return $captcha_success->success == true ? true : false;
    }

	function getDate($date, $type = 1) {
		global $ar_mon;

		if ($type == 1) {
			$year = substr($date, 0, 4);
			$mon = substr($date, 5, 2);
			$day = 0 + substr($date, 8, 2);
			$date = $day.'.'.$mon.'.'.$year;
		}

		if ($type == 2) {
			$year = substr($date, 0, 4);
			$mon = $ar_mon[0 + substr($date, 5, 2)];
			$day = 0 + substr($date, 8, 2);

			$date = $day.' '.$mon.' '.$year;
		}
		return $date;
	}

	function dateDifference($date_1 , $date_2 , $differenceFormat = '%iминут %s секунд назад') {
		$datetime1 = date_create($date_1);
		$datetime2 = date_create($date_2);

		$interval = date_diff($datetime1, $datetime2);
		return $interval;

		return $interval->format($differenceFormat);
	}

	/**
	 * Функция склоняет существительные в зависимости от количестенной характеристики (11 яблок, 2 яблока)
	 * @param int $number число
	 * @param array $words массив слов в виде("яблоко","яблока","яблок")
	 * @param boolean $show_number - возвращать ли только слово или вместе с числом
	 * @return string
	 */
	function declOfNum($number, $words, $show_number = true) {
		$cases = array(2, 0, 1, 1, 1, 2);
		if ($show_number)
			return $number." ".$words[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
		else
			return $words[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
	}

	/**
	 * Функция для отправки почтового сообщения
	 * @global type $config
	 * @param mixed $email			Адресат(ы)
	 * @param string $emailSub		Тема
	 * @param string $emailText		Содержание
	 * @param array $file			Массив файлов для аттачмента
	 * @param array $filename		Массив имен файлов
	 * @param string $fromName		Имя отправителя
	 * @return boolean
	 */
	function send_mail($email, $emailSub, $emailText, $file = false, $filename = false, $fromName = 0) {
		global $config;
		$mailer = new PHPMailer();

		$mailer->Subject = $emailSub;
		$mailer->Body = $emailText;
		$q = count($filename);
		for ($i = 0; $i < $q; $i++) {
			$mailer->AddAttachment($file[$i], $filename[$i]);
		}

		if (is_array($email)) {
			foreach ($email as $val) {
				$mailer->AddAddress($val);
			}
		}else {
			$mailer->AddAddress($email);
		}

		$mailer->From = 'noreply@'.$_SERVER['HTTP_HOST'];
		if ($fromName !== 0)
			$mailer->FromName = $fromName;
		else
			$mailer->FromName = $config['site_name'];

		$mailer->AltBody = $emailSub;
		if (!$mailer->Send()){
			$res = false;
		}else{
			$res = true;
		}

		$mailer->ClearAddresses();
		return $res;
	}

	/**
	 *
	 * @param string $path
	 * @return string
	 */
	function read_file($path) {
		$file = file($path);
		$page = '';
		for ($i = 0; $i < count($file); $i++) {
			$page .= $file[$i];
		}
		return $page;
	}

	/**
	 *
	 * @param string $path
	 * @return float
	 */
	function filesize($path) {
		return file::filesize($path);
	}

	/**
	 * Функция для формирования url
	 * @param type $main
	 * @return string
	 */
	function getUrl($id) {
		$url = tree::getUrl($id);
		$url = "/".$url;
		return $url;
	}


	/**
	 * Функция для добавления query части к url
	 * @param int $page			Страница
	 * @param string $oper		Операция
	 * @param int $id			Номер блока
	 * @param string $sort		Сортировка
	 * @param string $sortv		Сортировка
	 * @return string
	 */
	function addUrl($page = '', $oper = '', $id = '', $sort = '', $sortv = '') {
		$text = '';

		if ($page != '') {
			$text .= '_p'.$page;
		}
		if ($oper != '') {
			$text .= '_a'.$oper;
		}
		if ($id != '') {
			$text .= '_b'.$id;
		}
		if ($sortv != '') {
			$text .= '_v'.$sortv;
		}
		if ($sort != '') {
			$text .= '_s'.$sort;
		}
		if ($text != '') {
			$text = $text;
		}
		return $text;
	}

	/**
	 * Функция для изымания данных нужного блока
	 * @param int $catid			Id блока
	 * @param type $template		Шаблон блока
	 * @return object
	 */
	function b_data_all($catid, $template) {
		$d = sql::query("select p2.* from prname_btemplates p1, prname_bdatarel p2 where p1.key = '$template' and p2.templid=p1.id");
		while ($arr = sql::fetch_assoc($d)) {
			$fields[$arr['key']]->datatkey = $arr['datatkey'];
			$fields[$arr['key']]->comment = $arr['comment'];
		}

		$q = sql::query("SELECT * FROM prname_b_$template WHERE id = '$catid'");
		while ($arr = sql::fetch_assoc($q)) {

			$qarr = array_keys($arr);
			for ($i = 0; $i < count($qarr); $i++) {

				if ($qarr[$i] == 'price' || $qarr[$i] == 'oldprice' && !empty($arr[$qarr[$i]])) {
					$arr[$qarr[$i]] = ceil(str_replace(',', '.', $arr[$qarr[$i]]));
				}


				if (!isset($fields[$qarr[$i]]->datatkey))
					$fields[$qarr[$i]]->datatkey = "";
				switch ($fields[$qarr[$i]]->datatkey) {
					case 'html':$page->$qarr[$i] = self::parseHtml($arr[$qarr[$i]]);
						break;
					case 'textarea':$page->$qarr[$i] = str_replace(chr(13), "<br/>", $arr[$qarr[$i]]);
						break;


					case 'select':
						$d = explode(":", $fields[$qarr[$i]]->comment);
						$page->{$qarr[$i].'_sel'} = $arr[$qarr[$i]];

						if ((($n = strpos($arr[$qarr[$i]], "allblocks:")) !== false) || (($n3 = strpos($arr[$qarr[$i]], "visblocks:")) !== false) || (($n4 = strpos($arr[$qarr[$i]], "hidblocks:")) !== false)) {
							$page->{$qarr[$i].'_id'} = $arr[$qarr[$i]];
							$this->item[$i]->$ak[$ii] = sql::fetch_row(sql::query("select `".$d[1]."` from prname_b_".$d[4]." where `id`='".$arr[$qarr[$i]]."'"), 0, 1);
						}break;

					case 'date':
						$page->{$qarr[$i]} = $arr[$qarr[$i]];
						$page->{$qarr[$i].'_1'} = all::getDate($arr[$qarr[$i]], 1);
						$page->{$qarr[$i].'_2'} = all::getDate($arr[$qarr[$i]], 2);
						break;
					case 'file':$page->$qarr[$i] = $arr[$qarr[$i]];
						if (($n = strpos($page->fields[$qarr[$i]]->comment, 'resize:')) !== false) {
							$fn1 = explode('resize:', $page->fields[$qarr[$i]]->comment);
							$fs = explode(',', $fn1[1]);
							for ($if = 0; $if < count($fs); $if++)
								if (!is_file('files/'.($if + 1).'/'.$arr[$qarr[$i]])){
                                    resize_image($arr[$qarr[$i]], $fs[$if], $if + 1, '');
                                }

						};
						break;
					default:$page->$qarr[$i] = $arr[$qarr[$i]];
						break;
				}
			}
		}
		return $page;
	}

	/**
	 * Функция для добавления информационного блока
	 * @param string $template		Шаблон блока
	 * @param int $parent			Родитель
	 * @param array $data			Массив с данными для вставки
	 * @param int $visible			Флаг видимости
	 * @param int $blockparent		Блок-родитель
	 * @return int
	 */

	function insert_block($template, $parent, $data, $visible = 1, $blockparent = 0) {
		$q = sql::query("select p2.* from prname_btemplates p1, prname_bdatarel p2 where p1.key = '".$template."' and p2.templid=p1.id");
		$qs = '';
		while ($qww = sql::fetch_assoc($q)) {
			if ($qww['datatkey'] == "date") {
				if ($data[$qww['key']] == "") {
					$data[$qww['key']] = date("Y-m-d");
				}
			}
			if (strlen($data[$qww['key']]) == 0)
				$datar[$qww['key']] = $qww['default'];
			$qs .= " `$qww[key]` = '".$data[$qww['key']]."', ";
		}
		$sort = sql::fetch_row(sql::query("select MAX(sort) from prname_b_$template"), 0, 1) + 1;
		sql::query("insert into prname_b_$template set $qs `parent`='$parent',`visible`='$visible',`sort`='$sort', `blockparent`='$blockparent'");
		return sql::insert_id();
	}

	/**
	 * Функция для изымания данных нужной папки
	 * @param int $catid		Id папки
	 * @param type $template	Шаблон папки
	 * @return object
	 */
	function c_data_all($catid, $template) {
		$d = sql::query("select p2.* from prname_ctemplates p1, prname_cdatarel p2 where p1.key = '$template' and p2.templid=p1.id");
		while ($arr = sql::fetch_assoc($d)) {
			$fields[$arr['key']]->datatkey = $arr['datatkey'];
			$fields[$arr['key']]->comment = $arr['comment'];
		}
		$q = sql::query("select * from prname_c_$template where parent = '$catid'");
		while ($arr = sql::fetch_assoc($q)) {
			$qarr = array_keys($arr);
			for ($i = 0; $i < count($qarr); $i++) {

				if (!isset($fields[$qarr[$i]]))
					$fields[$qarr[$i]]->datatkey = "";

				switch ($fields[$qarr[$i]]->datatkey) {
					case 'html':$page->$qarr[$i] = self::parseHtml($arr[$qarr[$i]]);
						break;
					case 'textarea':$page->$qarr[$i] = str_replace(chr(13), "<br/>", $arr[$qarr[$i]]);
						break;
					case 'select':
						$page->{$qarr[$i].'_sel'} = $arr[$qarr[$i]];
						$d = explode(":", $fields[$qarr[$i]]->comment);
						break;
					case 'date':
						$page->item[$i]->{$qarr[$i]} = $arr[$qarr[$i]];
						$page->item[$i]->{$qarr[$i].'_1'} = all::getDate($arr[$qarr[$i]], 1);
						$page->item[$i]->{$qarr[$i].'_2'} = all::getDate($arr[$qarr[$i]], 2);
						break;
					case 'file':$page->$qarr[$i] = $arr[$qarr[$i]];
                        $page->fields[$qarr[$i]] =$fields[$qarr[$i]];
						if (($n = strpos($page->fields[$qarr[$i]]->comment, 'resize:')) !== false) {
							$fn1 = explode('resize:', $page->fields[$qarr[$i]]->comment);
							$fs = explode(',', $fn1[1]);
							for ($if = 0; $if < count($fs); $if++){
                                if (!is_file('files/'.($if + 1).'/'.$arr[$qarr[$i]]) && is_file('files/0/'.$arr[$qarr[$i]])){
                                    resize_image($arr[$qarr[$i]], $fs[$if], $if + 1, '');
                                }
                            }

						};
						break;
					default:$page->$qarr[$i] = $arr[$qarr[$i]];
						break;
				}
			}
		}

		return $page;
	}

	/**
	 * Функция для чтения переменной из urlparams
	 * @global type $control
	 * @param type $name	имя переменной
	 * @return mixed		Возвращает переменную или false, если переменная не найдена
	 */
	function getVar($name) {
		global $control;
		$urlparams = $control->urlparams;
		$params = explode("_", $urlparams);
		if (count($params) > 0) {
			foreach ($params as $param) {

				if (substr($param, 0, strlen($name)) == $name) {
					$var = substr($param, strlen($name));
					$var = trim($var, '/');

					if (($getPos = strpos($var, "?")) > -1) {
						$var = substr($var, 0, $getPos);
					}
					if(count(explode("&",$var)) > 1){
                        $var = explode("&",$var);
                        $var = $var[0];
                    }
					return $var;
				}
			}
		}
		return false;
	}

	/**
	 * Функция парсит html блоки из редактора
	 * @param type $text		Сам html
	 * @param type $shy			Флаг переноса слов
	 * @return string			Возвращает отформатированный html
	 */
	function parseHtml($text, $shy=0)  {
		include_once("textclass.php");
		$typo = new typography($text);

		$typo->cleanUp("utf-8");
		//$typo->wrapList();

		if ($shy) {
			$typo->transferLines();
		}

		return $typo->text;
	}

	function json_format($json) {
		$tab = "    ";
		$new_json = "";
		$indent_level = 0;
		$in_string = false;

		$json_obj = json_decode($json);

		if($json_obj === false)
			return false;

		$json = json_encode($json_obj);
		$len = strlen($json);

		for($c = 0; $c < $len; $c++) {
			$char = $json[$c];
			switch($char) {
				case '{':
				case '[':
					if(!$in_string) {
						$new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
						$indent_level++;
					}
					else {
						$new_json .= $char;
					}
					break;
				case '}':
				case ']':
					if(!$in_string) {
						$indent_level--;
						$new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
					}
					else {
						$new_json .= $char;
					}
					break;
				case ',':
					if(!$in_string) {
						$new_json .= ",\n" . str_repeat($tab, $indent_level);
					}
					else {
						$new_json .= $char;
					}
					break;
				case ':':
					if(!$in_string) {
						$new_json .= ": ";
					}
					else {
						$new_json .= $char;
					}
					break;
				case '"':
					if($c > 0 && $json[$c-1] != '\\') {
						$in_string = !$in_string;
					}
				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}

	function getRandom($min=0, $max=100) {
		$min = (int)$min;
		$max = (int)$max;

		mt_srand(self::make_seed());
		return mt_rand($min, $max);
	}

	function make_seed() {
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}
}

?>