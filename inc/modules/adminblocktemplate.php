<?php
class adminblocktemplate extends manage {

	function __construct() {
		global $control;
		parent::checkForUser();

		$this->menu = parent::getMenu();

        
		if (user_is("super") != 1) {
			header("Location: /manage/");
			return;
		}

		elseif (isset($_FILES['import'])) {
			return $this->import();
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
		elseif ($control->oper == 'export') {
			return $this->export();
		}
		else {
			return $this->printList();
		}
	}

	function printList() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		$tpls = sql::query("SELECT * FROM prname_btemplates ORDER by `name`");

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
		//РРјСЏ, РєР»СЋС‡, Р°Р»РёР°СЃ
		$blockname = $_POST['name'];
		$blockkey = $_POST['key'];

		function make_seed() {
			list($usec, $sec) = explode(' ', microtime());
			return (float) $sec + ((float) $usec * 100000);
		}
		mt_srand(make_seed());
		$randval = mt_rand();

		if ($blockname == "") $blockname = "РќРѕРІС‹Р№ С€Р°Р±Р»РѕРЅ".$randval;

		if ($blockkey == "") $blockkey = "newtempl".$randval;

		//РџСЂР°РІР°
		if (isset($_POST['candel'])) $candel = 1;
		else $candel = 0;

		if (isset($_POST['canedit'])) $canedit = 1;
		else $canedit = 0;

		if (isset($_POST['canadd'])) $canadd = 1;
		else $canadd = 0;

		if (isset($_POST['canmove'])) $canmove = 1;
		else $canmove = 0;

		if (isset($_POST['cancopy'])) $cancopy = 1;
		else $cancopy = 0;

		if (isset($_POST['canhide'])) $canhide = 1;
		else $canhide = 0;

		if (isset($_POST['seo'])) $seo = 1;
		else $seo = 0;

		if (isset($_POST['virtual'])) $virtual = 1;
		else $virtual = 0;


		//Р”РѕР±Р°РІР»РµРЅРёРµ РІ С‚Р°Р±Р»РёС†Сѓ СЃ С€Р°Р±Р»РѕРЅР°РјРё
		$sql = "INSERT INTO prname_btemplates
			(`name`,
			`key`,
			`candel`,
			`canedit`,
			`canadd`,
			`canmove`,
			`cancopy`,
			`canhide`,
			`seo`,
			`virtual`)
			VALUES (
				'".$blockname."',
				'".$blockkey."',
				".$candel.",
				".$canedit.",
				".$canadd.",
				".$canmove.",
				".$cancopy.",
				".$canhide.",
				".$seo.",
				".$virtual."
				)";



		sql::query($sql);

		$templid = sql::one_record("SELECT MAX(id) FROM prname_btemplates");

		//РЎРѕР·РґР°РЅРёРµ С‚Р°Р±Р»РёС†С‹ РґР»СЏ С€Р°Р±Р»РѕРЅР°
		$sql = "CREATE table IF NOT EXISTS prname_b_$blockkey (`id` int(12) NOT NULL auto_increment, `parent` int(12), `blockparent` int(12), `sort` int(12), `visible` tinyint(1), `modified` timestamp, PRIMARY KEY  (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;";

		sql::query($sql);


		//Р”РѕРї РїРѕР»СЏ
		if (count($_POST['addname']) > 1) {

			$sqlAdd = "";

			foreach ($_POST['addname'] as $key => $val) {
				if ($key == 0) continue;

				if ($_POST['addname'][$key] == '' || $_POST['addtype'][$key] == '' || $_POST['addkey'][$key] == '') continue;

				switch ($_POST['addtype'][$key]) {
					case 'html': $type = 'text'; break;
					case 'checkbox': $type = 'tinyint(1)'; break;
					case 'radiio': $type = 'tinyint(1)';break;
					case 'date': $type = 'date'; break;
					case 'subscribe': $type = 'datetime'; break;
					case 'file': $type = 'varchar(255)'; break;
					case 'text': $type = 'varchar(255)'; break;
					case 'mcheckbox': $type = 'varchar(100)'; break;
					default: $type = 'text'; break;
				}

				$sql = "INSERT INTO prname_bdatarel (`name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `tab`, `sort`, `templid`, `default`, `show`)
				VALUES (
					'".$_POST['addname'][$key]."',
					'".$_POST['addtype'][$key]."',
					'".$_POST['addkey'][$key]."',
					'".addslashes($_POST['addattr'][$key])."',
					'".$_POST['addinfo'][$key]."',
					".$_POST['addroh'][$key].",
					".$_POST['addtab'][$key].",
					".$_POST['addsort'][$key].",
					".$templid.",
					'".$_POST['adddefault'][$key]."',
					".$_POST['addshowh'][$key]."
					)";


				sql::query($sql);


				$sqlAdd .= " ADD `".$_POST['addkey'][$key]."` ".$type;
				if ($_POST['adddefault'][$key] != "") $sqlAdd .= " DEFAULT ".$_POST['adddefault'][$key]." NOT NULL, ";
				else $sqlAdd .= ", ";
			}

			$sqlAdd = substr($sqlAdd, 0, strlen($sqlAdd)-2);
			$sql = "ALTER TABLE prname_b_".$blockkey.$sqlAdd;


			sql::query($sql);
		}

		//РЎР•Рћ РїРѕР»СЏ
		if ($seo == 1) {
			$sql = "ALTER TABLE prname_b_$blockkey ADD `utitle` varchar(255), ADD `udescription` text, ADD `ukeywords` text, ADD `uurl` varchar(255)";
			sql::query($sql);
		}

		header("Location: /manage/blocktemplate/");
	}

