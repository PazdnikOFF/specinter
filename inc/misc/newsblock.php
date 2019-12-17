<?php

class newsblock {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$list = new Listing("news", "blocks", 323);
			$list->sortfield = "date";
			$list->sortby = "desc";
			$list->limit = 3;
			$list->getList();
			$list->getItem();

			$page->item = $list->item;

			$text = sprintt($page, 'templates/misc/'.$wrapper);

			// Кешируем на 24 часа
			phpFastCache::set($sign, $text, 86400);
		}
		else {
			$text = $content;
		}
		return $text;
	}
}
?>