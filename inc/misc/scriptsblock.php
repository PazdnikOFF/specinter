<?php

class scriptsblock {

	function Make($wrapper) {
		global $control;

		// Контакты
		if ($control->cid == 43) {
			$page->contacts = true;
			$page->coords = $control->coords;
		}


		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>