	function printEdit() {
		global $control;
		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;

		$parent = $page->parent = all::getVar("parent");

		//Р’СЃСЏ РёРЅС„Р° РїРѕ С€Р°Р±Р»РѕРЅСѓ
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_btemplates WHERE id=$parent"));
		foreach ($info as $key => $val) {
			$page->info[0]->$key = $val;
		}

		//РўРёРїС‹ РґР°РЅРЅС‹С…
		$dtypesS = sql::query("SELECT * FROM prname_datatypes");

		$i = 0;
		while ($dtype = sql::fetch_assoc($dtypesS)) {
			foreach ($dtype as $key => $val) {
				$page->dtypes[$i]->$key = $val;
			}
			$i ++;
		}

		//РџРѕР»СЏ Р±Р»РѕРєР°
		$addFields = sql::query("SELECT * FROM prname_bdatarel WHERE templid=$parent ORDER by tab, sort");

		$i = 0;
		while ($addField = sql::fetch_assoc($addFields)) {
			$tab = $addField['tab'];
			$page->tabs[$tab]->id = $tab;
			foreach ($addField as $key => $val) {
				$page->tabs[$tab]->addFields[$i]->$key = htmlspecialchars(stripslashes($val));
			}
			$page->tabs[$tab]->addFields[$i]->dtypes = $page->dtypes;
			$tabber[$tab] ++;
			$i ++;
		}

		$tabber = json_encode($tabber);
		$page->tabber = "var tabber = ". $tabber . ";\n";




		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_edit.html');
	}

