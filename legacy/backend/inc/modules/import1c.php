<?php

class import1c
{

    public function __construct()
    {
        global $control;


        echo "in import\r\n";
		
        if ($control->oper == 'getXml') {
			//exit();
			$this->getXml();
        } else {
			//exit();
			$this->importClass();
        }
    }

    private function getXml()
    {
        header("Content-Type: text/xml");
        header("Expires: Thu, 19 Feb 1998 13:24:18 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: post-check=0,pre-check=0");
        header("Cache-Control: max-age=0");
        header("Pragma: no-cache");

        $users_to_xml = sql::query("SELECT `email`, `phone`, `Address`, `Code` FROM `it_b_user`");
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<DATATOSITE>';
        while ($row = mysqli_fetch_assoc($users_to_xml)) {
            $code = $row["Code"];
            $address = $row["Address"];
            $email = $row["email"];
            $phone = $row["phone"];
            if (!empty($code)) {
                $xml .= '<Infocard Code="' . $code . '" Adress="' . $address . '" Email="' . $email . '" Telephone="' . $phone . '"/>';
            }
        }
        $xml .= "</DATATOSITE>";
        echo $xml;

        die();
    }

    private function importClass()
    {
        global $config, $control;
        date_default_timezone_set("Asia/Yekaterinburg");

        if (!isset($_GET['filename'])) {
            $fileName = "from1c.xml";
        } else {
            $fileName = trim($_GET['filename'], "/");
        }

        $file = DOC_ROOT . "/images/" . $fileName;

        if (!file_exists($file)) die ("noFile");

        $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml) die("noXml");

        set_time_limit(0);

        // Creation Parents
        $creationParents = array();

        foreach ($xml->CreationParents as $item) {
            $name = (string)$item->attributes()->Name;
            $code = (string)$item->attributes()->Code;
            $parentCode = (string)$item->attributes()->ParentCode;
            $parentCode = trim($parentCode);
            if ($parentCode == "") {
                if (!isset($creationParents[$code])) $creationParents[$code] = new stdClass();
                $creationParents[$code]->name = $name;
            }
        }

        foreach ($xml->CreationParents as $item) {
            $name = (string)$item->attributes()->Name;
            $code = (string)$item->attributes()->Code;
            $parentCode = (string)$item->attributes()->ParentCode;
            $parentCode = trim($parentCode);
            if ($parentCode != "") {
                if (array_key_exists($parentCode, $creationParents)) {
                    if (!isset($creationParents[$parentCode]->item[$code])) $creationParents[$parentCode]->item[$code] = new stdClass();
                    $creationParents[$parentCode]->item[$code]->name = $name;
                }
            }
        }

