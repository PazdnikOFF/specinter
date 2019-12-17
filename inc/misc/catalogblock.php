<?php

class catalogblock {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = /*null;*/phpFastCache::get($sign);

		if ($content == null) {
			$list = new Listing("catalog", "cats",40);
			$list->limit = 3;
			$list->getList();
			$list->getItem();
			
			$catalogUrl = all::getUrl(40);
				
			$page->item = $list->item;
			$text = sprintt($page, 'templates/misc/'.$wrapper);
			phpFastCache::set($sign, $text, 86400);
		}
		else {
			$text = $content;
		}
		return $text;
	}
}
?>