<?php
/**
 * This file is part of Elgrow CMS
 * Copyright 2012 Innokenty Sarayev <6319432@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Класс для дерева элементов
 */
class Tree {

	/**
	 *
	 * @return type
	 */
	function makeTree() {
        sql::query("BEGIN");
		sql::query("TRUNCATE TABLE prname_tree");

		$q = "INSERT INTO prname_tree (id, parent, name, level, left_key, right_key, `key`, template, visible) VALUES (1, 0, 'Главная страница', 0, 1, 2, 'main', 'first', 1) ";
		sql::query($q);

		$this->getTree(1);
		$this->writeUrlTree();
        sql::query("COMMIT");
	}


	/**
	 *
	 * @global type $control
	 * @global type $config
	 * @param type $parent
	 * @return type
	 */
	function tree_url($parent = '') {
		global $control;
		global $config;
		$q = sql::query("select p2.* from prname_tree p1, prname_tree p2 where p2.left_key<=p1.left_key and p2.right_key>=p1.right_key and p1.id='".($parent ? $parent : $control->cid)."' ORDER BY p2.left_key");
		while ($arr = mysql_fetch_assoc($q)) {
			$array[$arr['level']]->id = $arr['id'];
			$array[$arr['level']]->name = $arr['name'];
			$array[$arr['level']]->url = $arr['url'] == '/' ? $config['server_url'] : $config['server_url'].$arr['url'];
			$array[$arr['level']]->template = $arr['template'];
		}
		if (isset($array))
			return $array;
	}

	/**
	 *
	 * @global type $sql
	 */
	function writeUrlTree() {
		global $sql;

		$q = "SELECT id, `key`, left_key, right_key FROM prname_tree ORDER BY id";
		$res = sql::query($q);
		while ($str = sql::fetch_array($res)) {
			$id = $str['id'];
			$key = $str['key'];
			$left_key = $str['left_key'];
			$right_key = $str['right_key'];

			$url = '';

			$q = "SELECT id, `key`, level FROM prname_tree WHERE left_key <= '$left_key' AND right_key >= '$right_key' ORDER BY left_key ";
			$res2 = sql::query($q);
			$i = 0;
			while ($str2 = sql::fetch_array($res2)) {
				$tmp_id = $str2['id'];
				$tmp_key = $str2['key'];
				if ($i > 1)
					$url .= '/';
				if ($tmp_key <> '') {
					$url .= $tmp_key;
				}
				else {
					$url .= $tmp_id;
				}
				$i++;
			}
			$url .= '/';
			$url = substr($url, 4);
			$q = "UPDATE prname_tree SET url = '$url' WHERE id = '$id' ";
			sql::query($q);
		}
	}

	/**
	 *
	 * @param type $parent
	 */
	function getTree($parent) {
		$q = "SELECT * FROM prname_categories WHERE parent = '$parent' ORDER BY sort ";
		$res = sql::query($q);
		if (sql::num_rows($res) > 0) {
			while ($str = sql::fetch_array($res)) {
				$id = $str['id'];
				$name = $str['name'];
				$sort = $str['sort'];
				$key = $str['key'];
				$template = $str['template'];
				$visible = $str['visible'];

				$q = "SELECT level, left_key, right_key FROM prname_tree WHERE id = '$parent' ";
				$str1 = sql::fetch_array(sql::query($q));
				$level = $str1['level'];
				$left_key = $str1['left_key'];
				$right_key = $str1['right_key'];

				$q = "UPDATE prname_tree SET left_key = left_key + 2, right_key = right_key + 2 WHERE left_key > $right_key ";
				sql::query($q);

				$q = " UPDATE prname_tree SET right_key = right_key + 2 WHERE right_key >= '$right_key' AND left_key < '$right_key' ";
				sql::query($q);

				$q = "INSERT INTO prname_tree SET left_key = $right_key, right_key = $right_key + 1, level = $level + 1, id = '$id', name = '".sql::escape_string($name)."', parent = '$parent', sort = '$sort', `key` = '$key', template = '$template', visible = '$visible' ";
				sql::query($q);


				$this->getTree($id, $parent);
			}
		}
	}

