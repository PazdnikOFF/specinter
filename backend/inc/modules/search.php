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

    private function getWords($string)
    {
        $string = urldecode($string);
        $words  = $string;
        $words  = explode(' ', $words);
        $words  = array_map('trim', $words);
        $words  = array_filter($words, function ($word) {
            return preg_match('/^[a-zа-яё]+$/iu', $word, $m) && mb_strlen($word) >= 2;
        });
//        $words  = preg_replace('/[^a-z0-9а-яё]/iu',' ', $words);
//        $words  = explode(' ', $words);
//        $words  = array_filter($words, function ($w) {
//            return mb_strlen($w) >= 2;
//        });

        return $words;
    }

    private function getNumbers($string)
    {
        $string  = urldecode($string);
        $numbers = $string;
        $numbers   = explode(' ', $numbers);
        $numbers  = array_map('trim', $numbers);
        $numbers = array_filter($numbers, function ($number) {
            return preg_match('/[^a-zа-яё]/iu', $number) && mb_strlen($number) >= 2;
        });

        return $numbers;
    }

    private function getStemmedWords($words) {
        $words = \array_map(function ($word) {
            return preg_replace('/[аяоёуюэеыийь]$/iu', '', $word);
        }, $words);

        return $words;
    }

    private function getAgainst($words) {
        $wordsWith = function ($prefix, $postfix) use ($words) {
            return implode(' ', array_map(function ($word) use ($prefix, $postfix) {
                return $prefix . $word . $postfix;
            }, $words));
        };
        $results = [];
//        $results[] = ">(>({$wordsWith('+', '')}))";
//        $results[] = ">({$wordsWith('+', '*')})";
//        $results[] = "({$wordsWith('', '')})";
//        $results[] = "<({$wordsWith('', '*')})";
        $results[] = $wordsWith('+', '*');

        return implode(' ', $results);
    }

    private function getLike($fields, $words) {
        return $words ? '(' . implode(' OR ', array_map(function ($word) use ($fields) {
            return implode(' OR ', array_map(function($field) use ($word) {
                return sprintf('%s LIKE \'%%%s%%\'', $field, sql::escape_string($word));
            }, $fields));
        }, $words)) . ')' : 'FALSE';
    }

    private function highlightWords($words, $text) {
        $stemmedWords = $this->getStemmedWords($words);
        foreach ($words as $i => $word) {
            $originalText = $text;
            $text = preg_replace('/' . preg_quote($word, '/') . '/ui', "\x00$0\x01", $text);
            if ($text === $originalText) {
                $text = preg_replace('/' . preg_quote($stemmedWords[$i], '/') . '/ui', "\x00$0\x01", $text);
            }
        }
        $text = str_replace("\x00", '<span style="color:black;font-weight:bold">', $text);
        $text = str_replace("\x01", '</span>', $text);
        return $text;
    }

    private function sortItems(&$items, $words) {
//        return;
        if (!is_array($items) || !$words) {
            return;
        }
        $strtolower = function ($words) {
            return \array_map('mb_strtolower', $words);
        };
        $words = \array_unique($strtolower($words));
        $stemmedWords = \array_unique($this->getStemmedWords($words));
        /*
         * Поиск по полям art, name_rus
         * если искомое слово находится в строке целиком, то на каждое найденное слово ставится 1 балл.
         * если искомое слово находится в строке, но является подстрокой в найденном слове,
         * то ставится балл пропорциональный размеру вхождения подстроки по отношению к найденному слову (т.е. меньше 1)
         * если искомое слово находится не с начала слова, то балл считается так же как в предыдущем случае,
         * но из результата вычитается такой балл, который соответствует разности длины слова и найденной позиции,
         * поделенной на длину слова
         *
         * если
         * выходные баллы суммируются и делятся на количество искомых слов в поле.
         *
         * поиск по полям работает так - если слово найдено
         */

        $getScore = function ($words, $string) use ($strtolower) {
            $fieldWords = \array_unique($strtolower($this->getWords($string)));
            $score = 0.0;
            foreach ($words as $word) {
                foreach ($fieldWords as $fieldWord) {
                    $offset = mb_stripos($fieldWord, $word);
                    if ($offset === false) {
                        continue;
                    }
                    $wordLen      = (float)\mb_strlen($word);
                    $fieldWordLen = (float)\mb_strlen($fieldWord);
                    if ($offset === 0) {
                        $localScore   = 1 - (1 - ($wordLen / $fieldWordLen)) * 0.5;
                    } else {
                        $prefPostfLen = (float)($offset + ($fieldWordLen - ($offset + $wordLen)));
//                        $prefPostfLen = 0;//(float)($offset + ($fieldWordLen - ($offset + $wordLen)));
                        $localScore   = $wordLen / $fieldWordLen * (1 - $prefPostfLen / $fieldWordLen);
                    }
                    $score        += $localScore;
                }
            }

            return $score;// + ($score / count($fieldWords)) * 0.1;
        };

        $scores = \array_map(function (&$item) use ($words, $stemmedWords, $getScore) {
            $nameScore         = $getScore($words, $item->name_rus) / count($words);
            $artScore          = $getScore($words, $item->art);
            $commonScore       = $getScore($words, $item->art . ' ' . $item->name_rus) / \count($words);
            $commonScore2       = $getScore($stemmedWords, $item->art . ' ' . $item->name_rus) / \count($words);
//            $score             = max($commonScore, $artScore);
            $artScore += $artScore * 0.01;
            $score             = ($nameScore + $commonScore + $artScore + $commonScore2) / 4;
//            $item->score       = sprintf(' (n %0.2f a %0.2f c %0.4f c2 %0.4f = %0.4f)',
//            $nameScore,$artScore,$commonScore,$commonScore2, $score);

            return $score;
        }, $items);

        $res = array_multisort($scores, SORT_DESC, SORT_REGULAR, $items);
    }

    private function search()
    {
        if (strlen($_REQUEST['search-request']) > 2) {
            $word = sql::escape_string(trim($_REQUEST['search-request']));
            $page = new stdClass();
            $words = $this->getWords($word);
            $numbers = $this->getNumbers($word);
//            $against = $this->getAgainst($words);

            $criteria = '';
            if ($words) {
                $criteria .= "MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE) AND ";
            }
            if ($numbers) {
                $criteria .= $this->getLike(['art', 'name_rus'], $numbers) . ' AND ';
            }
            if (!$numbers && !$words) {
                $criteria .= 'FALSE AND ';
            }

            $page->word = $word;
            $list = new Listing(
                "ablock",
                "blocks",
                'all',
                $criteria
            );
//            $list->sortfield = "if (parent = 451, 1, 0) ASC, MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE)";
//            $list->sortby='DESC';
            $list->getList();
            $list->getItem();
            $page->count = $list->count;
            $page->items = [];
//            $existent_goods = [];
            $this->sortItems($list->item, $words);
            $words = array_merge($words, $numbers);
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
        if (strlen($q) > 2) {
            $word = sql::escape_string(trim($q));
            // if (isset($_COOKIE['vas-vas'])) {
            //     global $sql;
            //     echo "select * from it_b_ablock  where name_eng like '%".sql::escape_string($q)."%' or name_rus like '%".sql::escape_string($q)."%'";
            //     $res = sql::query("select * from it_b_ablock  where name_eng like '%".sql::escape_string($q)."%' or name_rus like '%".sql::escape_string($q)."%'");
            //    var_dump($res);
            //     var_dump(sql::fetch_assoc($res));
            //     die();
            // die("ablock", "blocks", 'all', " (name_eng like '%{$q}%' or name_rus like '%{$q}%'   or art like '%{$q}%') AND");
            // }
            $words   = $this->getWords($word);
            $numbers = $this->getNumbers($word);
//        $against = $this->getAgainst($words);
//        $q = sql::escape_string(trim($q));

            $criteria = '';
            if ($words) {
                $criteria .= "MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE) AND ";
            }
            if ($numbers) {
                $criteria .= $this->getLike(['art', 'name_rus'], $numbers) . ' AND ';
            }
            if (!$numbers && !$words) {
                $criteria .= 'FALSE AND ';
            }

            $list = new Listing(
                "ablock",
                "blocks",
                'all',
                $criteria
            );
//        $list->sortfield = "if (parent = 451, 1, 0) ASC, MATCH (art, name_rus, name_eng) AGAINST ('{$this->getAgainst($words)}' IN BOOLEAN MODE)";
//        $list->sortby='DESC';
            $list->getList();
            $list->getItem();
            $list->limit    = 9999999;
            $page           = new stdClass();
            $page->count    = $list->count;
            $page->items    = [];
            $existent_goods = [];
            $this->sortItems($list->item, $words);
            $words = array_merge($words, $numbers);
            foreach ($list->item as &$item) {
                if (isset($existent_goods[$item->good_id]) || !$item->good_id) {
                    continue;
                }
                $page->items[]                  = $item;
                $existent_goods[$item->good_id] = 1;
                if ($item->uurl) {
                    $item->url = '/' . $item->uurl;
                } else {
                    $item->url = all::getUrl($item->parent) . '_aview_b' . $item->id;
                }
                $item->art      = $this->highlightWords($words, $item->art);
                $item->name_rus = $this->highlightWords($words, $item->name_rus);
//            $item->parents = implode(' - ', tree::getParentsItems($item->parent, 2, 2));
            }

            //        $list = new Listing("variant", "items", 'all', " (name like '%{$q}%' or art like '%{$q}%') AND");
            //        $list->getList();
            //        $list->getItem();
            //        $page->variant = $list->item;
            echo sprintt($page, 'templates/search/ajax.html');
        }
        die();
    }
}
