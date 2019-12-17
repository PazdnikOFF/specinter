<?php

class menu_footer {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper.$control->module_url.$control->urlparams);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$page = tree::tree_all();

			$page->active = $control->cid;

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