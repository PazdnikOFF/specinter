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
 * Тип данных для вывода списка с единственным выбором (select)
 * В доп. информации указывается следующая информация:
 * type:table_field:visible:parent:table:callback или перечисление необходимых значений через точку с запятой
 *
 * type - b (блок) или c (каталог)
 * table_field - поле таблицы
 * visible - vis (чтобы выбрать только видимые) hide (только невидимые) all (все)
 * parent - id родителя, может быть указано all
 * table - название шаблона блока или каталога
 * callback - javascript callback - функция, вызываемая при изменении значения в выпадающем списке
 *
 * Например, для выборки имен только видимых зарегистрированных юзеров, которые хранятся в каталоге с id 363
 * b:name:vis:363:user
 *
 * Или для выборки всех подразделов каталога (допустим id у каталога 340)
 * c:name:all:340:catgroup
 */
class type_select {

	public function input($name, $data, $comment = '', $ro) {
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



		if ( strpos($comment, "c:") !== false || strpos($comment, "b:") !== false ) {
			$d = explode(":", $comment);

			$type = $d[0];
			$field = $d[1];
			$visible = $d[2] == 'vis' ? ' and `visible`=1' : ($d[2] == 'hide' ? ' and `visible`=0' : '');
			$parent = $d[3];
			$table = $d[4];

			if (count($d) == 6) {
				$callback = $d[5];
			}

			$s = "<select name=\"".htmlspecialchars($name)."\" $style $attr class='select-styled' data-placeholder='Выберите значение' data-callback='$callback'>";

			$parent = ($parent == 'all' ? '`parent`>1' : "`parent`='$parent'");

			if ($type == 'b') {
				$query = "SELECT id, $field FROM prname_".$type."_".$table." WHERE $parent $visible ORDER BY sort";
			}
			if ($type == 'c') {
				$query = "SELECT id, name FROM prname_categories WHERE $parent $visible AND template='$table' ORDER BY sort";
			}

			$result = sql::query($query);


			if (sql::num_rows($result) > 0) {
				while ($arr = sql::fetch_assoc($result)) {
					$s .= "<option value=\"".$arr['id']."\" ".(($data == $arr['id']) ? ' selected ' : '').">".htmlspecialchars($arr[$field])."</option>";
				}
			}

		}

		else {
			$s = "<select name=\"".htmlspecialchars($name)."\" $style $attr class='select-styled' data-placeholder='Выберите значение'>";
			$val = splstr($comment, ";");


			$d = splstr($data, ";");
			for ($i = 0; $i < count($val); $i++) {
				$s .= "<option value=\"".htmlspecialchars($val[$i + 1])."\" ".(in_array(htmlspecialchars($val[$i + 1]), $d) ? ' selected ' : '')."$attr>".htmlspecialchars($val[$i + 1])."</option>";
			}
		}
		$s .= "</select>";
		return $s;
	}

	public function save($name) {
		global ${"$name"};
		return ${"$name"};
	}

	// Значение, комментарий, рид-онли
	public function get($data, $comment, $ro, $id, $fieldName) {
		if ( strpos($comment, "hidden:") !== false) {
			return "";
		}

		if ( strpos($comment, "c:") !== false || strpos($comment, "b:") !== false ) {
			$d = explode(":", $comment);

			$type = $d[0];
			$field = $d[1];
			$visible = $d[2] == 'vis' ? ' and `visible`=1' : ($d[2] == 'hide' ? ' and `visible`=0' : '');
			$parent = $d[3];
			$table = $d[4];

			if (count($d) == 6) {
				$callback = $d[5];
			}

			$parent = ($parent == 'all' ? '`parent`>1' : "`parent`='$parent'");

			if ($type == 'b') {
				$query = "SELECT id, $field FROM prname_".$type."_".$table." WHERE $parent $visible ORDER BY sort";
			}
			if ($type == 'c') {
				$query = "SELECT id, name FROM prname_categories WHERE $parent $visible AND template='$table' ORDER BY sort";
			}

			$result = sql::query($query);

			// Read only - just text
			if ($ro) {
				if (sql::num_rows($result) > 0) {
					while ($arr = sql::fetch_assoc($result)) {
						if($arr['id'] == $data) {
							$s = htmlspecialchars($arr[$field]);
							break;
						}
					}

				}
			}

			// Fully editable
			else {
				if (sql::num_rows($result) > 0) {
					$s = "<select $style $attr class='select-styled select-live' data-placeholder='Выберите значение' data-id='$id' data-field='$fieldName' data-callback='$callback'>";

					if (sql::num_rows($result) > 0) {
						while ($arr = sql::fetch_assoc($result)) {
							$s .= "<option value=\"".$arr['id']."\" ".(($data == $arr['id']) ? ' selected ' : '').">".htmlspecialchars($arr[$field])."</option>";
						}
					}
				}
			}
		}

		else {
			$s = "<select name=\"".htmlspecialchars($name)."\" $style $attr class='select-styled' data-placeholder='Выберите значение'>";
			$val = splstr($comment, ";");


			$d = splstr($data, ";");
			for ($i = 0; $i < count($val); $i++) {
				$s .= "<option value=\"".htmlspecialchars($val[$i + 1])."\" ".(in_array(htmlspecialchars($val[$i + 1]), $d) ? ' selected ' : '')."$attr>".htmlspecialchars($val[$i + 1])."</option>";
			}
		}
		$s .= "</select>";
		return $s;
	}

}

?>