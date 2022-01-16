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
 * Класс выборки
 */
class Listing
{
	/**
	 * Поле для сортировки
	 * @var string
	 */
	public $sortfield = '';

	/**
	 * Направление сортировки
	 * @var string
	 */
	public $sortby = 'ASC';

	/**
	 * Флаг отображение всех блоков, в не только видимых
	 * @var boolean
	 */
	public $on = 'true';

	/**
	 * Лимит выборки
	 * @var int
	 */
	public $limit = 0;

	/**
	 * Номер текущей страницы
	 * @var int
	 */
	public $page = 0;

	/**
	 * Большой шаг для постранички
	 * @var int
	 */
	public $globalStep = 600;

	/**
	 * Количество подходящих под условия строк (без лимита)
	 * @var int
	 */
	public $count = 0;

	/**
	 * родитель
	 * @var int
	 */
	private $parent;

	/**
	 * Из чего нужно выборку - из блоков, вложенных блоков или папок
	 * @var string  blocks or items or cats
	 */
	private $type = 'blocks';

	/**
	 * Шаблон
	 * @var string
	 */
	private $template;

	/**
	 * Старт выборки для текущей страницы
	 * @var int
	 */
	private $start = 0;

	/**
	 * Выбранные строки
	 * @var array
	 */
	private $items = array();

	/**
	 * Группировка
	 * @var string
	 */
	private $groupBy = "";

	/**
	 * Собственный sql
	 * @var string
	 */
	private $megaSql = "";

	/**
	 * Собственный sql на количество, неотъемлемо от $megaSql
	 * @var string
	 */
	private $megaSqlCount = "";


	/**
	 * Отменяет рекурсию при выборке, в которой один блок может быть привязан к другому, и соответственно наоборот
	 * @var boolean
	 */
	private $preventRecursion = false;


	/**
	 * Инициализирует выборку
	 * @global object $control
	 * @global object $sql
	 * @param string $template Шаблон блока/папки
	 * @param string $type Тип(blocks, items, cats)
	 * @param mixed $cid Родитель
	 * @param string $critery Критерий для sql
	 */
	function __construct($template, $type = 'blocks', $cid = '', $critery = '', $preventRecursion = false, $groupBy = '')
	{
		global $control;
		global $sql;

		$this->blockparent = $cid ? $cid : $control->cid;
		if($type == 'items'){
            $this->parent = $cid == 'all' ? '' : "blockparent = '$cid' and";
        }else{
            $this->parent = $cid == 'all' ? '' : "parent = '$cid' and";
        }

		$this->cid = $cid;
		$this->template = $template;
		$this->critery = $critery;
		$this->preventRecursion = $preventRecursion;

		if (strpos($critery, "GROUP BY") !== false) {
			$this->groupBy = $this->critery;
			$this->critery = "";
		} elseif ($groupBy) {
            $this->groupBy = $groupBy;
        }

		if ($this->on == 'false') {
			$this->on = '(visible=1 OR visible=0)';
		} else {
			$this->on = '`visible`="1"';
		}

		$this->type = $type;

		if ($this->type == 'blocks') {
			$d = sql::query("select p2.* from prname_btemplates p1, prname_bdatarel p2 where p1.key = '$this->template' and p2.templid=p1.id");
			while ($arr = sql::fetch_assoc($d)) {
				$this->fields[$arr['key']]->datatkey = $arr['datatkey'];
				$this->fields[$arr['key']]->comment = $arr['comment'];
			}
		}

		if ($this->type == 'cats') {
			$d = sql::query("select p2.* from prname_ctemplates p1, prname_cdatarel p2 where p1.key = '$this->template' and p2.templid=p1.id");
			while ($arr = sql::fetch_assoc($d)) {
				$this->fields[$arr['key']]->datatkey = $arr['datatkey'];
				$this->fields[$arr['key']]->comment = $arr['comment'];
			}
		}

		if ($this->type == 'items') {
			$d = sql::query("select p2.* from prname_btemplates p1, prname_bdatarel p2 where p1.key = '$this->template' and p2.templid=p1.id");

			while ($arr = sql::fetch_assoc($d)) {
				$this->fields[$arr['key']]->datatkey = $arr['datatkey'];
				$this->fields[$arr['key']]->comment = $arr['comment'];
			}
		}


	}

