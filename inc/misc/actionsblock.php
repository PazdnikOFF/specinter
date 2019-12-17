<?php

class actionsblock {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$list = new Listing("news", "blocks", "all", "type='Акции' AND ");
			$list->sortfield = "id";
			$list->sortby = "desc";
			$list->limit = 2;
			$list->getList();
			$list->getItem();

			$page->item = $list->item;

			$text = sprintt($page, 'templates/misc/'.$wrapper);

			// Кешируем на 24 часа
			// phpFastCache::set($sign, $text, 86400);
		}
		else {
			$text = $content;
		}
		return $text;
	}
}
?>