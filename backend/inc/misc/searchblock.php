<?php

class searchblock {

	function Make($wrapper) {
		if (isset($_GET['string'])) {
			$page->word = mysql_real_escape_string($_GET['string']);
			$page->word = rtrim($page->word, "/");
		}

		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>