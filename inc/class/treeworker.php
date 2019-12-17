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
class Treeworker {

	private function find($id) {
		$found = sql::fetch_assoc(sql::query("SELECT * FROM prname_tree WHERE id=$id"));
		if ($found != null) {
			return $found;
		}
		return false;
	}

	public function insertAsFirstChildOf($node, $root = null) {

		//Если рут не задана - то значит рут - корневая нода
		if ($root == null) {
			$root = self::find(1);
		}		
		if ($root == false) return false;		
			
		//Начальные значения рутноды
		$lft = $root['left_key'];
		$rgt = $root['right_key'];
		$level = $root['level'];
		$rootId = $root['id'];

		//Инициализация массивов
		$props = array();	//Массив полей для вставки
		$values = array();	//Массив значений для вставки
		
		//Если вставлять нечего - то и не надо
		if (count($node) < 1) return false;

		//Если есть чаго - наполняем наши массивы
		foreach ($node as $prop => $value) {
			$props[] = $prop;
			$values[] = $value;
		}

		//Добавляем в массив свойств служебные поля		
		$props = array_merge(array("lft", "rgt", "level"), $props);

		//Вычисляем значеия служебных полей для вставки*/
		$newLft = $lft + 1;
		$newRgt = $newLft + 1;
		$newLevel = $level + 1;

		//Добавляем в массив значений служебные значения
		$values = array_merge(array($newLft, $newRgt, $newLevel), $values);
		
		
		//Подготовка к вставке - меняем левые и правые ключи у кого надо
		sql::query("UPDATE prname_tree SET left_key = left_key+2 WHERE left_key>=$newLft");
		sql::query("UPDATE prname_tree SET right_key = right_key+2 WHERE left_key>=$lft OR right_key>=$newRgt");
		
		
		//Вставляем саму ноду
		$str = "";
		if (is_array($props)) {
			$str .= " (";			
			foreach ($props as $name) {
				$str .= "`".$name."`,";
			}
			$str = trim($str, ",");
			$str .= ") ";
		}
		else $str .= " ";

		$str .= "VALUES (";
		foreach ($values as $value) {
			$str .= "'".$value."',";
		}
		$str = trim($str, ",");
		$str .= ")";

		sql::query("INSERT INTO prname_tree $str");
		

		//Реторним id вставленной ноды
		return sql::insert_id();
	}

	public function insertAsLastChildOf($node, $root = null) {
		
		/*Если рут не задана - то значит рут - корневая нода*/
		if ($root == null) {
			$root = self::find(1);
		}		
		if ($root == false) return false;
			
		//Начальные значения рутноды
		$lft = $root['left_key'];
		$rgt = $root['right_key'];
		$level = $root['level'];
		$rootId = $root['id'];

		//Инициализация массивов
		$props = array();	//Массив полей для вставки
		$values = array();	//Массив значений для вставки
		
		//Если вставлять нечего - то и не надо
		if (count($node) < 1) return false;

		//Если есть чаго - наполняем наши массивы
		foreach ($node as $prop => $value) {
			$props[] = $prop;
			$values[] = $value;
		}

		//Добавляем в массив свойств служебные поля		
		$props = array_merge(array("lft", "rgt", "level"), $props);
		
		//Вычисляем значения служебных полей для вставки
		$newLft = $rgt;
		$newRgt = $newLft + 1;		
		$newLevel = $level + 1;

		//Добавляем в массив значений служебные значения
		$values = array_merge(array($newLft, $newRgt, $newLevel), $values);

		//Подготовка к вставке - меняем левые и правые ключи у кого надо
		sql::query("UPDATE prname_tree SET left_key = left_key+2 WHERE left_key>$newLft");
		sql::query("UPDATE prname_tree SET right_key = right_key+2 WHERE right_key>=$newLft");

		//Вставляем саму ноду
		$str = "";
		if (is_array($props)) {
			$str .= " (";			
			foreach ($props as $name) {
				$str .= "`".$name."`,";
			}
			$str = trim($str, ",");
			$str .= ") ";
		}
		else $str .= " ";

		$str .= "VALUES (";
		foreach ($values as $value) {
			$str .= "'".$value."',";
		}
		$str = trim($str, ",");
		$str .= ")";

		sql::query("INSERT INTO prname_tree $str");
		
		//Реторним id вставленной ноды
		return sql::insert_id();
	}	
	
	
	//Фунция удаления ноды с вытекающими последствиями
	public function delete($node) {

		//Если нечего удалять, то и не надо
		if (!$node) {throw new exception("Нода гавно"); return;}
		else {
			$node = self::find($node);
		}

		//Начальные значения рутноды
		$lft = $node['left_key'];
		$rgt = $node['right_key'];
		$level = $node['level'];
		$id = $node['id'];
	
		//Проходим все страницы, подходящие для удаления, чтобы удалить связки с блоками, а также связанные файлы
		$allPages = sql::query("SELECT id, template FROM prname_tree WHERE left_key>=".$lft." AND right_key<=".$rgt);

		while ($page = sql::fetch_assoc($allPages)) {
			$template = $page['template'];
			$parent = $page['id'];

			/*Выборка полей с типом "файл" для удаления привязанных файлов*/
			$res = sql::query("SELECT temp.*, datarel.key as dkey FROM prname_cdatarel datarel, prname_c_".$template." temp, prname_ctemplates temps WHERE datarel.datatkey='file' AND datarel.templid=temps.id AND temps.key='".$template."' AND parent=".$parent);

			while ($row = sql::fetch_assoc($res)) {
				$toDel = $row['dkey'];
				
				$path = DOC_ROOT."/";
				@unlink($path."files/0/" . $row[$toDel]);
				@unlink($path."files/1/" . $row[$toDel]);
				@unlink($path."files/2/" . $row[$toDel]);
				@unlink($path."files/3/" . $row[$toDel]);
				@unlink($path."files/4/" . $row[$toDel]);
				@unlink($path."files/5/" . $row[$toDel]);
				@unlink($path."files/6/" . $row[$toDel]);
				@unlink($path."files/7/" . $row[$toDel]);
				@unlink($path."files/8/" . $row[$toDel]);
				@unlink($path."files/9/" . $row[$toDel]);
				
			}

			sql::query("DELETE FROM prname_c_".$template." WHERE parent=".$parent);
			
			//Удаляем Блоки
			$btemplates = sql::one_record("SELECT blocktypes FROM prname_ctemplates WHERE `key`='".$template."'");
			$btemplates = preg_split("# #", $btemplates, null, PREG_SPLIT_NO_EMPTY);		

			
			if (count($btemplates) > 0) {
				foreach ($btemplates as $val) {
					$blocks = sql::query("SELECT * FROM prname_b_".$val." WHERE parent=".$parent);
					
					while ($res = sql::fetch_assoc($blocks)) {
						delete_block($res['id'], $val);
					}				
				}
			}		
		}
		//Конец прохода по страницам



		


		$count = $rgt - $lft +  1;		
		
		//Удаление собственно самой ноды и всех ее детей		
		sql::query("DELETE FROM prname_tree WHERE left_key>=$lft AND right_key<=$rgt");
		
		
		//Последующее смещение ключей
		sql::query("UPDATE prname_tree SET left_key = left_key - $count WHERE left_key>=$lft");
		sql::query("UPDATE prname_tree SET right_key = right_key - $count WHERE left_key>=$lft OR right_key>=$rgt");
		
		return;
	}