	/**
	 * Функция делает первичную выборку
	 * @access public
	 */
	public function getList()
	{
		if ($this->on == 'false') {
			$this->on = '(visible=1 OR visible=0)';
		} else {
			$this->on = '`visible`="1"';
		}

		if (isset($this->page)) {
			$this->start = 0 + $this->page * $this->limit;
		}

		if ($this->type == 'blocks') {

			// Общее количество
			$q = "SELECT count(id) FROM prname_b_$this->template WHERE $this->parent $this->critery $this->on";
			$sql = "select * from prname_b_$this->template where $this->parent $this->critery $this->on $this->groupBy " . "order by " . ($this->sortfield && $this->sortfield !== 'sort' ? ($this->sortfield) : 'sort') . " $this->sortby" . "" . ($this->limit ? ' limit ' . ($this->start ? $this->start : '0') . ', ' . $this->limit : '') . "";

			$this->items = sql::query($sql);
		
			
			// if (isset($_COOKIE['vas-vas'])) {
			// 	var_dump(	$this->items);
			// }

		}

		if ($this->type == 'cats') {

			// Общее количество
			$q = "SELECT count(id) FROM prname_categories WHERE $this->parent $this->on AND template = '$this->template' ";

			$sql = "select p1.*,p2.name as page_name from prname_c_$this->template p1, prname_tree p2 where p2.parent='$this->cid' and $this->critery  p2.visible = '1' and p1.parent=p2.id   " . ($this->sortfield ? 'order by p1.' . $this->sortfield . ' ' . $this->sortby : ' order by p2.sort') . " " . ($this->limit ? ' limit ' . ($this->start ? $this->start : '0') . ', ' . $this->limit : '') . "";
		

			$this->items = sql::query($sql);
		}

		if ($this->type == 'items') {
			/*Общее количество*/
			$q = "SELECT count(id) FROM prname_b_$this->template WHERE `blockparent`='$this->blockparent' and $this->critery $this->on";

			$sql = "select * from prname_b_$this->template  where $this->parent $this->critery  $this->on  " . ($this->sortfield ? 'order by ' . $this->sortfield . ' ' . $this->sortby : ' order by sort') . " " . ($this->limit ? ' limit ' . ($this->start ? $this->start : '0') . ', ' . $this->limit : '');
			
			$this->items = sql::query($sql);
		}

		$this->count = sql::one_record($q);

	}

	/**
	 * Функция делает чистовую обработку выборки с учетом типов полей шаблона
	 * @access public
	 */