	/**
	 *
	 * @global type $control
	 * @param type $id
	 * @param type $level
	 * @param type $vis
	 * @return type
	 */
	function tree_all($id = '1', $level = '', $vis = 'and p1.visible=1') {
		global $control;

		$q = sql::query("SELECT p1.*,p2.level as parent_level FROM prname_tree p1,prname_tree p2 where p1.left_key > p2.left_key and p1.right_key < p2.right_key and p2.id='$id' ".$vis.($level !== '' ? " and p1.level<=$level" : "")." ORDER BY p1.left_key");

		$i = 0;

		while ($b = mysql_fetch_assoc($q)) {
			if ($b['level'] == $b['parent_level'] + 1) {
				if ($control->cid == $b['id'])
					$a->item[$b['id']]->link = 'nolink'; else
					$a->item[$b['id']]->link = 'link';
				$a->item[$b['id']]->name = $b['name'];
				$a->item[$b['id']]->id = $b['id'];
				$a->item[$b['id']]->parent = $b['parent'];
				$a->item[$b['id']]->level = $b['level'];
				$a->item[$b['id']]->url = "/".$b['url'];
				$a->item[$b['id']]->template = $b['template'];
				$a->item[$b['id']]->key = $b['key'];
				$a->item[$b['id']]->visible = $b['visible'];
				$a->item[$b['id']]->class = $i == 0 ? "Первый раздел всего меню сайта" : "Первый раздел ветки";
				$level = $b['level'];
				$last_id = $b['id'];
				$c[$b['level']] = & $a->item[$b['id']];
				$allid[$b['id']] = $b['id'];
			}
			else {
				if ($control->cid == $b['id']) {
					for ($l = 1; $l < $b['level']; $l++)
						if ($allid[$control->parents[$b['level'] - $l]])
							$c[$b['level'] - $l]->link = 'stronglink';

					$c[$b['level'] - 1]->item[$b['id']]->link = 'nolink';
				}

				else
					$c[$b['level'] - 1]->item[$b['id']]->link = 'link';

				$c[$b['level'] - 1]->item[$b['id']]->name = $b['name'];
				$c[$b['level'] - 1]->item[$b['id']]->id = $b['id'];
				$c[$b['level'] - 1]->item[$b['id']]->parent = $b['parent'];
				$c[$b['level'] - 1]->item[$b['id']]->level = $b['level'];
				$c[$b['level'] - 1]->item[$b['id']]->url = "/".$b['url'];
				$c[$b['level'] - 1]->item[$b['id']]->template = $b['template'];
				$c[$b['level'] - 1]->item[$b['id']]->key = $b['key'];
				$c[$b['level'] - 1]->item[$b['id']]->visible = $b['visible'];
				$c[$b['level'] - 1]->item[$b['id']]->class = "Последний в своей ветке";
				if ($level > $b['level'])
					$c[$b['level']]->class = '"Это раздел не последний в своей ветке и имеет вложения';
				if ($level == $b['level'])
					$c[$level]->class = 'Раздел не имеет вложений он не первый но и не последний';
				if ($level < $b['level'])
					$c[$level]->class = '"Этот раздел имеет вложение '.$c[$level]->class;
				$level = $b['level'];
				$last_id = $b['id'];
				$allid[$b['id']] = $b['id'];
				$c[$b['level']] = & $c[$b['level'] - 1]->item[$b['id']];
			}
			$i++;
		}
		return $a;
	}


	/**
	 *
	 * @param int $id
	 * @return string
	 */
	function getUrl($id) {
		$q = "SELECT url FROM prname_tree WHERE id = '$id'";
		$url = sql::one_record($q);
		$lastS = substr($url, -1, 1);
		if ($lastS != "/") {
			$url .= "/";
		}
		return $url;
	}

	/**
	 *
	 * @param int $id
	 * @return type
	 */
	function GetParents($id) {
		$parents = array();

		$q = "SELECT left_key, right_key FROM prname_tree WHERE id = '$id' ";
		$str = sql::fetch_array(sql::query($q));
		$left_key = $str['left_key'];
		$right_key = $str['right_key'];

		$q = "SELECT id, `key`, level FROM prname_tree WHERE left_key <= '$left_key' AND right_key >= '$right_key' ORDER BY left_key ";
		$res2 = sql::query($q);
		$i = 0;
		while ($str2 = sql::fetch_array($res2)) {
			$parents[$i] = $str2['id'];
			$i++;
		}

		return $parents;
	}

