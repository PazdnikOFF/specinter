<?php
class service {

	public function __construct() {

		global $control;
		$this->_getList();
	}

	private function _getList(){
        global $control;

        $page = all::c_data_all($control->cid,'service');

        $list = new Listing('service', "cats", $control->cid);

        $list->getList();
        $list->getItem();

        $page->items = $list->item;
        $this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'list.html');
    }
}
?>