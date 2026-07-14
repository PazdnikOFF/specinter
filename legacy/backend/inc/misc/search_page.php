<?php

class search_page
{
    function Make($wrapper)
    {
        global $control;
        
        if ($_REQUEST['search-request']) {
            $page->search = $_REQUEST['search-request'];
        }
        $text = sprintt($page, 'templates/misc/'.$wrapper);
        return $text;
    }
}

?>

