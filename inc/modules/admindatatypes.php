<?php
class admindatatypes extends manage {

	function __construct() {
		global $control;
		parent::checkForUser();
		$this->menu = parent::getMenu();

		if (user_is("super") != 1) {
			header("Location: /manage/");
			return;
		}

		elseif ($control->oper == 'add') {
			if (isset($_POST['name'])) {
				return $this->add();
			}
			else return $this->printAdd();
		}
		else {
			return $this->printList();
		}
	}

	function printList() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		$tpls = sql::query("SELECT * FROM prname_datatypes ORDER by `id`");

		$i = 0;
		while ($tpl = sql::fetch_assoc($tpls)) {
			foreach ($tpl as $key => $val) {
				$page->tpl[$i]->$key = $val;
			}
			$i ++;
		}

		$page->menu = $this->menu;
        $this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

	function printAdd() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;

		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_add.html');
	}

	function add() {

		//Имя, ключ, алиас
		$dname = $_POST['name'];
		$dkey = $_POST['key'];

		if (trim($dname) != "" && trim($dkey) != "") {
			//Добавление в таблицу с шаблонами
			$sql = "INSERT INTO prname_datatypes (`name`, `key`) VALUES ('".$dname."', '".$dkey."')";
			sql::query($sql);


			$dirName = 'datatypes/';
			$content = str_replace('tempFFFoooOOXX', $dkey, file_get_contents("datatypes/temp.php"));
			file::createFile($dkey.'.php', $dirName, $content);


			header("Location: /manage/admindatatypes/");
			return;
		}
		else {
			header("Location: /manage/admindatatypes/_aadd/");
			return;
		}
	}
}
?>