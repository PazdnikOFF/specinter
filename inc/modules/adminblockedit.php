<?php

class adminblockedit extends manage
{

    function __construct()
    {
        $_SESSION['newId'] = null;
        global $control;
        if ($_REQUEST['ajax'] == "Y") {
            return $this->ajax();
        } else if ($_REQUEST['chakge'] == "Y") {
            return $this->chakge();
        } else if ($_REQUEST['arts'] == "Y") {
            return $this->arts();
        }


        parent::checkForUser();

        $this->menu = parent::getMenu();
        $this->page = $control->page;


        if ($control->oper == 'add') {
            if (isset($_POST['parent']))
                return $this->add();
            else return $this->printAdd();
        }
        if ($control->oper == 'itemadd') {
            if (isset($_POST['blockparent']))
                return $this->add('item');
            else return $this->printAdd('item');
        }

        if ($control->oper == 'edit') {
            if (isset($_POST['parent']))
                return $this->edit();
            else return $this->printEdit();
        }

        if ($control->oper == 'itemedit') {
            if (isset($_POST['blockparent']))
                return $this->edit('item');
            else return $this->printEdit('item');
        }


        if ($control->oper == 'showhide') {
            return $this->showHide();
        }

        if ($control->oper == 'grouphide') {
            return $this->groupHide();
        }

        if ($control->oper == 'groupshow') {
            return $this->groupShow();
        }

        if ($control->oper == 'del') {
            return $this->delete();
        }
        if ($control->oper == 'itemdel') {
            return $this->delete('item');
        }

        if ($control->oper == 'move') {
            return $this->move();
        }

        if ($control->oper == 'copy') {
            return $this->copy();
        }

        if ($control->oper == 'list') {
            return $this->printList();
        }

        if ($control->oper == 'itemlist') {
            return $this->printListItem();
        }

        if ($control->oper == 'groupdel') {
            return $this->groupDel();
        }

        if ($control->oper == 'moveto') {
            return $this->moveTo();
        }

        if (isset($_POST['mode'])) {
            if ($_POST['mode'] == 'trigger') {
                return $this->trigger();
            }
            if ($_POST['mode'] == "mselecttrigger") {
                return $this->mselecttrigger();
            }
            if ($_POST['mode'] == "selecttrigger") {
                return $this->selecttrigger();
            }
            if ($_POST['mode'] == "subscribetrigger") {
                return $this->subscribetrigger();
            }
        }


    }

    public function arts()
    {
        $table = $_POST['table'];
        unset($_POST['arts']);
        unset($_POST['table']);
        unset($_POST['good_id_arts']);
        foreach ($_POST as $k => $v) {
            if ($k == 'name') {
                $_POST['name_rus'] = $v;
                unset($_POST['name']);
            }
        }

        $select = implode(',', array_merge(array('id'), array_keys($_POST)));
        $condition = array();
        foreach ($_POST as $key => $value) {
            if (!empty($value)) {
                $condition[] = "{$key} LIKE'{$value}%'";
            }

        }
        if (!empty($condition)) {
            $condition = implode(' AND ', $condition);
            $q = sql::query("SELECT {$select} FROM it_b_{$table} WHERE {$condition} limit 0,50");
            $result = array();
            while ($row = sql::fetch_assoc($q)) {
                $result [] = $row;
            }
        }
        if (!empty($result)) {
            echo json_encode(array('data' => $result, 'success' => true));
        } else {
            echo json_encode(array('error' => 'Не найдено'));
        }
    }

    function chakge()
    {
        unset($_POST['chakge']);
        unset($_POST['parent']);
        unset($_POST['num']);
        $select = implode(',', array_merge(array('id'), array_keys($_POST)));
        $condition = array();
        foreach ($_POST as $key => $value) {
            if (!empty($value)) {
                $condition[] = "{$key} LIKE'{$value}%'";
            }

        }
        if (!empty($condition)) {
            $condition = implode(' AND ', $condition);
            $q = sql::query("SELECT {$select} FROM it_b_catitem WHERE {$condition} limit 0,50");
            $result = array();
            while ($row = sql::fetch_assoc($q)) {
                $result [] = $row;
            }
        }
        if (!empty($result)) {
            echo json_encode(array('data' => $result, 'success' => true));
        } else {
            echo json_encode(array('error' => 'Не найдено'));
        }


    }

    function ajax()
    {
        $parent = $_POST['parent'];
        if ($_POST['no_link'] == 1) {
            $nolik = true;
        } else {
            $nolik = false;
        }
        unset($_POST['ajax']);
        unset($_POST['parent']);


        if (empty($_POST['good_id'])) {

            // if (in_array((int)$_REQUEST['parent'], array(451))) {

            $_POST['good_id'] = All::insert_block('catitem', 245, $_POST);

            # assign new item to special 451 category
            All::insert_block('ablock', 451, $_POST);
            // }


        } else {

            # drop item from special 451 category
            sql::query("DELETE FROM `it_b_ablock` WHERE `good_id` = " . (int)$_POST['good_id'] . " AND `parent` = 451");
        }

        if (!$nolik) {
            All::insert_block('ablock', $parent, $_POST);
        }
        if (!empty($_REQUEST["good_id"])) {
            $update = array();
            global $sql;

            foreach (array('art', 'name_rus', 'name_eng') as $value) {
                $update[] = $value . ' = "' . $_REQUEST[$value] . '"';
            }

            $sql->query("update it_b_catitem set " . implode(', ', $update) . " where id = " . $_REQUEST['good_id']);

        }
        echo json_encode(array('success' => $_POST['good_id']));
    }

