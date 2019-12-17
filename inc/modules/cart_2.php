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
        if(!empty($_REQUEST['id'])){
            $_SESSION['cart'][$_REQUEST['id']] += $_REQUEST['count'];
        }

        echo $this->_calc();
        die();

    }

    private function _calc()
    {
        $count = 0;
        foreach ($_SESSION['cart'] as $key => $item) {

            if(!empty($key)){
                $count += $item;
            }

        }
        return $count;
    }

    private function _cart_print()
    {
        global $control;
        foreach ($_SESSION['cart'] as $key => $value){
            if(!$key){
                unset($_SESSION['cart'][$key]);
            }
        }
        if (empty($_SESSION['cart'])) {
            $this->html['text'] = "<h3>Корзина пуста</h3>";
            return;
        }
        $data = array_keys($_SESSION['cart']);
        $data = array_diff($data,array('',null));
        foreach ($data as $key => $item) {

            if ($item[0] == 'P') {
                $parents[] = str_replace('P', '', $item);
                unset($data[$key]);
            }
        }

        $ids = implode(',', $data);

        $p_ids = implode(',', $parents);

        if ($ids) {
            $d = sql::query("
              SELECT v.id,v.parent,v.blockparent,v.img,v.price,v.time,v.art, c.name_rus as name, t.url,c.id as bid 
              FROM it_b_variant v 
              inner join it_b_catitem c on c.id = v.blockparent
              inner join it_tree t on c.parent = t.id
            
              WHERE v.id IN ($ids)");
            $page = new stdClass();
            while ($obj = sql::fetch_object($d)) {
                $obj->class = 'variant';
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
        foreach ($arr as $cart){
            if($cart->price > 0){
                $price[] =$cart;
            }else{
                $bill[] =$cart;
            }
        }
        if($login = $_SESSION['login']){
            $page->user = sql::fetch_object(sql::query("SELECT * FROM prname_b_user3 WHERE email = '".$login."'"));
        }
        
        $page->items = $arr;
        $page->price = $price;
        $page->bill = $bill;

        $this->html['text'] = sprintt($page, 'templates/cart/cart.html');

    }
}

?>