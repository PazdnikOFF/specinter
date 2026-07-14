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
 * Тип данных для выбора даты. Визуальный календарик.
 */
class type_date {
	public function input($name, $data, $comment = '', $ro) {
		if (!@checkdate(substr($data, 5, 2), substr($data, 8), substr($data, 0, 4)))
			$data = strftime("%Y-%m-%d");

		if ( strpos($comment, "hidden:") !== false) {
			$style=" display: none;";
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


		$data = substr($data, 8) . "." . substr($data, 5, 2) . "." . substr($data, 0, 4);
		$s =  "

			<input style=\"width: 100px; padding-left: 5px; float: left;$style\" class=\"text form-control\" maxlength=10 id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\" type=\"text\"  value=\"".htmlspecialchars($data)."\" readonly=\"readonly\">";

		if (!$ro) {
			$s .= "
					<button style=\"margin-left: 10px;$style\" class=\"calend\" id=\"trigger".htmlspecialchars($name)."\" ></button>

					<script>
						Calendar.setup({
							inputField: \"".htmlspecialchars($name)."\",
							ifFormat: \"%d.%m.%Y\",
							button: \"trigger".htmlspecialchars($name)."\",

							showsTime:false
						});
					</script>
			";
		}



		return $s;
	}

	public function save($name) {
		global ${"$name"};
		$data = substr(${"$name"}, 6) . "-" . substr(${"$name"}, 3, 2) . "-" . substr(${"$name"}, 0, 2);
		if (!@checkdate(substr($data, 5, 2), substr($data, 8), substr($data, 0, 4))) {
			$data = strftime("%Y-%m-%d");
		}
		return stripslashes($data);
	}

	public function get($data, $comment, $ro) {
		$data = explode("-", $data);
		$data = $data[2].".".$data[1].".".$data[0];
		return $data;
	}

}
?>