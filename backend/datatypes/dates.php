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
 * Тип данных для вывода списка с множественным выбором,
 * в котором содерджатся даты с текущего дня
 */
class type_dates {

	public function input($name, $data, $comment = '', $ro) {
		$splitData = splstr($data, ";");

		if ( strpos($comment, "hidden:") !== false) {
			$style="display: none;";
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

		$s = "<select data-placeholder=\"Даты\" class=\"multi select-styled\" name=\"".htmlspecialchars($name)."[]\" multiple style='$style' $attr>";

		$val = $this->getDates(365);


		$d = splstr($data, ";");
		for ($i = 0; $i < count($val); $i++) {
			$s .= "<option value=\"".$val[$i + 1]."\" ".(in_array($val[$i + 1], $d) ? ' selected ' : '').">".$val[$i + 1]."</option>";
		}

		$s .= "</select>";
		return $s;
	}

	public function save($name) {
		global ${"$name"};
		$s = '';
		if (!is_array(${"$name"})) {
			$s = ${"$name"};
		}
		else {
			foreach (${"$name"} as $el) {
				if ($s != '') {
					$s .= ";";
				}
				$s .= $el;
			}
		}
		return $s;
	}

	private function getDates($dayCount) {
		$today = date("d.m.Y");
		$timestamp = mktime(0, 0, 0);
		$days = array();
		$days[] = $today;
		for ($i=2; $i <= $dayCount; $i ++) {
			$timestamp += 24 * 60 * 60;
			$days[] = date("d.m.Y", $timestamp);
		}
		return $days;
	}

	// Значение, комментарий, рид-онли
	public function get($data, $comment, $ro) {
		return "";
	}

}

?>