    //Вывод списка блоков
    function printList()
    {
        global $control;
        global $config;

        // Поиск
        $_GET['getsearch'] = isset($_POST['search']) ? "" : $_GET['getsearch'];

        $searchText = trim($_POST['search'] ? $_POST['search'] : $_GET['getsearch']);
        if (!empty($searchText)) $isSearch = true;


        // для поиска выводим все
        $limit = isset($_POST['nolimit']) && $isSearch ? 99999 : 100;
        $limit = 100;

        //$page->getSearch = htmlentities($_GET['getsearch']);
        $page->getSearch = $_GET['getsearch'];
        if ($_GET['getsearch']) {
            $page->hidePagination = "hide";
        }


        $parent = all::getVar("parent");

        $page->status = $_SESSION['admin_status'];
        $page->admin_id = user_is('admin_id');

        $page->sitename = $control->settings->sitename;
        $page->theme = parent::$mainTheme;;
        if (user_is("super") == '1') $page->super = true;
        $page->parent = $parent;


        //Возможные блоки
        $blockTypes = sql::one_record("SELECT blocktypes FROM prname_ctemplates WHERE `key`=(SELECT template FROM prname_categories WHERE id=" . $parent . ")");

        $blockTypes = preg_split("/ /", $blockTypes, null, PREG_SPLIT_NO_EMPTY);
        $blockTypes = array_unique($blockTypes);


        //Если папка не имеет возможных блоков
        if (count($blockTypes) == 0 && !user_is("super")) {
            header("Location: /manage/");
            return;
        }

        if (user_is("super")) $treshold = 0;
        else $treshold = 1;

        //Если несколько возможных блоков - запихиваем в селект
        if (count($blockTypes) > $treshold) {
            foreach ($blockTypes as $val) {
                $info = sql::fetch_assoc(sql::query("SELECT * FROM prname_btemplates WHERE `key`='" . $val . "'"));
                $page->blocktypes[$info['id']]->name = $info['name'];
                $page->blocktypes[$info['id']]->key = $info['key'];
            }
        }

        if (user_is("super")) {
            $btemplates = sql::query("SELECT * FROM prname_btemplates");

            while ($btemplate = sql::fetch_assoc($btemplates)) {
                if (!isset($page->blocktypes[$btemplate['id']])) {
                    $page->sblocktypes[$btemplate['id']]->name = $btemplate['name'];
                    $page->sblocktypes[$btemplate['id']]->key = $btemplate['key'];
                }
            }

        }

        //Текущий шаблон - если передан параметром - значит он, если нет - первый из возможных, если и их нет и супрадмин - то первый из всех
        $currentTemplate = all::getVar("template");
        if (!$currentTemplate) $currentTemplate = reset($blockTypes);
        if (!$currentTemplate && user_is("super")) {
            foreach ($page->sblocktypes as $val) {
                $currentTemplate = $val->key;
                break;
            }
        }

        // Если есть текущий шаблон - выбираем возможные места переноса блоков
        if ($currentTemplate) {
            $sql = "SELECT tree.*, templ.blocktypes as block FROM prname_ctemplates templ, prname_tree tree WHERE templ.key=tree.template AND tree.id>10";

            $query = sql::query($sql);
            while ($res = sql::fetch_object($query)) {
                $res->levels = "";
                $i = $res->level;
                while ($i > 1) {
                    $res->levels .= "&nbsp;&nbsp;&nbsp;&nbsp;";
                    $i--;
                }
                if (strpos($res->block, $currentTemplate . " ") === 0 || strpos($res->block, " " . $currentTemplate . " ") > -1) {
                    $res->disabled = false;
                } else {
                    $res->disabled = true;
                }

                if ($res->id == $parent) {
                    $res->disabled = true;
                }
                $page->moveTo[] = $res;
            }
        }


        //Только если есть что выбирать
        if ($currentTemplate) {
            // Страница текущая

            if (all::getVar("page") != "" && isset($_POST['search'])) {
                $page->lpage = $lpage = all::getVar("page");
            } else if (all::getVar("page") != "") {
                $page->lpage = $lpage = all::getVar("page");
            } else {
                $page->lpage = $lpage = 0;
            }

            $start = 0 + $lpage * $limit;


            if ($isSearch) $start = 0;
            $page->istart = 0;
            if ($_REQUEST['start']) {
                $start = $page->istart = $_REQUEST['start'];
            }


            // Узнаем поля
            $dataRel = sql::query("SELECT p2.* from prname_btemplates p1, prname_bdatarel p2 WHERE p1.key='" . $currentTemplate . "' AND p2.templid=p1.id AND p2.show=1 ORDER by p2.tab, p2.sort");

            $j = 0;
            $searchSqlArr = array();
            while ($dr = sql::fetch_assoc($dataRel)) {
                $page->fields[$j] = (object)$dr;
                $j++;

                // Добавление сорировки в SQL
                if (isset($_GET[$dr['key']])) {
                    $_GET[$dr['key']] = $_GET[$dr['key']] == "desc" ? "desc" : "asc";

                    if ($dr['key'] == 'price') {
                        $orderSql = "CAST(`{$dr['key']}` AS DECIMAL) {$_GET[$dr['key']]} ";
                    } else {
                        $orderSql = "`{$dr['key']}` {$_GET[$dr['key']]} ";
                    }
                }

            }

            // Добавление поля id и modified в сорировку SQL
            if (!empty($_GET['id'])) {
                $_GET['id'] = $_GET['id'] == "desc" ? "desc" : "asc";
                $orderSql = "CAST(`id` AS UNSIGNED) {$_GET['id']} ";
                $page->idSort = $_GET['id'] == "asc" ? "headerSortDown" : "headerSortUp";
            }

            if ($parent == 41 && !$orderSql && !$_GET['modified']) {
                $_GET['modified'] = "desc";
            }


            if (!empty($_GET['modified'])) {
                $_GET['modified'] = $_GET['modified'] == "desc" ? "desc" : "asc";
                $orderSql = "`modified` {$_GET['modified']} ";
                $page->modSort = $_GET['modified'] == "asc" ? "headerSortDown" : "headerSortUp";
            }

            if (!$orderSql) {
                $orderSql = " `sort` ASC";
            }
            if ($currentTemplate == 'orderinfo') {
                $orderSql = 'id DESC';
            }


            if ($isSearch) {
                $searchSql = $isSearch ? " AND CONCAT(" . implode(",", $searchSqlArr) . ") LIKE '%{$searchText}%' " : "";
                $searchSql = "";
                $searchSql = " AND `name` LIKE '%{$searchText}%'";
            }
            $res_fields = sql::query("SHOW COLUMNS FROM prname_b_" . $currentTemplate . "");
            while ($field_row = sql::fetch_assoc($res_fields)) {
                $fields[] = $field_row['Field'];
            }

            if (!empty($fields) && $searchText) {
                $searchSql = 'and (';
                $searchArr = array();
                foreach ($fields as $field) {
                    if (in_array($field, array('name', 'name_rus', 'art'))) {
                        $searchArr[] = " `{$field}` LIKE '%{$searchText}%'";
                    }
                }
                $searchSql .= implode(' or ', $searchArr);
                $searchSql .= ' )';
            }


            $filterSql = "";
            if ($parent == 41) {
                $filterStr = sql::one_record("SELECT comment FROM prname_bdatarel WHERE id=668");
                $filterArr = explode(';', $filterStr);
                if ($filterArr) {
                    $page->filteritem = array();
                    foreach ($filterArr as $filterVal) {
                        $filterItem = new stdClass();
                        $filterItem->value = $filterVal;
                        $page->filteritem[] = $filterItem;
                    }
                }

                if (($_GET['filter'] = rtrim($_GET['filter'], "/")) && !empty($_GET['filter'])) {
                    $filterSql = sprintf(" AND `type` = '%s' ", sql::escape_string($_GET['filter']));
                    $page->filter = htmlspecialchars($_GET['filter']);
                }
            }


            // Узнаем кол-во блоков текущего шаблона
            $totalcount = sql::one_record("SELECT count(id) FROM prname_b_" . $currentTemplate . " WHERE parent=" . $parent . $filterSql);

            $tempUrl = $control->module_url;


            $queryString = $_SERVER['QUERY_STRING'] ? "?" . substr($_SERVER['QUERY_STRING'], 0, strlen($_SERVER['QUERY_STRING']) - 1) : "";

            $page->queryString = $queryString;

            // Удаляем getSearch из пагинации
            $queryString = preg_replace("/getsearch=.*sort/ui", "sort", $queryString);

            //var_dump($queryString);
            // Если блоков больше, чем влазит на страницу - делаем постраничку
            if ($totalcount > $limit) {
                $pagecount = ceil($totalcount / $limit);
                for ($i = 0; $i < $pagecount; $i++) {
                    $page->page[$i]->title = $i + 1;
                    $page->page[$i]->url = $tempUrl . "_alist_parent" . $parent . "_page" . $i . $queryString . "/";
                    $page->page[$i]->active = $i == $lpage ? true : false;
                }
            }

            //var_dump($orderSql);
            //var_dump("SELECT *, UNIX_TIMESTAMP(modified) as modified FROM prname_b_".$currentTemplate." WHERE parent=".$parent." {$searchSql} {$filterSql} ORDER by {$orderSql}  LIMIT $start, $limit");
            //die();
            //Выборка блоков текущего шаблона
            $result = sql::query("SELECT *, UNIX_TIMESTAMP(modified) as modified FROM prname_b_" . $currentTemplate . " WHERE parent=" . $parent . " {$searchSql} {$filterSql} ORDER by {$orderSql}  LIMIT $start, $limit");

            if ($_POST['search']) {
                $countRows = sql::num_rows(sql::query("SELECT *, UNIX_TIMESTAMP(modified) as modified FROM prname_b_" . $currentTemplate . " WHERE parent=" . $parent . " {$searchSql} {$filterSql} ORDER by {$orderSql} "));
            }

            if (($start + $limit) < $countRows && $_POST['search']) {
                $page->more = $start + $limit;
            }

            $j = 0;
            while ($r = sql::fetch_assoc($result)) {
                $page->item[$j]->id = $r['id'];
                $modified = date("Y-m-d H:i:s", $r['modified']);
                $page->item[$j]->modified = date("d.m.Y H:i:s", $r['modified']);
                $now = date("Y-m-d H:i:s");

                /*if ($r['modified']) {
                    $diff = all::dateDifference($now, $modified);
                    if ($diff->d > 0 && $diff->d < 6) {
                        $page->item[$j]->modified = all::declOfNum($diff->d, array("дня", "дней", "дней"))."  назад";
                    }
                    elseif ($diff->d < 1 && $diff->h > 0) {
                        $page->item[$j]->modified = all::declOfNum($diff->h, array("час", "часа", "часов"))."  назад";
                    }
                    elseif ($diff->h < 1 && $diff->i > 0) {
                        $page->item[$j]->modified = all::declOfNum($diff->i, array("минуту", "минуты", "минут"))." ".all::declOfNum($diff->s, array("секунду", "секунды", "секунд"))." назад";
                    }
                    elseif ($diff->h < 1 && $diff->i < 1 && $diff->s < 30) {
                        $page->item[$j]->modified = "только что";
                    }
                    elseif ($diff->h < 1 && $diff->i < 1 && $diff->s > 30) {
                        $page->item[$j]->modified = all::declOfNum($diff->s, array("секунду", "секунды", "секунд"))." назад";
                    }

                }*/

                $name = "";

                $jj = 0;
                if (!is_null($page->fields))
                    foreach ($page->fields as $val1) {
                        // генерируем вывод информации в таблицу

                        $value = $r[$val1->key];
                        $datatkey = $val1->datatkey;


                        if (!empty($_GET[$val1->key])) {
                            $val1->sorting = $_GET[$val1->key] == "asc" ? "headerSortDown" : "headerSortUp";
                        }

                        $class = "type_" . $datatkey;
                        $obj = new $class();


                        $genValue = $obj->get($value, $val1->comment, $val1->readonly, $r['id'], $val1->key);


                        $page->item[$j]->fields[$jj]->val = $genValue;

                        $jj++;
                    }
                $page->item[$j]->visible = $r['visible'];
                $page->item[$j]->sort = $r['sort'];

                $j++;

            }

            $page->template = $currentTemplate;
            $page->templname = sql::one_record("SELECT name FROM prname_btemplates WHERE `key`='" . $currentTemplate . "'");
            $page->rights = $this->getRights($currentTemplate);
        }


        $page->menu = $this->menu;
        $page->name = sql::one_record("SELECT name FROM prname_tree WHERE id=" . $parent);
        if ($_REQUEST['start']) {
            list(, $ajaxHtml) = explode('<!--AJAX-->', sprintt($page, 'templates/' . $control->template . '/' . $control->template . '.html'));
            echo $ajaxHtml;
            die();
        }

        $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/' . $control->template . '.html');
    }