        //require_once(DOC_ROOT . "libs/passgen.php");

// error_reporting(E_ALL);
        foreach ($xml->Infocard as $item) {
            $fio = (string)$item->attributes()->Owner;
            $code = (string)$item->attributes()->Code;
            $phone = (string)$item->attributes()->Telephone;
            $address = (string)$item->attributes()->Adress;
            $email = (string)$item->attributes()->Email;
            $discount = (string)$item->attributes()->Discount;

            $check_user_exists_by_code = sql::query("SELECT `name`,`Email` FROM `it_b_user` WHERE `Code`='$code'");
            $check_user_exists_by_mail = sql::query("SELECT `name` FROM `it_b_user` WHERE `Email`='$email'");
            $check_user_exists_by_code = sql::fetch_row($check_user_exists_by_code);
            $check_user_exists_by_mail = sql::fetch_row($check_user_exists_by_mail);
            if ($check_user_exists_by_mail[0]) {
//				sql::query("UPDATE `it_b_user` SET
//						parent=29,
//						`name`='$fio',
//						`login`='$email',
//					    `Phone`='$phone',
//						`Address`='$address',
//						`Code`= '$code',
//                        `discountshop` = '$discount' WHERE `Email`='$email'"
//				);
            } else if ($check_user_exists_by_code[0]) {
//				$readable_pass = passgen::generatePassword();
//				$password = md5($readable_pass . $config['md5']);
//				sql::query("UPDATE `it_b_user` SET
//						parent=29,
//						`name`='$fio',
//						`login`='$email',
//					    `Phone`='$phone',
//                        `password` = '$password',
//						`Address`='$address',
//						`Email`='$email',
//                        `discountshop` = '$discount' WHERE `Code`=$code");
                if ((!empty($email)) && ($check_user_exists_by_code[1] != $email)) {
//					echo "test2\r\n";
//					print_r($check_user_exists_by_code);
//					print_r("-----");
//					print_r($email);
//					print_r("-----");
//					print_r($code);
//					echo "\r\n";
//					echo "test22\r\n";
//					$mailpage->theme = "Данные для доступа на сайт " . $control->settings->sitename;
//					$sitename = $control->settings->sitename;
//					// Урл сайта
//					$mailpage->siteUrl = "http://maestria.bagetural.ru";
//					// Реквизиты входа
//					$mailpage->email = $email;
//					$mailpage->password = $readable_pass;
//					$mailpage->name = $fio;
//
//					// Генерация текста для письма (задается из админки)
//					$mailInfo = all::b_data_all(21, "sitetext");
//					$mailInfo->text = nl2br($mailInfo->text);
//					$mailInfo->text = str_replace("{name}", $mailpage->name, $mailInfo->text);
//					$mailInfo->text = str_replace("{email}", $mailpage->email, $mailInfo->text);
//					$mailInfo->text = str_replace("{password}", $mailpage->password, $mailInfo->text);
//					$mailInfo->text = str_replace("{siteUrl}", $mailpage->siteUrl, $mailInfo->text);
//					$mailpage->text = $mailInfo->text;
//
////					echo $mailpage->text;
//					$msg = sprintt($mailpage, DOC_ROOT . 'mailtemplates/touser/register_from_xml.html');
////					echo "test222\r\n";
//
//					all::send_mail($email, $mailpage->theme, $msg, false, false, "$sitename");
////					echo "test223\r\n";
//
                }
            } else {
//				echo "test3\r\n";
//				print_r($email);
//				echo "\r\n";
//				$readable_pass = passgen::generatePassword();
//				$password = md5($readable_pass . $config['md5']);
//				sql::query("INSERT INTO `it_b_user`(
//                        `parent`,
//                        `blockparent`,
//                        `sort`,
//                        `visible`,
//                        `name`,
//                        `login`,
//                        `password`,
//                        `email`,
//                        `active`,
//                        `subscribe`,
//                        `discountshop`,
//                        `modified`,
//                        `Address`,
//                        `Phone`,
//                        `Code`
//						)
//					VALUES(
//						29,
//						0,
//						0,
//						1,
//						'$fio',
//						'$email',
//						'$password',
//						'$email',
//						1,
//						1,
//						$discount,
//						 now(),
//						'$address',
//						'$phone',
//						'$code'
//						)");
//
//				// Отправка письма зарегистрированному пользователю
//				if (!empty($email)) {
//					$mailpage->theme = "Данные для доступа на сайт " . $control->settings->sitename;
//					$sitename = $control->settings->sitename;
//					// Урл сайта
//					$mailpage->siteUrl = "http://maestria.bagetural.ru";
//					// Реквизиты входа
//					$mailpage->email = $email;
//					$mailpage->password = $readable_pass;
//					$mailpage->name = $fio;
//
//					// Генерация текста для письма (задается из админки)
//					$mailInfo = all::b_data_all(21, "sitetext");
//					$mailInfo->text = nl2br($mailInfo->text);
//					$mailInfo->text = str_replace("{name}", $mailpage->name, $mailInfo->text);
//					$mailInfo->text = str_replace("{email}", $mailpage->email, $mailInfo->text);
//					$mailInfo->text = str_replace("{password}", $mailpage->password, $mailInfo->text);
//					$mailInfo->text = str_replace("{siteUrl}", $mailpage->siteUrl, $mailInfo->text);
//					$mailpage->text = $mailInfo->text;
//
//					$msg = sprintt($mailpage, DOC_ROOT . '/mailtemplates/touser/register_from_xml.html');
//
//					all::send_mail($email, $mailpage->theme, $msg, false, false, "$sitename");
//
//				}
            }
        }


