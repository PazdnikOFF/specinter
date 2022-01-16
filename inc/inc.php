<?php
include_once('libs/images.php');
foreach($_REQUEST as $k=>$v)$$k=$v;
foreach($_GET as $k=>$v)$$k=$v;
foreach($_POST as $k=>$v)$$k=$v;



//Дебаг
function debug($var) {
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}

//Дебаг
function debug2($var) {
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}


//Проверяет наличие файлов модуля
function module_prapare($module, $key) {
	if (!is_file('inc/modules/'.$module.'.php') || !is_file('templates/'.$key.'/'.$key.'.html'))
		generate_module($module, $key);
	return true;
}

//Создает файлы модуля
function generate_module($module, $key) {
	if ($module != 'temp1' && $module != 'temp2' && $module != 'temp3' && $module != 'temp4') {
		$dirName = 'inc/modules/';
		$content = str_replace('temp2', $key, file_get_contents("inc/modules/temp2.php"));
		file::createFile($module.'.php', $dirName, $content);
	}

	$dirName = 'templates/'.$key.'/';
	switch ($module) {
		case 'temp1':
			$content = str_replace('{module}', $key, file_get_contents("templates/temps/list.html"));
			file::createFile($key.'.html', $dirName, $content);
			break;

		case 'temp2':
			$content = str_replace('{module}', $key, file_get_contents("templates/temps/list.html"));
			file::createFile($key.'.html', $dirName, $content);

			$content2 = str_replace('{module}', $key, file_get_contents("templates/temps/one.html"));
			file::createFile($key.'_one.html', $dirName, $content2);
			break;

		case 'temp3':
			$content = str_replace('{module}', $key, file_get_contents("templates/temps/list.html"));
			file::createFile($key.'.html', $dirName, $content);
			break;

		default:
			$content = str_replace('{module}', $key, file_get_contents("templates/temps/list.html"));
			file::createFile($key.'.html', $dirName, $content);

			$content2 = str_replace('{module}', $key, file_get_contents("templates/temps/one.html"));
			file::createFile($key.'_one.html', $dirName, $content2);
			break;
	}
}

//Выбор определенного поля в таблице с юзером
function user_is($s, $userid = 0) {
	if ($s == 'super') return true;
	global $prname;
	global $config;
	if ($userid == 0) {
		$qq = "admin_name='".$_SESSION['admin_name']."' AND admin_password='".md5(base64_encode($config['md5'].$_SESSION['admin_password']))."'";
	}
	else {
		$qq = "admin_id=$userid";
	}
	$q = "SELECT `$s` FROM $prname"."_sadmin, $prname"."_rt WHERE aid = $prname"."_sadmin.admin_id AND $qq";

	$res = mysql_query($q);
	$row = mysql_fetch_array($res);
	return $row[$s];
}

//Сохраняет загруженный файл
function save_uploaded($source) {
	global $blocks_blocks;
	global $blocks_categories;
	global $blocks_sections;
	$ret = false;
	global $config;
	global ${$source};
	global ${$source."_name"};
	if (is_file(${$source})) {
		$name = explode('.', ${$source."_name"});
		$name = get_random_name() . "." . $name[count($name) - 1];
		copy(${$source}, $config['upload_dir'].$name);
		$ret = $name;
	}
	return $ret;
}

//Сохраняет загруженное изображение
function save_uploaded_image($source, $small) {
	global $blocks_blocks;
	global $blocks_categories;
	global $blocks_sections;

	$ret = false;

	global ${$source};
	global ${$source."_name"};
	global $config;
	$file = ${$source};
	$file_name = ${$source."_name"};
	if ($s = @getimagesize($file)) {
		$name = get_random_name().".jpg";
		if (($s[2] == 2) || ($s[2] == 3)) {
			if ($s[2] == 2) {
				$im = @imagecreatefromjpeg($file);
			}
			else {
				$im = @imagecreatefrompng($file);
			}

			if ($im) {
				$sn = shrink_dimensions($s);
				switch ($small) {
					case 0: imagejpeg($im, $config['upload_dir'].$name); break;
					case 1: $im_small = imagecreate($sn[0], $sn[1]);
						imagecopyresized($im_small, $im, 0, 0, 0, 0, $sn[0], $sn[1], $s[0], $s[1]);
						imagejpeg($im_small, $config['upload_dir']."small_".$name); break;
					case 2: $im_small = imagecreate($sn[0], $sn[1]);
						imagecopyresized($im_small, $im, 0, 0, 0, 0, $sn[0], $sn[1], $s[0], $s[1]);
						imagejpeg($im, $config['upload_dir'].$name);
						imagejpeg($im_small, $config['upload_dir']."small_".$name); break;
				}
				$ret = $name;
			}
		}
		elseif (($s[2] == 1) && ($small == 0)) {
			copy($file, $config['upload_dir'].$name);
			$ret = $name;
		}
	}

	return $ret;
}

