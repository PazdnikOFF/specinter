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
            $list = new Listing("catitem", "blocks", '245', ' id in( ' . implode(',', $good_ids) . ' ) and ');
            $list->getList();
            $list->getItem();
            $items = $list->item;

            foreach ($items as $key => $real_item) {

                $items[$key]->url = $urls[$real_item->id];
                $real_item->num = $nums[$real_item->id];
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
            }

            usort($page->items, function ($a, $b) {
                return $a->asot > $b->asot;
            });


        }

        $this->html['text'] = sprintt($page, 'templates/catalog/catalog_one.html');

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

        $page = all::b_data_all($_data->good_id, 'catitem');


        

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


        if (!empty($aarts)) {
            $list = new Listing("variant", "items", 'all', ' blockparent in(' . implode(',', $aarts) . ') and        ');
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

        $count = 0;


//        if (strlen($page->recomend)) {
//            $recomend = explode(',', $page->recomend);
//            $arSorting = array('parents' => array(), 'items' => array());
//            foreach ($recomend as $key => &$item) {
//                $count++;
//                if ($count > 8) break;
//                if ($item[0] == 'P') {
//                    $arSorting['parents'][] = (int)str_replace('P', '', $item);
//                    $catitems[] = (int)str_replace('P', '', $item);
//                    unset($recomend[$key]);
//                } else {
//                    $arSorting['items'][] = $item;
//                }
//                $item = (int)$item;
//            }
//        }
//        if ($count < 8) {
//            $_res = sql::query('select id from prname_b_aarts where blockparent = ' . $_data->good_id);
//            $_parents = array();
//
//            while ($data = sql::fetch_object($_res)) {
//                $_parents[] = $data->id;
//            }
//            $ids = '';
//            if (count($recomend)) {
//                $ids = ' and id not in(' . implode(',', $recomend) . ') ';
//            }
//
//            if (!empty($_parents)) {
//                $__res = sql::query('select id from prname_b_variant where blockparent IN( ' . implode(',', $_parents) . ') and id != ' . $_data->good_id . ' ' . $ids . ' limit 0,' . (8 - $count));
//
//                while ($data = sql::fetch_object($__res)) {
//                    $count++;
//                    $recomend[] = $data->id;
//                }
//
//                if ($control < 8) {
//                    foreach ($_parents as $__parent) {
//                        $parents[] = $__parent;
//                        $count++;
//                        if ($count >= 8) {
//                            break;
//                        }
//                    }
//                }
//            }
//
//        }
//        if ($count < 8) {
//            $ids = '';
//            $res = sql::query('select id from prname_b_catitem where parent = ' . $control->cid . ' and id  != ' . $_data->good_id);
//
//            while ($data = sql::fetch_object($res)) {
//                $parents[] = $data->id;
//            }
//
//            if (count($recomend)) {
//                $ids = ' and id not in(' . implode(',', $recomend) . ') ';
//            }
//
//            if ($parents) {
//                $res = sql::query('select id from prname_b_variant where blockparent IN( ' . implode(',', $parents) . ') and id != ' . $_data->good_id . ' ' . $ids . ' limit 0,' . (8 - $count));
//                while ($data = sql::fetch_object($res)) {
//                    $count++;
//                    $recomend[] = $data->id;
//                }
//            }
//
//
//            if ($count < 8) {
//                foreach ($parents as $parent) {
//                    if ($count >= 8)
//                        break;
//                    $catitems[] = $parent;
//                    $count++;
//                }
//            }
//            if ($count < 8) {
//
//                if ($ids) {
//                    $res = sql::query('select id from prname_b_variant where 1=1 ' . $ids . ' limit 0,' . (8 - $count));
//                    while ($data = sql::fetch_object($res)) {
//                        $count++;
//                        $recomend[] = $data->id;
//                    }
//                }
//            }
//        }
//        if ($count < 8) {
//
//            if ($ids) {
//                $res = sql::query('select id from prname_b_variant where 1=1 ' . $ids . ' limit 0,' . (8 - $count) . ' ');
//            } else {
//                $res = sql::query('select id from prname_b_variant limit 0,' . (8 - $count));
//            }
//
//            while ($data = sql::fetch_object($res)) {
//                $count++;
//                $recomend[] = $data->id;
//            }
//        }
//        if (!empty($recomend)) {
//            $res = sql::query('
//        select v.*,c.name_rus,t.url,ab.id as parent_id
//from it_b_variant v
//inner join it_b_catitem c on c.id = v.blockparent
//inner join it_b_ablock ab on ab.good_id = c.id
//inner join it_tree t on t.id = ab.parent
//        where v.id in( ' . implode(',', $recomend) . ')');
//            while ($data = sql::fetch_object($res)) {
//                $arRecommendSorting[$data->id] = $data;
//                $page->recomend_items[] = $data;
//            }
//        }
//        if (!empty($catitems)) {
//
//            $list = new Listing("ablock", "blocks", 'all', ' good_id in (' . implode(', ', $catitems) . ') and ');
//            /*
//            $list = new Listing("ablock", "block", 'all' , ' id in ('. implode(', ', $catitems).') and ');
//            */
//            $list->getList();
//            $list->getItem();
//            $page->recomend_p_items = $list->item;
//
//            foreach ($list->item as $item) {
//                $arRecommendParentSorting[$item->good_id] = $item;
//            }
//            /*
//            $res = sql::query('select c.*,t.url from it_b_catitem c inner join it_tree t on t.id = c.parent where c.id in(' . implode(',', $catitems) . ')');
//            while ($data = sql::fetch_object($res)) {
//                $page->recomend_p_items[] = $data;
//            }
//            */
//        }
//        if (!empty($arSorting['parents'])) {
//
//            $tmp = $page->recomend_p_items;
//            $page->recomend_p_items = $page->recomend_p_items_sort = array();
//            foreach ($arSorting['parents'] as $item) {
//                if(!empty($arRecommendParentSorting[$item])){
//                    $page->recomend_p_items_sort[] = $arRecommendParentSorting[$item];
//                }
//            }
//
//            foreach ($tmp as $add){
//                $_add = true;
//                foreach ($page->recomend_p_items_sort as $item){
//                    if($item->id == $add->id){
//                        $_add = false;
//                    }
//                }
//                if($_add){
//                    $page->recomend_p_items[] = $add;
//                }
//            }
//        }
//        if (!empty($arSorting['items'])) {
//            $tmp = $page->recomend_items;
//            $page->recomend_items = $page->recomend_items_sort = array();
//            foreach ($arSorting['items'] as $item) {
//                if(!empty($arRecommendSorting[$item])){
//                    $page->recomend_items_sort[] = $arRecommendSorting[$item];
//                }
//            }
//
//            foreach ($tmp as $add){
//                $_add = true;
//                foreach ($page->recomend_items_sort as $item){
//                    if($item->id == $add->id){
//                        $_add = false;
//                    }
//                }
//                if($_add){
//                    $page->recomend_items[] = $add;
//                }
//            }
//        }
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
            || !empty($page->video);
        $page->recomend_items = $tmp;
        $control->titleSeo = $page->name_rus . ' ' . $page->art . ' для ' . all::get_name($control->parents[2]) . ' на specinter.ru';
        $control->descriptionSeo = $page->name_rus . ' ' . $page->art . ' купить на сайте specinter.ru | запчасти ' . all::get_name($control->parents[2]);
        $control->name = $page->name_rus;
        
        
     

        # get duplicate items
        $res = sql::query('select * from it_b_aarts where blockparent = '.(int)$page->id.' and visible = 1');
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

            $res = sql::query('select * from it_b_ablock where good_id IN ('.implode(', ', $good_id_arts).') and visible = 1');
            while ($data = sql::fetch_object($res)) {
                $data->url = all::getUrl($data->parent) . '_aview_b' . $data->id . '/';

                foreach ($page->duplicates as $i => $duplicateItem) {
                    if ((int)$duplicateItem->good_id_arts === (int)$data->good_id) {
                        $page->duplicates[$i]->url = $data->url;
                    }
                }
            }
        }

        $page->back_url = $control->module_url;
        $this->html['text'] = sprintt($page, 'templates/catalog/catalog_good.html');
    }


    private function __showMethodNameForAdmin($methodName)
    {
        if ($_REQUEST['admin'] == "Y" || $_COOKIE['admin'] == "Y") {
            echo $methodName;
        }
    }
}

?>