    //Вывод списка вложенных блоков
    function printListItem()
    {
        global $control;

        $parent = all::getVar("parent");
        $blockparent = all::getVar("id");

        $template = all::getVar("template");

        $page->status = $_SESSION['admin_status'];
        $page->admin_id = user_is('admin_id');

        $page->sitename = $control->settings->sitename;
        $page->theme = parent::$mainTheme;
        if (user_is("super") == '1') $page->super = true;
        $page->parent = $parent;


        if ($blockparent) {

            if (\in_array($template, ['aarts', 'aarts2'], true)) {
                $result = sql::query("SELECT dub.id, dub.blockparent, dub.visible, dub.good_id_arts, good.art, good.name_rus name
                    FROM prname_b_" . $template . " dub
                    JOIN prname_b_catitem good on good.id = dub.good_id_arts
                    WHERE dub.blockparent=" . $blockparent . " ORDER by dub.sort");
            } else {
                $result = sql::query("SELECT * FROM prname_b_" . $template . " WHERE blockparent=" . $blockparent . " ORDER by sort");
            }

            $dataRel = sql::query("SELECT p2.* from prname_btemplates p1, prname_bdatarel p2 WHERE p1.key='" . $template . "' AND p2.templid=p1.id AND p2.show=1 ORDER by p2.sort");

            $j = 0;
            while ($dr = sql::fetch_assoc($dataRel)) {
                $dataKey[$j] = $dr['key'];
                $page->fields[$j]->name = $dr['name'];
                $page->fields[$j]->sorting = "sort-desc";


                $j++;
            }


            $j = 0;
            while ($r = sql::fetch_assoc($result)) {
                $page->item[$j]->id = $r['id'];

                $name = "";

                $jj = 0;
                foreach ($dataKey as $val1) {
                    $page->item[$j]->fields[$jj]->val = mb_substr(strip_tags($r[$val1]), 0, 50);
                    $jj++;
                }

                $page->item[$j]->name = $name;
                $page->item[$j]->visible = $r['visible'];

                $j++;

            }
            $page->template = $template;
            $page->blockparent = $blockparent;

            $page->rights = $this->getRights($template);

            $page->canAdd = $this->getRight("canadd", $template);
        } else {
            $page->no = true;
        }

        $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/itemlist.html');
    }

    //Вывод формы добавления блока
    function printAdd($mode = '')
    {
        global $control;


        $parent = all::getVar("parent");
        $template = all::getVar("template");
        $page->lpage = all::getVar("page");
        if (!$page->lpage) {
            $page->lpage = 0;
        }


        $page->status = $_SESSION['admin_status'];
        $page->admin_id = user_is('admin_id');

        $page->sitename = $control->settings->sitename;
        $page->theme = parent::$mainTheme;
        if (user_is("super") == '1') $page->super = true;
        $page->parent = $parent;
        $page->template = $template;

        if ($mode == 'item') {
            $page->blockparent = all::getVar("blockparent");
        }

        //Сео поля
        $seo = sql::one_record("SELECT seo FROM prname_btemplates WHERE `key`='" . $template . "'");
        if ($seo > 0) {
            $page->addFields[0]->name = "Адрес блока";
            $page->addFields[0]->key = "uurl";

            $page->addFields[1]->name = "Title";
            $page->addFields[1]->key = "utitle";

            $page->addFields[2]->name = "Description";
            $page->addFields[2]->key = "udescription";

            $page->addFields[3]->name = "Keywords";
            $page->addFields[3]->key = "ukeywords";
        }


        //Поля блока
        $templId = sql::one_record("SELECT id FROM prname_btemplates WHERE `key`='$template'");

        $sqlFields = sql::query("SELECT * FROM prname_bdatarel WHERE templid=$templId ORDER BY tab, sort, `key`");


        $i = 0;
        while ($field = sql::fetch_assoc($sqlFields)) {

            $class = "type_" . $field['datatkey'];
            $obj = new $class();
            $genValue = $obj->input('data' . $i, '', $field['comment'], $field['readonly']);

            //Если тип данных - загрузка изображений - выносим в отдельную вкладку (так как нужна новая форма для dropzone)

            if ($field['datatkey'] == 'imageload') {
                $genValue2 = $obj->input2('data' . $i, '', $field['comment']);

                $page->imageload[$i]->value = $genValue;
                $page->imageload[$i]->number = $i * 2000;
                $page->imageload[$i]->name = $field['name'];

                $page->tabs[0]->fields[$i]->name = $field['name'];
                $page->tabs[0]->fields[$i]->value = $genValue2;
                $page->tabs[0]->fields[$i]->key = $field['key'];
                $page->tabs[0]->fields[$i]->datatkey = $field['datatkey'];
                $page->tabs[0]->fields[$i]->index = $i;

            } else {
                $tab = $field['tab'];
                $page->tabs[$tab]->id = $tab;
                $page->tabs[$tab]->fields[$i]->name = $field['name'];
                $page->tabs[$tab]->fields[$i]->key = $field['key'];
                $page->tabs[$tab]->fields[$i]->value = $genValue;
                $page->tabs[$tab]->fields[$i]->datatkey = $field['datatkey'];
                $page->tabs[$tab]->fields[$i]->index = $i;
            }


            $i++;
        }

        $queryString = $_SERVER['QUERY_STRING'] ? "?" . substr($_SERVER['QUERY_STRING'], 0, strlen($_SERVER['QUERY_STRING']) - 1) : "";
        $page->queryString = $queryString;


        $page->add = true;


        if (in_array($page->template, array('aarts2', 'aarts'))) {
            $page->aatrs = true;
        } else {
            $page->aatrs = false;
        }

        if ($mode == '') {
            $page->menu = $this->menu;
            $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/' . $control->template . '_add.html');
        } else {
            $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/itemedit.html');
        }
    }


    //Обработка POST на добавление блока
    function add($mode = '')
    {
        global $control;
        $cache = new phpFastCache();
        $cache->cleanup();
        $parent = $_POST['parent'];
        $template = $_POST['template'];
        $lpage = $_POST['page'];


        if ($mode == 'item') {
            $blockparent = $_POST['blockparent'];
        }


        $sort = 1 + sql::one_record("SELECT MAX(sort) as msort FROM prname_b_" . $template);


        //Сначала то, что знаем
        $query = "INSERT INTO prname_b_" . $template . " SET parent=" . $parent . ", visible=1, sort=" . $sort;

        if ($mode == 'item') {
            //Сначала то, что знаем
            $query = "INSERT INTO prname_b_" . $template . " SET visible=1, sort=" . $sort . ", blockparent=" . $blockparent;
        }

//        sql::query($query);


//        $query .= ", visible=1";

        //Дальше - поля блока


        for ($i = 0; $i < count($_POST['dat']); $i++) {
            $class = "type_" . $_POST['dkey'][$i];
            $obj = new $class();

            $_genValue = $obj->save('data' . $i);
            if (is_array($_genValue)) {
                foreach ($_genValue as $k => $v) {
                    $genValue[$k] = mysql_real_escape_string($v);
                }

            } else {
                $genValueS = mysql_real_escape_string($_genValue);
            }

            if (is_array($genValue)) {

                foreach ($genValue as $__key => $__value) {
                    if ($__key == 0) {
                        $data[$_POST['dat'][$i]] = $__value;
                        $genValueS = $__value;
                    } else {
                        sql::query("INSERT INTO prname_b_" . $template . " SET visible=1, sort=" . ($sort + 1) . ", blockparent=" . $blockparent . " , `" . addslashes($_POST['dat'][$i]) . "` = '" . $__value . "' ");
                    }
                }
            } else {
                $data[$_POST['dat'][$i]] = $genValue;
            }


            $query .= " , `" . addslashes($_POST['dat'][$i]) . "` = '" . $genValueS . "' ";

        }


        if ($template == 'aarts' || $template == 'aarts2') {
            $row = sql::fetch_row(sql::query("SELECT art,name_rus  FROM prname_b_catitem WHERE id = " . $blockparent), -1);
            if (!empty($data['art']) && !empty($data['name'])) {
                if (!empty($row[0]) && !empty($row[1]) && !empty($data['good_id_arts'])) {
                    $_query = "INSERT INTO prname_b_" . $template . " SET visible=1, sort=" . $sort . ", blockparent=" . $data['good_id_arts'] . ", art='{$row[0]}', name = '{$row[1]}', good_id_arts='{$blockparent}'";
                    sql::query($_query);
                }
            }
        }


        //А потом SEO-поля
        if ($_POST['uurl'] != '') {
            $value = htmlspecialchars(trim($_POST['uurl']));
            $value = preg_replace("#[\"']#", "", $value);
            $url = sql::one_record("SELECT url FROM prname_tree WHERE id=" . $parent);
            $value = $url . $value . "/";
            $value = $this->matchesUrl($value);
            $query .= " , `uurl`='" . $value . "' ";
        }

        if ($_POST['utitle'] != '') {
            $value = htmlspecialchars(trim($_POST['utitle']));
            $query .= " , `utitle`='" . $value . "' ";
        }

        if ($_POST['udescription'] != '') {
            $value = htmlspecialchars(trim($_POST['udescription']));
            $query .= " , `udescription`='" . $value . "' ";
        }


        if ($_POST['ukeywords'] != '') {
            $value = htmlspecialchars(trim($_POST['ukeywords']));
            $query .= " , `ukeywords`='" . $value . "' ";
        }

//        $query .= " WHERE id=" . $_SESSION['newId'];


        # via-profit
        # if good_id are emty then set it permanetly (inject in sql query)
        if (
            isset($_POST['dat'][4]) &&
            $_POST['dat'][4] === 'good_id' &&
            isset($_POST['data4']) &&
            $_POST['data4'] === ''
        ) {

            $goodId = All::insert_block('catitem', $parent, array(
                'good_id' => '',
                'no_link' => '1',
                'num' => $_POST['data0'],
                'art' => $_POST['data1'],
                'name_rus' => $_POST['data2'],
                'name_eng' => $_POST['data3']
            ));
            $query = preg_replace('/`good_id`\s=\s\'\'/', 'good_id = \'' . $goodId . '\'', $query);
        }



        sql::query($query);
        $_SESSION['newId'] = sql::insert_id();
//        sql::query($query);

        //Сохраняем в таблицу с ЧПУ
        if ($_POST['uurl'] != '') {
            $value = htmlspecialchars(trim($_POST['uurl']));

            $value = preg_replace("#[\"']#", "", $value);

            $url = sql::one_record("SELECT url FROM prname_tree WHERE id=" . $parent);
            $value = $url . $value . "/";

            $value = $this->matchesUrl($value);


            $lastId = sql::one_record("SELECT MAX(id) FROM prname_b_" . $template);
            $realUrl = $url;

            $urlSql = "INSERT INTO prname_urls (`url`, `realurl`, `template`, `blockid`) VALUES ('" . $value . "', '" . $realUrl . "', '" . $template . "', " . $lastId . ")";
            sql::query($urlSql);
        }

        if (($template == 'aarts2' || $template == 'aarts') && $mode == 'item') {
            self::addItemsByParentId($blockparent, $template);
        }
        if ($mode == '') {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "_page" . $lpage . "/");
        } else {
            header("Location: /manage/blockedit/_aitemlist_parent" . $parent . "_id" . $blockparent . "_template" . $template . "/");
        }

    }