	public function getItem($items = false, $fields = false)
	{
		$i = 0;
		$tr = 0;


		if ($items) {
			$this->items = $items;
			$this->fields = $fields;
		}
		
		while ($one_arr = sql::fetch_assoc($this->items)) {


			if (isset($this->row) && $tr == $this->row) {
				$tr = 0;
				$this->item[$i]->tr = true;
			} else {
				$tr++;
				$this->item[$i]->tr = false;
			}

			$ak = array_keys($one_arr);

			if (!isset($this->url[$one_arr['parent']]) && !isset($this->is_global)) {
				$this->url[$one_arr['parent']] = all::getUrl($one_arr['parent']);

			}

			if ($this->type !== 'cats') {
				$this->item[$i]->url = "";
				if (isset($this->url[$one_arr['parent']]) && $this->url[$one_arr['parent']] != "") {
					$this->item[$i]->url = $this->url[$one_arr['parent']];
				} else {
					$this->item[$i]->url = $this->tmp_url;
				}



				if (isset($this->page))
					$addPage = $this->page;
				else
					$addPage = "";

				if ($this->type == 'c')
					$addType = $one_arr['parent'];
				else
					$addType = $one_arr['id'];

				$this->item[$i]->url .= all::addUrl('', 'view', $addType, '', '');

				if (isset($one_arr['uurl']) && $one_arr['uurl'] != '') {
					$this->item[$i]->url = "/" . $one_arr['uurl'];
				}

			}

			if ($this->type == 'cats') {
				$this->item[$i]->url = $this->url[$one_arr['parent']];
			}

			$lastS = substr($this->item[$i]->url, -1, 1);
			if ($lastS != "/") {
				$this->item[$i]->url .= "/";
			}


		

			for ($ii = 0; $ii < count($ak); $ii++) {

				if (!isset($this->fields[$ak[$ii]]))
					$this->fields[$ak[$ii]]->datatkey = "";
				if ($ak[$ii] == 'price' || $ak[$ii] == 'oldprice' && !empty($one_arr[$ak[$ii]])) {
					
					
					# Price value must be a float type. Not a string!
					// $one_arr[$ak[$ii]] = ceil(str_replace(',', '.', $one_arr[$ak[$ii]]));
					$one_arr[$ak[$ii]] = (float)str_replace(',', '.', $one_arr[$ak[$ii]]);
				}

				switch ($this->fields[$ak[$ii]]->datatkey) {

					case 'html' :
						$this->item[$i]->$ak[$ii] = all::parseHtml($one_arr['' . $ak[$ii] . ''], 0);
						break;
					case 'textarea':
						$this->item[$i]->$ak[$ii] = str_replace(array(chr(13)), "<br/>", $one_arr['' . $ak[$ii] . '']);
						break;
					case 'select':
						$this->item[$i]->{$ak[$ii] . '_sel'} = $one_arr['' . $ak[$ii] . ''];
						$d = explode(":", $this->fields[$ak[$ii]]->comment);

						if ((($n = strpos($one_arr['' . $ak[$ii] . ''], "allblocks:")) !== false) || (($n3 = strpos($one_arr['' . $ak[$ii] . ''], "visblocks:")) !== false) || (($n4 = strpos($one_arr['' . $ak[$ii] . ''], "hidblocks:")) !== false)) {
							$this->item[$i]->{$ak[$ii] . '_id'} = $one_arr['' . $ak[$ii] . ''];
							$this->item[$i]->$ak[$ii] = sql::fetch_row(sql::query("select `" . $d[1] . "` from prname_b_" . $d[4] . " where `id`='" . $one_arr['' . $ak[$ii] . ''] . "'"), 0, 1);
						}
						break;

					case 'date':
						$this->item[$i]->{$ak[$ii]} = $one_arr['' . $ak[$ii] . ''];
						$this->item[$i]->{$ak[$ii] . '_1'} = all::getDate($one_arr['' . $ak[$ii] . ''], 1);
						$this->item[$i]->{$ak[$ii] . '_2'} = all::getDate($one_arr['' . $ak[$ii] . ''], 2);
						break;

					case 'file':
						$this->item[$i]->$ak[$ii] = $one_arr['' . $ak[$ii] . ''];
						if (($n = strpos($this->fields[$ak[$ii]]->comment, 'resize:')) !== false) {
							$fn1 = explode('resize:', $this->fields[$ak[$ii]]->comment);
							$fs = explode(',', $fn1[1]);


							for ($if = 0; $if < count($fs); $if++) {
								if (is_file('files/' . $if . '/' . $one_arr[$ak[$ii]])) {
									if (!is_file('files/' . ($if + 1) . '/' . $one_arr['' . $ak[$ii] . ''])){
                                        //resize_image($one_arr['' . $ak[$ii] . ''], $fs[$if], $if + 1, '',false);
                                    }

								}
							}
						}
						break;


					case 'imageload':
						$dataimageload = splstr($one_arr['' . $ak[$ii] . ''], ";");
						foreach ($dataimageload as $dkey => $dval) {
							if ($dval != "") {
								$dataArray[]->image = $dval;
							}
						}
						$this->item[$i]->$ak[$ii] = $dataArray;
						unset($dataArray);

						break;

					case 'mselect':
						$datadd = splstr($one_arr['' . $ak[$ii] . ''], ";");


						$comment = $this->fields[$ak[$ii]]->comment;


						if (strpos($comment, ";") > -1) {


							$ddCounter = 0;
							foreach ($datadd as $dkey => $dval) {
								$item[$ddCounter]->name = $dval;
								$ddCounter++;
							}
							$this->item[$i]->$ak[$ii] = $item;
						} else {
							$cri = "(";

							foreach ($datadd as $vall) {
								$cri .= "id=" . $vall . " OR ";
							}

							if ($cri != "(") {
								$cri = substr($cri, 0, strlen($cri) - 3);
								$cri .= ") AND ";
							} else {
								$cri = "";
							}


							$blockk = explode(":", $this->fields[$ak[$ii]]->comment);
							$block = $blockk[4];
							$parent = $blockk[3];

							//parent в виде переменной ($parent)
							if ($parent == "$parent") {
								$parent = "all";
							}


							if ($cri != "" && !$this->preventRecursion) {
								$res = new Listing($block, "blocks", $parent, $cri, true);
								$res->getList();
								$res->getItem();
								$this->item[$i]->$ak[$ii] = $res->item;
							} else {
								$this->item[$i]->$ak[$ii] = null;
							}
						}

						$item = null;

						break;

					case 'items':
						$this->item[$i]->$ak[$ii] = array();


						$b = new Listing($this->fields[$ak[$ii]]->comment, 'items', $one_arr['id']);
						$b->sortfield = $this->sortfield;
						$b->sortby = $this->sortby;
						$b->getList();
						$b->getItem();

						$this->item[$i]->$ak[$ii] = $b->item;
						break;

					default:
						$this->item[$i]->$ak[$ii] = $one_arr['' . $ak[$ii] . ''];
						break;
				}


				if ($this->type == 'blocks') {
					$this->item[$i]->utemplate = $this->template;
				}

			}

			$i++;
		}
	}

