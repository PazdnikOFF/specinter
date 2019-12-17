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
 * Тип данных для вывода таблицы характеристик (для интернет магазинов)
 *
 *		Обшая структура функционала:
 *
 *		Имеется Каталог "Таблицы характеристик"
 *		В нем находятся созданные каталоги "Таблица характеристик"
 *		В каждой из которых содержатся блоки типа "spec"
 *		Блок "spec" состоит из трех полей - Название(name),служебное название (sname) и единица измерения(unit)
 *
 *		К каталогам типа "группа товаров" должна ассоциироваться одна из таблиц характеристик
 *		с помощью добавления к каталогу поля (!!важно) "tablech" (!!важно) типа select.
 */

class type_features {
	public function input($name, $data, $comment = '') {
		$parent = all::getVar("parent");
		$blockid = all::getVar("id");


		$template = sql::one_record("SELECT template FROM prname_tree WHERE id=$parent");


		$table = all::c_data_all($parent, $template);
		$table = $table->tablech_sel;

		// Формируем список характеристик
		$list = new Listing('spec', 'blocks', $table);
		$list->getList();
		$list->getItem();
		$item = $list->item;

		// Создание таблицы для характеристик
		$sql = "CREATE table IF NOT EXISTS prname_ch_".$table." (`id` int(12) NOT NULL auto_increment, `itemid` int(12) NOT NULL, PRIMARY KEY  (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;";

		sql::query($sql);

		// Добавление полей к таблице если необходимо
		$fields = array();
		$sql = "DESCRIBE prname_ch_".$table;


		$result = sql::query($sql);
		while ($r = sql::fetch_assoc($result)) {
			$fields[] = $r['Field'];
		}

		unset($fields[0]);
		unset($fields[1]);




		// Доп поля
		if ($item != null) {

			$sqlAdd = "";
			foreach ($item as $key => $val) {

				if (!in_array($val->sname, $fields)) {

					$keyForDelete = array_search($val->sname, $fields);
					unset($fields[$keyForDelete]);
					$sqlAdd .= " ADD `".$val->sname."` varchar(255)";
					$sqlAdd .= ", ";
				}
				if (in_array($val->sname, $fields)) {
					$keyForDelete = array_search($val->sname, $fields);
					unset($fields[$keyForDelete]);
				}
			}

			if ($sqlAdd != "") {
				$sqlAdd = substr($sqlAdd, 0, strlen($sqlAdd)-2);
				$sql = "ALTER TABLE prname_ch_".$table.$sqlAdd;
			}


			sql::query($sql);
		}




		// Удаляем ненужные/устаревшие поля
		if (count($fields) > 0) {
			foreach ($fields as $val) {
				$sqlAdd = " DROP `".$val."`";
				$sql = "ALTER TABLE prname_ch_".$table.$sqlAdd;
				sql::query($sql);
			}
		}

		// Выяснение значений
		if ($blockid) {
			// Проверяем есть ли уже такая запись
			$is = sql::one_record("SELECT id FROM prname_ch_".$table." WHERE itemid=".$blockid);
			if ($is) {
				$data = sql::fetch_assoc(sql::query("SELECT * FROM prname_ch_".$table." WHERE itemid=".$blockid));
				foreach ($item as $key => $val) {
					$type = $val->sname;
					$item[$key]->value = $data[$type];
				}
			}
		}

		$page->item = $item;

		$text = sprintt($page, 'datatypes/templates/features.html');
		return $text;
	}

	public function save($name) {
		$parent = $_POST['parent'];
		if ($_SESSION['newId'] != null) {
			$blockid = $_SESSION['newId'];
		}
		else {
			$blockid = $_POST['blockid'];
		}

		$template = sql::one_record("SELECT template FROM prname_tree WHERE id=$parent");

		$table = all::c_data_all($parent, $template);
		$table = $table->tablech_sel;

		$fields = array();
		$sql = "DESCRIBE prname_ch_".$table;

		$result = sql::query($sql);

		while ($r = sql::fetch_assoc($result)) {
			$fields[] = $r['Field'];
		}






		// Проверяем есть ли уже такая запись
		$is = sql::one_record("SELECT id FROM prname_ch_".$table." WHERE itemid=".$blockid);


		if ($is) {
			$query = "UPDATE prname_ch_".$table." SET itemid=".$blockid;
		}
		else {
			$query = "INSERT INTO prname_ch_".$table." SET itemid=".$blockid;
		}



		foreach ($fields as $val) {
			if (isset($_POST[$val."_feat"])) {
				$postval = mysql_real_escape_string($_POST[$val."_feat"]);
				$query .= " , `".$val."` = '".$postval."' ";
			}
		}



		if ($is) {
			$query .= " WHERE itemid=".$blockid;
		}

		sql::query($query);

		return "all";
	}

	public function get($data, $comment, $ro) {
		return "";
	}
}
?>