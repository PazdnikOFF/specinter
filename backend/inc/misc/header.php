<?php

class header
{

    function Make($wrapper)
    {
        global $control;


        $page = all::c_data_all(12, 'settings');
        $page->active = $control->cid;
        if (
            isset($_SESSION['user_name']) &&
            isset($_SESSION['password']) &&
            isset($_SESSION['login'])

        ) {
            $page->login = $_SESSION['user_name'];
        }

        $count = 0;

        if (is_array($_SESSION['cart']))
            $cart = $_SESSION['cart'];
            foreach ($cart as $key => $item) {
                if($key){
                    $count += $item;
                }
            }
        $page->cart = $count;
        $text = sprintt($page, 'templates/misc/' . $wrapper);

        return $text;
    }
}

?>