	public function moveTo ($node, $toNode) {
		//Если нечего перемещать, то и не надо
		if (!$node) {throw new exception("Нода переноса не установлена"); return;}
		else {
			$node = self::find($node);
		} 

		//Если некуда перемещать, то и не надо
		if (!$toNode) {throw new exception("Точка переноса не установлена"); return;}
		else {
			$toNode = self::find($toNode);
		}

		//Начальные значения  перемещаемой ноды
		$lft = $node['left_key'];
		$rgt = $node['right_key'];
		$level = $node['level'];
		$id = $node['id'];

		//Начальные значения перемещаемой ноды
		$toLft = $toNode['left_key'];
		$toRgt = $toNode['right_key'];
		$toLevel = $toNode['level'];
		$toId = $toNode['id'];


		if ($toLft < $lft && $toRgt > $rgt && $toLevel < $level - 1) {
            $sql = "UPDATE prname_tree SET 
			level = CASE WHEN left_key BETWEEN $lft AND $rgt THEN level".sprintf('%+d', -($level-1)+$toLevel) . " ELSE level END, 
            right_key = CASE WHEN right_key BETWEEN ".($rgt+1)." AND ".($toRgt-1)." THEN right_key - ".($rgt-$lft+1)." 
            WHEN left_key BETWEEN $lft AND $rgt THEN right_key + ".((($toRgt-$rgt-$level+$tolevel)/2)*2+$level-$toLevel-1)." ELSE right_key END, 
            left_key = CASE WHEN left_key BETWEEN ".($rgt+1)." AND ".($toRgt-1)." THEN left_key - ".($rgt-$lft+1)." 
            WHEN left_key BETWEEN $lft AND $rgt THEN left_key + ".((($toRgt-$rgt-$level+$toLevel)/2)*2+$level-$toLevel-1)." ELSE left_key END 
			WHERE left_key BETWEEN ".($toLft+1)." AND ".($toRgt-1);
        } elseif ($toLft < $lft) {
            $sql = "UPDATE prname_tree SET 
			level = CASE WHEN left_key BETWEEN $lft AND $rgt THEN level".sprintf('%+d', -($level-1)+$toLevel) . " ELSE level END, 
            left_key = CASE WHEN left_key BETWEEN $toRgt AND ".($lft-1)." THEN left_key + ".($rgt-$lft+1)." 
            WHEN left_key BETWEEN $lft AND $rgt THEN left_key - ".($lft-$toRgt)." ELSE left_key END, 
            right_key = CASE WHEN right_key BETWEEN $toRgt AND $lft THEN right_key + ".($rgt-$lft+1)." 
            WHEN right_key BETWEEN $lft AND $rgt THEN right_key - ".($lft-$toRgt)." ELSE right_key END 
            WHERE (left_key BETWEEN $toLft AND $rgt 
            OR right_key BETWEEN $toLft AND $rgt)";
        } else {
            $sql = "UPDATE prname_tree SET
			level = CASE WHEN left_key BETWEEN $lft AND $rgt THEN level".sprintf('%+d', -($level-1)+$toLevel) ." ELSE level END, 
			left_key = CASE WHEN left_key BETWEEN $rgt AND $toRgt THEN left_key - ($rgt-$lft+1) 
			WHEN left_key BETWEEN $lft AND $rgt THEN left_key + ".($toRgt-1-$rgt)." ELSE left_key END, 
			right_key = CASE WHEN right_key BETWEEN ".($rgt+1)." AND ".($toRgt-1)." THEN right_key - ".($rgt-$lft+1)." 
			WHEN right_key BETWEEN $lft AND $rgt THEN right_key + ".($toRgt-1-$rgt)." ELSE right_key END 
			WHERE ( left_key BETWEEN $lft AND $toRgt 
			OR right_key BETWEEN $lft AND $toRgt )";
        }

		debug($sql);

		sql::query($sql);

	}