        // Creations
        $creations = array();
        foreach ($xml->Creation as $item) {
            $name = (string)$item->attributes()->Name;
            $code = (string)$item->attributes()->Code;
            $parentCode = (string)$item->attributes()->ParentCode;
            $parentCode = trim($parentCode);
            $creations[$code] = $name;

            if (array_key_exists($parentCode, $creationParents)) {
                $creationParents[$parentCode]->item[$code]->name = $name;
            } else {
                if (is_array($creationParents))
                    foreach ($creationParents as $key => $val) {
                        if (is_array($val->item))
                            foreach ($val->item as $key2 => $val2) {
                                if ($key2 == $parentCode) {
                                    if (!isset($creationParents[$key]->item[$key2]->item[$code])) $creationParents[$key]->item[$key2]->item[$code] = new stdClass();
                                    $creationParents[$key]->item[$key2]->item[$code]->name = $name;
                                    continue 2;
                                }
                            }
                    }
            }
        }

        // Выбираем все разделы творчества
        $sql = "SELECT code FROM prname_b_tgroup";
        $query = sql::query($sql);
        $tgroups = array();

        while ($res = sql::fetch_assoc($query)) {
            $tgroups[] = $res['code'];
        }
        $exists_tgroups = array();


//		debug("\t\t start  creationParents");

        foreach ($creationParents as $key => $val) {
//			debug("\t\t if for1");
            $val->name = htmlspecialchars(sql::escape_string($val->name));
            if (in_array($key, $tgroups)) {
                sql::query("UPDATE prname_b_tgroup SET name='$val->name', parentcode=0 WHERE code='$key'");
//				 debug("Updated block");
            } else {
                $array = array();
                $array['name'] = $val->name;
                $array['code'] = $key;
                $array['parentcode'] = 0;
                all::insert_block("tgroup", 51, $array, 1);
//				 debug("Inserted block");
            }
                $exists_tgroups[] = $key;


            if ($val->item) {
                foreach ($val->item as $key2 => $val2) {
                    $val2->name = htmlspecialchars(sql::escape_string($val2->name));
//					debug("\t\t if for2");
                    if (in_array($key2, $tgroups)) {
//						debug("\t\t update in 1");
                        sql::query("UPDATE prname_b_tgroup SET name='$val2->name', parentcode='$key' WHERE code='$key2'");
//						 debug("\tUpdated block with parent1");
                    } else {
                        $array = array();
                        $array['name'] = $val2->name;
                        $array['code'] = $key2;
                        $array['parentcode'] = $key;
                        all::insert_block("tgroup", 51, $array, 1);
//						 debug("\tInserted block with parent1");
                    }
                        $exists_tgroups[] = $key2;

                    if (is_array($val2->item))
                        foreach ($val2->item as $key3 => $val3) {
                            $val3->name = htmlspecialchars(sql::escape_string($val3->name));
                            if (in_array($key3, $tgroups)) {
//							debug("\t\t update in 2: "."UPDATE prname_b_tgroup SET name='$val3->name', parentcode='$key2' WHERE code='$key3'");
                                sql::query("UPDATE prname_b_tgroup SET name='$val3->name', parentcode='$key2' WHERE code='$key3'");
//							 debug("\t\tUpdated block with parent2");
                            } else {
                                $array = array();
                                $array['name'] = $val3->name;
                                $array['code'] = $key3;
                                $array['parentcode'] = $key2;
                                all::insert_block("tgroup", 51, $array, 1);
//							 debug("\t\tInserted block with parent2");
                            }
                                $exists_tgroups[] = $key3;
                        }
                }
            }
        }
        foreach ($tgroups as $key) {
            if (!in_array($key, $exists_tgroups)) {
                sql::query("UPDATE prname_b_tgroup SET visible = 0 WHERE code = '{$key}'");
            }
        }
//		debug("\t\t close  creationParents");

        sql::query("UPDATE prname_b_catitem SET flagexist=0");
        sql::query("UPDATE prname_b_variant SET flagexist=0");

        // Товары
        $i = 1;
        $total = count($xml->Goods);
        $percent = 0;

        $time = array();


