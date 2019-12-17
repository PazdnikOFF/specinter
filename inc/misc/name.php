<?php
  class name{

	function Make($wrapper)	{
		global $control;

		$page->name  = $control->name;
		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>

