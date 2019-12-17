<?php

class addressblock {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$list = new Listing("address", "blocks", 43);
			$list->getList();
			$list->getItem();
			$page->item = $list->item;

			$settings = $control->settings;
			$page->code = $settings->code;
			if (!empty($settings->phone))
				$page->phone = $settings->phone;
			if (!empty($settings->phone2))
				$page->phone2 = $settings->phone2;


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