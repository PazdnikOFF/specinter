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
 * Тип данных для редактирования html данных.
 * Используется редактор TinyMce и файловый менеджер https://github.com/2b3ez/FileManager4TinyMCE
 */
class type_html {
	public function input($name, $data, $comment = '', $ro) {
		$id = $name;

		if ($ro) {
			$editor = '<div id="'.$id.'" style="width: 100%; height: 467px;" class="elgrow_html_readonly">'.$data.'</div>';
		}
		else {
			$editor = '<div id="'.$id.'" style="width: 100%; height: 467px;" class="elgrow_html">'.$data.'</div>';
		}
		return $editor;
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