	/**
	 *
	 * @global type $control
	 * @param type $id
	 * @param type $depth
	 * @param type $type
	 * @return type
	 */
	function GetNode($id, $depth = 1000000, $type = 'full') {
		global $control;

		$q = "SELECT left_key, right_key, level FROM prname_tree WHERE id = '$id' ";
		$str = sql::fetch_array(sql::query($q));
		$left_key = $str['left_key'];
		$right_key = $str['right_key'];
		$level = $str['level'];

		$q = "  SELECT pr1.id as id, pr1.name as title, pr1.url as url, pr1.level as level, pr2.id as parent, pr2.name, pr2.level as parentlevel, pr1.template as template FROM prname_tree pr1, prname_tree pr2 WHERE pr1.right_key > $left_key AND pr1.left_key < $right_key AND pr1.level < '".($level + $depth + 1)."' AND pr2.left_key <= pr1.left_key AND pr2.right_key >= pr1.right_key   AND pr1.visible = 1 ORDER BY pr1.left_key, pr1.sort, pr2.level  ";

		$res = sql::query($q);

		$arr = array();

		while ($str = sql::fetch_array($res)) {
			$id = $str['id'];
			$title = $str['title'];
			$parent = $str['parent'];
			$level = $str['level'];
			$template = $str['template'];
			$url = $str['url'];
			$parentlevel = $str['parentlevel'];
			if (!isset($arr[$id]['parents'])) {
				$arr[$id]['parents'] = array();
			}
			$arr[$id]['id'] = $id;
			$arr[$id]['level'] = $level;
			$arr[$id]['title'] = $title;
			$arr[$id]['template'] = $template;
			$arr[$id]['url'] = substr($url, 0, strlen($url) - 1);
			array_push($arr[$id]['parents'], array('level' => $parentlevel, 'parent' => $parent));
		}

		if (count($arr) > 0) {

			$contr_parents = $control->parents;
			unset($contr_parents[count($contr_parents) - 1]);

			foreach ($arr as $one_arr) {
				$level = $one_arr['level'];
				$link = 'link';
				if ($one_arr['id'] == $control->cid) {
					$link = 'nolink';
					if ($control->bid > 0) {
						$link = 'stronglink';
					}
				}

				if (count($contr_parents) > 0) {
					foreach ($contr_parents as $one_parent) {
						if ($one_parent == $one_arr['id']) {
							$link = 'stronglink';
						}
					}
				}

				if (count($one_arr['parents']) > 0) {

					$strok = '';
					foreach ($one_arr['parents'] as $parent) {
						if ($type == 'full') {
							$strok .= 'item'.$parent['level'].'['.$parent['parent'].']->';
						}
						if ($type == 'formap') {
							$strok .= 'item['.$parent['parent'].']->';
						}
					}

					eval("\$".$strok."title = '".$one_arr['title']."'; ");
					eval("\$".$strok."url = '<!--base_url//-->".$one_arr['url']."'; ");
					eval("\$".$strok."link = '".$link."'; ");
					eval("\$".$strok."level = '".$level."'; ");
					eval("\$".$strok."id = '".$one_arr['id']."'; ");
					eval("\$".$strok."template = '".$one_arr['template']."'; ");
				}
			}

			if ($type == 'full') {
				$page->items = $item0;
			}
			if ($type == 'formap') {
				$page->items = $item;
			}

			return $page->items[1];
		}
	}

	/**
	 *
	 * @param type $left_key
	 * @param type $right_key
	 * @return type
	 */
	function getParentsNew($left_key, $right_key) {
		$res2 = sql::query("SELECT id, `key`, level FROM prname_tree WHERE left_key <= '$left_key' AND right_key >= '$right_key' ORDER BY left_key ");
		$i = 0;
		while ($str2 = sql::fetch_array($res2)) {
			$parents[$i] = $str2['id'];
			$i++;
		}
		return $parents;
	}