    //Вывод формы редактирования блока
    function printEdit($mode = '')
    {
        global $control;
        $parent = all::getVar("parent");
        $template = all::getVar("template");
        $blockid = all::getVar("id");

        $page->lpage = all::getVar("page");
        if (!$page->lpage) {
            $page->lpage = 0;
        }

        if ($mode == 'item') {
            $page->blockparent = all::getVar("blockparent");
        }


        $page->status = $_SESSION['admin_status'];
        $page->admin_id = user_is('admin_id');

        $page->sitename = $control->settings->sitename;
        $page->theme = parent::$mainTheme;
        if (user_is("super") == '1') $page->super = true;
        $page->parent = $parent;
        $page->template = $template;
        $page->blockid = $blockid;


        //Спец поля
        $seo = sql::one_record("SELECT seo FROM prname_btemplates WHERE `key`='" . $template . "'");
        if ($seo > 0) {

            $aF = sql::fetch_assoc(sql::query("SELECT uurl, utitle, udescription, ukeywords FROM prname_b_" . $template . " WHERE id=" . $blockid));

            $i = 0;
            foreach ($aF as $key => $val) {
                switch ($key) {
                    case "uurl" :
                        $page->addFields[$i]->name = "Адрес блока";
                        break;
                    case "utitle" :
                        $page->addFields[$i]->name = "Title";
                        break;
                    case "udescription" :
                        $page->addFields[$i]->name = "Description";
                        break;
                    case "ukeywords" :
                        $page->addFields[$i]->name = "Keywords";
                        break;
                }

                $page->addFields[$i]->key = $key;

                $page->addFields[$i]->value = $val;
                if ($key == 'uurl' && $val != '') {
                    $value = trim($val, "/");
                    $value = substr($value, strrpos($value, "/") + 1);
                    $page->addFields[$i]->value = $value;
                }
                $i++;
            }
        }


        //Поля блока
        $templId = sql::one_record("SELECT id FROM prname_btemplates WHERE `key`='" . $template . "'");

        $sqlFields = sql::query("SELECT * FROM prname_bdatarel WHERE templid=" . $templId . " ORDER BY tab, sort, `key`");

        $sqlData = sql::fetch_assoc(sql::query("SELECT * FROM prname_b_" . $template . " WHERE id=" . $blockid));

        $i = 0;


        $page->tabs[0]->fields[$i]->name = 'Порядок вывода';
        $page->tabs[0]->fields[$i]->value = (new type_text())->input('data'.$i, $sqlData['sort'], '', false);
        $page->tabs[0]->fields[$i]->key = 'sort';
        $page->tabs[0]->fields[$i]->datatkey = 'text';
        $page->tabs[0]->fields[$i]->index = $i;
        $i++;

        while ($field = sql::fetch_assoc($sqlFields)) {
            if ($blockid == 80 && $field['key'] == 'texthtml') {
                continue;
            }
            $value = $sqlData[$field['key']];

            $class = "type_" . $field['datatkey'];
            $obj = new $class();
            $genValue = $obj->input('data' . $i, $value, $field['comment'], $field['readonly']);


            //Если тип данных - загрузка изображений - выносим в отдельную вкладку (так как нужна новая форма для dropzone)

            if ($field['datatkey'] == 'imageload') {
                $genValue2 = $obj->input2('data' . $i, $value, $field['comment']);

                $page->imageload[$i]->value = $genValue;
                $page->imageload[$i]->number = $i * 2000;
                $page->imageload[$i]->name = $field['name'];

                $page->tabs[0]->fields[$i]->name = $field['name'];
                $page->tabs[0]->fields[$i]->value = $genValue2;
                $page->tabs[0]->fields[$i]->key = $field['key'];
                $page->tabs[0]->fields[$i]->datatkey = $field['datatkey'];
                $page->tabs[0]->fields[$i]->index = $i;

            } else {
                $tab = $field['tab'];
                $page->tabs[$tab]->id = $tab;
                $page->tabs[$tab]->fields[$i]->name = $field['name'];
                $page->tabs[$tab]->fields[$i]->key = $field['key'];
                $page->tabs[$tab]->fields[$i]->value = $genValue;
                $page->tabs[$tab]->fields[$i]->data_value = $value;
                $page->tabs[$tab]->fields[$i]->datatkey = $field['datatkey'];
                $page->tabs[$tab]->fields[$i]->index = $i;
            }

            $i++;
        }

        $queryString = $_SERVER['QUERY_STRING'] ? "?" . substr($_SERVER['QUERY_STRING'], 0, strlen($_SERVER['QUERY_STRING']) - 1) : "";
        $page->queryString = $queryString;

        if ($_GET['back_url']) {
            $page->back_url = $_GET['back_url'];
        }

        if ($mode == '') {
            $page->menu = $this->menu;
            $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/' . $control->template . '_add.html');
        } else {
            $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/itemedit.html');
        }

    }


