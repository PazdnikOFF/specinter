<?php
class adminmoduleorders extends manage {

	public function __construct() {
		global $control;

		parent::checkForUser();

		$this->menu = parent::getMenu();

		$this->page = $control->page;
		$this->getSettings();

		if (isset($_POST) && count($_POST) > 0) {
			if ($_POST['mode'] == 'gs' || $_POST['mode'] == 'ls') return $this->changeStatus($_POST['mode']);
		}

		if ($control->oper == 'view') {
			$this->printOne($control->bid);
		}
		else {
			$this->printList($control->module_parent);
		}
	}

	private function printOne($bid) {
		global $control;
		$page = all::b_data_all($bid, $control->module_wrap);

		$page->back = all::getUrl($control->module_parent).all::addUrl($this->page);
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_one.html');
	}

	private function printList($cid) {
		global $control;

		$list = new Listing('order', 'blocks', 'all');
		$list->sortfield = "id";
		$list->sortby = "desc";
		$list->limit = 10;
		$list->page = $control->page;
		$list->on = "false";
		$list->tmp_url = All::getUrl($control->module_parent);
		$list->getList();
		$list->getItem();
		$list->getPage();



		$page->item = $list->item;
		$page->page = $list->navigation;
		$page->url_last = $list->url_last;
		$page->url_p = $list->url_p;
		$page->url_n = $list->url_n;
		$page->url_next = $list->url_next;

		$page->theme = parent::$mainTheme;
		$page->menu = $this->menu;

		$page->name = $control->name;
        $page->pages_down = sprintt($page, 'templates/temps/pages_downadmin.html');

		//Собираем поля блока заказа (на разных сайтах разные поля у блока заказа - так вот это будет не важно)
		$dataRel = sql::query("SELECT p2.* from prname_btemplates p1, prname_bdatarel p2 WHERE p1.key='order' AND p2.templid=p1.id ORDER by p2.sort");

		while ($dr = sql::fetch_assoc($dataRel)) {
			$fields[$j]->name = $dr['name'];
			$fields[$j]->key = $dr['key'];
			$j ++;
		}

		//Заполняем поля
		foreach ($page->item as $key => $val) {
			foreach ($fields as $key2 => $val2) {
				if ($val2->key != "order" &&
					$val2->key != "date" &&
					$val2->key != "status" &&
					$val2->key != "date_1" &&
					$val2->key != "date_2" &&
					$val2->key != "tr" &&
					$val2->key != "url" &&
					$val2->key != "id" &&
					$val2->key != "utemplate" &&
					$val2->key != "parent" &&
					$val2->key != "blockparent" &&
					$val2->key != "sort" &&
					$val2->key != "visible") {

					$page->item[$key]->fields[$key2]->name = $val2->name;
					$page->item[$key]->fields[$key2]->value = $val->{$val2->key};
				}
			}
		}



        $this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

	private function getSettings() {
		global $control;
		$this->settings = all::c_data_all($control->cid, $control->template);
	}

	private function changeStatus($mode) {
		$id = $_POST['id'];
		$status = $_POST['status'];

		//Текущий пользователь
		$user = user_is("admin_name");
		$userStatus = user_is("status");

		//Пользователь заказа
		$ouser = sql::one_record("SELECT ouser FROM prname_b_order WHERE id=$id");

		if ($ouser != "") {

			$ouserInfo = sql::fetch_assoc(sql::query("SELECT * FROM prname_sadmin WHERE admin_name='".$ouser."'"));

			if ($ouserInfo['status'] < $userStatus || ($ouserInfo['admin_name'] != $user && $ouserInfo['status'] == $userStatus)) {
				die("no");
			}

		}

		if ($status == 0 ) {
			$status = "Принят";
			$class = "add";
		}
		if ($status == 1 && $mode == 'gs') {
			$status = "Выполнен";
			$class = "success";
		}
		if ($status == 1 && $mode == 'ls') {
			$status = "Новый";
			$class = "new";
		}
		if ($status == 2 && $mode == 'ls') {
			$status = "Принят";
			$class = "add";
		}

		if ($status == "Новый") $user = "";


		sql::query("UPDATE prname_b_order SET `status`='".$status."', `ouser`='".$user."' WHERE id=$id");



		$html = "<div class='$class'>";
		if ($status == "Принят") {
			$html .= "<a style='padding-right: 10px;' href='#' data-status='1' data-mode='ls' data-id='$id' class='changeStatus' rel='tooltip' title='Пометить как новый'><i class='icon-arrow-left'></i></a>";
		}

		if ($status == "Выполнен") {
			$html .= "<a style='padding-right: 10px;' href='#' data-status='2' data-mode='ls' data-id='$id' class='changeStatus' rel='tooltip' title='Снять пометку о выполнении'><i class='icon-arrow-left'></i></a>";
		}

		$html .= "<b id='status_$id'>$status</b>";

		if ($status == "Новый") {
			$html .= "<a style='padding-left: 10px;' href='#' data-status='0' data-mode='gs' data-id='$id' class='changeStatus' rel='tooltip' title='Принять заказ'><i class='icon-arrow-right'></i></a>";
		}

		if ($status == "Принят") {
			$html .= "<a style='padding-left: 10px;' href='#' data-status='1' data-mode='gs' data-id='$id' class='changeStatus' rel='tooltip' title='Пометить как выполненный'><i class='icon-arrow-right'></i></a>";
		}

		$html .= "</div>";



		die($html."##".$user);
	}
}
?>