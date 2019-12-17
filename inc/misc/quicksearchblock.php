<?php

class quicksearchblock {

	function Make($wrapper) {

		$list = new Listing("tgroup", "blocks", "all", "parentcode=0 AND ");
		$list->getList();
		$list->getItem();
		$page->item = $list->item;

		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>