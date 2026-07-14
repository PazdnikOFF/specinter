<?php
class admincatedit extends manage {

	function __construct() {
		global $control;

		parent::checkForUser();

		$this->menu = parent::getMenu();


		$this->page = $control->page;
		if ($control->oper == 'add') {
			if (isset($_POST['cat_name'])) {
                $this->add();
                $tree = new tree();
                $tree->makeTree();
                return;
            }
			else $this->printAdd();
		}

		if ($control->oper == 'edit') {
			if (isset($_POST['cat_name'])){
                $this->edit();
                $tree = new tree();
                $tree->makeTree();
                tree::fixUrls($_POST['parent']);
                return;
            }
			else $this->printEdit();
		}

		if ($control->oper == 'showhide') {
			$this->showHide();
            $tree = new tree();
            $tree->makeTree();
            return;
		}

		if ($control->oper == 'del') {
			$this->delete();
            $tree = new tree();
            $tree->makeTree();
            return;
		}

		if ($control->oper == 'move') {
			$this->move();
            $tree = new tree();
            $tree->makeTree();
            $parent = all::getVar("parent");
            tree::fixUrls($parent);
            return;
		}

		if ($control->oper == 'copy') {
			$this->copy();
            $tree = new tree();
            $tree->makeTree();
            return;
		}
	}


