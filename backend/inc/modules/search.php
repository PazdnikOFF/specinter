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

    private function getWords($word) {
        $word = urldecode($word);
        $words = $word;
        $words = preg_replace('/[^a-z0-9а-яё]/iu',' ', $words);
        $words = explode(' ',$words);
        $words = array_filter($words, function ($w) {
            return mb_strlen($w) >= 2;
        });

        return $words;
    }

    private function getAgainst($words) {
        $wordsWith = function ($prefix, $postfix) use ($words) {
            return implode(' ', array_map(function ($word) use ($prefix, $postfix) {
                return $prefix . $word . $postfix;
            }, $words));
        };
        $results = [];
        $results[] = ">(>({$wordsWith('+', '')}))";
        $results[] = ">({$wordsWith('+', '*')})";
        $results[] = "({$wordsWith('', '')})";
        $results[] = "<({$wordsWith('', '*')})";

        return implode(' ', $results);
    }

    private function highlightWords($words, $text) {
        foreach ($words as $word) {
            $text = preg_replace('/' . preg_quote($word, '/') . '/ui', '<span style="color:black;font-weight:bold">$0</span>', $text);
        }
        return $text;
    }

    private function search()
    {
        if (strlen($_REQUEST['search-request']) > 2) {


            $word = sql::escape_string(trim($_REQUEST['search-request']));
            $page = new stdClass();
            $words = $this->getWords($word);
            $against = $this->getAgainst($words);

            $page->word = $word;
            $list = new Listing(
                "ablock",
                "blocks",
                'all',
                " MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE) AND "
            );
            $list->sortfield = "if (parent = 451, 1, 0) ASC, MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE)";
            $list->sortby='DESC';
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
                    $item->art = $this->highlightWords($words, $item->art);
                    $item->name_rus = $this->highlightWords($words, $item->name_rus);
                    $item->parents = implode(' - ', tree::getParentsItems($item->parent, 2, null));
                    $item->html = $this->highlightWords($words, $item->html);
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
        $words = $this->getWords($q);
        $against = $this->getAgainst($words);
        $q = sql::escape_string(trim($q));
        $list = new Listing(
            "ablock",
            "blocks",
            'all',
            " MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE) AND "
        );
        $list->sortfield = "if (parent = 451, 1, 0) ASC, MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE)";
        $list->sortby='DESC';
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
            $item->art = $this->highlightWords($words, $item->art);
            $item->name_rus = $this->highlightWords($words, $item->name_rus);
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
