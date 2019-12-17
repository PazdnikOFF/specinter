<?php

class mainaction {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$list = new Listing("news", "blocks", "all", "type='Новости компании' AND ");
			$list->sortfield = "id";
			$list->sortby = "desc";
			$list->limit = 6;
			$list->getList();
			$list->getItem();

			$page->item = $list->item;

			foreach ($page->item as $key => $val) {
				$text = strip_tags($val->text);
				$text = substr($text, 0, 300);
				$text = substr($text, 0, strrpos($text, " "))."...";
				$val->text = $text;
			}

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