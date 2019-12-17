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
 * Тип данных для выбора времени. Визуальный календарик.
 */
class type_time {
	public function input($name, $data, $comment = '') {
		if ($data == "") $data = date('H:i');

		$s =  "<input  class=\"text\" maxlength=10 id=\"t".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\" type=\"text\" value=\"".htmlspecialchars($data)."\" onkeypress=\"return false\">
		<button class=\"calend\" id=\"trigger".htmlspecialchars($name)."\"></button>

		<script type=\"text/javascript\">
			Calendar.setup({
				inputField: \"t".htmlspecialchars($name)."\",
				ifFormat: \"%H:%M\",

				button: \"trigger".htmlspecialchars($name)."\"
			});
		</script>";

		return $s;
	}

	public function save($name) {
		global ${"$name"};
		return stripslashes(${"$name"});
	}

	public function get($data, $comment, $ro) {
		return $data;
	}

}
?>