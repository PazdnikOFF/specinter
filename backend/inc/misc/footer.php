<?php

class footer {

	function Make($wrapper) {
		global $control;

		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$page = $control->settings;
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