	function edit() {
		//РРјСЏ, РєР»СЋС‡, Р°Р»РёР°СЃ
		$blockname = $_POST['name'];
		$blockkey = $_POST['key'];
		$oldkey = $_POST['oldkey'];
		$parent = $_POST['hide-parent'];

		//РџСЂР°РІР°
		if (isset($_POST['candel'])) $candel = 1;
		else $candel = 0;

		if (isset($_POST['canedit'])) $canedit = 1;
		else $canedit = 0;

		if (isset($_POST['canadd'])) $canadd = 1;
		else $canadd = 0;

		if (isset($_POST['canmove'])) $canmove = 1;
		else $canmove = 0;

		if (isset($_POST['cancopy'])) $cancopy = 1;
		else $cancopy = 0;

		if (isset($_POST['canhide'])) $canhide = 1;
		else $canhide = 0;

		if (isset($_POST['seo'])) $seo = 1;
		else $seo = 0;

		if (isset($_POST['virtual'])) $virtual = 1;
		else $virtual = 0;

		//Р•СЃР»Рё РјРµРЅСЏРµС‚СЃСЏ РЅР°РІР·Р°РЅРёРµ С€Р°Р±Р»РѕРЅР° - РїРµСЂРµРёРјРµРЅРѕРІС‹РІР°РµРј РїР°РїРєСѓ, Р° С‚Р°РєР¶Рµ blocktypes РІ cattemplates
		if ($_POST['key'] != $_POST['oldkey']) {
			sql::query("ALTER TABLE prname_b_$oldkey RENAME prname_b_$blockkey");
			sql::query("UPDATE prname_ctemplates SET blocktypes=REPLACE(`blocktypes`, '".$_POST['oldkey']."', '".$_POST['key']."')");
			sql::query("UPDATE prname_bdatarel SET comment=REPLACE(`comment`, '".$_POST['oldkey']."', '".$_POST['key']."') WHERE datatkey='items'");
		}


		$sql = "UPDATE prname_btemplates SET
			`name`='".$blockname."',
			`key`='".$blockkey."',
			`candel`=".$candel.",
			`canedit`=".$canedit.",
			`canadd`=".$canadd.",
			`canmove`=".$canmove.",
			`cancopy`=".$cancopy.",
			`canhide`=".$canhide.",
			`seo`=".$seo.",
			`virtual`=".$virtual."
			 WHERE id=".$parent;

		sql::query($sql);


		//РџСЂРѕРІРµСЂСЏРµРј СЃС‚Р°СЂС‹Рµ РїРѕР»СЏ С€Р°Р±Р»РѕРЅР°
		$oldFields = sql::query("SELECT * FROM prname_bdatarel WHERE templid=$parent");

		while ($oldField = sql::fetch_assoc($oldFields)) {
			$id = $oldField['id'];

			//Р•СЃР»Рё РїСЂРёС€Р»Рѕ РїРѕСЃС‚РѕРј, Р·РЅР°С‡РёС‚ РѕСЃС‚Р°РЅРµС‚СЃСЏ, РІРѕР·РјРѕР¶РЅРѕ РёР·РјРµРЅРёС‚СЃСЏ
			if (isset($_POST['curname'][$id])) {

				$sql = "UPDATE prname_bdatarel SET
					name='".$_POST['curname'][$id]."',
					`key`='".$_POST['curkey'][$id]."',
					`datatkey`='".$_POST['curtype'][$id]."',
					`comment`='".addslashes($_POST['curinfo'][$id])."',
					`attr`='".addslashes($_POST['curattr'][$id])."',
					`default`='".$_POST['curdefault'][$id]."',
					`tab`=".$_POST['curtab'][$id].",
					`sort`=".$_POST['cursort'][$id].",
					`readonly`=".$_POST['curroh'][$id].",
					`show`=".$_POST['curshowh'][$id]."
					 WHERE id=".$id;

				$sql2 = "ALTER TABLE prname_b_$blockkey CHANGE `".$oldField['key']."` `".$_POST['curkey'][$id]."`";

				switch ($_POST['curtype'][$id]) {
					case 'html': $type = 'text'; break;
					case 'checkbox': $type = 'tinyint(1)'; break;
					case 'radiio': $type = 'tinyint(1)';break;
					case 'date': $type = 'date'; break;
					case 'subscribe': $type = 'datetime'; break;
					case 'file': $type = 'varchar(255)'; break;
					case 'text': $type = 'varchar(255)'; break;
					case 'mcheckbox': $type = 'varchar(100)'; break;
					case 'select': $type = 'varchar(255)'; break;
					default: $type = 'text'; break;
				}

				$sql2 .= " ".$type;

				if ($_POST['curdefault'][$id] != '') $sql2 .= " DEFAULT '".$_POST['curdefault'][$id]."' NOT NULL";


			}

			else {
				$sql = "DELETE FROM prname_bdatarel WHERE id=$id";
				$sql2 = "ALTER TABLE prname_b_$blockkey DROP `".$oldField['key']."`";
			}

			sql::query($sql);
			sql::query($sql2);
		}


		//РџСЂРѕРІРµСЂСЏРµРј РЅРѕРІС‹Рµ РїРѕР»СЏ С€Р°Р±Р»РѕРЅР°
		if (count($_POST['addname']) > 1) {

			$sqlAdd = "";

			foreach ($_POST['addname'] as $key => $val) {
				if ($key == 0) continue;
				switch ($_POST['addtype'][$key]) {
					case 'html': $type = 'text'; break;
					case 'checkbox': $type = 'tinyint(1)'; break;
					case 'radiio': $type = 'tinyint(1)';break;
					case 'date': $type = 'date'; break;
					case 'subscribe': $type = 'datetime'; break;
					case 'file': $type = 'varchar(255)'; break;
					case 'text': $type = 'varchar(255)'; break;
					case 'mcheckbox': $type = 'varchar(100)'; break;
					case 'select': $type = 'varchar(255)'; break;
					default: $type = 'text'; break;
				}



				$sql = "INSERT INTO prname_bdatarel (`name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `tab`, `sort`, `templid`, `default`, `show`)
				VALUES (
					'".$_POST['addname'][$key]."',
					'".$_POST['addtype'][$key]."',
					'".$_POST['addkey'][$key]."',
					'".addslashes($_POST['addattr'][$key])."',
					'".$_POST['addinfo'][$key]."',
					".$_POST['addroh'][$key].",
					".$_POST['addtab'][$key].",
					".$_POST['addsort'][$key].",
					".$parent.",
					'".$_POST['adddefault'][$key]."',
					".$_POST['addshowh'][$key]."
					)";


				sql::query($sql);


				$sqlAdd .= " ADD `".$_POST['addkey'][$key]."` ".$type;
				if ($_POST['adddefault'][$key] != "") $sqlAdd .= " DEFAULT ".$_POST['adddefault'][$key]." NOT NULL, ";
				else $sqlAdd .= ", ";
			}

			$sqlAdd = substr($sqlAdd, 0, strlen($sqlAdd)-2);
			$sql = "ALTER TABLE prname_b_".$blockkey.$sqlAdd;

			sql::query($sql);
		}

		//seo
		if ($_POST['oldseo'] == 0 && $seo == 1) {
			$sql = "ALTER TABLE prname_b_$blockkey ADD `utitle` varchar(255), ADD `udescription` text, ADD `ukeywords` text, ADD `uurl` varchar(255)";
			sql::query($sql);
		}

		if ($_POST['oldseo'] == 1 && $seo == 0) {
			$sql = "ALTER TABLE prname_b_$blockkey DROP `utitle`, DROP `udescription`, DROP `ukeywords`, DROP `uurl`";
			sql::query($sql);
		}

		header("Location: /manage/blocktemplate/");
	}

