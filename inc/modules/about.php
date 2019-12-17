<?php
class about {

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

			$page = all::c_data_all($cid, $control->template);

			$list = new Listing("photo", "blocks", $cid);
			$list->getList();
			$list->getItem();
			$page->photo = $list->item;

			$list = new Listing("document", "blocks", $cid);
			$list->getList();
			$list->getItem();
			$page->document = $list->item;

			$list = new Listing("sertificate", "blocks", $cid);
			$list->getList();
			$list->getItem();
			$page->sertificate = $list->item;

			$page->name = $control->name;
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