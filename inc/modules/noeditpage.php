<?php
class noeditpage {

	public function __construct() {
		global $control;
		$this->printList($control->module_parent);
	}

	private function printList($cid) {
		global $control;

		$page->name = $control->name;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_'.$cid.'.html');
	}
}
?>