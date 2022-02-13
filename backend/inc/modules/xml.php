<?php
include_once __DIR__ . '/../../libs/PHPExcel-1.8/Classes/PHPExcel.php';

class xml extends manage
{
    const PRICE = 1.15;

    public function __construct()
    {


        if ($_GET['read'] == 1) {
            $info = sql::fetch_assoc(sql::query("SELECT COUNT(id) as count FROM it_suppliers "));

            $page = !empty($_GET['page']) ? $_GET['page'] : 0;

            $result = $this->read($page);
            $result['update'] += $_GET['update'];
            $result['crete'] += $_GET['crete'];
            $page++;

            if (ceil($info['count'] / 500) >= $page) {
                header("Location: /manage/xml/?read=1&page=" . $page . "&update=" . $result['update'] . "&crete=" . $result['crete']);
            }

        }


        if (!empty($_FILES['file']['tmp_name'])) {
            $objPHPExcel = PHPExcel_IOFactory::load($_FILES['file']['tmp_name']);
            $sheets = $objPHPExcel->setActiveSheetIndex(0);
            $xlsFileArr = $sheets->toArray(null, false, false, false);
            sql::query("TRUNCATE TABLE it_suppliers");
            foreach ($xlsFileArr as $key => $row) {
                if (!empty($row[6]) && $key > 6) {
                    $data = array(
                        'maker' => $row[0],
                        'name' => $row[1],
                        'art' => $row[2],
                        'price' => $row[4],
                        'quantity' => $row[5],
                        'code' => $row[6],
                    );


                    $sql = "INSERT INTO it_suppliers (supplier, data) VALUES('" . $_POST['supplier'] . "', '" . serialize($data) . "');";
                    mysqli_query(Sql::$connection, $sql);
                    header("Location: /manage/xml/?read=1&page=0&update=0&crete=0");
                }
            }

        } elseif ($page) {
            global $control;
            $items = new stdClass();


            $items->menu = $this->menu;
            $parent = all::getVar("parent");
            $template = all::getVar("template");
            $items->lpage = all::getVar("page");

            if (!$items->lpage) {
                $items->lpage = 0;
            }

            $items->status = $_SESSION['admin_status'];
            $items->admin_id = user_is('admin_id');

            $items->sitename = $control->settings->sitename;
            $items->theme = parent::$mainTheme;

            if (user_is("super") == '1') $items->super = true;
            $items->parent = $parent;
            $items->template = $template;
            $this->menu = $this->getMenu();
            $items->menu = $this->menu;

            $items->update = $result['update'];
            $items->crete = $result['crete'];
            $this->html['text'] = sprintt($items, 'templates/xml/xml.html');
        } else {
            $this->printPage();
        }
    }

    private function read($page = 0)
    {
        $res = sql::query("SELECT * FROM it_suppliers LIMIT " . $page * 500 . ",500");
        $result = [];

        while ($row = sql::fetch_assoc($res)) {
            $data = unserialize($row['data']);
            $res2 = sql::query("SELECT * FROM it_b_variant WHERE xlsx_id = '" . $data['code'] . "'");
            $update = false;
            $price = ceil(($data['price'] * self::PRICE) / 0.5) * 0.5;

            while ($row2 = sql::fetch_assoc($res2)) {
                $update = true;
                sql::query("UPDATE `it_b_variant` SET `art` ='{$data['art']}',`name` = '{$data['name']}',`quantity` = '{$data['quantity']}',`price` = '{$price}' WHERE `id` = {$row2['id']}");
                sql::query("UPDATE `it_b_catitem` SET `art` ='{$data['art']}',`name_rus` = '{$data['name']}' WHERE `id` = {$row2['blockparent']}");
            }

            if (!$update) {
                $result['crete']++;
                sql::query("INSERT INTO it_b_catitem (name_rus, art,parent,visible) VALUES ('{$data['name']}','{$data['art']}',245,1)");
                $id = sql::insert_id();
                $sort = 1 + sql::one_record("SELECT MAX(sort) as msort FROM it_b_ablock WHERE parent=451");
                sql::query("INSERT INTO it_b_ablock (name_rus, art,good_id,parent,visible,sort) VALUES ('{$data['name']}','{$data['art']}','{$id}',451,1,'{$sort}')");

                all::insert_block('variant', $id, array(
                    'price' => $price,
                    'name' => $data['name'],
                    'art' => $data['art'],
                    'quantity' => $data['quantity'],
                    'xlsx_id' => $data['code']
                ), 1, $id);


            } else {
                $result['update']++;
            }
        }

        return $result;
    }

