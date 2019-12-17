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
 * Тип данных для встраивания блоков внутрь других блоков.
 * Например блок "заказ" должен содежать блоки "товар"
 * Чтобы указать, какой блок должен быть вложен, нужно прописать его название в поле "Доп.информация"
 */
class type_items {

	public function input($name, $data, $comment = '') {
		$parent = all::getVar("id");
		$cparent = all::getVar("parent");

		$s = '<iframe name="itemframe" frameborder="no" id="itemframe'.$comment.'" width="100%" src="/manage/blockedit/_aitemlist_parent'.$cparent.'_id'.$parent.'_template'.$comment.'/" onload="iframeLoaded(\'itemframe'.$comment.'\')"></iframe>';

		return $s;
	}

	public function save($name) {
		global ${"$name"};
		return ${"$name"};
	}

	public function get($data, $comment, $ro) {
		return "";
	}

}

?>