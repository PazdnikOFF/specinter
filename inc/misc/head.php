<?php
  class head{

	function Make($wrapper)	{
		global $control;

		$page->title = $control->titleSeo;
		$page->description = $control->descriptionSeo;
		$page->keywords = $control->keywordsSeo;
		if ($control->page != "") {
			$page->canonicalUrl = $_SERVER['DOMAIN_NAME'].$control->module_url;
		}


		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>

