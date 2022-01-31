<?php
require_once(BACKEND_ROOT."/inc/helpers/helper_images.php");
class helper_items {

	private static $stocks = array();

	private static $tables = array();

	public function processItems($items) {

		$catalogUrl = all::getUrl(40);
		if (count(self::$stocks) < 1) {
			// Склады
			$list = new Listing("address", "blocks", "all");
			$list->getList();
			$list->getItem();
			$stocks = $list->item;

			$newStocks = array();
			foreach ($stocks as $stock_one) {
				$newStocks[$stock_one->stockname] = $stock_one->address;
			}


			self::$stocks = $newStocks;
		}

/*if(isset($_COOKIE['developer'])){
	echo"<pre>";
	var_dump($items);
	echo"</pre>";
	die();
}*/
		foreach ($items as $key => $val) {
			if (!isset($val->url_item))
			    $val->url_item = $catalogUrl . '_aview_b' . $val->cid . '/';
			// Цена
			$val->price_format = number_format(str_replace(',', '.', ceil($val->price)), 0, ".", ",");

			// В корзине
			if (array_key_exists($val->id, $_SESSION['cart'])) {
				$val->inCart = true;
			}

			// Изображение
			$val->image = helper_images::processImages($val->image);
			if ($val->vimage) {
				$val->vimage = helper_images::processImages($val->vimage);
			}

			// Наличие
			
			$ballance = explode(";", $val->ballance);
		
			$val->ballance = array();
			$val->ballanceIsset = false;
			$i = 0;
			$cnt_ballance = 0;

			foreach($ballance as $bkey => $bval) {
				if ($bval != "") {
					$b_one = explode("]", $bval);
					$count = trim($b_one[0], "[");
					$stock = $b_one[1];

					if (self::$stocks[$stock]) {
						$val->ballance[$i]->count = $count;
						$val->ballance[$i]->stock = self::$stocks[$stock];
						$cnt_ballance += $count;
						$val->ballanceIsset = true;
						$i++;
					}
					if(empty($val->ballance[$i]->count) || empty($val->ballance[$i]->stock)){
						unset($val->ballance[$i]);
					}
				}
			}


			$val->cnt_ballance=$cnt_ballance;

			// Бренд
			$val->brand = mysqli_real_escape_string(Sql::$connection, $val->brand);


			// Виды товара
			foreach ($val->variants as $key2 => $val2) {
				// Изображение
				$val2->image = helper_images::processImages($val2->image);

				$val->zoom = true;
				$size = getimagesize(DOC_ROOT."/images/$val->image");


				// Наличие
				$ballance = explode(";", $val2->ballance);
				$val2->ballance = array();
				$val2->ballanceIsset = false;
				$i = 0;

				foreach($ballance as $bval){
					if ($bval != "") {
						$b_one = explode("]", $bval);
						$count = trim($b_one[0], "[");
						$stock = $b_one[1];

						$val2->ballance[$i]->count = $count;
						$val2->ballance[$i]->stock = self::$stocks[$stock];
						
						if(isset($val2->ballance[$i]->stock)){
							$val2->ballanceIsset = true;
						}
						$i ++;
					}
				}
				$val2->cnt_item = 1;
			}
		}


		return $items;
	}

	public static function myCompareFunc($a, $b) {
		$a = $a->ballanceIsset;
		$b = $b->ballanceIsset;

		if ($a === $b) {
			return 0;
		}
		if ($a < $b) {
			return 1;
		}
		if ($a > $b) {
			return -1;
		}
	}
}
?>