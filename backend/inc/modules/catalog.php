<?php

class catalog
{
    const ITEMS_COUNT = 8;

    public function __construct()
    {
        global $control;

        if (empty($_SESSION['user_name']) && $control->settings->close == 1) {
            $_SESSION["REAL_REFERER"] = $_SERVER["REQUEST_URI"];
            header('Location: http://' . $_SERVER['SERVER_NAME'] . '?login=1');
        }

        if ($control->bid) {
            $this->__showMethodNameForAdmin('_showGood');
            return $this->_showGood();
        } else if ($control->oper == 'view') {
            $this->__showMethodNameForAdmin('_showItem');
            return $this->_showItem($control->bid);
        } else {
            $this->__showMethodNameForAdmin('_showList');
            return $this->_showList();
        }

    }

    private function _showList()
    {
        global $control;
        $page = all::c_data_all($control->cid, 'catalog');
        $page->name = $control->name;

        $list = new Listing("catalog", "cats", $control->cid);
        $list->getList();
        $list->getItem();
        $page->items = $list->item;

//        if ($page->special_list == 1 || $_COOKIE['dev'] == "Y") {
//
//            $arts = new Listing("ablock", "blocks", $control->cid);
//            $arts->getList();
//            $arts->getItem();
//            $page->items = $arts->item;
//            //var_dump($page);
//            $this->html['text'] = sprintt($page, 'templates/catalog/special_list.html');
//        } else {

        if (!count($page->items)) {
            return $this->_showOne();
        }

        if ($control->cid == 40) {
            $this->html['text'] = sprintt($page, 'templates/catalog/catalog_main.html');
        } else {

            if ($page->list) {
                $this->html['text'] = sprintt($page, 'templates/catalog/catalog_special.html');
            } else {
                $this->html['text'] = sprintt($page, 'templates/catalog/catalog_sub.html');
            }

        }

        // }

    }

    private function _showOne()
    {

        global $control;
        $page = all::c_data_all($control->cid, 'catalog');

        $page->name = $control->name;
        $arts = new Listing("ablock", "blocks", $control->cid);
        $arts->getList();
        $arts->getItem();

        $arSort = $arNums = $good_ids = $urls = $nums = array();

        foreach ($arts->item as $aitem) {

            $good_ids[] = $aitem->good_id;
            $urls[$aitem->good_id] = $aitem->url;
            $nums[$aitem->good_id] = $aitem->num;
            $arNums[$aitem->good_id][] = $aitem->num;
            $arSort[$aitem->good_id][] = $aitem->sort;
            $sort[$aitem->good_id] = $aitem->sort;
        }


        if (!empty($good_ids)) {

            # via-profit
            # Setting a fuse for an SQL query that contained more than 2000 IN (...)
            $maxLimit = 100;
            $new = array();
            foreach ($good_ids as $key => $good_id) {
                $new[$good_id] = (int)$good_id;
            }
            if (count($good_ids) > $maxLimit + 1) {
//                $good_ids = array_slice($good_ids, 0, $maxLimit);
            }

            $list = new Listing("catitem", "blocks", '245', ' id in( ' . implode(',', $new) . ' ) and ');

            if ($page->special_list == 1) {
                $list->limit = 96;
                $list->page = $control->page;
            }
            $list->getList();
            if ($page->special_list == 1) {
                $url = all::getUrl($control->cid);
                $list->getPage();

                $navigation = new stdClass();

                $navigation->next = $url . $list->next;
                $navigation->url_last = $url . $list->url_last;
                $navigation->last_page = $url . $list->last_page;
                $navigation->url_next = $url . $list->url_next;
                $navigation->first_page = $url . $list->first_page;
                $navigation->url_n = $url . $list->url_n;
                $page->navigation = $navigation;
                $page->navigation_list = $list->navigation;


                if (count($page->navigation_list) > 9) {
                    $firstPage = $page->navigation_list[0];
                    $dataItem = new stdClass();
                    $dataItem->title = '...';
                    $dataItem->url = false;
                    $dataItem->active = 1;
                    $endPage = $page->navigation_list[count($page->navigation_list) - 1];
                    $needEnd = true;
                    if (count($page->navigation_list) - 5 < $control->page) {
                        $needEnd = false;
                    }
                    $offset = $control->page - 3;

                    $length = 7;
                    if ($offset < 0) {
                        $offset = 0;

                    }

                    $page->navigation_list = array_slice($page->navigation_list, $offset, $length);
                    if ($offset > 0) {
                        array_unshift($page->navigation_list, $dataItem);
                        array_unshift($page->navigation_list, $firstPage);
                    }
                    if ($needEnd) {
                        array_push($page->navigation_list, $dataItem);
                        array_push($page->navigation_list, $endPage);
                    }


                }

                foreach ($page->navigation_list as &$pagerItem) {

                    if ($pagerItem->num == $control->page) {
                        $pagerItem->active = 1;
                    } else {
                        $pagerItem->active = 0;
                    }
                    if ($pagerItem->url != false) {
                        $pagerItem->url = $url . $pagerItem->url;
                    }
                }


            }

            $list->getItem();

            $items = $list->item;


            foreach ($items as $key => &$real_item) {
                $items[$key]->real_num = $real_item->num;
                $items[$key]->url = $urls[$real_item->id];
                $items[$key]->num = $nums[$real_item->id];
            }
            

            $_items = array();
            foreach ($items as $item) {
                $_items[$item->id] = $item;
            }
            foreach ($good_ids as $key => $id) {
                if ($_items[$id]) {
                    $tmp = clone $_items[$id];
                    $page->items[] = $tmp;
                }
            }

            foreach ($page->items as $key => &$value) {
                $value->nnum = end($arNums[$value->id]);
                unset($arNums[$value->id][count($arNums[$value->id]) - 1]);

                $value->asot = end($arSort[$value->id]);
                unset($arSort[$value->id][count($arSort[$value->id]) - 1]);


                $art = $value->art;
                if(strlen($art) > 25 && strripos($art, '/') !== false){
 
                  $artParts = explode('/', $art, 3);
                  $delimeterCount = count($artParts)-1;
                  if($delimeterCount){
                    $art = $artParts[0].'/<br>'.$artParts[1];
                  }
                  else {
                    $art = $artParts[0].'/'.$artParts[1].'<br>'.$artParts[2];
                  }
                  

                  $value->art = $art;
                }

                $name_rus = $value->name_rus;
                $value->name_rus_title = $name_rus;
                if(strlen($name_rus) > 30){
                    $res = preg_split('##u', $name_rus, -1, PREG_SPLIT_NO_EMPTY);

                    $newstr = '';
                    $brokeTrigger = true;
                    foreach($res as $k => $v){

                        if($k >= 15 && $brokeTrigger && trim($v) == ''){

                            $newstr .= $v.'<br>';
                            $brokeTrigger = false;
                        }
                        else {
                            $newstr .= $v;
                        }
                    }

                    $value->name_rus = $newstr;
                  
                }
               
                
            }

            usort($page->items, function ($a, $b) {
                return $a->asot > $b->asot;
            });


        }

        if ($page->special_list == 1) {
            $this->html['text'] = sprintt($page, 'templates/catalog/special_list.html');
        } else {
            $this->html['text'] = sprintt($page, 'templates/catalog/catalog_one.html');
        }


    }

