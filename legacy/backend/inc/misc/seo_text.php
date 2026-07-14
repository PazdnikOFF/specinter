<?php
  class seo_text{

	function Make($wrapper)	{
		global $control;
		
		if ($control->page != "") {
			$page->canonicalUrl = $_SERVER['DOMAIN_NAME'].$control->module_url;
		}
		$page = all::c_data_all($control->cid,$control->module);
		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}
}
?>

