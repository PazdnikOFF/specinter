<?php

class bgclass {

	function Make($wrapper) {
		global $control;

		$parent = $control->parents[1];
		$page->class="bg-home-wrapp";

		if ($parent == 41) {
			$page->class = "bg-news-wrapp";
			if ($control->oper == "view") {
				$page->class = "bg-news-wrapp-2";
			}
		}

		if ($parent == 42) {
			$page->class = "bg-pay-and-del-wrapp";
		}

		if ($parent == 43) {
			$page->class = "bg-contact-wrapp";
		}

		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>