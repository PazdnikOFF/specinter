<?php
class adminusers extends manage {

	function __construct() {
		global $control;

		parent::checkForUser();
		$this->menu = parent::getMenu();

		if ($_SESSION['admin_status'] > 2) {
			header("Location: /manage/");
			return;
		}

		elseif ($control->oper == 'add') {
			if (isset($_POST['name'])) {
				return $this->add();
			}
			else return $this->printAdd();
		}

		elseif ($control->oper == 'edit') {
			if (isset($_POST['password'])) {
				return $this->edit();
			}
			else return $this->printEdit();
		}

		elseif ($control->oper == 'hide') {
			return $this->showHide();
		}
		elseif ($control->oper == 'del') {
			return $this->delete();
		}
		else {
			return $this->printList();
		}

	}

	function printList() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		$status = $_SESSION['admin_status'];

		$types = array("", "Суперадмин", "Администратор", "Редактор");

		$users = sql::query("SELECT * FROM prname_sadmin WHERE status>=".$status." ORDER by `admin_id`");

		$i = 0;
		while ($user = sql::fetch_assoc($users)) {
			$page->user[$i]->type = $types[$user['status']];
			foreach ($user as $key => $val) {
				$page->user[$i]->$key = $val;

			}
			$i ++;
		}

		if ($control->oper == "error") {
			$page->error = $control->bid;
		}




		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

	function printAdd() {
		global $control;
		$page = tree::admin_tree_all(0);
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;

		$page->admin_name = user_is("admin_name");
		$page->super = user_is("super");


		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_add.html');
	}