	function printAdd() {
		global $control;
		$parent = all::getVar("parent");

		//���������� ���������� �� ����������
		$template = sql::one_record("SELECT template FROM prname_categories WHERE id=".$parent);
		if (!parent::getRight("canaddcat", $template)) header("Location: /manage/");

		$page->status = $_SESSION['admin_status'];
		$page->admin_id = user_is('admin_id');

		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		if (user_is("super") == '1') $page->super = true;
		$page->add = true;
		$page->parent = $parent;


		//������� �����
		if ($page->super) {
			$r = sql::query("SELECT * FROM prname_ctemplates ORDER BY name");

			if ($template) {
				$templatesFor = sql::one_record("SELECT `cattypes` FROM prname_ctemplates WHERE `key`='".$template."'");
				$templatesFor = preg_split("/ /", $templatesFor, null, PREG_SPLIT_NO_EMPTY);
				$templatesFor = array_unique($templatesFor);
				$templatesFor = array_shift($templatesFor);
			}
			else {
				$templateFor = "";
			}


			$i = 0;
			while ($templates = sql::fetch_assoc($r)) {
				$page->templates[$i]->value = $templates['key'];
				$page->templates[$i]->name = $templates['name']." (".$templates['key'].")";

				if ($templatesFor != "") {
					if ($templates['key'] == $templatesFor) $page->templates[$i]->sel = true;
				}
				else {
					if ($i == 0) $page->templates[$i]->sel = true;
				}
				$i ++;
			}

		}
		else {
			if ($template) {
				$templates = sql::one_record("SELECT `cattypes` FROM prname_ctemplates WHERE `key`='".$template."'");
				$templates = preg_split("/ /", $templates, null, PREG_SPLIT_NO_EMPTY);
				$templates = array_unique($templates);

				$i = 0;
				foreach ($templates as $key => $val) {
					$page->templates[$key]->value = $val;
					$page->templates[$key]->name = sql::one_record("SELECT `name` FROM prname_ctemplates WHERE `key`='".$val."'") . " (".$val.")";
					if ($i == 0) $page->templates[$key]->sel = true;
					$i ++;
				}
			}
		}

		$page->name = $control->name;
		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

	function add() {
		global $control;

//		$cache = new phpFastCache();
//		$cache->cleanup();

		$parent = $_POST['parent'];
		$template = $_POST['template'];
		$cat_key = $_POST['cat_key'];
		$cat_name = $_POST['cat_name'];


		$sort = 1 + sql::one_record("SELECT MAX(sort) as msort FROM prname_categories WHERE parent=".$parent);


		$query = "INSERT INTO prname_categories (name, `key`, sort, visible, parent, template) VALUES ('".$cat_name."', '".$cat_key."', ".$sort.", 0, ".$parent.", '".$template."')";
		sql::query($query);


		$lastId = sql::one_record("SELECT MAX(id) AS mid FROM prname_categories WHERE parent=".$parent);
		$query = "INSERT INTO prname_c_".$template." set `parent`='".$lastId."'";
		sql::query($query);

		header("Location: /manage/catedit/_aedit_parent".$lastId."/");
	}

	function printEdit() {
		global $control;
		global $config;
		$parent = all::getVar("parent");

		//���������� ���������� �� ����������
		$template = $page->template = sql::one_record("SELECT template FROM prname_categories WHERE id=".$parent);
		if (!parent::getRight("canedit", $template)) header("Location: /manage/");
        $page->id = $parent;
		$page->status = $_SESSION['admin_status'];
		$page->admin_id = user_is('admin_id');

		$page->sitename = $control->settings->sitename;
		$page->theme = parent::$mainTheme;
		if (user_is("super") == '1') $page->super = true;
		$page->parent = $parent;

		$page->item = array();

		$r = sql::fetch_object(sql::query("SELECT p1.*, p1.name as uname, p2.* FROM prname_categories as p1, prname_c_".$template." as p2 WHERE p1.id=".$parent." AND p2.parent=".$parent));

		$page->item = $r;






		//������� �����
		if ($page->super) {
			$r = sql::query("SELECT * FROM prname_ctemplates ORDER BY name");


			$i = 0;
			while ($templates = sql::fetch_assoc($r)) {
				$page->templates[$i]->value = $templates['key'];
				$page->templates[$i]->name = $templates['name']." (".$templates['key'].")";
				if ($templates['key'] == $template) $page->templates[$i]->sel = true;
				$i ++;
			}

		}
		else {

			if ($template) {
				$templates = sql::fetch_assoc(sql::query("SELECT * FROM prname_ctemplates WHERE `key`='".$template."'"));



					$page->templates[0]->value = $templates['key'];
					$page->templates[0]->name = $templates['name']." (".$templates['key'].")";
					$page->templates[0]->sel = true;
					$page->dis = true;

			}
		}

		//��� ����
		$templId = sql::one_record("SELECT id FROM prname_ctemplates WHERE `key`='".$template."'");
		$sqlFields = sql::query("SELECT * FROM prname_cdatarel WHERE templid=".$templId." ORDER BY sort, `key`");
		$sqlData = sql::fetch_assoc(sql::query("SELECT * FROM prname_c_".$template." WHERE parent=".$parent));

		$i = 0;
		while ($field = sql::fetch_assoc($sqlFields)) {
			$value = $sqlData[$field['key']];

			$class = "type_".$field['datatkey'];
			$obj = new $class();
			$genValue = $obj->input('data'.$i, $value, $field['comment']);

			$page->additem[$i]->name = $field['name'];
			$page->additem[$i]->key = $field['key'];
			$page->additem[$i]->value = $genValue;

			$i ++;
		}

		$page->name = $control->name;
		$page->menu = $this->menu;
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}

	function edit() {
		global $control;
		$cache = new phpFastCache();
		$cache->cleanup();
		$parent = $_POST['parent'];
		$template = $_POST['template'];
		$oldtemplate = $_POST['oldtemplate'];
		$cat_key = $_POST['cat_key'];
		$cat_name = $_POST['cat_name'];

		$utitle = $_POST['utitle'];




		$ukeywords = $_POST['ukeywords'];
		$udescription = $_POST['udescription'];

		if (!$template) $template = sql::one_record("SELECT template FROM prname_categories WHERE id=".$parent);



		sql::query("UPDATE prname_categories SET name='".$cat_name."', `key`='".$cat_key."', `template`='".$template."' WHERE id=".$parent);

		//���� ������ �� ��������� - ��������� ���� � ������� �������
		if ($oldtemplate == $template) {
			sql::query("UPDATE prname_c_".$template." SET utitle='".$utitle."', ukeywords='".$ukeywords."', udescription='".$udescription."' WHERE parent=".$parent);
		}
		//���� ������ ��������� - ��������� ������ � ������� ������ ������� � ������� ������ �� ������� ������� �������
		else {
			sql::query("INSERT INTO prname_c_".$template."(utitle, ukeywords, udescription, parent) VALUES('".$utitle."', '".$ukeywords."', '".$udescription."', ".$parent.")");
			sql::query("DELETE FROM prname_c_".$oldtemplate." WHERE parent=".$parent);
		}

		//��� ���� - ������ ���� ������ �� ���������, ����� ��� ������
		if ($oldtemplate == $template) {
			$templId = sql::one_record("SELECT id FROM prname_ctemplates WHERE `key`='".$template."'");
			$sqlFields = sql::query("SELECT * FROM prname_cdatarel WHERE templid=".$templId." ORDER BY sort, `key`");
			$sqlData = sql::fetch_assoc(sql::query("SELECT * FROM prname_c_".$template." WHERE parent=".$parent));

			$i = 0;

			while ($field = sql::fetch_assoc($sqlFields)) {
				if (array_key_exists($field['key'], $sqlData)) {

					$class = "type_".$field['datatkey'];

					$obj = new $class();

					$genValue = mysqli_real_escape_string(Sql::$connection, $obj->save('data'.$i));
					debug($genValue);


					sql::query("UPDATE prname_c_".$template." SET `".$field['key']."`='".$genValue."' WHERE parent=".$parent);


				}
				$i ++;
			}


		}

		
		header("Location: /manage/#tr".$parent);
	}

	function delete() {
		global $control;
		$parent = all::getVar("parent");

		$cache = new phpFastCache();
		$cache->cleanup();

		//���������� ���������� �� ��������
		$template = sql::one_record("SELECT template FROM prname_categories WHERE id=".$parent);
		if (!parent::getRight("candel", $template)) {
			header("Location: /manage/");
			return;
		}

		delete_category($parent);
		header("Location: /manage/");
		return;
	}

	function showHide() {
		global $control;

		$cache = new phpFastCache();
		$cache->cleanup();

		$parent = all::getVar("parent");

		//���������� ���������� �� �������/�����
		$template = sql::one_record("SELECT template FROM prname_categories WHERE id=".$parent);
		if (!parent::getRight("canhide", $template)) {
			header("Location: /manage/");
			return;
		}

		sql::query("UPDATE prname_categories SET visible=1-visible WHERE id=".$parent);
		header("Location: /manage/");
		return;
	}

	function move() {
		global $control;

		$cache = new phpFastCache();
		$cache->cleanup();

		$parent = all::getVar("parent");

		//���������� ���������� �� �����������
		$template = sql::one_record("SELECT template FROM prname_categories WHERE id=".$parent);

		if (!parent::getRight("canmoveto", $template)) {
			header("Location: /manage/_aerror_b2/");
			return;
		}

		$newParent = all::getVar("newparent");
		$after = all::getVar("after");
		$before = all::getVar("before");


        sql::query('BEGIN');

		$oldParent = sql::one_record("SELECT parent FROM prname_categories WHERE id=".$parent);

		if ($newParent > 0) {

			if ($oldParent != $newParent) {
				//���������� ���������� �� ����������� � �����
				$templateParent = sql::one_record("SELECT template FROM prname_categories WHERE id=".$newParent);
				$templates = sql::one_record("SELECT `cattypes` FROM prname_ctemplates WHERE `key`='".$templateParent."'");
				$templates = preg_split("/ /", $templates, null, PREG_SPLIT_NO_EMPTY);
				$templates = array_unique($templates);


				if (!in_array($template, $templates) && user_is("super") != "1") {
					header("Location: /manage/_aerror_b1/");
					return;
				}

                if ($this->checkNewParent($parent, $newParent)) {
                    $sort = 1 + sql::one_record("SELECT MAX(sort) FROM prname_categories WHERE parent=" . $newParent);
                    sql::query("UPDATE prname_categories SET sort=" . $sort . ", parent=" . $newParent . " WHERE id=" . $parent);
                } else {
                    header("Location: /manage/_aerror_b1/");
                    return;
                }

			}
		}

		if ($after > 0) {
			$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_categories WHERE id=".$after));

			if ($info['parent'] != $oldParent) {


				//���������� ���������� �� ����������� � �����
				$templates = sql::one_record("SELECT `cattypes` FROM prname_ctemplates WHERE `key`='".$info['parent']."'");
				$templates = preg_split("/ /", $templates, null, PREG_SPLIT_NO_EMPTY);
				$templates = array_unique($templates);

				if (!in_array($template, $templates) && user_is("super") != "1") {
					header("Location: /manage/_aerror_b1/");
					return;
				}
			}

			$sort = $info['sort'] + 1;

            if ($this->checkNewParent($parent, $info['parent'])) {
                sql::query("UPDATE prname_categories SET sort=sort+1 WHERE sort>" . $info['sort'] . " AND parent=" . $info['parent']);
                sql::query("UPDATE prname_categories SET sort=" . $sort . ", parent=" . $info['parent'] . " WHERE id=" . $parent);
            } else {
                header("Location: /manage/_aerror_b1/");
                return;
            }
		}

		if ($before > 0) {
			$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_categories WHERE id=".$before));

			if ($info['parent'] != $oldParent) {
				//���������� ���������� �� ����������� � �����
				$templates = sql::one_record("SELECT `cattypes` FROM prname_ctemplates WHERE `key`='".$info['parent']."'");
				$templates = preg_split("/ /", $templates, null, PREG_SPLIT_NO_EMPTY);
				$templates = array_unique($templates);

				if (!in_array($template, $templates) && user_is("super") != "1") {
					header("Location: /manage/_aerror_b1/");
					return;
				}
			}

			$sort = $info['sort'];

            if ($this->checkNewParent($parent, $info['parent'])) {
                sql::query("UPDATE prname_categories SET sort=sort+1 WHERE sort>=".$info['sort']." AND parent=".$info['parent']);
                sql::query("UPDATE prname_categories SET sort=".$sort.", parent=".$info['parent']." WHERE id=".$parent);
            } else {
                header("Location: /manage/_aerror_b1/");
                return;
            }
		}
        sql::query('COMMIT');

		header("Location: /manage/");
		return;
	}

    function checkNewParent($id, $newParent) {
        $row1 = sql::fetch_assoc(sql::query('select left_key, right_key from prname_tree where id = ' . (int)$id));
        $row2 = sql::fetch_assoc(sql::query('select left_key, right_key from prname_tree where id = '. (int)$newParent));
        return $row2['left_key'] < $row1['left_key'] || $row2['right_key'] > $row1['right_key'];
    }

	function copy() {
		global $control;

		$cache = new phpFastCache();
		$cache->cleanup();


		$catId = all::getVar("parent");

		$newParent = all::getVar("newparent");
		$after = all::getVar("after");
		$before = all::getVar("before");


		//���������� � ���������� ��������
		$infoCopy = sql::fetch_assoc(sql::query("SELECT * FROM prname_categories WHERE id=".$catId));


		//���������� � �������� - ��������
		if ($newParent > 0) {
			$catParent = $newParent;
		}
		else {
			$catParent = $infoCopy['parent'];
		}
		$infoParent = sql::fetch_assoc(sql::query("SELECT * FROM prname_categories WHERE id=".$catParent));


		//���������� ���������� �� ����������� ���� �����
		if (!parent::getRight("cancopyto", $infoCopy['template'])) {
			header("Location: /manage/_aerror_b4/");
			return;
		}


		//���������� ���������� �� ����������� � ����� ��������
		$templates = sql::one_record("SELECT `cattypes` FROM prname_ctemplates WHERE `key`='".$infoParent['template']."'");
		$templates = preg_split("/ /", $templates, null, PREG_SPLIT_NO_EMPTY);
		$templates = array_unique($templates);

		if (!in_array($infoCopy['template'], $templates) && user_is("super") != "1") {
			header("Location: /manage/_aerror_b1/");
			return;
		}


		//����������� �������� ���������� sort
		if ($newParent > 0) {
			$sort = 1 + sql::one_record("SELECT MAX(sort) as msort FROM prname_categories WHERE parent=".$catParent);
		}

		if ($after > 0) {
			$oldSort = sql::one_record("SELECT sort FROM prname_categories WHERE parent=$catParent AND id=$after");
			$sort = $oldSort + 1;
			sql::query("UPDATE prname_categories SET sort=sort+1 WHERE sort>".$oldSort." AND parent=".$catParent);
		}

		if ($before > 0) {
			$sort = sql::one_record("SELECT sort FROM prname_categories WHERE parent=$catParent AND id=$before");
			sql::query("UPDATE prname_categories SET sort=sort+1 WHERE sort>=".$sort." AND parent=".$catParent);
		}

		//��������
		$query = "INSERT INTO prname_categories (name, `key`, sort, visible, parent, template) VALUES ('".$infoCopy['name']."', '', ".$sort.", 0, ".$catParent.", '".$infoCopy['template']."')";
		sql::query($query);


		$lastId = sql::one_record("SELECT MAX(id) AS mid FROM prname_categories WHERE parent=".$catParent);
		$query = "INSERT INTO prname_c_".$infoCopy['template']." set `parent`='".$lastId."'";
		sql::query($query);

		header("Location: /manage/");
		return;
	}

}
?>