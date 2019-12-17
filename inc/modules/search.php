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


            $word = mysql_escape_string($_REQUEST['search-request']);
            $page = new stdClass();
            $page->word = $word;
            $list = new Listing("ablock", "blocks", 'all', " (name_eng like '%{$word}%' or name_rus like '%{$word}%'   or art like '%{$word}%') AND");
            $list->getList();
            $list->getItem();
            $page->count = $list->count;
            $page->items = $list->item;
            if (is_array($page->items))
                foreach ($page->items as &$item) {
                    $item->url = all::getUrl($item->parent);
                    $item->parents = implode(' - ', tree::getParentsItems($item->parent));
                }
        }

        $this->html['text'] = sprintt($page, 'templates/search/search.html');
    }


    private function searchAjax($q)
    {


        // if (isset($_COOKIE['vas-vas'])) {
        //     global $sql;
        //     echo "select * from it_b_ablock  where name_eng like '%".mysql_escape_string($q)."%' or name_rus like '%".mysql_escape_string($q)."%'";
        //     $res = sql::query("select * from it_b_ablock  where name_eng like '%".mysql_escape_string($q)."%' or name_rus like '%".mysql_escape_string($q)."%'");
        //    var_dump($res);
        //     var_dump(sql::fetch_assoc($res));
        //     die();
            // die("ablock", "blocks", 'all', " (name_eng like '%{$q}%' or name_rus like '%{$q}%'   or art like '%{$q}%') AND");
        // }
        $q = mysql_escape_string($q);
        $list = new Listing("ablock", "blocks", 'all', " (name_eng like '%{$q}%' or name_rus like '%{$q}%'   or art like '%{$q}%') AND");
        $list->getList();
        $list->getItem();
        $list->limit = 9999999;
        $page = new stdClass();
        $page->count = $list->count;
        $page->items = $list->item;
        foreach ($page->items as &$item) {

            $item->parents = implode(' - ', tree::getParentsItems($item->parent));
        }

        //        $list = new Listing("variant", "items", 'all', " (name like '%{$q}%' or art like '%{$q}%') AND");
        //        $list->getList();
        //        $list->getItem();
        //        $page->variant = $list->item;
        echo sprintt($page, 'templates/search/ajax.html');
        die();
    }
}
