<?php

class searchblock {

	function Make($wrapper) {
		if (isset($_GET['string'])) {
			$page->word = mysqli_real_escape_string(Sql::$connection, $_GET['string']);
			$page->word = rtrim($page->word, "/");
		}

		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>