	public function changePosition ($id1, $id2, $position = 'after') {
		//Если нечего перемещать, то и не надо
		if (!$id1) {throw new exception("Нода переноса не установлена"); return;}
		else {
			$node = self::find($id1);
		} 

		//Если некуда перемещать, то и не надо
		if (!$id2) {throw new exception("Точка переноса не установлена"); return;}
		else {
			$toNode = self::find($id2);
		}

		//Начальные значения  перемещаемой ноды
		$leftId1 = $node['left_key'];
		$rightId1 = $node['right_key'];
		$level1 = $node['level'];
		

		//Начальные значения ноды, куда перемещаем
		$leftId2 = $toNode['left_key'];
		$rightId2 = $toNode['right_key'];
		$level2 = $toNode['level'];
	

		if ('before' == $position) {
            if ($leftId1 > $leftId2) {
				$sql = "UPDATE prname_tree SET 
				 right_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN right_key - ($leftId1 - $leftId2) 
				 WHEN left_key BETWEEN $leftId2 AND ($leftId1 - 1) THEN right_key + ($rightId1 - $leftId1 + 1) ELSE right_key END, 
				 left_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN left_key - ($leftId1 - $leftId2) 
				 WHEN left_key BETWEEN $leftId2 AND ($leftId1 - 1) THEN left_key + ($rightId1 - $leftId1 + 1) ELSE left_key END 
				 WHERE left_key BETWEEN $leftId2 AND $rightId1";
			}
			else {
				$sql = "UPDATE prname_tree SET 
				 right_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN right_key + (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) 
				 WHEN left_key BETWEEN ($rightId1 + 1) AND ($leftId2 - 1) THEN right_key - (($rightId1 - $leftId1 + 1)) ELSE right_key END, 
				 left_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN left_key + (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) 
				 WHEN left_key BETWEEN ($rightId1 + 1) AND ($leftId2 - 1) THEN left_key - ($rightId1 - $leftId1 + 1) ELSE left_key END 
				 WHERE left_key BETWEEN $leftId1 AND ($leftId2 - 1)";
			}
		}
        if ('after' == $position) {
			if ($leftId1 > $leftId2) {
				$sql = "UPDATE prname_tree SET 
				right_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN right_key - ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) 
				WHEN left_key BETWEEN ($rightId2 + 1) AND ($leftId1 - 1) THEN right_key + ($rightId1 - $leftId1 + 1) ELSE right_key END, 
				left_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN left_key - ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) 
				WHEN left_key BETWEEN ($rightId2 + 1) AND ($leftId1 - 1) THEN left_key + ($rightId1 - $leftId1 + 1) ELSE left_key END 
				WHERE left_key BETWEEN ($rightId2 + 1) AND $rightId1";
			}
			else {
				$sql = "UPDATE prname_tree SET 
				right_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN right_key + ($rightId2 - $rightId1) 
				WHEN left_key BETWEEN ($rightId1 + 1) AND $rightId2 THEN right_key - (($rightId1 - $leftId1 + 1)) ELSE right_key END, 
				left_key = CASE WHEN left_key BETWEEN $leftId1 AND $rightId1 THEN left_key + ($rightId2 - $rightId1) 
				WHEN left_key BETWEEN ($rightId1 + 1) AND $rightId2 THEN left_key - ($rightId1 - $leftId1 + 1) ELSE left_key END 
				WHERE left_key BETWEEN $leftId1 AND $rightId2";
			}
		}
		

		sql::query($sql);

	}



	

}

?>