//Генерация рандомного имени
function get_random_name() {
	return time().(rand(0, 32767)*rand(0, 32767));
}


//Удаляет категорию
function delete_category($parent) {
	$info = sql::fetch_assoc(sql::query("SELECT * FROM prname_tree WHERE id=".$parent));

	$leftKey = $info['left_key'];
	$rightKey = $info['right_key'];

	$allPages = sql::query("SELECT id, template FROM prname_tree WHERE left_key>=".$leftKey." AND right_key<=".$rightKey);

	while ($page = sql::fetch_assoc($allPages)) {
		$template = $page['template'];
		$parent = $page['id'];

		//Выборка полей с типом "файл" для удаления привязанных файлов
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

		sql::query("DELETE FROM prname_categories WHERE id=".$parent);
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
}



//Копирует блок
function dublicate_block($parent, $templ, $newparent) {
	global $prname;
	$v = mysql_query("select * from $prname"."_b_".$templ." where `parent`='$parent'");
	$vd = mysql_query("select p1.* from $prname"."_bdatarel p1,  $prname"."_btemplates p2 where p1.templid =p2.id and p2.key='$templ'");
	while ($fr = mysql_fetch_array($vd))
		$keyses[$fr[key]] = $fr[datatkey];
	$sort =  mysql_result(mysql_query("select MAX(sort) from $prname"."_b_".$templ." where `parent`='$parent'"), 0, 0);
	$c = mysql_query("describe  $prname"."_b_".$templ);

	while ($r = mysql_fetch_array($v)) {
		$str = "insert into  $prname"."_b_".$templ." set ";
		while ($keys = mysql_fetch_assoc($c)) {
			if ($keyses[$keys['Field']] != 'file' && $keys['Field'] !== 'id' && $keys['Field'] !== 'sort' && $keys['Field'] !== 'parent') {
				$str .= "`".$keys['Field']."` = '".$r[$keys['Field']]."' ,";
			}
		}
		$str .= "`sort` = '".++$sort."' , `parent`='$newparent'";
	}
	mysql_query($str);
}


//Удаляет блок
function delete_block($parent, $templ) {

	$q  = sql::query("DESCRIBE prname_b_$templ");


	while ($arr_f = sql::fetch_assoc($q)) {


		if (sql::fetch_row(sql::query("SELECT `key` FROM prname_bdatarel WHERE `key` ='$arr_f[Field]' AND `datatkey`= 'file'"), 0, 1)) {
			$data = sql::fetch_row(sql::query("SELECT $arr_f[Field] FROM prname_b_$templ WHERE id=$parent"), 0, 1);

			$path = DOC_ROOT."/";
			@unlink($path."files/0/" . $data);
			@unlink($path."files/1/" . $data);
			@unlink($path."files/2/" . $data);
			@unlink($path."files/3/" . $data);
			@unlink($path."files/4/" . $data);
			@unlink($path."files/5/" . $data);
			@unlink($path."files/6/" . $data);
			@unlink($path."files/7/" . $data);
			@unlink($path."files/8/" . $data);
			@unlink($path."files/9/" . $data);
		}



		if ($comm = sql::fetch_row(sql::query("SELECT `comment` FROM prname_bdatarel WHERE `key` ='$arr_f[Field]' AND `datatkey`= 'items'"), 0, 1)) {

			$data = sql::query("SELECT id from prname_b_$comm WHERE blockparent=$parent");
			while ($arr_b = sql::fetch_row($data, 0, 1)) {
				delete_block($arr_b, $comm);
			}
		}
	}

	$sort = sql::one_record("SELECT sort FROM prname_b_$templ WHERE id=$parent");
	

	sql::query("DELETE FROM prname_b_$templ WHERE id=$parent");
    if($sort && $sort != ''){
        sql::query("UPDATE prname_b_$templ SET sort = sort-1 WHERE sort>$sort");
    }
}

//Ресайз изображения
function resize_image($data, $val, $resized, $cropData=false) {
	$mimes = explode("x", $val);
	$image = new sys_images(DOC_ROOT."/files/0/".$data);


	if ($cropData) {
		$image = new sys_images(DOC_ROOT."/files/3/".$data);
		$image->crop($cropData['x1'], $cropData['y1'], $cropData['w'], $cropData['h']);
	}

	if (isset($mimes[3]) && $mimes[3] == 'realcrop' && !$cropData) {
		$image->resize(890, 2000);
		$image->save(DOC_ROOT."/files/3/".$data);
	}

	//Квадратное кадрирование
	if (isset($mimes[3]) && $mimes[3] == 'crop') {
		$size = GetImageSize(DOC_ROOT."/files/0/".$data);
		if ($size[0] < $size[1]) {
			$image->crop(0, 0, $size[0], $size[0]);
		}
		else {
			$image->crop(0, 0, $size[1], $size[1]);
		}
	}

	$image->resize($mimes[0], $mimes[1]);
	$image->save(DOC_ROOT."/files/".$resized."/".$data);
}

//обновляет изображение
function update_images($fn, $resized) {
	global $prname;
	if ($resized < 1) return false;
	$q = "SELECT * FROM $prname"."_data WHERE data='$fn'";
	$res = mysql_query($q);
	$row = mysql_fetch_array($res);
	$q = "SELECT comment FROM $prname"."_".(($row['blockid'] > 0)?'b':'c')."datarel WHERE `key`='".addslashes($row['relkey'])."'";
	$res2 = mysql_query($q);
	if (($comment = @mysql_result($res2, 0, 0)) === false) {
		return false;
	}
	if (($n = strpos(strtolower($comment), 'resize:')) === false) {
		return false;
	}
	if (($n2 = strpos($comment, ' ', $n)) == false) {
		$n2 = strlen($comment);
	}

	$n += 7;

	$val2 = substr($comment, $n, $n2 - $n);
	$sz = explode(",", $val2);
	if ($resized > count($sz)) {
		return false;
	}
	return resize_image($fn, $sz[$resized - 1], $resized);
}

function btemplate_num_fields($s) {
	global $prname;
	$res = mysql_query("SELECT COUNT(r.id) FROM $prname"."_bdatarel r, $prname"."_btemplates t WHERE t.key='".addslashes($s)."' AND t.id=r.templid");
	return mysql_result($res, 0, 0);
}



function ctemplate_num_fields($s) {
	global $prname;
	$res = mysql_query("SELECT COUNT(r.id) FROM $prname"."_cdatarel r, $prname"."_ctemplates t WHERE t.key='".addslashes($s)."' AND t.id=r.templid");
	return mysql_result($res, 0, 0);
}

//Автолоад
function __autoload($className) {
	if (substr($className, 0, 5) == 'type_') {
		require_once("datatypes/".substr($className, 5).".php");
	}
	if (file_exists("inc/modules/".$className.".php")) {
		require_once("inc/modules/".$className.".php");
	}
	else return false;
}

//Функция для определения прав на редактирование контента прямо на сайте
function checkUserRights($type, $template, $id) {
	if (!isset($_SESSION['admin_name'])) return false;
	if ($_SESSION['admin_status'] == 1) return true;

	if ($type == "cat") $type = "c";
	else $type = "b";

	if ($_SESSION['admin_status'] == 2 || $_SESSION['admin_status'] == 3) {
		$can = sql::one_record("SELECT `canedit` FROM prname_".$type."templates WHERE `key`='".$template."'");
		if ($can == 1) {
			if ($_SESSION['admin_status'] == 3) {
				if (strstr($_SESSION['admin_canedit'], ';'.$id.';')) {
					return true;
				}
				else return false;
			}
			return true;
		}
		return false;
	}
}


function tree_create($id = false, $addq = '', $hidestructure = false) {
	do {
		$prefix = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).time();
	} while (isset(${$prefix.'stree'}));
	if ($id === false) $id = 0;
	global ${$prefix.'stree'};
	global $prname;
	${$prefix.'stree'} = array();
	${$prefix.'stree'}[0] = 0;
	$pars = false;
	if ($hidestructure && ($hidestructure !== true)) {
		$pars = array($id2 = $hidestructure);
		while ($id2 > 0) {
			array_push($pars, $id2 = mysql_result(mysql_query("SELECT parent FROM $prname"."_categories WHERE id=$id2"), 0, 0));
		}
	}
	addcats($id, 0, $prefix, $addq, $pars, $hidestructure);
	return $prefix;
}