	function delete() {
		$id = all::getVar("id");

		$template = sql::one_record("SELECT `key` FROM prname_btemplates WHERE id=".$id);
		$result = sql::query("SELECT * FROM prname_b_".$template);
		if (sql::num_rows($result) > 0) {
			header("Location: /manage/blocktemplate/_aerror_b1/");
			return;
		}
		else {
			//РЈРґР°Р»Р°СЏРµРј
			sql::query("DELETE FROM prname_btemplates WHERE id=".$id);
			sql::query("DROP TABLE prname_b_".$template);
			sql::query("DELETE FROM prname_bdatarel WHERE templid=".$id);
			header("Location: /manage/blocktemplate/");
			return;
		}
	}

	function copy() {

		$parent = all::getVar('parent');


		//Р’СЃСЏ РёРЅС„Р° РїРѕ С€Р°Р±Р»РѕРЅСѓ
		$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_btemplates WHERE id=$parent"));

		//Р”РѕР±Р°РІР»РµРЅРёРµ РІ С‚Р°Р±Р»РёС†Сѓ СЃ С€Р°Р±Р»РѕРЅР°РјРё
		$sql = "INSERT INTO prname_btemplates
				(`name`,
				`key`,
				`candel`,
				`canedit`,
				`canadd`,
				`canmove`,
				`cancopy`,
				`canhide`,
				`seo`,
				`virtual`
				)
			SELECT
				'".$info['name']."_РљРѕРїРёСЏ"."',
				'".$info['key']."copy"."',
				`candel`,
				`canedit`,
				`canadd`,
				`canmove`,
				`cancopy`,
				`canhide`,
				`seo`,
				`virtual`
			FROM prname_btemplates WHERE id=$parent";


		sql::query($sql);

		$templid = sql::one_record("SELECT MAX(id) FROM prname_btemplates");


		//РљРѕРїРёСЂРѕРІР°РЅРёРµ С‚Р°Р±Р»РёС†С‹ С€Р°Р±Р»РѕРЅР°
		sql::query("CREATE TABLE prname_b_".$info['key']."copy LIKE prname_b_".$info['key']);


		//РџРѕР»СЏ
		$datarel = sql::query("SELECT * FROM prname_bdatarel WHERE templid=".$parent);

		while ($data = sql::fetch_assoc($datarel)) {
			$sql = "INSERT INTO prname_bdatarel (`name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `tab`, `sort`, `templid`, `default`, `show`)
				SELECT `name`, `datatkey`, `key`, `attr`, `comment`, `readonly`, `tab`, `sort`, ".$templid.", `default`, `show` FROM prname_bdatarel WHERE id=".$data['id'];
			sql::query($sql);

		}

		header("Location: /manage/blocktemplate/_aedit_parent$templid/");
	}