        $k = 0;
        foreach ($xml->Goods as $item) {
            $time[$k][] = microtime(true);

            // Creation
            $creation = (string)$item->attributes()->Creation;

            // Остаток
            $ballance = (string)$item->attributes()->Balance;

            if (!$creation && strpos($ballance, "Офис") < 0) {
                continue;
            }


            // Название
            $name = (string)$item->attributes()->Name;
            $name = addslashes(htmlspecialchars($name));

            $subitems = false;

            if (strpos($name, "#") > -1) {
                $subitems = true;
                $tmpname = explode("#", $name);

                $name = $tmpname[0];
                $name = trim($name);
                $name = trim($name, ",");

                $subname = $tmpname[1];
                $subname = trim($subname);
                $subname = trim($subname, ",");
            }

            $description = (string)$item->attributes()->Description;
            $description = htmlspecialchars(sql::escape_string($description));

            // 1 отсечка
            $time[$k][] = microtime(true);


            // Код
            $code = (string)$item->attributes()->Code;
            $code = trim($code);

            // Похожие товары
            $related = (string)$item->attributes()->Related;

            // Бренд
            $brand = (string)$item->attributes()->Brand;
            $brand = addslashes($brand);

            // Цена
            $price = (string)$item->attributes()->Price;
            $price = str_replace(",", ".", $price);
            $price = str_replace(" ", "", $price);

            //Sale
            $sale = (string)$item->attributes()->Sale;

            if ($sale) {
                $old_price = $price . "";
                $price = $price - (int)$price / 100 * $sale;
            } else {
                $old_price = "";
            }

            //New
            $new = (string)$item->attributes()->New;

            // 2 отсечка
            $time[$k][] = microtime(true);


            // Горячая доставка
            $warmdelivery = (string)$item->attributes()->WarmDelivery;


            // Изображение
            $imageValue = "";
            $image = (string)$item->attributes()->Image;
            if ($image) {
                if (!file_exists(DOC_ROOT . "images/real/0/$image") || filemtime(DOC_ROOT . "images/$image") > filemtime(DOC_ROOT . "images/real/0/$image")) {
			if (copy(DOC_ROOT . "images/$image", DOC_ROOT . "images/real/0/$image"))	{
	                    $this->createTh($image);
			}
                }
                $imageValue .= $image;
            }

            $image1 = (string)$item->attributes()->Image1;
            if ($image1) {
                if (!file_exists(DOC_ROOT . "images/real/0/$image1") || filemtime(DOC_ROOT . "images/$image1") > filemtime(DOC_ROOT . "images/real/0/$image1")) {
                    copy(DOC_ROOT . "images/$image1", DOC_ROOT . "images/real/0/$image1");
                    $this->createTh($image1);
                }
                $imageValue .= ";" . $image1;
            }

            $image2 = (string)$item->attributes()->Image2;
            if ($image2) {
                if (!file_exists(DOC_ROOT . "images/real/0/$image2") || filemtime(DOC_ROOT . "images/$image2") > filemtime(DOC_ROOT . "images/real/0/$image2")) {
                    copy(DOC_ROOT . "images/$image2", DOC_ROOT . "images/real/0/$image2");
                    $this->createTh($image2);
                }
                $imageValue .= ";" . $image2;
            }

            $image3 = (string)$item->attributes()->Image3;
            if ($image3) {
                if (!file_exists(DOC_ROOT . "images/real/0/$image3") || filemtime(DOC_ROOT . "images/$image3") > filemtime(DOC_ROOT . "images/real/0/$image3")) {
                    copy(DOC_ROOT . "images/$image3", DOC_ROOT . "images/real/0/$image3");
                    $this->createTh($image3);
                }
                $imageValue .= ";" . $image3;
            }

            // 3 отсечка
            $time[$k][] = microtime(true);


            $parent = 40;

            if (strpos($ballance, "Офис") > -1) {
                // Багетная мастерская
                $parent = 53;
            }

            // 4 отсечка
            $time[$k][] = microtime(true);

            // Если товар с подвидами - выясняем есть ли у нас сам родитель товара
            $resSubs = false;
            if ($subitems) {
                $resSubs = sql::one_record("SELECT id FROM prname_b_catitem WHERE name='$name'");
                $blockparent = $resSubs;
            }

            // 5 отсечка
            $time[$k][] = microtime(true);

            // Если нету товара
            if (!$resSubs) {

                $res = sql::one_record("SELECT id FROM prname_b_catitem WHERE code='$code'");
                if ($res) {
                    $goodsImage = sql::one_record("SELECT image FROM prname_b_catitem WHERE id='$res'");
                    if ($imageValue != "" && !$goodsImage) {
                        $sql = "UPDATE prname_b_catitem SET
							flagexist=1,
							name='$name',
							ballance='$ballance',
							creation='$creation',
							brand='$brand',
							price='$price',
							oldprice='$old_price',
							related='$related',
							image='$imageValue',
							warmdelivery=$warmdelivery,
							description='$description',
							parent=$parent,
							`action`='$sale',
							`newitem`='$new'
							WHERE code='$code'";
                    } else {
                        $sql = "UPDATE prname_b_catitem SET
							flagexist=1,
							name='$name',
							ballance='$ballance',
							creation='$creation',
							brand='$brand',
							price='$price',
							oldprice='$old_price',
							related='$related',
							warmdelivery=$warmdelivery,
							description='$description',
							parent=$parent,
							`action`='$sale',
							`newitem`='$new'
							WHERE code='$code'";
                    }


                    sql::query($sql);

                    $blockparent = $res;
                } else {
                    $sort = sql::one_record("SELECT MAX(sort) FROM prname_b_catitem");
                    if (!$sort) $sort = 1;
                    $sort++;

                    sql::query("INSERT INTO prname_b_catitem(
						flagexist,
						name,
						code,
						creation,
						brand,
						ballance,
						price,
						oldprice,
						related,
						image,
						warmdelivery,
						description,
						parent,
						visible,
						sort,
						action,
						newitem
						)
					VALUES(
						1,
						'$name',
						'$code',
						'$creation',
						'$brand',
						'$ballance',
						'$price',
						'$old_price',
						'$related',
						'$imageValue',
						$warmdelivery,
						'$description',
						$parent,
						1,
						$sort,
						$sale,
						$new)"
                    );

                    $blockparent = sql::insert_id();
                }
            } else {
                $goodsImage = sql::one_record("SELECT image FROM prname_b_catitem WHERE id='$resSubs'");
                if ($imageValue != "" && !$goodsImage) {
                    $sql = "UPDATE prname_b_catitem SET
						flagexist=1,
						name='$name',
						ballance='$ballance',
						creation='$creation',
						brand='$brand',
						price='$price',
						oldprice='$old_price',
						related='$related',
						image='$imageValue',
						warmdelivery=$warmdelivery,
						description='$description',
						parent=$parent,
							`action`='$sale',
							`newitem`='$new'
						WHERE id=$resSubs";
                } else {
                    $sql = "UPDATE prname_b_catitem SET
						flagexist=1,
						name='$name',
						ballance='$ballance',
						creation='$creation',
						brand='$brand',
						price='$price',
						oldprice='$old_price',
						related='$related',
						warmdelivery=$warmdelivery,
						description='$description',
						parent=$parent,
							`action`='$sale',
							`newitem`='$new'
						WHERE id=$resSubs";
                }


                sql::query($sql);

                $blockparent = $resSubs;
            }

