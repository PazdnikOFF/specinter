<?php

class search
{
    public function __construct()
    {
        global $control;
        if ($_REQUEST['ajax'] == "Y" && strlen($_REQUEST['query']) > 0) {
            return $this->searchAjax($_REQUEST['query']);
        } else if (!empty($_REQUEST['search-request'])) {
            return $this->search();
        }
        die();
    }

    private function search()
    {
        if (strlen($_REQUEST['search-request']) > 2) {


            $word = sql::escape_string(trim($_REQUEST['search-request']));
            $page = new stdClass();
            $page->word = $word;
            $list = new Listing("ablock", "blocks", 'all', " (name_eng like '%{$word}%' or name_rus like '%{$word}%'   or art like '%{$word}%') AND");
            $list->sortfield = 'if (parent = 451, 1, 0)';
            $list->getList();
            $list->getItem();
            $page->count = $list->count;
            $page->items = [];
//            $existent_goods = [];
            if (is_array($page->items))
                foreach ($list->item as &$item) {
//                    if (isset($existent_goods[$item->good_id]) || !$item->good_id) {
//                        continue;
//                    }
                    $page->items[] = $item;
//                    $existent_goods[$item->good_id] = 1;
                    if ($item->uurl) {
                        $item->url = '/'.$item->uurl;
                    } else {
                        $item->url = all::getUrl($item->parent) . '_aview_b'.$item->id;
                    }
                    $item->parents = implode(' - ', tree::getParentsItems($item->parent, 2, null));
                }
        }

        $this->html['text'] = sprintt($page, 'templates/search/search.html');
    }


    private function searchAjax($q)
    {


        // if (isset($_COOKIE['vas-vas'])) {
        //     global $sql;
        //     echo "select * from it_b_ablock  where name_eng like '%".sql::escape_string($q)."%' or name_rus like '%".sql::escape_string($q)."%'";
        //     $res = sql::query("select * from it_b_ablock  where name_eng like '%".sql::escape_string($q)."%' or name_rus like '%".sql::escape_string($q)."%'");
        //    var_dump($res);
        //     var_dump(sql::fetch_assoc($res));
        //     die();
            // die("ablock", "blocks", 'all', " (name_eng like '%{$q}%' or name_rus like '%{$q}%'   or art like '%{$q}%') AND");
        // }
        $q = sql::escape_string(trim($q));
        $list = new Listing("ablock", "blocks", 'all', " (name_eng like '%{$q}%' or name_rus like '%{$q}%'   or art like '%{$q}%') AND");
        $list->sortfield = 'if (parent = 451, 1, 0)';
        $list->getList();
        $list->getItem();
        $list->limit = 9999999;
        $page = new stdClass();
        $page->count = $list->count;
        $page->items = [];
        $existent_goods = [];
        foreach ($list->item as &$item) {
            if (isset($existent_goods[$item->good_id]) || !$item->good_id) {
                continue;
            }
            $page->items[] = $item;
            $existent_goods[$item->good_id] = 1;
            if ($item->uurl) {
                $item->url = '/'.$item->uurl;
            } else {
                $item->url = all::getUrl($item->parent) . '_aview_b'.$item->id;
            }
//            $item->parents = implode(' - ', tree::getParentsItems($item->parent, 2, 2));
        }

        //        $list = new Listing("variant", "items", 'all', " (name like '%{$q}%' or art like '%{$q}%') AND");
        //        $list->getList();
        //        $list->getItem();
        //        $page->variant = $list->item;
        echo sprintt($page, 'templates/search/ajax.html');
        die();
    }
}