	function export() {

		$parent = all::getVar('parent');


		//Р’СЃСЏ РёРЅС„Р° РїРѕ С€Р°Р±Р»РѕРЅСѓ
		$info = sql::fetch_object(sql::query("SELECT * FROM prname_btemplates WHERE id=$parent"));

		$template->name = $info->name;
		$template->key = $info->key;
		$template->rights->delete = (boolean)$info->candel;
		$template->rights->edit = (boolean)$info->canedit;
		$template->rights->create = (boolean)$info->canadd;
		$template->rights->move = (boolean)$info->canmove;
		$template->rights->copy = (boolean)$info->cancopy;
		$template->rights->hide = (boolean)$info->canhide;
		$template->seo = (boolean)$info->seo;
		$template->virtual = (boolean)$info->virtual;


		//РџРѕР»СЏ
		$datarel = sql::query("SELECT * FROM prname_bdatarel WHERE templid=".$parent);

		$i = 0;
		while ($data = sql::fetch_object($datarel)) {
			$fields[$i]->name = $data->name;
			$fields[$i]->key = $data->key;
			$fields[$i]->type = $data->datatkey;
			$fields[$i]->default = $data->default;
			$fields[$i]->comment = $data->comment;
			$fields[$i]->sort = $data->sort;
			$fields[$i]->tab = $data->tab;
			$fields[$i]->readonly = (boolean)$data->readonly;
			$fields[$i]->show = (boolean)$data->show;

			$i ++;
		}

		$template->fields = $fields;

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $template->key.".json");
		$template = json_encode($template);
		$template = all::json_format($template);
		echo $template;
	}

	function import() {
		$fileInfo = $_FILES['import'];
		$realName = $fileInfo['name'];
		$ext = pathinfo($realName);
		$ext = $ext['extension'];
		if ($ext != "json") {
			header("Location: /manage/blocktemplate/");
			return;
		}
		$file = $fileInfo['tmp_name'];
		$content = file_get_contents($file);
		$content = json_decode($content);

		// РџСЂРѕРІРµСЂСЏРµРј РЅР° РЅР°Р»РёС‡РёРµ С‚Р°РєРѕРіРѕ С€Р°Р±Р»РѕРЅР°
		$isset = sql::one_record("SELECT `key` FROM prname_btemplates WHERE `key`='".$content->key."'");
		if ($isset) {
			$content->name .= "_copy";
			$content->key .= "_copy";
		}



		//Р”РѕР±Р°РІР»РµРЅРёРµ РІ С‚Р°Р±Р»РёС†Сѓ СЃ С€Р°Р±Р»РѕРЅР°РјРё
		$sql = "INSERT INTO prname_btemplates
				(`name`,
				`key`,
				`candel`,
				`canedit`,
				`canadd`,
				`canmove`,
				`cancopy`,
				`canhide`,
				`seo`,
				`virtual`
				)
				VALUES(
					'".$content->name."',
					'".$content->key."',
					".(int)$content->rights->delete.",
					".(int)$content->rights->edit.",
					".(int)$content->rights->create.",
					".(int)$content->rights->move.",
					".(int)$content->rights->copy.",
					".(int)$content->rights->hide.",
					".(int)$content->seo.",
					".(int)$content->virtual."
				)
			";


		sql::query($sql);

		$templid = sql::one_record("SELECT MAX(id) FROM prname_btemplates");

		$blockkey = $content->key;

		//РЎРѕР·РґР°РЅРёРµ С‚Р°Р±Р»РёС†С‹ РґР»СЏ С€Р°Р±Р»РѕРЅР°
		$sql = "CREATE table IF NOT EXISTS prname_b_$blockkey (`id` int(12) NOT NULL auto_increment, `parent` int(12), `blockparent` int(12), `sort` int(12), `visible` tinyint(1), PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;";

		sql::query($sql);


		// РџРѕР»СЏ С€Р°Р±Р»РѕРЅР°
		$sqlAdd = "";

		foreach ($content->fields as $key => $val) {
			if ($val->name == '' || $val->key == '' || $val->type == '') continue;

			switch ($val->type) {
				case 'html': $type = 'text'; break;
				case 'checkbox': $type = 'tinyint(1)'; break;
				case 'date': $type = 'date'; break;
				case 'subscribe': $type = 'datetime'; break;
				case 'file': $type = 'varchar(255)'; break;
				case 'text': $type = 'varchar(255)'; break;
				case 'mcheckbox': $type = 'varchar(100)'; break;
				default: $type = 'text'; break;
			}

			$sql = "INSERT INTO prname_bdatarel (`name`, `datatkey`, `key`, `comment`, `readonly`, `tab`, `sort`, `templid`, `default`, `show`)
			VALUES (
				'".$val->name."',
				'".$val->type."',
				'".$val->key."',
				'".$val->comment."',
				".(int)$val->readonly.",
				".$val->tab.",
				".$val->sort.",
				".$templid.",
				'".$val->default."',
				".(int)$val->show."
				)";


			sql::query($sql);


			$sqlAdd .= " ADD `".$val->key."` ".$type;
			if ($val->default != "") $sqlAdd .= " DEFAULT ".$val->default." NOT NULL, ";
			else $sqlAdd .= ", ";
		}

		$sqlAdd = substr($sqlAdd, 0, strlen($sqlAdd)-2);
		$sql = "ALTER TABLE prname_b_".$blockkey.$sqlAdd;


		sql::query($sql);


		// РЎР•Рћ РїРѕР»СЏ
		if ($content->seo) {
			$sql = "ALTER TABLE prname_b_$blockkey ADD `utitle` varchar(255), ADD `udescription` text, ADD `ukeywords` text, ADD `uurl` varchar(255)";
			sql::query($sql);
		}

		header("Location: /manage/blocktemplate/");
	}

}
?>