function tree_next($prefix) {
	global ${$prefix.'stree'};
	return (${$prefix.'stree'}[0] < (count(${$prefix.'stree'}) - 1)) ? ${$prefix.'stree'}[++${$prefix.'stree'}[0]] : false;
}

function tree_prev($prefix) {
	global ${$prefix.'stree'};
	return (${$prefix.'stree'}[0] > 0) ? ${$prefix.'stree'}[${$prefix.'stree'}[0]-- - 1] : false;
}

function tree_tofirst($prefix) {
	global ${$prefix.'stree'};
	${$prefix.'stree'}[0] = 0;
}

function tree_itemno($n, $prefix) {
	global ${$prefix.'stree'};
	return ${$prefix.'stree'}[$n + 1];
}

function tree_tolast($prefix) {
	global ${$prefix.'stree'};
	${$prefix.'stree'}[0] = count(${$prefix.'stree'} - 2);
}

function tree_pos($prefix) {
	global ${$prefix.'stree'};
	return ${$prefix.'stree'}[0];
}

function tree_count($prefix) {
	global ${$prefix.'stree'};
	return count(${$prefix.'stree'}) - 1;
}

function tree_path($id) {
	global $prname;
	$res = mysql_query("SELECT * FROM $prname"."_categories WHERE id=$id");
	if ($row = mysql_fetch_array($res)) $s = " / ".strip_tags($row['name']);
	while ($row['parent'] > 0) {
		$res = mysql_query("SELECT * FROM $prname"."_categories WHERE id=".$row['parent']);
		$row = mysql_fetch_array($res);
		$s = " / " . strip_tags($row['name']) . $s;
	}
	$s = "Структура" . $s;
	return $s;
}

