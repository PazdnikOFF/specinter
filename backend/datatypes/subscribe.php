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
 * Тип данных для отправки рассылки из списка блоков
 */
class type_subscribe {
	public function input($name, $data, $comment = '') {
		return;
	}

	public function save($name) {
		return "";
	}

	// Значение, комментарий, рид-онли
	public function get($data, $comment, $ro, $id, $fieldName) {
		if ($data) {
			$data = explode(" ", $data);
			$data[1] = explode(":", $data[1]);
			$data[1] = $data[1][0].":".$data[1][1];
			$data = all::getDate($data[0], 1). " в ".$data[1];

			$last = "Последняя отправка: $data ";
		}

		$return = "$last<button type='button' class='btn btn-primary btn-sm subscribe-live' checked data-id='$id' data-field='$fieldName'>Разослать</button>";

		return $return;
	}

}
?>