
<?
die('Ooops');
$link = mysql_connect('localhost', 'u67887', 'Awmn3a4HzCbVwuC');
mysql_set_charset('utf8');
mysql_select_db("u67887", $link);

$sql = "
  select
    item.id,
    item.art,
    item.name_rus,
    item.name_eng,
    block.id as block_id
  from
    it_b_catitem as item
  left join
    it_b_ablock as block
  on
    block.good_id = item.id
  where
    block.id is null;
";
$result = mysql_query($sql, $link);


while ($row = mysql_fetch_assoc($result)) {

  $sql = "
  INSERT INTO it_b_ablock
  (parent, blockparent, sort, visible, modified, num, art, name_rus, name_eng, good_id)
  VALUES(451, 0, 0, 1, CURRENT_TIMESTAMP, '', '".mysql_real_escape_string($row['art'])."', '".mysql_real_escape_string($row['name_rus'])."', '".mysql_real_escape_string($row['name_eng'])."', ".$row['id'].");
  
  ";
  mysql_query($sql);
  echo "Linked item [".$row['name_rus']."]\n";

}



mysql_close($link);
die('Done');

