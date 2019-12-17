<?php

class pagepatch {

	function Make($wrapper) {
		global $control;
		global $ar_mon;

		$page->items = Tree::tree_url();

		$page->name = $control->name;
		$page->printurl = all::getUrl($control->cid)."/printpage";

		if ($control->oper == "view") {
			$page->last = false;
		}
		else {
			$page->last = true;
		}

		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>