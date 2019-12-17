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
 * Тип данных многострочного текста (textarea).
 * Создавался для счетчиков статистики, отсюда и название
 */
class type_counter {
	public function input($name, $data, $comment = '', $ro) {
		$maxlength = "";
		$style = "";
		$readonly = "";
		$return = "";

		// comment attributes

		// hidden
		if ( strpos($comment, "hidden:") !== false) {
			$style=" style='display: none;'";
		}
		else {
			$style="style='width: 1000px; height: 400px'";
		}

		// maxlength
		if (($n = strpos($comment, 'maxlength:')) !== false) {
			if (($n2 = strpos($comment, ' ', $n)) == false) {$n2 = strlen($comment);}
			$n += 10;
			$maxlengthval = substr($comment, $n, $n2 - $n);
			$maxlength = "data-maxlength='$maxlengthval'";
		}

		// read only
		if ($ro) {
			$readonly = "readonly";
		}

		if ($maxlength) {
			$return = "<p class='maxlength'>Символов осталось: <b>$maxlengthval</b></p>";
		}

		$return .= "<textarea class='textarea form-control' name='".htmlspecialchars($name)."' id='my$name' $style $readonly $maxlength>".htmlspecialchars($data)."</textarea>";

		return $return;
	}

	public function save($name) {
		global ${"$name"};
		return stripslashes(${"$name"});
	}

	public function get($data, $comment, $ro) {
		if (strlen($data) >= 50) {
			$data = substr($data, 0, 50);
			$data = substr($data, 0, strrpos($data, " "))."...";
		}
		return strip_tags(trim($data));
	}

}
?>