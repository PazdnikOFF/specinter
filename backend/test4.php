<?php
error_reporting(E_ALL);
$_SERVER['DOCUMENT_ROOT'] = '/home/c26864/specinter.ru/www/';
include "includes.php";

$sql = new Sql();
$sql->connect();

$res = sql::query('
select 
  *
from it_b_ablock 
where uurl = ""

');


while ($row = sql::fetch_object($res)) {
var_dump($row);
    if ($row->art) {
        $itemUrl = translit_sef($row->art);
        $parentUrl = ltrim(all::getUrl($row->parent), '/');
        sql::query('update it_b_ablock set uurl = "' . $parentUrl . $itemUrl . '" where id =' . $row->id);
        sql::query('INSERT INTO it_urls (url,realurl,template,blockid) VALUES ("' . $parentUrl . $itemUrl . '/","' . $parentUrl . '","ablock",' . $row->id . ')');
    }
}


function translit_sef($value)
{
    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    );

    $value = mb_strtolower($value);
    $value = strtr($value, $converter);
    $value = mb_ereg_replace('[^-0-9a-z]', '-', $value);
    $value = mb_ereg_replace('[-]+', '-', $value);
    $value = trim($value, '-');

    return $value;
}