    private function printPage()
    {
        global $control;
        global $config;
        $tableList = array('it_b_variant','it_b_catitem','it_b_ablock');
//        $tableList = array('test');//TRUNCATE TABLE b_admin_notify;

        $dbhost = $config['dbhost'];
        $dbuser = $config['dbuser'];
        $dbpass = $config['dbpass'];


        $page = new stdClass();
        $page->menu = $this->menu;
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
        $this->menu = $this->getMenu();
        $page->menu = $this->menu;
        $dirPath = $_SERVER['DOCUMENT_ROOT'] . 'backup/';
        $list = scandir($dirPath);
        $listItems = [];
        foreach ($list as $item) {
            if (preg_match('/\d+/m', $item)) {
                $tmp = new stdClass();
                $tmp->name = date('Y.m.d H:i:s', $item);
                $tmp->code = $item;
                $listItems[$item] = $tmp;
            }
        }

        if (count($listItems) > 14) {
            $vals = array_values($listItems);
            for ($i = 0; $i <= (count($listItems) - 14); $i++) {
                if ($vals[$i]) {
                    system('rm -rf ' . $_SERVER['DOCUMENT_ROOT'] . 'backup/' . $vals[$i]->code);
                    unset($listItems[$vals[$i]->code]);
                }
            }
        }

        if (!empty($_REQUEST['bu'])) {

            $path = $_SERVER['DOCUMENT_ROOT'] . 'backup/' . time();
            mkdir($path);


            foreach ($tableList as $table) {
                $command = "mysqldump --opt -h$dbhost -u$dbuser -p$dbpass " . " " . $config['dbname'] . " $table > $path/$table.sql";
                $test = exec($command);
            }


            header("Location: /manage/xml/");
        } else if (!empty($_REQUEST['b']) || !empty($_REQUEST['d'])) {
            $code = !empty($_REQUEST['b']) ? $_REQUEST['b'] : $_REQUEST['d'];
            $type = !empty($_REQUEST['d']) ? 'delete' : 'backup';
            $code = mb_substr($code, 0, -1);


            if ($type == 'delete' && is_dir($_SERVER['DOCUMENT_ROOT'] . 'backup/' . $code)) {
                exec('rm -rf ' . $_SERVER['DOCUMENT_ROOT'] . 'backup/' . $code);
                unset($listItems[$code]);
            } elseif ($type == 'backup') {
                $path = $_SERVER['DOCUMENT_ROOT'] . 'backup/' . $code;
                foreach ($tableList as $table) {
                    $command = "mysql --user=$dbuser --password=$dbpass " . $config['dbname'] . " < $path/$table.sql";
                    system($command);

                }
            }
//            header("Location: /manage/xml/");

        }
        krsort($listItems);
        $page->items = $listItems;


        $this->html['text'] = sprintt($page, 'templates/xml/xml.html');

    }

    private function printOne($bid)
    {
        global $control;

        $sign = md5($control->template . $control->module_url . $control->urlparams);
        phpFastCache::$storage = "auto";
        $content = phpFastCache::get($sign);

        if ($content == null) {
            $page = all::b_data_all($bid, $control->module_wrap);

            $page->back = all::getUrl($control->module_parent) . all::addUrl($this->page);
            $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/' . $control->template . '_one.html');

            // Кешируем на 24 часа
            // phpFastCache::set($sign, $this->html['text'], 86400);
        } else {
            $this->html['text'] = $content;
        }
    }

    private function printList($cid)
    {
        global $control;

        $sign = md5($control->template . $control->module_url . $control->urlparams);
        phpFastCache::$storage = "auto";
        $content = phpFastCache::get($sign);

        if ($content == null) {
            $list = new Listing($control->module_wrap, "blocks", $cid);
            $list->page = $control->page;
            $list->tmp_url = all::getUrl($control->module_parent);
            $list->getList();
            $list->getItem();
            $list->getPage();

            $page->item = $list->item;
            $page->page = $list->navigation;
            $page->url_last = $list->url_last;
            $page->url_p = $list->url_p;
            $page->url_n = $list->url_n;
            $page->url_next = $list->url_next;


            $page->name = $control->name;
            $page->pages_down = sprintt($page, 'templates/temps/pages_down.html');
            $this->html['text'] = sprintt($page, 'templates/' . $control->template . '/' . $control->template . '.html');

            // Кешируем на 24 часа
            // phpFastCache::set($sign, $this->html['text'], 86400);
        } else {
            $this->html['text'] = $content;
        }
    }
}

?>