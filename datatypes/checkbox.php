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
 * Тип данных чекбокс, для значений типа "вкл/выкл". В базусохраняет 0 или 1.
 */
class type_checkbox {
	public function input($name, $data, $comment = '', $ro) {
		$ch = ($data > 0) ? " checked" : "";
		if ( strpos($comment, "hidden:") !== false) {
			$style=" style='display: none;'";
		}
		else {
			$style="";
		}
		if ($ro) {
			$attr = " readonly disabled";
		}
		else {
			$attr = "";
		}

		return "<input class=\"ch\" type=\"checkbox\" name=\"".htmlspecialchars($name)."\" value=\"1\"$ch $attr $style>";
	}

	public function save($name) {
		global ${"$name"};
		return (${"$name"} > 0)?1:0;
	}

	// Значение, комментарий, рид-онли, id блока, поле
	public function get($data, $comment, $ro, $id, $field) {
		if ($ro == 1) {
			return $data == 1 ? "Да" : "Нет";
		}
		else {
			if ($data == 1) {
				$return = "<input type='checkbox' class='ch ch-live' checked data-id='$id' data-field='$field'>";
			}
			else {
				$return = "<input type='checkbox' class='ch ch-live' data-id='$id' data-field='$field'>";
			}
			return $return;
		}
	}

}
?>