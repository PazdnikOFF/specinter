<?php

class cart_2
{

    public function __construct()
    {
        global $control;

        if ($_REQUEST["remove"]) {
            $this->_remove();
        } elseif ($_REQUEST["add_cart"]) {
            $this->_addCart();
        } else {
            $this->_cart_print();
        }
    }

    private function _remove()
    {

        global $control;

        unset($_SESSION['cart'][$_REQUEST['remove']]);
        echo $this->_calc();
        die();

    }

    private function _addCart()
    {

        global $control;
        if (!empty($_REQUEST['id'])) {
            $_SESSION['cart'][$_REQUEST['id']] += $_REQUEST['count'];
        }

        echo $this->_calc();
        die();

    }

    private function _calc()
    {
        $count = 0;
        foreach ($_SESSION['cart'] as $key => $item) {

            if (!empty($key)) {
                $count += $item;
            }

        }
        return $count;
    }

    private function _cart_print()
    {
        global $control;

        foreach ($_SESSION['cart'] as $key => $value) {
            if (!$key) {
                unset($_SESSION['cart'][$key]);
            }
        }
        if ($id = (int)$_REQUEST['order_id']) {
            $ids = array_map(function ($data) {
                return (int)$data;
            }, explode('|', $_REQUEST['id']));

            $result = sql::fetch_array(sql::query("SELECT * FROM `it_b_orderinfo` WHERE id = " . $id));
            $goods = unserialize($result['order_json']);

            $_SESSION['cart'] = array();
            foreach ($goods['goods'] as $id => $ct) {
                if (in_array($id, $ids) || in_array(str_replace('P','',$id), $ids)) {
                    $_SESSION['cart'][$id] = $ct;
                }


            }

            header('Location: /cart/');

            exit();

        } else if (empty($_SESSION['cart'])) {
            $this->html['text'] = "<h3>Корзина пуста</h3>";
            return;
        }

        $data = array_keys($_SESSION['cart']);
        $data = array_diff($data, array('', null));

        foreach ($data as $key => $item) {

            if ($item[0] == 'P') {
                $parents[] = str_replace('P', '', $item);
                unset($data[$key]);
            }elseif($item[0] == 'C'){
                $cItems[] =  str_replace('C', '', $item);
                unset($data[$key]);
            }elseif($item[0] == 'С'){
                $cItems[] =  str_replace('С', '', $item);
                unset($data[$key]);
            }
        }

        $ids = implode(',', array_filter(array_merge([0], $data), 'is_numeric'));
        $p_ids = implode(',', array_filter(array_merge([0], $parents), 'is_numeric'));
        $c_ids = implode(',', array_filter(array_merge([0], $cItems), 'is_numeric'));

        if ($ids) {
            $d = sql::query("
              SELECT v.id,v.parent,v.blockparent,v.img,v.price,v.time,v.art, c.name_rus as name, t.url,c.id as bid , c.name_eng as name_eng 
              FROM it_b_variant v 
              inner join it_b_catitem c on c.id = v.blockparent
              inner join it_tree t on c.parent = t.id
            
              WHERE v.id IN ($ids) or c.id IN ($ids)");

            $page = new stdClass();
            while ($obj = sql::fetch_object($d)) {
                $obj->class = 'variant';
                $obj->count = (int)$_SESSION['cart'][$obj->id];
                $obj->calc = $obj->count * $obj->price;
                $page->total += $obj->calc;
                $arr[] = $obj;
            }
        }
        if(!empty($cItems)){
            $d = sql::query("
              SELECT v.id,v.parent,v.blockparent,v.img, 0 as price,v.time,v.art, c.name_rus as name, t.url,c.id as bid , c.name_eng as name_eng 
              FROM it_b_catitem c 
              inner join it_b_variant v on c.id = v.blockparent
              inner join it_tree t on c.parent = t.id
            
              WHERE c.id IN ($c_ids)");

            $page = new stdClass();
            while ($obj = sql::fetch_object($d)) {
                $obj->class = 'variant';
                $obj->id = 'C' . $obj->bid;
                $obj->count = (int)$_SESSION['cart'][$obj->id];
                $obj->calc = $obj->count * $obj->price;
                $page->total += $obj->calc;
                $arr[] = $obj;
            }
        }

        if ($p_ids) {
            $d = sql::query("
              SELECT c.id,c.name_rus,t.url,c.art FROM prname_b_catitem c
              inner join it_tree t on c.parent = t.id
              WHERE c.id IN ($p_ids)
              
              ");
            while ($obj = sql::fetch_object($d)) {
                $obj->id = 'P' . $obj->id;
                $obj->name = $obj->name_rus;
                $obj->count = (int)$_SESSION['cart'][$obj->id];
                $arr[] = $obj;
            }
        }
        $price = $bill = array();
        foreach ($arr as $cart) {
            if ($cart->price > 0) {
                $price[] = $cart;
            } else {
                $bill[] = $cart;
            }
        }
        if ($login = $_SESSION['login']) {
            $page->user = sql::fetch_object(sql::query("SELECT * FROM prname_b_user3 WHERE email = '" . $login . "'"));
        }

        $page->items = $arr;
        $page->price = $price;
        $page->bill = $bill;

        $this->html['text'] = sprintt($page, 'templates/cart/cart.html');

    }
}

?>