<?php
  class slider{

	function Make($wrapper)	{
		global $control;

		$list = new Listing("slider", "blocks", "all");
		$list->getList();
		$list->getItem();
		$page->items = $list->item;
		$text = sprintt($page, 'templates/misc/'.$wrapper);
		
		return $text;
	}
}
?>

