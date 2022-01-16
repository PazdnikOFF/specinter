<?php
class admincattemplate extends manage {

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
		elseif ($control->oper == 'edit') {
			if (isset($_POST['name'])) {
				return $this->edit();
			}
			else return $this->printEdit();
		}

		elseif ($control->oper == 'del') {
			return $this->delete();
		}
		elseif ($control->oper == 'copy') {
			return $this->copy();
		}
		else {
			return $this->printList();
		}
	}

	function printList() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		$tpls = sql::query("SELECT * FROM prname_ctemplates ORDER by `name`");

		if ($control->oper == "error") {
			$page->error = $control->bid;
		}

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
		$page->add = true;

		$btypes = sql::query("SELECT * FROM prname_btemplates");

		$i = 0;
		while ($btype = sql::fetch_assoc($btypes)) {
			foreach ($btype as $key => $val) {
				$page->btypes[$i]->$key = $val;
			}
			$i ++;
		}

		$ctypes = sql::query("SELECT * FROM prname_ctemplates");

		$i = 0;
		while ($ctype = sql::fetch_assoc($ctypes)) {
			foreach ($ctype as $key => $val) {
				$page->ctypes[$i]->$key = $val;
			}
			$i ++;
		}

		$dtypes = sql::query("SELECT * FROM prname_datatypes");

		$i = 0;
		while ($dtype = sql::fetch_assoc($dtypes)) {
			foreach ($dtype as $key => $val) {
				$page->dtypes[$i]->$key = $val;
			}
			$i ++;
		}

		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_add.html');
	}

	function add() {

		//Имя, ключ, алиас
		$catname = $_POST['name'];
		$catkey = $_POST['key'];
		$catalias = $_POST['alias'];

		//Права
		if (isset($_POST['candel'])) $candel = 1;
		else $candel = 0;

		if (isset($_POST['canedit'])) $canedit = 1;
		else $canedit = 0;

		if (isset($_POST['canadd'])) $canadd = 1;
		else $canadd = 0;

		if (isset($_POST['canaddbl'])) $canaddbl = 1;
		else $canaddbl = 0;

		if (isset($_POST['canmoveto'])) $canmoveto = 1;
		else $canmoveto = 0;

		if (isset($_POST['cancopyto'])) $cancopyto = 1;
		else $cancopyto = 0;

		if (isset($_POST['canhide'])) $canhide = 1;
		else $canhide = 0;

		if (isset($_POST['hidestr'])) $hidestr = 1;
		else $hidestr = 0;

		if (isset($_POST['virtual'])) $virtual = 1;
		else $virtual = 0;

		if (isset($_POST['cache'])) $cache = 1;
		else $cache = 0;

		//Папки/блоки
		$blocktypes = "";
		if (isset($_POST['btypes'])) {
			foreach ($_POST['btypes'] as $val) {
				$blocktypes .= $val." ";
			}
		}

		$cattypes = "";
		if (isset($_POST['ctypes'])) {
			foreach ($_POST['ctypes'] as $val) {
				$cattypes .= $val." ";
			}
		}

		//Добавление в таблицу с шаблонами
		$sql = "INSERT INTO prname_ctemplates
			(`name`,
			`key`,
			`alias`,
			`candel`,
			`canedit`,
			`canaddcat`,
			`canaddbl`,
			`canmoveto`,
			`cancopyto`,
			`canhide`,
			`hidestructure`,
			`blocktypes`,
			`cattypes`,
			`visible`,
			`virtual`,
			`cache`)
			VALUES (
				'".$catname."',
				'".$catkey."',
				'".$catalias."',
				".$candel.",
				".$canedit.",
				".$canadd.",
				".$canaddbl.",
				".$canmoveto.",
				".$cancopyto.",
				".$canhide.",
				".$hidestr.",
				'".$blocktypes."',
				'".$cattypes."',
				1,
				".$virtual.",
				".$cache."
				)";


		sql::query($sql);

		$templid = sql::one_record("SELECT MAX(id) FROM prname_ctemplates");

		//Создание таблицы для шаблона
		$sql = "CREATE table IF NOT EXISTS prname_c_".$catkey." (`id` int(12) NOT NULL auto_increment, `parent` int(12),`utitle` varchar(255),`udescription` text,`ukeywords` text, PRIMARY KEY  (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;";

		sql::query($sql);

		//Доп поля
		if (count($_POST['addname']) > 1) {

			$sqlAdd = "";

			foreach ($_POST['addname'] as $key => $val) {
				if ($key == 0) continue;
				switch ($_POST['addtype'][$key]) {
					case 'html': $type = 'text'; break;
					case 'checkbox': $type = 'tinyint(1)'; break;
					case 'radiio': $type = 'tinyint(1)';break;
					case 'date': $type = 'date'; break;
					case 'file': $type = 'varchar(255)'; break;
					case 'text': $type = 'varchar(255)'; break;
					case 'mcheckbox': $type = 'varchar(100)'; break;
					default: $type = 'text'; break;
				}

				$sql = "INSERT INTO prname_cdatarel (`name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `sort`, `templid`)
				VALUES (
					'".$_POST['addname'][$key]."',
					'".$_POST['addtype'][$key]."',
					'".$_POST['addkey'][$key]."',
					'".$_POST['addattr'][$key]."',
					'".$_POST['addinfo'][$key]."',
					".$_POST['addroh'][$key].",
					".$_POST['addsort'][$key].",
					".$templid."
					)";

				sql::query($sql);


				$sqlAdd .= " ADD `".$_POST['addkey'][$key]."` ".$type;
				if ($_POST['adddefault'][$key] != "") $sqlAdd .= " DEFAULT ".$_POST['adddefault'][$key]." NOT NULL, ";
				else $sqlAdd .= ", ";
			}

			$sqlAdd = substr($sqlAdd, 0, strlen($sqlAdd)-2);
			$sql = "ALTER TABLE prname_c_".$catkey.$sqlAdd;

			sql::query($sql);
		}

		header("Location: /manage/cattemplate/");
	}

	function printEdit() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;

		$parent = $page->parent = all::getVar("parent");

		//Вся инфа по шаблону
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_ctemplates WHERE id=".$parent));
		foreach ($info as $key => $val) {
			$page->info[0]->$key = $val;
		}


		//Все блоки
		$btypes = sql::query("SELECT * FROM prname_btemplates");

		//Выбранные блоки
		$selBlocks = $info['blocktypes'];
		$selBlocks = explode(" ", $selBlocks);

		$i = 0;
		while ($btype = sql::fetch_assoc($btypes)) {
			foreach ($btype as $key => $val) {
				$page->btypes[$i]->$key = $val;
				if ($key == 'key') {
					if (in_array($val, $selBlocks)) {
						$page->btypes[$i]->sel = true;
					}
				}
			}
			$i ++;
		}

		//Все папки
		$ctypes = sql::query("SELECT * FROM prname_ctemplates");

		//Выбранные папки
		$selCats = $info['cattypes'];
		$selCats = explode(" ", $selCats);

		$i = 0;
		while ($ctype = sql::fetch_assoc($ctypes)) {
			foreach ($ctype as $key => $val) {
				$page->ctypes[$i]->$key = $val;
				if ($key == 'key') {
					if (in_array($val, $selCats)) {
						$page->ctypes[$i]->sel = true;
					}
				}
			}
			$i ++;
		}

		$dtypesS = sql::query("SELECT * FROM prname_datatypes");

		$i = 0;
		while ($dtype = sql::fetch_assoc($dtypesS)) {
			foreach ($dtype as $key => $val) {
				$page->dtypes[$i]->$key = $val;
			}
			$i ++;
		}

		$addFields = sql::query("SELECT * FROM prname_cdatarel WHERE templid=".$parent." ORDER by sort");

		$i = 0;
		while ($addField = sql::fetch_assoc($addFields)) {
			foreach ($addField as $key => $val) {
				$page->addFields[$i]->$key = $val;
			}
			$page->addFields[$i]->dtypes = $page->dtypes;
			$i ++;
		}

		$page->addCount = $i;


		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_edit.html');
	}

	function edit() {
		//Имя, ключ, алиас
		$catname = $_POST['name'];
		$catkey = $_POST['key'];
		$catalias = $_POST['alias'];
		$oldkey = $_POST['oldkey'];
		$parent = $_POST['hide-parent'];

		//Права
		if (isset($_POST['candel'])) $candel = 1;
		else $candel = 0;

		if (isset($_POST['canedit'])) $canedit = 1;
		else $canedit = 0;

		if (isset($_POST['canadd'])) $canadd = 1;
		else $canadd = 0;

		if (isset($_POST['canaddbl'])) $canaddbl = 1;
		else $canaddbl = 0;

		if (isset($_POST['canmoveto'])) $canmoveto = 1;
		else $canmoveto = 0;

		if (isset($_POST['cancopyto'])) $cancopyto = 1;
		else $cancopyto = 0;

		if (isset($_POST['canhide'])) $canhide = 1;
		else $canhide = 0;

		if (isset($_POST['hidestr'])) $hidestr = 1;
		else $hidestr = 0;

		if (isset($_POST['virtual'])) $virtual = 1;
		else $virtual = 0;

		if (isset($_POST['cache'])) $cache = 1;
		else $cache = 0;

		//Папки/блоки
		$blocktypes = "";
		if (isset($_POST['btypes'])) {
			foreach ($_POST['btypes'] as $val) {
				$blocktypes .= $val." ";
			}
		}

		$cattypes = "";
		if (isset($_POST['ctypes'])) {
			foreach ($_POST['ctypes'] as $val) {
				$cattypes .= $val." ";
			}
		}

		//А вдруг название шаблона изменилось - меняем название таблицы, а также поле "template" в таблице categories
		if ($_POST['key'] != $_POST['oldkey']) {
			sql::query("ALTER TABLE prname_c_".$oldkey." RENAME prname_c_".$catkey);
			sql::query("UPDATE prname_categories SET `template`=REPLACE(`template`,'".$_POST['oldkey']."','".$_POST['key']."')");
		}


		$sql = "UPDATE prname_ctemplates SET
			name='".$catname."',
			`key`='".$catkey."',
			alias='".$catalias."',
			candel=".$candel.",
			canedit=".$canedit.",
			canaddcat=".$canadd.",
			canaddbl=".$canaddbl.",
			canmoveto=".$canmoveto.",
			cancopyto=".$cancopyto.",
			canhide=".$canhide.",
			hidestructure=".$hidestr.",
			blocktypes='".$blocktypes."',
			cattypes='".$cattypes."',
			`virtual`=".$virtual.",
			`cache`=".$cache."
			 WHERE id=".$parent;


		sql::query($sql);


		//Проверяем старые поля шаблона
		$oldFields = sql::query("SELECT * FROM prname_cdatarel WHERE templid=".$parent);

		while ($oldField = sql::fetch_assoc($oldFields)) {
			$id = $oldField['id'];

			//Если пришло постом, значит останется, возможно изменится
			if (isset($_POST['curname'][$id])) {
				$sql = "UPDATE prname_cdatarel SET
					name='".$_POST['curname'][$id]."',
					`key`='".$_POST['curkey'][$id]."',
					datatkey='".$_POST['curtype'][$id]."',
					comment='".$_POST['curinfo'][$id]."',
					attr='".$_POST['curattr'][$id]."',
					`default`='".$_POST['curdefault'][$id]."',
					sort='".$_POST['cursort'][$id]."',
					readonly='".$_POST['curroh'][$id]."'
					 WHERE id=".$id;

				$sql2 = "ALTER TABLE prname_c_".$catkey." CHANGE `".$oldField['key']."` ".$_POST['curkey'][$id];

				switch ($_POST['curtype'][$id]) {
					case 'html': $type = 'text'; break;
					case 'checkbox': $type = 'tinyint(1)'; break;
					case 'radiio': $type = 'tinyint(1)';break;
					case 'date': $type = 'date'; break;
					case 'file': $type = 'varchar(255)'; break;
					case 'text': $type = 'varchar(255)'; break;
					case 'mcheckbox': $type = 'varchar(100)'; break;
					default: $type = 'text'; break;
				}

				$sql2 .= " ".$type;

				if ($_POST['curdefault'][$id] != '') $sql2 .= " DEFAULT ".$_POST['curdefault'][$id]." NOT NULL";



			}
			//Не пришло постом - значит удаляем к черту
			else {
				$sql = "DELETE FROM prname_cdatarel WHERE id=".$id;
				$sql2 = "ALTER TABLE prname_c_".$catkey." DROP `".$oldField['key']."`";
			}

			sql::query($sql);
			sql::query($sql2);
		}


		//Проверяем новые поля шаблона
		if (count($_POST['addname']) > 1) {

			$sqlAdd = "";

			foreach ($_POST['addname'] as $key => $val) {
				if ($key == 0) continue;
				switch ($_POST['addtype'][$key]) {
					case 'html': $type = 'text'; break;
					case 'checkbox': $type = 'tinyint(1)'; break;
					case 'radiio': $type = 'tinyint(1)';break;
					case 'date': $type = 'date'; break;
					case 'file': $type = 'varchar(255)'; break;
					case 'text': $type = 'varchar(255)'; break;
					case 'mcheckbox': $type = 'varchar(100)'; break;
					default: $type = 'text'; break;
				}

				$sql = "INSERT INTO prname_cdatarel (`name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `sort`, `templid`)
				VALUES (
					'".$_POST['addname'][$key]."',
					'".$_POST['addtype'][$key]."',
					'".$_POST['addkey'][$key]."',
					'".$_POST['addattr'][$key]."',
					'".$_POST['addinfo'][$key]."',
					".$_POST['addroh'][$key].",
					".$_POST['addsort'][$key].",
					".$parent."
					)";


				sql::query($sql);


				$sqlAdd .= " ADD `".$_POST['addkey'][$key]."` ".$type;
				if ($_POST['adddefault'][$key] != "") $sqlAdd .= " DEFAULT ".$_POST['adddefault'][$key]." NOT NULL, ";
				else $sqlAdd .= ", ";
			}

			$sqlAdd = substr($sqlAdd, 0, strlen($sqlAdd)-2);
			$sql = "ALTER TABLE prname_c_".$catkey.$sqlAdd;

			sql::query($sql);
		}

		header("Location: /manage/cattemplate/");
	}

	function delete() {
		$id = all::getVar("id");



		$template = sql::one_record("SELECT `key` FROM prname_ctemplates WHERE id=".$id);
		$result = sql::query("SELECT * FROM prname_c_".$template);
		if (sql::num_rows($result) > 0) {
			header("Location: /manage/cattemplate/_aerror_b1/");
			return;
		}
		else {
			//Удалаяем
			sql::query("DELETE FROM prname_ctemplates WHERE id=".$id);
			sql::query("DROP TABLE prname_c_".$template);
			sql::query("DELETE FROM prname_cdatarel WHERE templid=".$id);
			header("Location: /manage/cattemplate/");
			return;
		}
	}

	function copy() {

		$parent = all::getVar('parent');

		//Вся инфа по шаблону
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_ctemplates WHERE id=".$parent));

		//Добавление в таблицу с шаблонами
		$sql = "INSERT INTO prname_ctemplates
				(`name`,
				`key`,
				`alias`,
				`candel`,
				`canedit`,
				`canaddcat`,
				`canaddbl`,
				`canmoveto`,
				`cancopyto`,
				`canhide`,
				`hidestructure`,
				`blocktypes`,
				`cattypes`,
				`visible`,
				`virtual`,
				`cache`)
			SELECT
				'".$info['name']."_Копия"."',
				'".$info['key']."copy"."',
				`alias`,
				`candel`,
				`canedit`,
				`canaddcat`,
				`canaddbl`,
				`canmoveto`,
				`cancopyto`,
				`canhide`,
				`hidestructure`,
				`blocktypes`,
				`cattypes`,
				`visible`,
				`virtual`,
				`cache`
			FROM prname_ctemplates WHERE id=".$parent;


		sql::query($sql);

		$templid = sql::one_record("SELECT MAX(id) FROM prname_ctemplates");


		//Копирование таблицы шаблона
		sql::query("CREATE TABLE prname_c_".$info['key']."copy LIKE prname_c_".$info['key']);


		//Поля
		$datarel = sql::query("SELECT * FROM prname_cdatarel WHERE templid=".$parent);

		while ($data = sql::fetch_assoc($datarel)) {
			$sql = "INSERT INTO prname_cdatarel (`name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `sort`, `templid`)
				SELECT `name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `sort`, ".$templid." FROM prname_cdatarel WHERE id=".$data['id'];
				sql::query($sql);

		}

		header("Location: /manage/cattemplate/_aedit_parent".$templid."/");
	}

}
?>