	function add() {
		global $config;

		$name = preg_replace("/[\W]/i", "", $_POST['name']);
		$status = $_POST['status'] + 1 - 1;

		// Проверка существования логина
		$is = sql::one_record("SELECT admin_name FROM prname_sadmin WHERE admin_name='$name'");
		if ($is) {
			header("Location: /manage/users/_aerror_b3/");
			return;
		}

		// Проверка прав на создание пользователя
		if (!isset($_SESSION['admin_id'])) {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		$curAdmin = sql::fetch_assoc(sql::query("SELECT * FROM prname_sadmin WHERE admin_id=".$_SESSION['admin_id']));
		if (!$curAdmin) {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		if ($status > 1 && $status < $curAdmin['status']) {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		if ($status == "1" && $curAdmin['admin_name'] != "superadmin") {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		$password = preg_replace("/[\W]/i", "", $_POST['password']);
		$passwordrep = preg_replace("/[\W]/i", "", $_POST['passwordrep']);

		if ($name == "" || $password == "" || $passwordrep == "" || $passwordrep != $password) {
			header("Location: /manage/users/");
			return;
		}

		$password = md5(base64_encode($config['md5'].$password));

		$canedit = "";

		if (isset($_POST['cat'])) {
			$canedit = ";";
			foreach ($_POST['cat'] as $val) {
				$canedit .= $val.";";
			}
		}


		if ($status == "2") {
			$canedit = "";
		}
		if ($status == "1") {
			$canedit = "";
		}

		$sql = "INSERT INTO prname_sadmin(`admin_name`, `admin_password`, `status`, `enabled`, `canedit`) VALUES ('".$name."', '".$password."', ".$status.", 1, '".$canedit."')";

		sql::query($sql);

		$aid = sql::one_record("SELECT MAX(admin_id) FROM prname_sadmin");

		$super = $status == "1" ? 1 : 0;
		sql::query("INSERT INTO prname_rt(super, aid) VALUES($super, $aid)");

		header("Location: /manage/users/");
		return;
	}

	function printEdit() {
		global $control;
		$parent = all::getVar("parent");

		// Определяем разрешение на редактирование
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_sadmin WHERE admin_id=".$parent));
		$pStatus = $info['status'];
		$pName = $info['admin_name'];
		if ($_SESSION['admin_status'] > $pStatus && $_SESSION['admin_name'] != "superadmin") {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		// Все инфо о польователе
		$info = sql::fetch_object(sql::query("SELECT * FROM prname_sadmin WHERE admin_id=".$parent));


		$page = tree::admin_tree_all(0);
		if ($info->status > 2) {
			$page = $this->canEdit($page, $info->canedit);
		}

		$page->info[] = $info;


		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		$page->parent = $parent;

		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_edit.html');
	}

	function edit() {
		global $config;

		$parent = $_POST['parent'];
		$timezone = trim($_POST['timezone']);
		
		if ($_SESSION['admin_id'] == $parent) {
			$_SESSION['admin_timezone'] = $timezone;
		}



		// Определяем разрешение на редактирование
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_sadmin WHERE admin_id=".$parent));
		$pStatus = $info['status'];
		$pName = $info['admin_name'];
		if ($_SESSION['admin_status'] > $pStatus && $_SESSION['admin_name'] != "superadmin") {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		if ($parent == 1 && $_SESSION['admin_name'] != "superadmin") {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}



		$password = preg_replace("/[\S]/i", "", $_POST['password']);
		$passwordrep = preg_replace("/[\S]/i", "", $_POST['passwordrep']);

		if ($passwordrep != $password) {
			header("Location: /manage/users/_aerror_b2/");
			return;
		}

		$canedit = "";

		if (isset($_POST['cat'])) {
			$canedit = ";";
			foreach ($_POST['cat'] as $val) {
				$canedit .= $val.";";
			}
		}

		$status = $_POST['status'];

		if ($status == "2") {
			$canedit = "";
		}

		// Если пароль не пришел - значит остается старым
		if ($password == "") {
			$sql = "UPDATE prname_sadmin SET `status`=$status, `enabled`=1, `canedit`='$canedit', timezone='$timezone' WHERE `admin_id`=$parent";
		}
		else {
			$password = md5(base64_encode($config['md5'].$password));

			$sql = "UPDATE prname_sadmin SET `admin_password`='$password', `status`=$status, `enabled`=1, `canedit`='$canedit', timezone='$timezone' WHERE `admin_id`=$parent";
			
		}

		sql::query($sql);

		header("Location: /manage/users/");
		return;
	}

	function showHide() {
		global $control;
		$parent = all::getVar("parent");

		// Определяем разрешение на скрытие/показ
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_sadmin WHERE admin_id=".$parent));
		$pStatus = $info['status'];
		$pName = $info['admin_name'];
		if (($_SESSION['admin_status'] > $pStatus && $_SESSION['admin_name'] != "superadmin") || $pName == $_SESSION['admin_name']) {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		if ($parent == 1 && $_SESSION['admin_name'] != "superadmin") {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		sql::query("UPDATE prname_sadmin SET enabled=1-enabled WHERE admin_id=".$parent);

		header("Location: /manage/users/");
		return;
	}

	function delete() {
		global $control;
		$parent = all::getVar("id");

		// Определяем разрешение на удаление
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_sadmin WHERE admin_id=".$parent));
		$pStatus = $info['status'];
		$pName = $info['admin_name'];
		if (($_SESSION['admin_status'] > $pStatus && $_SESSION['admin_name'] != "superadmin") || $pName == $_SESSION['admin_name']) {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		if ($parent == 1 && $_SESSION['admin_name'] != "superadmin") {
			header("Location: /manage/users/_aerror_b1/");
			return;
		}

		sql::query("DELETE FROM prname_sadmin WHERE admin_id=".$parent);
		sql::query("DELETE FROM prname_rt WHERE aid=".$parent);

		header("Location: /manage/users/");
		return;
	}

	function canEdit($page, $canedit) {
		foreach ($page->item as $key => $val) {
			if (strstr($canedit, ';'.$val->id.';')) {
				$page->item[$key]->canedit = true;
			}
			else {
				$page->item[$key]->canedit = false;
			}
			if (isset($val->item)) {
				$val = $this->canEdit($val, $canedit);
			}
		}
		return $page;
	}
}
?>