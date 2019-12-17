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

/**
 * Тип данных для загрузки изображений
 */

 /**
 * Тип данных для загрузки изображений.
 * Параметры в комментарии:
 * "resize:" - ресайзы изображений. например <resize:100x500> сделает точный ресайз (с выводом кроппера)
 * Чтобы сделать несколько ресайзов нужно писать их через
 * запятую. Например <resize:100x500,300x600>
 * Все ресайзы с кропом
 */
class type_imageload {
	public function input($name, $data, $comment = '') {
		if (strpos($comment, "resize") > -1) {
			$comment = str_replace("resize:", "", $comment);
			$sizes = explode(",", $comment);

			foreach ($sizes as $key => $val) {
				$sizes[$key] = explode("x", $val);
				$sizes[$key]['aspectRatio'] = $sizes[$key][0] / $sizes[$key][1];
			}
		}


		//Ресайзы
		$sizesScript = json_encode($sizes);
		
		$sizesScript = "<script>sizes['$name'] = $sizesScript;</script>\n";

		$page->sizesScript = $sizesScript;
		$page->name = $name;

		//Значения
		$data = explode(";", $data);
        
		$i = 0;
		foreach ($data as $key => $val) {

			//Проверка на наличие файла и не пустую строку
			if ($val != "" && file_exists(DOC_ROOT."/files/0/".$val)) {
				$page->data[$i]->name = $val;
				$page->data[$i]->sname = "file_".$name."_".$i;
				$i ++;
			}
		}


		$text = sprintt($page, 'datatypes/templates/imageload.html');

		return $text;
	}

	public function input2($name, $data, $comment = '') {
		$data = explode(";", $data);


		//Проверка на наличие файла и не пустую строку
		foreach ($data as $key => $val) {
			if ($val == "" || !file_exists(DOC_ROOT."/files/0/".$val)) {
				unset($data[$key]);
			}
		}

		$data = implode(";", $data);

		$return = '<input name="'.htmlspecialchars($name).'" id="'.htmlspecialchars($name).'imageload" type="hidden" value="'.htmlspecialchars($data).'">';

		return $return;
	}

	public function save($name) {
		global ${"$name"};
		return stripslashes(${"$name"});
	}

	public function get($data, $comment, $ro) {
		return "";
	}

}
?>