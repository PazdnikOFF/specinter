<?php
  class counters{

	function Make($wrapper)	{
		global $control;
		$page = all::c_data_all(12,'settings');
		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>

