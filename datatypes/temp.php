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
 * Тип данных для однострочных инпутов
 */
class type_tempFFFoooOOXX {
	public function input($name, $data, $comment = '') {
		$return = '<input  class="text" name="'.htmlspecialchars($name).'" id="'.htmlspecialchars($name).'text" type="text" value="'.htmlspecialchars($data).'">';

		return $return;
	}

	public function save($name) {
		global ${"$name"};
		return stripslashes(${"$name"});
	}

}
?>