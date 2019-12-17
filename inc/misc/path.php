<?php
  class path{

      function Make($wrapper)	{
          global $control;
          $parents = array();
          $page = new stdClass();
          for ($i = 1; $i < (sizeof($control->parents)); $i++) {
              $parents[$i] = new stdClass();
              $parents[$i]->name = All::get_name($control->parents[$i]);
              $parents[$i]->url = All::getUrl($control->parents[$i]);
          }
          $page->items= $parents;
          $text = sprintt($page, 'templates/misc/'.$wrapper);
          return $text;
      }
  }

?>