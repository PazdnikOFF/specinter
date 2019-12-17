<?php
class temp2 {

	public function __construct() {
		global $control;
		if ($control->oper == 'view') {
			$this->printOne($control->bid);
		}
		else {
			$this->printList($control->module_parent);
		}
	}

	private function printOne($bid) {
		global $control;

		$sign = md5($control->template.$control->module_url.$control->urlparams);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$page = all::b_data_all($bid, $control->module_wrap);

			$page->back = all::getUrl($control->module_parent).all::addUrl($this->page);
			$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_one.html');

			// Кешируем на 24 часа
			// phpFastCache::set($sign, $this->html['text'], 86400);
		}
		else {
			$this->html['text'] = $content;
		}
	}

	private function printList($cid) {
		global $control;

		$sign = md5($control->template.$control->module_url.$control->urlparams);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$list = new Listing($control->module_wrap, "blocks", $cid);
			$list->page = $control->page;
			$list->tmp_url = all::getUrl($control->module_parent);
			$list->getList();
			$list->getItem();
			$list->getPage();

			$page->item = $list->item;
			$page->page = $list->navigation;
			$page->url_last = $list->url_last;
			$page->url_p = $list->url_p;
			$page->url_n = $list->url_n;
			$page->url_next = $list->url_next;


			$page->name = $control->name;
			$page->pages_down = sprintt($page, 'templates/temps/pages_down.html');
			$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');

			// Кешируем на 24 часа
			// phpFastCache::set($sign, $this->html['text'], 86400);
		}
		else {
			$this->html['text'] = $content;
		}
	}
}
?>