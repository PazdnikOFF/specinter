<?php
class manage {

	public function __construct() {
		global $control;
		$this->checkForUser();

		$this->menu = $this->getMenu();
		$this->page = $control->page;
		$this->printList($control->module_parent);

	}

	//Вывод дерева сайта
	private function printList($cid) {
		global $config;
		global $control;

		sql::query("TRUNCATE TABLE prname__templates");

		if (user_is("super") == '1') {
			$super = 1;
		}
		else {
			$super = 0;
		}

		$page = tree::admin_tree_all($super);

		$page->status = $_SESSION['admin_status'];
		$page->admin_id = user_is('admin_id');
		if ($super == 1) $page->super = true;

		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;



		$page->name = $control->name;
		$page->parent = 0;

		$page->item = $this->reTree($page->item);


		if ($control->oper == "error") {
			$page->error = $control->bid;
		}



		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

	//Переборка дерева для создания графики
	private function reTree($item, $l=null) {
		$newItem = $item;

		$i = 0;
		if ($l == null) $l = array();
		foreach ($item as $key => $val) {
			//Установка прав
			$val = $this->getRights($val);

			$l[$val->level] = 'closed';

			$i = $key;

			$subcount = $this->getCount($val->item);

			while ($i <= $subcount+$key+1) {
				if (array_key_exists(1+$i, $item)) {
					if ($item[1+$i]->parent == $val->parent) {
						$l[$val->level] = 'opened';
					}
					break;
				}
				$i ++;
			}

			for ($i = 0; $i <= $val->level; $i ++) {
				if ($i < $val->level) {
					if ($l[$i] == 'closed') $item[$key]->td[$i]->class = 'none';
					else $item[$key]->td[$i]->class = 'vertical';
				}
				else {
					if ($l[$i] == 'closed') $item[$key]->td[$i]->class = 'last';
					else $item[$key]->td[$i]->class = 'simple';

					if ($val->hs == 1 && $_COOKIE['hs_'.$val->id] == 1) {
						$item[$key]->hs = 2;
					}
					if ($val->hs == 1 && $_COOKIE['hs_'.$val->id] != 1) {
						$item[$key]->hs = 1;
					}


				}
			}
			if ($val->item != null) $val->item = $this->reTree($val->item, $l);
		}

		return $item;
	}

	private function getCount($item, $count=0) {
		$count += count($item);
		foreach ($item as $key=>$val) {
			$count = $this->getCount($val->item, $count);
		}
		return $count;
	}

	//Выборка прав для строки под пользователя, не являющегося суперадмином
	private function getRights($item) {
		$template = $item->template;
		if (user_is("super") != "1") {
			$rights = sql::fetch_assoc(sql::query("SELECT candel, canedit, canaddcat, canaddbl, canmoveto, cancopyto, canhide FROM prname_ctemplates WHERE `key`='".$template."'"));

			if ($_SESSION['admin_status'] == 3 && !strstr($_SESSION['admin_canedit'], ';'.$item->id.';')) {
				$item->no = true;
			}

			foreach ($rights as $key => $val) {
				if ($item->id == 1 && $key = 'candel') {
					$item->candel = 0;
				}
				elseif ($item->id == 1 && $key = 'canmoveto') {
					$item->canmoveto = 0;
				}
				elseif ($item->id == 1 && $key = 'cancopyto') {
					$item->cancopyto = 0;
				}
				elseif ($item->id == 1 && $key = 'canhide') {
					$item->canhide = 0;
				}
				else $item->$key = $val;
			}
		}
		return $item;
	}

	private function getRight($name, $template) {
		if (user_is("super")) return true;
		$right = sql::one_record("SELECT ".$name." FROM prname_ctemplates WHERE `key`='".$template."'");
		if ($right > 0) return true;
		return false;
	}

	//Функиция вывода меню админки - общая для всех админ. модулей
	private function getMenu() {
		global $control;

		$page = new StdClass();
		if (user_is("super") == "1") $page->super = true;
		$page->status = $_SESSION['admin_status'];
		return sprintt($page, 'templates/misc/admin/menu.html');
	}

	//Функция авторизации и т.д - общая для всех админ. модулей
	private function checkForUser() {
		global $config;
		global $control;
		global $user_is_super;



		$page = new StdClass();
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;


		//Если пришел пост - пробуем авотризоваться
		if (isset($_POST['authlogin']) && $_POST['authlogin'] != "") {
			$authlogin = trim(addslashes($_POST['authlogin']));
			$authpass = trim(addslashes($_POST['authpass']));
			$authpass = md5(base64_encode($config['md5'].$authpass));

			$sql = "SELECT prname_sadmin.*, prname_rt.* FROM prname_sadmin, prname_rt WHERE aid = prname_sadmin.admin_id AND enabled='1' AND admin_name='".$authlogin."' AND admin_password='".$authpass."'";

			$res = sql::fetch_array(sql::query($sql));


			if (!$res) {
				die(printt($page, 'templates/'.$control->template.'/login.html'));
			}
			else {
				$_SESSION['admin_name'] = $authlogin;
				$_SESSION['admin_password'] = stripslashes($_POST['authpass']);
				$_SESSION['admin_status'] = $res['status'];
				$_SESSION['admin_id'] = $res['admin_id'];
				$_SESSION['admin_canedit'] = $res['canedit'];

			}
		}

		//Если пришел гет - логаутимся
		if (isset($_GET['logout'])) {

			unset($_SESSION['admin_name']);
			unset($_SESSION['admin_password']);
			unset($_SESSION['admin_status']);
			unset($_SESSION['admin_id']);
			unset($_SESSION['admin_canedit']);
			header("Location: /manage"); return;
		}

		$user_is_super = (user_is('super') == '1');

		//Если не авторизованы - кидаем на форму авторизации
		if (!isset($_SESSION['admin_name'])) {
			die(printt($page, 'templates/'.$control->template.'/login.html'));
		}
	}

}
?>