	/**
	 * Функция строит объект для постраничного разбиения
	 * @return mixed
	 */
	public function getPage()
	{
		global $control;

		if ($this->limit == 0)
			return;
		$rescount = ceil($this->count / $this->limit);

		if ($rescount > 1) {
			for ($i = 0; $i < $rescount; $i++) {
				$this->pages[$i]['num'] = $i;
				$this->pages[$i]['current'] = $i == $this->page ? '1' : '';
			}
		}

		$globalStep = $this->globalStep;
		$co_list = $this->page;
		$p = floor($co_list / $globalStep) * $globalStep;

		// query string
		foreach ($_GET as $gkey => $gval) {
			$get[$gkey] = trim($gval, "/");
		}
		$filterQuery = "?" . http_build_query($get);
//		print_r($filterQuery);
		if ($filterQuery == "?") {
			$filterQuery = "";
		}


		$i = 0;
		if (all::getVar("type") != "") {
			$this->tmp_url .= "_type" . all::getVar("type");
		}
		foreach ($this->pages as $val) {
			if ($i >= $p && $i < $p + $globalStep) {
				$this->navigation[$val['num']]->title = ($val['num'] + 1);
				$this->navigation[$val['num']]->num = $val['num'];
				$this->navigation[$val['num']]->page = $val['num'] + 1;
				$this->navigation[$val['num']]->current = $val['current'];
//				print_r($val);
//				print_r($this->tmp_url);
				$url = ($tmpurl ? $tmpurl : $this->tmp_url) . ($tmpurl ? (str_replace('/', '', all::addUrl($val['num'], $control->oper, $control->bid, $control->sort_f))) : (all::addUrl($val['num'], $control->oper, $control->bid, $control->sort_f)));

				if (substr($url, -1, 1) == "/") {
					$url = substr($url, 0, strlen($url) - 1);
				}


				$url .= $filterQuery . "/";
				$url = str_replace("//", "/", $url);

				$this->navigation[$val['num']]->url = $url;
			}
			$i++;
		}

		$this->next = $p + $shag;


		if ($this->page > $shag - 1) {
			$url = ($tmpurl ? $tmpurl : $this->tmp_url) . all::addUrl($p - $shag, $control->oper, $control->bid, $control->sort_f);
			if (substr($url, -1, 1) == "/") {
				$url = substr($url, 0, strlen($url) - 1);
			}
			$url .= $filterQuery . "/";
			$url = str_replace("//", "/", $url);

			$this->url_last = $url;

		}
		if ($this->next < count($this->pages)) {
			$url = ($tmpurl ? $tmpurl : $this->tmp_url) . all::addUrl($this->next, $control->oper, $control->bid, $control->sort_f);
			if (substr($url, -1, 1) == "/") {
				$url = substr($url, 0, strlen($url) - 1);
			}
			$url .= $filterQuery . "/";
			$url = str_replace("//", "/", $url);

			$this->url_next = $url;
		}

		// last page
		$url = ($tmpurl ? $tmpurl : $this->tmp_url) . all::addUrl(count($this->pages) - 1, $control->oper, $control->bid, $control->sort_f);
		if (substr($url, -1, 1) == "/") {
			$url = substr($url, 0, strlen($url) - 1);
		}
		$url .= $filterQuery . "/";
		$url = str_replace("//", "/", $url);

		$this->last_page = $url;


		// first page
		$url = ($tmpurl ? $tmpurl : $this->tmp_url) . all::addUrl("", $control->oper, $control->bid, $control->sort_f);
		if (substr($url, -1, 1) == "/") {
			$url = substr($url, 0, strlen($url) - 1);
		}
		$url .= $filterQuery . "/";
		$url = str_replace("//", "/", $url);

		$this->first_page = $url;


		// previous page
		if ($this->page > 0) {
			$url = ($tmpurl ? $tmpurl : $this->tmp_url) . all::addUrl($this->page - 1, $control->oper, $control->bid, $control->sort_f);
			if (substr($url, -1, 1) == "/") {
				$url = substr($url, 0, strlen($url) - 1);
			}
			$url .= $filterQuery . "/";
			$url = str_replace("//", "/", $url);

			$this->url_p = $url;
		}

		// next page
		if ($this->page < count($this->pages) - 1) {
			$url = ($tmpurl ? $tmpurl : $this->tmp_url) . all::addUrl($this->page + 1, $control->oper, $control->bid, $control->sort_f);
			if (substr($url, -1, 1) == "/") {
				$url = substr($url, 0, strlen($url) - 1);
			}
			$url .= $filterQuery . "/";
			$url = str_replace("//", "/", $url);

			$this->url_n = $url;
		}

	}

}

?>