    private function _showItem($id)
    {
        $page = all::b_data_all($id, 'catitem');
        $this->html['text'] = sprintt($page, 'templates/catalog/catalog_item.html');
    }

    private function _showGood()
    {

        global $control;

        $_data = all::b_data_all($control->bid, 'ablock');
        $catalog =  all::c_data_all(40, 'catalog');;
        $page = all::b_data_all($_data->good_id, 'catitem');
        $ppage = all::c_data_all($control->cid, 'catalog');
        $page->catalog_img = $ppage->img;
        $page->num = $_data->num;
        $page->all_good = $catalog->all_good;
        $arts = new Listing("ablock", "blocks", 'all', ' good_id =' . (int)$page->id . ' and ');
        $arts->getList();
        $arts->getItem();
        if (!empty($arts->item)) {
//            $page->num = end($arts->item)->num;
        }

        $list = new Listing("goodimage", "items", $_data->good_id);
        $list->getList();
        $list->getItem();
        $page->images = $list->item;

        $list = new Listing("variant", "items", $_data->good_id);


        $list->getList();
        $list->getItem();
        $page->items = $list->item;



        if (is_null($page->items)) {
            $pItem = all::b_data_all($_data->good_id, 'catitem');
            $pItem->id = 'P' . $pItem->id;
            $page->items[] = $pItem;
        }


        $list = new Listing("aarts", "items", $_data->good_id);
        $list->getList();
        $list->getItem();

        $aarts = array_map(function ($obg) {
            if ($obg->good_id_arts) {
                return $obg->good_id_arts;
            }

        }, $list->item);

        $aarts = array_diff($aarts, array('', null, 0));

        $list = new Listing("aarts2", "items", $_data->good_id);
        $list->getList();
        $list->getItem();


        $aarts2 = array_map(function ($obg) {
            if ($obg->good_id_arts) {
                return $obg->good_id_arts;
            }

        }, $list->item);


        $aarts2 = array_diff($aarts2, array('', null, 0));


        if (!empty($aarts) && empty($page->items)) {
            $list = new Listing("variant", "items", 'all', ' blockparent in(' . implode(',', $aarts) . ') and ');
            $list->getList();
            $list->getItem();

            if (is_null($page->items)) {
                $page->items = array();
            }
            if (is_null($list->item)) {
                $list->item = array();
            }

            $tmp = array($page->items, $list->item);
            $page->items = array();
            foreach ($tmp as $_tmp) {
                foreach ($_tmp as $item) {
                    $page->items [] = $item;
                }
            }


        }

        if (!empty($aarts2)) {

            $list = new Listing("variant", "items", 'all', ' blockparent in(' . implode(',', $aarts2) . ') and ');
            $list->getList();
            $list->getItem();

            if (is_null($page->items2)) {
                $page->items2 = array();
            }

            if (is_null($list->item)) {
                $list->item = array();
            }


            $tmp = array($page->items2, $list->item);
            $page->items2 = array();
            foreach ($tmp as $_tmp) {
                foreach ($_tmp as $item) {
                    $page->items2 [] = $item;
                }
            }


        }

        $count = 0;



        $page->recomend = str_replace('P', '', $page->recomend);
        $_recommendGoods = explode(',', $page->recomend);

        $_recommendGoods = array_diff($_recommendGoods, array('', null));

        if (!empty($_recommendGoods)) {
            $res = sql::query('select id from prname_b_ablock where good_id in (' . implode(',', $_recommendGoods) . ') group by good_id');
            while ($data = sql::fetch_object($res)) {
                $recommendGoods[$data->id] = $data->id;
            }

            $count = count($recommendGoods);
        }


        if ($count < self::ITEMS_COUNT && !empty($recommendGoods)) {
            $res = sql::query('select id from prname_b_ablock where parent = ' . $_data->parent . ' and id NOT IN( ' . implode(',', array_merge($_recommendGoods, array($_data->id))) . ') and visible = 1 limit 0,' . (self::ITEMS_COUNT - $count));
            while ($data = sql::fetch_object($res)) {
                $recommendGoods[$data->id] = $data->id;
            }
            $count = count($recommendGoods);
        }


        if ($count < self::ITEMS_COUNT && !empty($recommendGoods)) {

            $res = sql::query('select id from prname_b_ablock where id NOT IN(' . implode(',', $recommendGoods) . ')  and id != ' . $_data->id . ' and visible = 1 limit 0,' . (self::ITEMS_COUNT - $count));

            while ($data = sql::fetch_object($res)) {
                $recommendGoods[$data->id] = $data->id;
            }
            $count = count($recommendGoods);
        }

        if (count($recommendGoods)) {
            $res = sql::query('select count(id) as count from prname_b_ablock where id  IN(' . implode(',', $recommendGoods) . ')  and id != ' . $_data->id);
            $data = sql::fetch_object($res);
            $count = $data->count;
        } else {
            $count = 0;
        }

        if ($count < self::ITEMS_COUNT) {
            if (empty($recommendGoods)) {
                $recommendGoods = array(0);
            }
            $res = sql::query('select id from prname_b_ablock where id NOT IN(' . implode(',', $recommendGoods) . ')  and id != ' . $_data->id . ' and visible = 1 limit 0,' . (self::ITEMS_COUNT - $count));
            while ($data = sql::fetch_object($res)) {
                $recommendGoods[] = $data->id;
            }
        }


        $res = sql::query('select good_id from prname_b_ablock where id  IN(' . implode(',', $recommendGoods) . ') and visible = 1 and id != ' . $_data->id);
        $parens = $parentList = array();
        while ($data = sql::fetch_object($res)) {
            $parens[] = $data->good_id;
        }

        $res = sql::query('select * from prname_b_catitem where id  IN(' . implode(',', $parens) . ') and visible = 1 and id != ' . $_data->id);
        while ($data = sql::fetch_object($res)) {
            $parentList[$data->id] = $data;
        }

        $list = new Listing("ablock", "blocks", 'all', ' id in (' . implode(', ', $recommendGoods) . ') and id !=' . $_data->id . ' and  ');
        $list->getList();
        $list->getItem();
        $tmp = array();

        foreach ($recommendGoods as $key => $sort) {

            foreach ($list->item as $item) {

                if ($item->id == $sort) {
                    $price = 0;
                    $res = sql::query('select good_id_arts  from it_b_aarts where blockparent = ' . $item->good_id);
                    $variants = array();
                    $variants[] = $item->good_id;
                    while ($data = sql::fetch_object($res)) {

                        $variants[] = $data->good_id_arts;
                    }

                    $variants = array_diff($variants, array('', null));

                    $q = sql::fetch_object(sql::query('select min(price) as price from it_b_variant where blockparent IN (' . implode(',', $variants) . ')'));
                    if ($q) {
                        $price = $q->price;
                    }
                    $item->img = $parentList[$item->good_id]->img;
                    $item->price = $price;
                    $tmp[] = $item;
                }
            }
        }

        $page->has_image = !empty($page->images)
            || !empty($page->img_1)
            || !empty($page->img_2)
            || !empty($page->img_3)
            || !empty($page->img_4)
            || !empty($page->video_file)
            || !empty($page->video) || !empty($page->catalog_img);
        $page->recomend_items = $tmp;
        $control->titleSeo = $page->name_rus . ' ' . $page->art . ' для ' . all::get_name($control->parents[2]) . ' на specinter.ru';
        $control->descriptionSeo = $page->name_rus . ' ' . $page->art . ' купить на сайте specinter.ru | запчасти ' . all::get_name($control->parents[2]);
        $control->name = $page->name_rus;


        # get duplicate items
        $res = sql::query('select dub.id, dub.blockparent, dub.good_id_arts, good.art, good.name_rus name from it_b_aarts dub
                            join it_b_catitem good on good.id = dub.good_id_arts
                            where dub.blockparent = ' . (int)$page->id . ' and dub.visible = 1');
        $page->duplicates = array();
        $good_id_arts = array();
        while ($data = sql::fetch_object($res)) {
            $page->duplicates[$data->id] = $data;
            if (!!$data->good_id_arts) {
                $good_id_arts[] = $data->good_id_arts;
            }
        }


        if (!empty($page->duplicates)) {
            $page->has_duplicates = true;

            if (!!$good_id_arts) {

                $res = sql::query('select * from it_b_ablock where good_id IN (' . implode(', ', $good_id_arts) . ') and visible = 1');
                while ($data = sql::fetch_object($res)) {
                    $data->url = all::getUrl($data->parent) . '_aview_b' . $data->id . '/';

                    $itemsDataBlock = all::b_data_all($data->good_id, 'catitem');
                    foreach ($page->duplicates as $i => $duplicateItem) {

                        if ((int)$duplicateItem->good_id_arts === (int)$data->good_id) {
                            $page->duplicates[$i]->url = $data->url;
                            $page->duplicates[$i]->name_eng = $data->name_eng;
                            $page->duplicates[$i]->img = $itemsDataBlock->img;
                            $page->duplicates[$i]->_data = $data;
                        }
                    }
                }
            }
        }


        $res = sql::query('select dub.id, dub.blockparent, dub.good_id_arts, good.art, good.name_rus name from it_b_aarts2 dub
                            join it_b_catitem good on good.id = dub.good_id_arts
                            where dub.blockparent = ' . (int)$page->id . ' and dub.visible = 1');
        $page->duplicates2 = array();
        $good_id_arts2 = array();
        while ($data = sql::fetch_object($res)) {
            $page->duplicates2[$data->id] = $data;
            if (!!$data->good_id_arts) {
                $good_id_arts2[] = $data->good_id_arts;
            }
        }


        if (!empty($page->duplicates2)) {
            $page->has_duplicates2 = true;

            if (!!$good_id_arts2) {

                $res = sql::query('select * from it_b_ablock where good_id IN (' . implode(', ', $good_id_arts2) . ') and visible = 1');
                while ($data = sql::fetch_object($res)) {
                    $data->url = all::getUrl($data->parent) . '_aview_b' . $data->id . '/';
                    $itemsDataBlock = all::b_data_all($data->good_id, 'catitem');
                    foreach ($page->duplicates2 as $i => $duplicateItem) {

                        if ((int)$duplicateItem->good_id_arts === (int)$data->good_id) {
                            $page->duplicates2[$i]->url = $data->url;
                            $page->duplicates2[$i]->name_eng = $data->name_eng;
                            $page->duplicates2[$i]->img = $itemsDataBlock->img;
                            $page->duplicates2[$i]->_data = $data;
                        }
                    }
                    if (!$page->duplicates2[$i]->price) {
                        $page->duplicates2[$i]->price = false;
                    }
                }
            }
        }



        # set item names
        $page->needRow = true;


        if (!empty($page->items)) {
            foreach ($page->items as $i => $item) {
                if ($item->price) {
                    $page->needRow = false;
                }
                $page->items[$i]->name_rus = $page->name_rus;
                if (empty($item->img)) {
                    $item->img = $page->img;
                }

                if ($item->art && strlen($item->art) > 15 && (strripos($item->art, '/') !== false || strripos($item->art, '+') !== false)) {
                    $item->art = str_replace('/', '/<br>', $item->art);
                    $item->art = str_replace('+', '+<br>', $item->art);
                }
                
            }
        }

        $page->back_url = $control->module_url;
        $this->html['text'] = sprintt($page, 'templates/catalog/catalog_good.html');
    }


    private function __showMethodNameForAdmin($methodName)
    {
//        if ($_REQUEST['admin'] == "Y" || $_COOKIE['admin'] == "Y") {
//            echo $methodName;
//        }
    }
}

?>