    function getParentsItems($id, $min_level=0, $max_level=null) {
        $parents = array();

        $q = "SELECT left_key, right_key FROM prname_tree WHERE id = '$id' ";
        $str = sql::fetch_array(sql::query($q));
        $left_key = $str['left_key'];
        $right_key = $str['right_key'];

        if ($min_level || $max_level) {
            if ($min_level > 0 && $max_level) {
                $level = "and level between $min_level and $max_level";
            } elseif ($max_level) {
                $level = "and level <= $max_level";
            } else {
                $level = "and level >= $min_level";
            }
        } else {
            $level = '';
        }

        $q = "SELECT id, `key`, level,`name` FROM prname_tree WHERE left_key <= '$left_key' AND right_key >= '$right_key'  AND id!= 1 {$level} ORDER BY left_key ";
        $res2 = sql::query($q);
        $i = 0;
        while ($str2 = sql::fetch_array($res2)) {
            $parents[$i] = $str2['name'];
            $i++;
        }

        return $parents;
    }
	/**
	 *
	 * @param type $super
	 * @return type
	 */
	function admin_tree_all($super) {
		$q = sql::query("SELECT p1.*, p3.hidestructure as hs, p3.virtual as virtual FROM prname_tree p1, prname_ctemplates p3 where p1.template=p3.key ORDER BY p1.left_key");

		$i = 0;

		while ($b = mysql_fetch_assoc($q)) {

			if ($b['level'] < 1) {
				$a->item[$i]->name = $b['name'];
				$a->item[$i]->template = $b['template'];
				$a->item[$i]->visible = $b['visible'];
				$a->item[$i]->parent = $b['parent'];
				$a->item[$i]->id = $b['id'];
				$a->item[$i]->key = $b['key'];
				$a->item[$i]->level = $b['level'];
				$a->item[$i]->hs = $b['hs'];
				$a->item[$i]->url = $b['url'];
				$a->item[$i]->virtual = $b['virtual'];

				$a->item[$i]->class = $i == 0 ? "Первый раздел всего меню сайта" : "Первый раздел ветки";
				$level = $b['level'];
				$last_id = $b['id'];
				$c[$b['level']] = & $a->item[$i];
				$allid[$b['id']] = $b['id'];
			}
			else {
				if ($super == 0 && $b['key'] == 'manage') {
					$manageKey = $b['id'];
					continue;
				}
				if (isset($manageKey) && $b['parent'] == $manageKey) {
					continue;
				}
				$c[$b['level'] - 1]->item[$i]->name = $b['name'];
				$c[$b['level'] - 1]->item[$i]->template = $b['template'];
				$c[$b['level'] - 1]->item[$i]->visible = $b['visible'];
				$c[$b['level'] - 1]->item[$i]->parent = $b['parent'];
				$c[$b['level'] - 1]->item[$i]->id = $b['id'];
				$c[$b['level'] - 1]->item[$i]->key = $b['key'];
				$c[$b['level'] - 1]->item[$i]->level = $b['level'];
				$c[$b['level'] - 1]->item[$i]->hs = $b['hs'];
				$c[$b['level'] - 1]->item[$i]->url = $b['url'];
				$c[$b['level'] - 1]->item[$i]->virtual = $b['virtual'];


				$c[$b['level'] - 1]->item[$i]->class = "Последний в своей ветке";

				if ($level > $b['level'])
					$c[$b['level']]->class = '"Это раздел не последний в своей ветке и имеет вложения';
				if ($level == $b['level'])
					$c[$level]->class = 'Раздел не имеет вложений он не первый но и не последний';
				if ($level < $b['level'])
					$c[$level]->class = '"Этот раздел имеет вложение '.$c[$level]->class;



				$level = $b['level'];
				$last_id = $b['id'];
				$allid[$b['id']] = $b['id'];
				$c[$b['level']] = & $c[$b['level'] - 1]->item[$i];
			}
			$i++;
		}
		return $a;
	}

	/**
	 *
	 * @return type
	 */
	function getSiteMap() {
		$q = sql::query("SELECT tree.*, ct.blocktypes as bt FROM prname_tree tree, prname_ctemplates ct where tree.template=ct.key AND ct.virtual=0 ORDER BY tree.left_key");

		$i = 0;

		while ($b = mysql_fetch_assoc($q)) {

			if ($b['level'] < 1) {
				$a->item[$i]->name = $b['name'];
				$a->item[$i]->template = $b['template'];
				$a->item[$i]->parent = $b['parent'];
				$a->item[$i]->id = $b['id'];
				$a->item[$i]->key = $b['key'];
				$a->item[$i]->level = $b['level'];
				$a->item[$i]->url = $b['url'];

				/* Блоки */
				$bt = $b['bt'];
				$bt = explode(" ", $bt);

				foreach ($bt as $val) {
					if ($val != "") {
						$list = new Listing($val, "blocks", $b['id']);
						$list->getList();
						$list->getItem();
						$a->item[$i]->blocks[]->item = $list->item;
					}
				}


				$level = $b['level'];
				$last_id = $b['id'];
				$c[$b['level']] = & $a->item[$i];
				$allid[$b['id']] = $b['id'];
			}
			else {
				if ($b['key'] == 'manage') {
					$manageKey = $b['id'];
					continue;
				}
				if (isset($manageKey) && $b['parent'] == $manageKey) {
					continue;
				}
				$c[$b['level'] - 1]->item[$i]->name = $b['name'];
				$c[$b['level'] - 1]->item[$i]->template = $b['template'];
				$c[$b['level'] - 1]->item[$i]->parent = $b['parent'];
				$c[$b['level'] - 1]->item[$i]->id = $b['id'];
				$c[$b['level'] - 1]->item[$i]->key = $b['key'];
				$c[$b['level'] - 1]->item[$i]->level = $b['level'];
				$c[$b['level'] - 1]->item[$i]->url = $b['url'];

				/* Блоки */
				$bt = $b['bt'];
				$bt = explode(" ", $bt);

				foreach ($bt as $val) {
					if ($val != "") {

						$virtual = sql::one_record("SELECT virtual FROM prname_btemplates WHERE `key`='".$val."'");
						if ($virtual)
							continue;
						$list = new Listing($val, "blocks", $b['id']);
						$list->getList();
						$list->getItem();
						$c[$b['level'] - 1]->item[$i]->blocks[]->item = $list->item;
					}
				}



				$level = $b['level'];
				$last_id = $b['id'];
				$allid[$b['id']] = $b['id'];
				$c[$b['level']] = & $c[$b['level'] - 1]->item[$i];
			}
			$i++;
		}
		return $a;
	}

}

?>