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
 * Тип данных выводит дерево сайта от необходимого элемента в виде чекбоксов
 * Id элемента указывается в доп. информации
 * Может быть использован для редактирования отображения баннеров на разных страницах сайта.
 */
class type_mapfrom {
	public function input($name, $data, $comment = '') {
		$page = tree::admin_tree_all(0);
		$canedit = $data;
		$page = $this->canEdit($page, $canedit);

		$text = sprintt($page, 'datatypes/templates/mapfrom.html');
		return $text;
	}

	public function save($name) {
		global ${"$name"};
		$canedit = ';';
		if($_POST[cat]) {
			for ($r=0; $r < count($_POST[cat]); $r++) {
				$canedit .= $_POST[cat][$r].';';
			}
			return $canedit;
		}
	}

	private function canEdit($page, $canedit) {
		foreach ($page->item as $key => $val) {
			if ($val->virtual == 1) {
				unset($page->item[$key]);
				continue;
			}
			if (strstr($canedit, ';'.$val->id.';')) {
				$page->item[$key]->canedit = true;
			}
			else {
				$page->item[$key]->canedit = false;
			}
			if (isset($val->item)) {
				$val = canEdit($val, $canedit);
			}
		}
		return $page;
	}

	public function get($data, $comment, $ro) {
		return "";
	}
}
?>