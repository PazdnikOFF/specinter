<?php
class map {

	public function __construct() {
		global $control;

		$page = tree::getSiteMap();

		$page->name = $control->name;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

}
?>