            // 6 отсечка
            $time[$k][] = microtime(true);

            // Виды товара
            if ($subitems) {
                $res = sql::one_record("SELECT id FROM prname_b_variant WHERE code='$code'");

                $description = strip_tags($description);


                if ($res) {
                    $sql = "UPDATE prname_b_variant SET flagexist=1, name='$subname', blockparent=$blockparent, description='$description', price='$price',
                        oldprice='$old_price', ballance='$ballance' WHERE id=$res";
                    sql::query($sql);
                } else {
                    $array = array();
                    $array['name'] = $subname;
                    $array['code'] = $code;
                    $array['description'] = $description;
                    $array['ballance'] = $ballance;
                    $array['price'] = $price;
                    $array['oldprice'] = $old_price;
                    $array['image'] = $image;
                    $array['flagexist'] = 1;
                    all::insert_block("variant", 0, $array, 1, $blockparent);
                }
            }

            // 7 отсечка
            $time[$k][] = microtime(true);

            $k++;


            $newPercent = ceil($i / $total * 100) . "%";

            if ($newPercent > $percent || $i == 1) {
                echo($percent . "\n");
            }

            $i++;

            $percent = $newPercent;

            $k++;
        }

        foreach ($time as $key => $val) {
            $i = 1;
            while (count($val) > 1) {
                $a = $val[0];
                $b = $val[1];
                $c = $b - $a;
                $c = intval($c * 10000);

                debug("Step " . $i . ": " . $c . " ms");
                array_shift($val);

                $i++;
            }
            break;
        }


        sql::query("DELETE FROM prname_b_catitem WHERE flagexist=0");
        sql::query("DELETE FROM prname_b_variant WHERE flagexist=0");

        // Проходимся по подвидам товаров
        sql::query("UPDATE prname_b_catitem SET ballance=1 WHERE id IN (SELECT blockparent FROM prname_b_variant WHERE ballance<>'')");

        require_once dirname(__FILE__) . '/../../libs/cache/php_fast_cache.php';
        $cache = new phpFastCache();
        $cache->cleanup();

        die("ok");
    }

    private function parseClass($node)
    {
        //Структура каталога
        foreach ($node as $item) {

            $name = (string)$item->attributes()->Name;
            $code = (string)$item->attributes()->Code;
            $parentCode = (string)$item->attributes()->ParentCode;

            if ($name == "") continue;

            if ($parentCode == "0") {
                $parent = 40;
            } else {
                $parent = sql::one_record("SELECT parent FROM prname_c_catgroup WHERE code='$parentCode'");
            }

            if (!$parent) {
                continue;
            }

            // Проверка на существование раздела
            $isset = sql::one_record("SELECT parent FROM prname_c_catgroup WHERE code='$code'");


            if (!$isset) {
                $sort = 1 + sql::one_record("SELECT MAX(sort) as msort FROM prname_categories WHERE parent='$parent'");
                $query = "INSERT INTO prname_categories (name, `key`, sort, visible, parent, template) VALUES ('$name', '', $sort, 1, $parent, 'catgroup')";

                sql::query($query);

                $lastId = sql::one_record("SELECT MAX(id) AS mid FROM prname_categories WHERE parent='$parent'");
                $query = "INSERT INTO prname_c_catgroup set `parent`='$lastId', `code`='$code'";
                sql::query($query);

            } else {
                $query = "UPDATE prname_categories SET parent=$parent, name='$name' WHERE id=$isset";
                $lastId = $isset;
                sql::query($query);
            }
        }
    }

    private function createTh($filename)
    {

        $n = strrpos($filename, ".");
        $ext = substr($filename, $n);

        $ext = strtolower($ext);

        // check if jpg
        if ($ext == '.jpg' || $ext == '.jpeg') {
            $format = 'JPG';
        } // check if png
        elseif ($ext == '.png') {
            $format = 'PNG';
        } else {
            return;
        }

        $lastSegment = explode("/", $filename);
        $lastSegment = $lastSegment[0];

        $filename = trim($filename, "\/");
        $fileFrom = DOC_ROOT . "images/real/0/$filename";
        $fileTo1 = DOC_ROOT . "images/real/1/";
        $fileTo2 = DOC_ROOT . "images/real/2/";
		$wmPath =  DOC_ROOT . "images/real/wm/wm.png";

        if (file_exists($fileFrom)) {
            // "mogrify -resize 452x378 -background white -gravity center -extent 452x378 -path /var/www/web/sites/maestria.bagetural.ru/images/real/1/ /var/www/web/sites/maestria.bagetural.ru/images/real/0/80893794-d450-11e4-80cf-485b39b8f0d4.jpg"

           // exec("mogrify -resize 452x378 -background white -gravity center -extent 452x378 -path $fileTo1 $fileFrom");
            exec("mogrify -resize 452x378 -background white -gravity center -extent 452x378 -path $fileTo1 $fileFrom && composite -gravity south $wmPath $fileTo1$filename $fileTo1$filename");
//        file::checkDir(DOC_ROOT."/images/real/1/".$filename, 0777, true);


            exec("mogrify -resize 223x128 -background white -gravity center -extent 223x128 -path $fileTo2 $fileFrom");

//        file::checkDir(DOC_ROOT."/images/real/2/".$filename, 0777, true);
        }
    }
}

?>