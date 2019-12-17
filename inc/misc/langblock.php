<?php

class langblock {
	var $all;
	var $html;
	var $wrapper;
	function Make($wrapper) {
		$this->wrapper = $wrapper;

		$page->lang = $_SESSION['lang'];

		$lang = explode(".", $_SERVER['SERVER_NAME']);

		$domain = $lang[count($lang)-2].".".$lang[count($lang)-1];

		$ruDomain = "//ru.".$domain;
		$enDomain = "//en.".$domain;
		$deDomain = "//de.".$domain;
		$czDomain = "//".$domain;

		$page->ruUrl = $ruDomain.$_SERVER['REQUEST_URI'];
		$page->enUrl = $enDomain.$_SERVER['REQUEST_URI'];
		$page->deUrl = $deDomain.$_SERVER['REQUEST_URI'];
		$page->czUrl = $czDomain.$_SERVER['REQUEST_URI'];



		$this->html['text'] = sprintt($page, 'templates/misc/'.$this->wrapper);
		return $this->html['text'];
	}
}
?>