    //Обработка POST на редактирование блока
    function edit($mode = '')
    {

        global $control;
        $cache = new phpFastCache();
        $cache->cleanup();
        $parent = $_POST['parent'];
        $template = $_POST['template'];
        $blockid = $_POST['blockid'];
        $lpage = $_POST['page'];


        if ($mode == 'item') {
            $blockparent = $_POST['blockparent'];
        }

        //Сначала то, что знаем
        $query = "UPDATE prname_b_" . $template . " SET visible=visible";

        //Дальше - поля блока
        for ($i = 0; $i < count($_POST['dat']); $i++) {
            $class = "type_" . $_POST['dkey'][$i];
            $obj = new $class();
            $genValue = mysql_real_escape_string($obj->save('data' . $i));

            $query .= " , `" . addslashes($_POST['dat'][$i]) . "` = '" . $genValue . "' ";
        }


        //А потом SEO-поля
        $seo = sql::one_record("SELECT seo FROM prname_btemplates WHERE `key`='" . $template . "'");

        if ($seo > 0) {
            $value = htmlspecialchars(trim($_POST['uurl']));
            $value = preg_replace("/[\"']+/", "", $value);

            //Если урл пустой пришел - удаляем запись из базы урлов
            if ($value == "") {
                $query .= " , `uurl`='' ";
                sql::query("DELETE FROM prname_urls WHERE template='" . $template . "' AND blockid=" . $blockid);
            } //Если есть урл, то пишем его в базу урлов
            else {
                $url = $realUrl = sql::one_record("SELECT url FROM prname_tree WHERE id=" . $parent);

                $value = $url . $value . "/";
                $value = $this->matchesUrl($value, $blockid, $template);

                $query .= " , `uurl`='$value' ";

                $urlS = sql::one_record("SELECT id FROM prname_urls WHERE template='" . $template . "' AND blockid=" . $blockid);
                //Если урл уже был - обновляем

                if ($urlS > 0) {
                    $q1 = "UPDATE prname_urls SET url='" . sql::escape_string($value) . "', realurl='".sql::escape_string($realUrl)."' WHERE template='" . $template . "' AND blockid=" . $blockid;
                    sql::query($q1);
                }
                //Если нет - вставляем новую запись
                else sql::query("INSERT INTO prname_urls (`url`, `realurl`, `template`, `blockid`) VALUES ('" . $value . "', '" . $realUrl . "', '" . $template . "', " . $blockid . ")");
            }


            if ($_POST['utitle'] != '') {
                $value = htmlspecialchars(mysql_real_escape_string(trim($_POST['utitle'])));
                $query .= " , `utitle`='" . $value . "' ";
                //$value = htmlspecialchars(trim($_POST['utitle']));
                //$query .= " , `utitle`='".$value."' ";
            }

            if ($_POST['udescription'] != '') {
                $value = htmlspecialchars(mysql_real_escape_string(trim($_POST['udescription'])));
                $query .= " , `utitle`='" . $value . "' ";
                //$value = htmlspecialchars(trim($_POST['udescription']));
                //$query .= " , `udescription`='".$value."' ";
            }

            if ($_POST['ukeywords'] != '') {
                $value = htmlspecialchars(mysql_real_escape_string(trim($_POST['ukeywords'])));
                $query .= " , `utitle`='" . $value . "' ";
                //$value = htmlspecialchars(trim($_POST['ukeywords']));
                //$query .= " , `ukeywords`='".$value."' ";
            }
        }

        $query .= " WHERE id=" . $blockid;

        if ($template == 'catitem') {
            $this->addItemsByParentId($blockid, 'aarts');
            $this->addItemsByParentId($blockid, 'aarts2');
        }

        //$this->updateCache($blockid, $parent, $template);

        $queryString = $_SERVER['QUERY_STRING'] ? "?" . substr($_SERVER['QUERY_STRING'], 0, strlen($_SERVER['QUERY_STRING']) - 1) : "";

        sql::query($query);

        if ($template == "catitem") {
            $q = sql::query('select * from it_b_catitem where id = ' . $blockid);
            $parentData = sql::fetch_assoc($q);
            $q = sql::query('select * from it_b_ablock where good_id = ' . $blockid);
            while ($arItem = sql::fetch_assoc($q)) {
                $children[] = $arItem;
            }

            foreach ($children as $child) {
                sql::one_record("UPDATE it_b_ablock SET art = '{$parentData['art']}', name_rus = '{$parentData['name_rus']}', name_eng = '{$parentData['name_eng']}' WHERE id = " . $child['id']);
            }

        } else if ($template == 'ablock') {
            $q = sql::query('select * from it_b_ablock where id = ' . $blockid);
            $parentData = sql::fetch_assoc($q);
            $q = sql::query('select * from it_b_catitem where id = ' . $parentData['good_id']);
            while ($arItem = sql::fetch_assoc($q)) {
                $children[] = $arItem;
            }
            foreach ($children as $child) {
                sql::one_record("UPDATE it_b_catitem SET art = '{$parentData['art']}', name_rus = '{$parentData['name_rus']}', name_eng = '{$parentData['name_eng']}' WHERE id = " . $child['id']);
            }

        }


        if (($template == 'aarts' || $template == 'aarts2') && $mode == 'item') {
            self::addItemsByParentId($blockparent);
        }
        if ($_REQUEST['back_url']) {
            $dataUrl = explode('_', $_REQUEST['back_url']);
            if (count($dataUrl)) {
                $dataId = array_shift($dataUrl);
                $dataTemplate = str_replace('/', '', implode('', $dataUrl));
                $q = sql::query("SELECT * FROM it_b_" . $dataTemplate . " WHERE id=" . $dataId);
                $dataParent = sql::fetch_assoc($q);
                header("Location: /manage/blockedit/_aedit_id" . $dataId . "_templateablock_parent" . $dataParent['parent'] . "_page0?getsearch=&sort&filter=undefined/");
            }
        }
        $queryString .= '&url_decode=1';

        if ($mode == '') {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "_page" . $lpage . $queryString . "/");
        } else {
            header("Location: /manage/blockedit/_aitemlist_parent" . $parent . "_id" . $blockparent . "_template" . $template . $queryString . "/");
        }

    }

    function addItemsByParentId($id, $template)
    {

        $q = sql::query('select * from it_b_' . $template . ' where blockparent = ' . $id);
        $parents = array();
        if ($id) {
            $parents[$id] = $id;
        }

        while ($item = sql::fetch_assoc($q)) {
            $items[$item['blockparent']] = array(
                'name' => $item['name'],
                'art' => $item['art'],
                'good_id_arts' => $item['good_id_arts'],
            );
            if ($item['good_id_arts']) {
                $parents[$item['good_id_arts']] = $item['good_id_arts'];
            }
        }

        $q = sql::query('select * from it_b_' . $template . ' where blockparent in (' . implode(',', $parents) . ') ');

        while ($item = sql::fetch_assoc($q)) {
            $items[$item['blockparent']] = array(
                'name' => $item['name'],
                'art' => $item['art'],
                'good_id_arts' => $item['good_id_arts'],
            );
            if ($item['good_id_arts']) {
                $parents[$item['good_id_arts']] = $item['good_id_arts'];
            }
        }

        $parents = array_diff($parents, array('', null));


        $q = sql::query('select distinct art,name,good_id_arts from it_b_' . $template . ' where blockparent in(' . implode(',', $parents) . ')');
        $distinctItems = array();
        while ($item = sql::fetch_assoc($q)) {
            if ($item['good_id_arts']) {
                $distinctItems[$item['art']] = array(
                    'name' => $item['name'],
                    'art' => $item['art'],
                    'good_id_arts' => $item['good_id_arts'],
                );
            }
        }

        $q = sql::query('select * from it_b_catitem where id in(' . implode(',', $parents) . ')');
        $parentsList = array();
        while ($item = sql::fetch_assoc($q)) {
            $parentsList[$item['id']] = array(
                'name' => $item['name_rus'],
                'art' => $item['art'],
                'good_id_arts' => $item['id']
            );
            $distinctItems[$item['art']] = array(
                'name' => $item['name_rus'],
                'art' => $item['art'],
                'good_id_arts' => $item['id']
            );
        }
        $q = sql::query('select * from it_b_' . $template . ' where blockparent in (' . implode(',', $parents) . ')');
        while ($item = sql::fetch_assoc($q)) {
            $parentsList[$item['blockparent']]['items'][] = $item;
        }


        foreach ($parentsList as $parentId => $parentData) {
            foreach ($distinctItems as $distinctItem) {
                $insert = true;
                foreach ($parentData['items'] as $parentItem) {
                    if ($distinctItem['art'] == $parentItem['art'] || $distinctItem['good_id_arts'] == $parentItem['good_id_arts']) {
                        $insert = false;
                    }
                }
                if ($distinctItem['good_id_arts'] == $parentId) {
                    $insert = false;
                }

                if ($insert) {
                    all::insert_block($template, null, $distinctItem, 1, $parentId);
                }
            }
        }


    }

    function addItemsByParentId_($id)
    {
        $q = sql::query('select * from it_b_aarts where blockparent = ' . $id);
        $removeIds = array();
        while ($item = sql::fetch_assoc($q)) {
            unset($item['blockparent']);
            unset($item['sort']);
            unset($item['modified']);
            unset($item['id']);
            $items[] = $item;
        }

        $q = sql::query('select * from it_b_catitem where id = ' . $id);
        $parentByArt = array();
        while ($item = sql::fetch_assoc($q)) {
            $parent = array(
                'name' => $item['name_rus'],
                'art' => $item['art'],
                'good_id_arts' => $item['id'],
            );


            $parentByArt[$item['art']] = $item;
        }


        $itemsIds = array_map(function ($item) {
            return $item['good_id_arts'];
        }, $items);


        $itemsIds = array_diff($itemsIds, array('', null));
        $blocksByArt = array();
        if (!empty($itemsIds)) {

            $q = sql::query('select * from it_b_aarts where blockparent in(' . implode(',', $itemsIds) . ')');
            while ($item = sql::fetch_assoc($q)) {
                $key = $item['blockparent'];
                unset($item['blockparent']);
                unset($item['sort']);
                unset($item['modified']);
                unset($item['id']);
                $blockItems[$key][] = $item;

            }


            $insertParent = true;
            if (empty($blockItems)) {
                foreach ($itemsIds as $dataId) {
                    all::insert_block('aarts', null, $parent, 1, $dataId);
                    $insertParent = false;
                }
            }

            foreach ($blockItems as $parentKey => $parentItems) {

                foreach ($parentItems as $parentItem) {
                    if ($parent['art'] == $parentItem['art']) {
                        $insertParent = false;
                    }
                }

                foreach ($items as $item) {
                    $needInsert = true;
                    foreach ($parentItems as $parentItem) {
                        if ($parentItem['art'] == $item['art']) {
                            $needInsert = false;
                            break;
                        }
                        if ($parentKey == $item['good_id_arts']) {
                            $needInsert = false;
                            break;
                        }

                    }

                    if ($needInsert) {
                        all::insert_block('aarts', null, $item, 1, $parentKey);
                    }
                }

                foreach ($parentItems as $parentItem) {
                    $needInsert = true;
                    foreach ($items as $item) {
                        if ($parentItem['art'] == $item['art']) {
                            $needInsert = false;
                            break;
                        }
                        if ($parentKey == $item['good_id_arts']) {
                            $needInsert = false;
                            break;
                        }
                    }
                    if ($needInsert) {
                        all::insert_block('aarts', null, $parentItem, 1, $id);
                    }
                }

            }
            if ($insertParent) {
                all::insert_block('aarts', null, $parent, 1, $dataId);
            }

        }
    }

    function delete($mode = '')
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $blockid = all::getVar("id");
        $template = all::getVar("template");
        $parent = all::getVar("parent");

        if ($mode == 'item') {
            $blockparent = all::getVar("blockparent");
            $data = all::b_data_all($blockid, $template);


            if ($template == 'aarts' || $template == 'aarts2') {
                $q = sql::query("select id from prname_b_$template where good_id_arts = " . $blockparent);
                while ($id = sql::fetch_assoc($q)) {
                    delete_block($id['id'], $template);
                }

                if ($data->good_id_arts && $data->art) {
                    $q = sql::query("select id from prname_b_$template where good_id_arts = " . $data->good_id_arts . ' and art = "' . $data->art . '"');
                    while ($id = sql::fetch_assoc($q)) {
                        delete_block($id['id'], $template);
                    }
                }
            }
        }


        //Определяем разрешение на удаление
        if (!$this->getRight("candel", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }

        delete_block($blockid, $template);

        if ($mode == '') {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
        } else {
            header("Location: /manage/blockedit/_aitemlist_parent" . $parent . "_id" . $blockparent . "_template" . $template . "/");
        }
    }

    //Групповое удаление
    function groupDel()
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $ids = all::getVar("ids");
        $template = all::getVar("template");
        $parent = all::getVar("parent");

        //Определяем разрешение на удаление
        if (!$this->getRight("candel", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }

        $ids = explode(";", $ids);

        foreach ($ids as $val) {
            if ($val !== "") {
                delete_block($val, $template);
            }
        }

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }

    function move()
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $blockid = all::getVar("id");
        $template = all::getVar("template");
        $parent = all::getVar("parent");
        $after = all::getVar("after");
        $before = all::getVar("before");


        //Определяем разрешение на перемещение
        if (!$this->getRight("canmove", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }


        if ($after > 0) {

            $sort = sql::one_record("SELECT sort FROM prname_b_$template WHERE id=$after");
            sql::query("UPDATE prname_b_$template SET sort=sort+1 WHERE sort>$sort AND parent=$parent");
            sql::query("UPDATE prname_b_$template SET sort=" . ($sort + 1) . " WHERE id=$blockid");
        }

        if ($before > 0) {
            $sort = sql::one_record("SELECT sort FROM prname_b_$template WHERE id=$before");
            sql::query("UPDATE prname_b_$template SET sort=sort+1 WHERE sort>=$sort AND parent=$parent");
            sql::query("UPDATE prname_b_$template SET sort=" . $sort . " WHERE id=$blockid");
        }

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }

    function copy()
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $blockid = all::getVar("id");
        $template = all::getVar("template");
        $parent = all::getVar("parent");

        //Определяем разрешение на копирование
        if (!$this->getRight("cancopy", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }

        $infoBlock = sql::fetch_assoc(sql::query("SELECT * FROM prname_b_" . $template . " WHERE id=" . $blockid));
        unset($infoBlock['id']);

        $sort = 1 + sql::one_record("SELECT MAX(sort) FROM prname_b_" . $template . " WHERE parent=" . $parent);
        $infoBlock['sort'] = $sort;
        if (isset($infoBlock['uurl']) && $infoBlock['uurl'] != "") {
            $infoBlock['uurl'] = $this->matchesUrl($infoBlock['uurl']);
        }
        $query = "INSERT INTO prname_b_" . $template . " SET ";

        $i = 0;
        foreach ($infoBlock as $key => $val) {
            if ($i != 0) $query .= ", ";
            $query .= "`" . $key . "` = '" . $val . "'";
            $i++;
        }

        sql::query($query);

        $lastId = sql::one_record("SELECT MAX(id) FROM prname_b_" . $template);

        if (isset($infoBlock['uurl']) && $infoBlock['uurl'] != "") {
            $url = $infoBlock['uurl'];
            sql::query("INSERT INTO prname_urls (`url`, `realurl`, `template`, `blockid`) SELECT '" . $url . "', `realurl`, `template`, " . $lastId . " FROM prname_urls WHERE template='" . $template . "' AND blockid=" . $blockid);

        }

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }

    function showHide()
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $parent = all::getVar("parent");
        $blockid = all::getVar("id");
        $template = all::getVar("template");

        //Определяем разрешение на скрытие/показ
        if (!$this->getRight("canhide", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }

        sql::query("UPDATE prname_b_" . $template . " SET visible=1-visible WHERE id=" . $blockid);

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }

    //Групповое скрытие
    function groupHide()
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $parent = all::getVar("parent");
        $ids = all::getVar("ids");
        $template = all::getVar("template");

        //Определяем разрешение на скрытие/показ
        if (!$this->getRight("canhide", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }

        $ids = explode(";", $ids);

        foreach ($ids as $val) {
            if ($val !== "") {
                sql::query("UPDATE prname_b_" . $template . " SET visible=0 WHERE id=" . $val);
            }
        }

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }

    //Групповое скрытие
    function groupShow()
    {
        global $control;

        $cache = new phpFastCache();
        $cache->cleanup();

        $parent = all::getVar("parent");
        $ids = all::getVar("ids");
        $template = all::getVar("template");

        //Определяем разрешение на скрытие/показ
        if (!$this->getRight("canhide", $template)) {
            header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
            return;
        }

        $ids = explode(";", $ids);

        foreach ($ids as $val) {
            if ($val !== "") {
                sql::query("UPDATE prname_b_" . $template . " SET visible=1 WHERE id=" . $val);
            }
        }

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }


    // Перемещение блоков к другому родителю
    function moveTo()
    {
        global $control;
        $ids = all::getVar("ids");
        $template = all::getVar("template");
        $parent = all::getVar("parent");
        $newParent = all::getVar("new") + 1 - 1;


        $ids = explode(";", $ids);
        $sort = sql::one_record("SELECT MAX(sort) FROM prname_b_$template WHERE parent=$newParent");


        foreach ($ids as $val) {
            if ($val !== "") {
                $sort++;
                sql::query("UPDATE prname_b_$template SET parent=$newParent, sort=$sort WHERE id=$val");
                $updateId = sql::one_record("SELECT id FROM prname_urls WHERE blockid=" . $val);

                $rurl = sql::one_record("SELECT url FROM prname_tree WHERE id=" . $newParent);
                if ($rurl && $updateId) {
                    $url = sql::one_record("SELECT uurl FROM prname_b_$template WHERE id=" . $val);
                    $url = explode('/', $url);
                    $url  = $url[count($url) -2];
                    sql::query("UPDATE prname_urls SET realurl='$rurl', url='$rurl$url/' WHERE blockid=$val");
                    sql::query("UPDATE prname_b_$template SET uurl='$rurl$url/' WHERE id=$val");

                }

            }
        }

        header("Location: /manage/blockedit/_alist_parent" . $parent . "_template" . $template . "/");
    }

    function getRights($template)
    {
        if (user_is("super") != "1") {
            $rights = sql::fetch_assoc(sql::query("SELECT canadd, candel, canedit, canmove, cancopy, canhide FROM prname_btemplates WHERE `key`='" . $template . "'"));

            foreach ($rights as $key => $val) {
                $rights[0]->$key = $val;
            }
            return $rights;
        } else return 0;
    }

    function getRight($name, $template)
    {
        if (user_is("super")) return true;
        $right = sql::one_record("SELECT " . $name . " FROM prname_btemplates WHERE `key`='" . $template . "'");
        if ($right > 0) return true;
        return false;
    }

    function matchesUrl($url, $id = null, $template = null)
    {
        if ($id != null) {
            $matches = sql::one_record("SELECT id FROM prname_urls WHERE url='" . $url . "' AND (blockid<>" . $id . " AND template='" . $template . "')");
        } else {
            $matches = sql::one_record("SELECT id FROM prname_urls WHERE url='" . $url . "'");
        }


        if ($matches != "") {
            $value = trim($url, "/");
            $value .= rand(0, 9) . "/";

            return $this->matchesUrl($value, $id, $template);
        } else {
            return $url;
        }
    }

    function updateCache($blockid, $parent, $template)
    {
        $data = all::b_data_all($blockid, $template);
        $list = new Listing($template, 'blocks', $parent, " id=$blockid AND ");
        $list->getList();
        $list->getItem();

        $item = $list->item[0];
        $url = str_replace(array('{base_url}', '<!--base_url//-->'), '', $item->url);

        $arr_string = preg_split('#/#', $url, null, PREG_SPLIT_NO_EMPTY);

        if (count($arr_string) > 0) {
            $cdir = 'templates/_templates/';

            foreach ($arr_string as $one_arr) {
                $d = opendir($cdir);
                while ($entry = readdir($d)) {
                    if (is_file($cdir . $entry) && $entry != '..' && $entry != '.') {

                        unlink($cdir . $entry);
                    }
                }

                $cdir .= $one_arr . '/';
            }

            $d = opendir($cdir);
            while ($entry = readdir($d)) {
                if (is_file($cdir . $entry) && $entry != '..' && $entry != '.') {

                    unlink($cdir . $entry);
                }
            }
        }
    }

    private function trigger()
    {
        global $control, $config;

        $id = $_POST['id'] + 1 - 1;
        $template = $_POST['template'];
        $field = $_POST['field'];
        $value = $_POST['value'];

        if ($value == "true") {
            $value = 1;
        } else {
            $value = 0;
        }
        $sql = "UPDATE prname_b_$template SET $field=$value WHERE id=$id";
        sql::query($sql);
        die();
    }

    // Мультиселект триггер
    private function mselecttrigger()
    {
        global $control, $config;


        $id = $_POST['id'] + 1 - 1;
        $template = $_POST['template'];
        $field = $_POST['field'];
        $value = $_POST['value'];
        $value = implode(";", $value);


        $sql = "UPDATE prname_b_$template SET $field='$value' WHERE id=$id";
        sql::query($sql);
        die();
    }

    // Селект триггер
    private function selecttrigger()
    {
        global $control, $config;


        $id = $_POST['id'] + 1 - 1;
        $template = $_POST['template'];
        $field = $_POST['field'];
        $value = $_POST['value'];

        $sql = "UPDATE prname_b_$template SET $field='$value' WHERE id=$id";
        sql::query($sql);

        // Callback
        if ($_POST['callback'] != "") {
            $trigger = new triggers();
            $callback = $_POST['callback'];
            $trigger->$callback($id);
        }
        die();
    }

    // Рассылка
    private function subscribetrigger()
    {
        global $control, $config;


        $id = $_POST['id'] + 1 - 1;
        $template = $_POST['template'];
        $field = $_POST['field'];

        // Список пользователей-адресатов
        $list = new Listing("user", "blocks", "all", "subscribe=1 AND ");
        $list->getList();
        $list->getItem();
        $users = $list->item;

        // Информация для рассылки
        $info = all::b_data_all($id, $template);

        //$info->text = str_replace("/ufiles/", "http://".$_SERVER["SERVER_NAME"]."/ufiles/", $info->text);

        $page->info = $info;
        $page->theme = $info->name;
        $page->domain = $_SERVER["SERVER_NAME"];

        $sitename = $control->settings->sitename;

        // Генерация e-mail'ов и тела письма
        $message = sprintt($page, 'mailtemplates/topeople/subscribe.html');


        if (isset($info->email) && !empty($info->email) && trim($info->email) != '') {
            $arrTestDelivery = explode(',', trim($info->email));
            unset($users);
            foreach ($arrTestDelivery AS $key => $val) {
                $usersTest[] = trim($val);
            }
        }


        if ($usersTest) {
            all::send_mail($usersTest, $page->theme, $message, false, false, $sitename);
        } else {
            foreach ($users as $key => $val) {
                // Отправка писем
                all::send_mail($val->email, $page->theme, $message, false, false, $sitename);
            }
        }

        /*foreach ($users as $key => $val) {
            // Отправка писем
            all::send_mail($val->email, $page->theme, $message, false, false, $sitename);
        }*/


        // Обновление поля - записываем информацию о дате отправки
        $date = date("Y-m-d H:i:s");
        $sql = "UPDATE prname_b_$template SET $field='$date' WHERE id=$id";
        sql::query($sql);

        $result = array('count' => (string)count($users), 'date' => $date);

        die(json_encode($result));
        //die((string)count($users));
    }
}

?>