function tree_item($os = 0, $prefix) {
	global ${$prefix.'stree'};
	$pos = ${$prefix.'stree'}[0] + $os;
	if (($pos >= (count(${$prefix.'stree'}) - 1)) || ($p < 0)) {
		return false;
	}
	else return ${$prefix.'stree'}[$pos + 1];
}

function addcats($id, $lev, $prefix, $addq, $pars, $hidestructure) {
	global ${$prefix.'stree'};
	global $prname;
	$q = "SELECT c.*, t.hidestructure FROM $prname"."_categories c, $prname"."_ctemplates t WHERE c.parent=$id AND c.template=t.key AND c.id>0 $addq ORDER BY c.sort";
	$res = mysql_query($q);
	while ($row = mysql_fetch_array($res)) {
		$row['lev'] = $lev + 1;
		if (is_array($pars)) $can = in_array($row['id'], $pars) || ($row['lev'] >= count($pars)); else $can = true;
		if ($can) {
			if ($hidestructure === false) $can = true;
			elseif (($hidestructure === true) || ($row['lev'] >= @count($pars))) $can = $row['hidestructure'] < 1; else $can = true;
			if (!$can) $row['catcount'] = mysql_result(mysql_query("SELECT COUNT(id) FROM $prname"."_categories WHERE parent=".$row['id']), 0, 0);
			array_push(${$prefix.'stree'}, $row);
			if ($can) addcats($row['id'], $lev + 1, $prefix, $addq, $pars, $hidestructure);
		}
	}
}

function splstr($ss, $delim) {
	$s = array();
	$inq = false;
	$n = 1;
	for ($i = 0; $i < strlen($ss); $i++) {
		$c = substr($ss, $i, 1);
		if ($i == (strlen($ss) - 1)) {$c2 = '';} else {$c2 = substr($ss, $i+1, 1);};
		if (($c == $delim) && (!$inq)) {$n++;}
		elseif (($c == '"') && ($c2 != '"')) {$inq = !$inq;}
		elseif (($c == '"') && ($c2 == '"')) {$i++; $s[$n] .= $c;}
		else {$s[$n] .= $c;};
	}
	return $s;
}

?>