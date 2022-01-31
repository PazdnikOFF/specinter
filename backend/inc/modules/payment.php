<?php 

class payment{
	
	private $SHOP_ID = "64737"	; /*параметр, передаваемый Интернет-магазином для совершения платежа, в соответствии с pуководством*/
	private $LOGIN = "oplata-maestria@mail.ru"; /*E-mail адрес ответственного сотрудника*/
	private $SHOP_NAME = "maestria.su"; /*должно указываться в чеке, который предоставляется заказчику на эл.адрес*/
	private $PAY_PASS = "ubr02884"; /*пароль для совершения платежей*/
	private $SHOP_ID_PAYCANCEL = "00002884"; /*используется при направлении заявления об отмене операции оплаты товара*/
	private $PSWD = "513809";
	private $resultRequest = array();
	
	public function __construct(){
		global $control;				
		if((isset($_POST['STATE']) && !empty($_POST['STATE'])) && $control->cid == '64'){
			$this->bankAnswer();
		}else{
			$this->payment();
		}
	}
		
	public function payment(){
		global $control;
		
		if($_GET['paymentOk'] == 1){
			$page->text = "Ваш заказ оплачен";
		}
		if($_GET['paymentOk'] == 0){
			$page->text = "Оплата отменена";
		}
		
		if(isset($_GET['payment'])){
			$page->SHOP_ID = 	$this->SHOP_ID;
			$page->LOGIN = 		$this->LOGIN;
			$page->PAY_PASS = 	md5($this->PAY_PASS);
			$page->PSWD = 		$this->PSWD;
			$page->name = 		$control->name;
			
			$page->VALUE_1 = $page->ORDER_ID = (int)$_GET['payment'];
			$sql = "SELECT * FROM `prname_b_order` WHERE `id` = '".sql::escape_string($page->ORDER_ID)."'";
			$res = sql::fetch_array(sql::query($sql));
			
			$this->requestInBank($url, $data);

			
			$page->PAY_SUM = (float)$res['totalprice'];

			$page->URL_OK = "http://maestria.su/?paymentOk=1";
			$page->URL_NO = "http://maestria.su/?paymentOk=0";
			
			$page->SIGN = strtoupper(md5(md5($page->SHOP_ID) . '&' . 
										md5($page->LOGIN) . '&' . 
										$page->PAY_PASS . '&' . 
										md5($page->ORDER_ID) . '&' . 
										md5($page->PAY_SUM)
									));
		}
	
		$get_cart = $this->get_cart();
		
		$page->catitem = $get_cart["catitem"];
		$page->variant = $get_cart["variant"];
		
		foreach ($page->catitem as $value) {
			$value->delivery_date = date("d.m.y", strtotime($value->delivery_date));
		}
		foreach ($page->variant as $value) {
			foreach ($value as $one_variant) {
				$one_variant->delivery_date = date("d.m.y", strtotime($one_variant->delivery_date));
			}
		}
		
		$priceInfo = $this->countPrice(false, true);
		$page->total_price = $priceInfo[0];
		$page->total_oldprice = $priceInfo[1];
		$page->total_pricedif = $page->total_oldprice - $page->total_price;
		if ($page->total_pricedif <= 0 ) {
			$page->total_pricedif = false;
		} else {
			$page->total_pricedif = number_format($page->total_pricedif, 2, ".", " ");;
		}

		if ($userDiscount) {
			$page->discount = $userDiscount;
			$page->price_with_disc = $page->total_price - ($page->total_price / 100 * $page->discount);

			$page->price_with_disc = number_format($page->price_with_disc, 2, ".", " ");
		}
		if ($userDiscountShop) {
			$page->discountShop = $userDiscountShop;
			$page->price_with_disc_shop = $page->total_price - ($page->total_price / 100 * $page->discountShop);

			$page->price_with_disc_shop = number_format($page->price_with_disc_shop, 2, ".", " ");
		}
		$page->with_delivery = number_format($page->total_price /*+ 800*/, 2, ".", " ");
		$page->total_price = number_format($page->total_price, 2, ".", " ");					


		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');
	}
 
	
	private function bankAnswer(){
		
		switch($_POST['STATE']){
			case 'authorized':
				$this->authorized();
			break;
			case 'paid':
				$this->paid();
			break;			
			case 'canceled':
				$this->canceled();
			break;
		}
	}
	
	/*блокировка средств на карте*/
	private function authorized(){
		global $control;


		$to_email = new stdClass;
		$sitename = $to_email->sitename = $control->settings->sitename;
		
		$data = array(
			'SHOP_ID'=>$_POST['SHOP_ID'],
			'LOGIN'=>$_POST['LOGIN'],
			'ORDER_ID'=>$_POST['ORDER_ID'],
			'PAY_PASS'=>$this->PAY_PASS,
		);
		
		$getPaymentInfo = $this->requestInBank('https://oplata.ubrr.ru/estore_result.php',$data);		
		
		/*письмо админу*/
		/*$to_email->emailAdmin = $control->settings->email;
		$to_email->theme = "Блокировка средств на карте по заказу ".$_POST['ORDER_ID']." на сайте $sitename";
		preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $to_email->emailAdmin, $matches);
		$to_email->emailAdmin = $matches[0];
		$to_email->ORDER_ID = $_POST['ORDER_ID'];
		$to_email->ORDER_IDP = $_POST['ORDER_IDP'];
		$to_email->mail_text = "Средства по заказу №".$_POST['ORDER_ID']."(".$_POST['ORDER_IDP'].") заблокированы";
		$to_email->htmlAdmin = sprintt($to_email, 'mailtemplates/toadmin/paymentAdmin.html');
		all::send_mail($to_email->emailAdmin, $to_email->theme, $to_email->htmlAdmin, false, false, "$sitename");*/
		
		/*письмо покупателю*/
		/*$sql = "SELECT `email` FROM `prname_b_order` WHERE `id` = '".sql::escape_string($_POST['ORDER_ID'])."'";
		$res = sql::fetch_assoc(sql::query($sql));
		$to_email->ORDER_ID = $_POST['ORDER_ID'];
		$to_email->ORDER_IDP = $_POST['ORDER_IDP'];
		$to_email->mail_text = "Средства по заказу №".$_POST['ORDER_ID']."(".$_POST['ORDER_IDP'].") заблокированы";
		$to_email->htmlUser = sprintt($to_email, 'mailtemplates/touser/paymentUser.html');
		all::send_mail($res['email'], $to_email->theme, $to_email->htmlUser, false, false, "$sitename");*/
		
		@unlink($checkFile);

		$sql = "UPDATE `prname_b_order` 
				SET `paymentData` = '".$getPaymentInfo."' 
				WHERE `id` = '".sql::escape_string($_POST['ORDER_ID'])."'
				";
		@sql::query($sql);
		
	}
	
	/*оплачен. транзакция успешно выполнена*/
	private function paid(){
		global $control;

		$to_email = new stdClass;

		$sitename = $to_email->sitename = $control->settings->sitename;

		/*письмо админу*/
		$to_email->emailAdmin = $control->settings->email;
		$to_email->theme = "Заказ №".$_POST['ORDER_ID']." на сайте $sitename оплачен.";
		preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $to_email->emailAdmin, $matches);
		$to_email->emailAdmin = $matches[0];
		$to_email->ORDER_ID = $_POST['ORDER_ID'];
		$to_email->ORDER_IDP = $_POST['ORDER_IDP'];
		$to_email->mail_text = "Средства по заказу №".$_POST['ORDER_ID']."(".$_POST['ORDER_IDP'].") списаны с карты покупателя";
		$to_email->htmlAdmin = sprintt($to_email, 'mailtemplates/toadmin/paymentAdmin.html');
		
		$data = array(
			'SHOP_ID'=>$this->SHOP_ID,
			'LOGIN'=>$this->LOGIN,
			'ORDER_ID'=>$_POST['ORDER_ID'],
			'PSWD'=>$this->PSWD,
		);		
		$getPaymentInfo = $this->requestInBank('https://oplata.ubrr.ru/estore_result.php',$data);

		$checkFile = $this->getCheck($_POST['ORDER_ID']);
	
		all::send_mail($to_email->emailAdmin, $to_email->theme, $to_email->htmlAdmin, $checkFile, false, "$sitename");
		/*письмо покупателю*/
		$sql = "SELECT `email` FROM `prname_b_order` WHERE `id` = '".sql::escape_string($_POST['ORDER_ID'])."'";
		$res = sql::fetch_assoc(sql::query($sql));

		$to_email->ORDER_ID = $_POST['ORDER_ID'];
		$to_email->ORDER_IDP = $_POST['ORDER_IDP'];
		$to_email->theme = "Оплата по заказу №".$_POST['ORDER_ID']." прошла успешно.";
		$to_email->mail_text = "Ждите сообщения об отправке заказа в ближайшее время";
		$to_email->htmlUser = sprintt($to_email, 'mailtemplates/touser/paymentUser.html');
		all::send_mail($res['email'], $to_email->theme, $to_email->htmlUser, $checkFile, false, "$sitename");
		@unlink($checkFile);
	
		$sql = "UPDATE `prname_b_order` 
				SET `paymentData` = '".$getPaymentInfo."' 
				WHERE `id` = '".sql::escape_string($_POST['ORDER_ID'])."'
				";
		@sql::query($sql);
	}
	
	/*отменен.(выполнена транзакция по снятию блокировки средств или выполнена опреция возврата платежа, по которому уже выполнена финансовая транзакция)*/
	private function canceled(){
		global $control;

		$to_email = new stdClass;

		$sitename = $to_email->sitename = $control->settings->sitename;

		/*письмо админу*/
		$to_email->emailAdmin = $control->settings->email;
		$to_email->theme = "Отмена заказа ".$_POST['ORDER_ID']." на сайте $sitename";
		preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $to_email->emailAdmin, $matches);
		$to_email->emailAdmin = $matches[0];
		$to_email->ORDER_ID = $_POST['ORDER_ID'];
		$to_email->ORDER_IDP = $_POST['ORDER_IDP'];
		$to_email->mail_text = "Заказ № ".$_POST['ORDER_ID']."(".$_POST['ORDER_IDP'].") отменен. Сумма будет возвращена покупателю";
		$to_email->htmlAdmin = sprintt($to_email, 'mailtemplates/toadmin/paymentAdmin.html');
		$data = array(
			'SHOP_ID'=>$this->SHOP_ID,
			'LOGIN'=>$this->LOGIN,
			'ORDER_ID'=>$_POST['ORDER_ID'],
			'PSWD'=>$this->PSWD,
		);		
		$getPaymentInfo = $this->requestInBank('https://oplata.ubrr.ru/estore_result.php',$data);

		$checkFile = $this->getCheck($_POST['ORDER_ID']);
if(isset($_COOKIE['developer'])){
		all::send_mail('yakovlev.n@list.ru', $to_email->theme, $to_email->htmlAdmin, $checkFile, false, "$sitename");
}else{
		all::send_mail($to_email->emailAdmin, $to_email->theme, $to_email->htmlAdmin, $checkFile, false, "$sitename");
}
		/*письмо покупателю*/
		$sql = "SELECT `email` FROM `prname_b_order` WHERE `id` = '".sql::escape_string($_POST['ORDER_ID'])."'";
		$res = sql::fetch_assoc(sql::query($sql));

		$to_email->ORDER_ID = $_POST['ORDER_ID'];
		$to_email->ORDER_IDP = $_POST['ORDER_IDP'];
		$to_email->mail_text = "Заказ №".$_POST['ORDER_ID']."(".$_POST['ORDER_IDP'].") отменен. В ближайшее время все сумма будет возвращена на Вашу банковскую карту";
		$to_email->htmlUser = sprintt($to_email, 'mailtemplates/touser/paymentUser.html');
if(isset($_COOKIE['developer'])){
		all::send_mail('yakovlev.n@list.ru', $to_email->theme, $to_email->htmlUser, $checkFile, false, "$sitename");
}else{
		all::send_mail($res['email'], $to_email->theme, $to_email->htmlUser, $checkFile, false, "$sitename");
}
		@unlink($checkFile);

	}
	
	private function requestInBank($url, $data){	
		$curl = curl_init();
//if(isset($_COOKIE['developer'])){
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		
		curl_setopt($curl, CURLOPT_SSLCERT, $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/ubrir.payment/install/sale_payment/ubrir/sdk/certsc/user.pem');
		curl_setopt($curl, CURLOPT_SSLCERTPASSWD, '475403');

		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
/*}else{
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
}*/		
		$result = curl_exec($curl); 
		$dataPayment = simplexml_load_string($result);

		
		foreach($dataPayment->estore->order AS $key=>$val){
			$this->resultRequest['estore_order'] = $val['estore_order'];
			$this->resultRequest['start_dt'] = $val['start_dt'];
			$this->resultRequest['state_code'] = $val['state_code'];
			$this->resultRequest['state_msg'] = $val['state_msg'];
			$this->resultRequest['oper_code'] = $val['oper_code'];
			$this->resultRequest['oper_msg'] = $val['oper_msg'];
			$this->resultRequest['last_dt'] = $val['last_dt'];
			
			$this->resultRequest['rrn'] = $val['rrn'];
			$this->resultRequest['cardholder'] = $val['cardholder'];
			$this->resultRequest['pan'] = $val['pan'];
			$this->resultRequest['app_code'] = $val['app_code'];
			$this->resultRequest['pay_trans'] = $val['pay_trans'];
			$this->resultRequest['pay_sum'] = $val['pay_sum'];
		}	
		return $result;
	}
	
	private function getCheck($orderId){
		global $control;
		
		$sql = "SELECT `order` FROM `prname_b_order` WHERE `id` = '".(int)$orderId."'";
		$res = sql::fetch_assoc(sql::query($sql));

		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		//$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Maestria.su');
		$pdf->SetTitle('Заказ №'.$_POST['ORDER_ID']);
		$pdf->SetFont('dejavusans', '', 10);
		$pdf->setPrintHeader(false); 
		$pdf->setPrintFooter(false);
		$pdf->AddPage();
		$html = '<h1>Информация о заказе</h1>
			<a href="http://maestria.su/" traget="_blank"><img alt="Maestria" src="https://gallery.mailchimp.com/dc993495ad6398f74b1fdb57a/images/860b0dcb-1e07-4a56-a733-db7afb67ddbe.jpg" style="max-width:130px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage" align="middle" width="130"></a>
			<br />
			<table width="100%">
			<tr>
				<td>Tорговое наименование Интернет-магазина:</td>
				<td>Maestria</td>
			</tr>

			<tr>
				<td>Cайт</td>
				<td><a href="http://maestria.su/" target="_blank">maestria.su</a></td>
			</tr>
			<tr>
				<td>Сумма платежа</td>
				<td>'.$this->resultRequest['pay_sum'].'</td>
			</tr>
			<tr>
				<td>Дата операции</td>
				<td>'.$this->resultRequest['start_dt'].'</td>
			</tr>
			<tr>
				<td>Код заказа</td>
				<td>'.$this->resultRequest['estore_order'].'</td>
			</tr>
			<tr>
				<td>Номер карты</td>
				<td>'.$this->resultRequest['pan'].'</td>
			</tr>
			'.
			(isset($this->resultRequest['cardholder']) ? '<tr><td>Дата операции</td><td>'.$this->resultRequest['cardholder'].'</td></tr>' : '')
			.'
			<tr>
				<td>Код подтверждения</td>
				<td>'.$this->resultRequest['app_code'].'</td>
			</tr>
			<tr>
				<td>Тип операции</td>
				<td>'.$this->resultRequest['state_msg'].'</td>
			</tr>
			<tr>
				<td>Наименование товара</td>
				<td>'.$res['order'].'</td>
			</tr>
			<tr>
				<td><a href="http://maestria.su/usloviya-obmena-i-vozvrata/" target="_blank">Условия возврата</a></td>
				<td></td>
			</tr>
			<tr>
				<td>Контактный адрес электронной почты и контактный телефон:</td>
				<td><a href="mailto:maestria66@yandex.ru">maestria66@yandex.ru</a>, тел. +7(343) 350-82-79</td>
			</tr>			
			<tr>
				<td>ИП Гальцева Лилия Шарипзяновна</td>
				<td></td>
			</tr>
			</table>
		';

		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
		//$pdf->Output('Check_'.$_POST['ORDER_ID'].'.pdf', 'I');
		$pdf->Output($_SERVER['DOCUMENT_ROOT'].'files/Check_'.$_POST['ORDER_ID'].'.pdf', 'F');
		@chmod($_SERVER['DOCUMENT_ROOT'].'files/Check_'.$_POST['ORDER_ID'].'.pdf', 0777);

		return array($_SERVER['DOCUMENT_ROOT'].'files/Check_'.$_POST['ORDER_ID'].'.pdf');
	}
	
	private function get_cart(){

		$sql = "SELECT * 
				FROM `payment`  
				WHERE `id_order` = '".(int)$_GET['payment']."'
				LIMIT 0,1
				";
		$res = sql::fetch_assoc(sql::query($sql));		
		$orderInfo = unserialize($res['goods_info']);
		
		$cart = (array)$orderInfo;
		
		
		$id_cat = array();
		$id_variant = array();
		foreach ($cart as $key => $value) {

			$id_cat[] = $key;
			if (isset($value["variant"])) {
				foreach ($value["variant"] as $key => $value) {
					$id_variant[] = $key;
				}
			}
		}
		$list = new Listing("catitem", "blocks", "all", "id in ('" . implode("', '", $id_cat) . "') AND ");
		$list->getList();
		$list->getItem();
		$page->catitem = $list->item;

		$page->catitem = helper_items::processItems($page->catitem);
		$array_catitem = array();
		$array_variant = array();
		$array_del_catitem = array();
		$max_date = date("Y-m-d");
		$max_date_pikup_1 = date("Y-m-d");
		$max_date_pikup_2 = date("Y-m-d");
//		print_r($_SESSION['cart']);
//		print_r($_SESSION["cart"]);
		$in_1_shop = false;
		$in_2_shop = false;
		$in_1_2_shop = false;
		$array_shops = array(1 => 0, 2 => 0);
		foreach ($list->item as $key => $value) {

//			$_SESSION["cart"][$value->id]["del"] = array();
			if ($value->ballance[1]->stock == "ул.Стачек, 4") {
//				$_SESSION["cart"][$value->id]["del"]["2"] = 1;
//				$in_1_2_shop = true;

				if ($cart[$value->id]["count"] > $value->ballance[0]->count) {
//						$in_1_shop = true;
					$array_shops[2]++;
				} elseif ($cart[$value->id]["count"] > $value->ballance[1]->count) {
//						$in_2_shop = true;
					$array_shops[1]++;
				}

			} elseif ($value->ballance[0]->stock == "ул.Стачек, 4") {
//				$_SESSION["cart"][$value->id]["del"]["2"] = 1;
//				$in_2_shop = true;
				$array_shops[2]++;
			} elseif ($value->ballance[0]->stock == "ул. Первомайская, 33") {
//				$_SESSION["cart"][$value->id]["del"]["1"] = 1;
//				$in_1_shop = true;
				$array_shops[1]++;
			}
			if ($array_shops[1] > 0 && $array_shops[2] > 0) {
				$_SESSION["cart"]["craft"] = 1;
			} else {
				if (isset($_SESSION["cart"]["craft"]))
					unset($_SESSION["cart"]["craft"]);
			}
		}

		if (count($id_variant)) {
			$list_variant = new Listing("variant", "blocks", "all", "id in ('" . implode("', '", $id_variant) . "') AND ");
			$list_variant->getList();
			$list_variant->getItem();

			$list_variant->item = helper_items::processItems($list_variant->item);

			if ($array_shops[1] == 0 || $array_shops[2] == 0) {
				foreach ($list_variant->item as $key => $value) {

					$_SESSION["cart"][$value->blockparent]["variant"][$value->id]["del"] = array();
					if ($value->ballance[1]->stock == "ул.Стачек, 4") {
						if ($cart[$value->blockparent]["variant"][$value->id]["count"] > $value->ballance[0]->count) {
							$array_shops[2]++;
						} elseif ($cart[$value->blockparent]["variant"][$value->id]["count"] > $value->ballance[1]->count) {
							$array_shops[1]++;
						}

					} elseif ($value->ballance[0]->stock == "ул.Стачек, 4") {
						$array_shops[2]++;
					} elseif ($value->ballance[0]->stock == "ул. Первомайская, 33") {
						$array_shops[1]++;
					}
					if ($array_shops[1] > 0 && $array_shops[2] > 0) {
						$_SESSION["cart"]["craft"] = 1;
					} else {
						if (isset($_SESSION["cart"]["craft"]))
							unset($_SESSION["cart"]["craft"]);
					}
				}
			}
		}


		foreach ($list->item as $key => $value) {
			
			$value->cnt_item = $cart[$value->id]["count"];
			$value->final_price = number_format($value->cnt_item * $value->price, 2, ".", " ");
			if ($value->oldprice) $value->final_oldprice = number_format($value->cnt_item * $value->oldprice, 2, ".", " ");
			$value->price = number_format($value->price, 2, ".", " ");
				
			$value->delivery_date = min($this->get_date($value->ballance, $value->cnt_item, false, 1), $this->get_date($value->ballance, $value->cnt_item, false, 2));

			$value->pikup_1 = $this->get_date($value->ballance, $value->cnt_item, true, 1);
			$value->pikup_2 = $this->get_date($value->ballance, $value->cnt_item, true, 2);
//			$value->delivery_date = date("Y-m-d", strtotime($value->delivery_date));
//			print_r(strtotime($value->delivery_date));
//			print_r($value->delivery_date);
//			print_r("\r\n");
//
//			print_r(time());
//			print_r("\r\n");
//			print_r(strtotime(date("d.m.Y")));
//			print_r("\r\n");
			
			if ($max_date < $value->delivery_date) {
//				print_r($value->delivery_date);
				$max_date = $value->delivery_date;
			}
			if ($max_date_pikup_1 < $value->pikup_1) {
				$max_date_pikup_1 = $value->pikup_1;
			}
			if ($max_date_pikup_2 < $value->pikup_2) {
				$max_date_pikup_2 = $value->pikup_2;
			}
			
			$array_catitem[$value->id] = $value;
		}

		if (count($id_variant)) {
//			$list_variant = new Listing("variant", "blocks", "all", "id in ('" . implode("', '", $id_variant) . "') AND ");
//			$list_variant->getList();
//			$list_variant->getItem();
//
//			$list_variant->item = helper_items::processItems($list_variant->item);
//			$list_variant->item = helper_items::processItems($list_variant->item);

			foreach ($list_variant->item as $key => $value) {
				
				$value->parent_name = $array_catitem[$value->blockparent]->name;
				$value->parent_url = $array_catitem[$value->blockparent]->url;
				$value->cnt_item = $cart[$value->blockparent]["variant"][$value->id]["count"];
				$value->final_price = number_format($value->cnt_item * $value->price, 2, ".", " ");
                if ($value->oldprice) $value->final_oldprice = number_format($value->cnt_item * $value->oldprice, 2, ".", " ");
				$value->price = number_format($value->price, 2, ".", " ");
//				$value->delivery_date = $this->get_date($value->ballance, $value->cnt_item, false, 1);

				$value->delivery_date = min($this->get_date($value->ballance, $value->cnt_item, false, 1), $this->get_date($value->ballance, $value->cnt_item, false, 2));
				$value->pikup_1 = $this->get_date($value->ballance, $value->cnt_item, true, 1);
				$value->pikup_2 = $this->get_date($value->ballance, $value->cnt_item, true, 2);
//				$value->delivery_date = date("Y-m-d", strtotime($value->delivery_date));
				if ($max_date < $value->delivery_date) {
					$max_date = $value->delivery_date;
				}
				if ($max_date_pikup_1 < $value->pikup_1) {
					$max_date_pikup_1 = $value->pikup_1;
				}
				if ($max_date_pikup_2 < $value->pikup_2) {
					$max_date_pikup_2 = $value->pikup_2;
				}
				$array_variant[$value->blockparent][] = $value;
				if (!in_array($value->blockparent, $array_del_catitem))
					$array_del_catitem[] = $value->blockparent;
			}
		}

		foreach ($array_del_catitem as $value) {
			unset($array_catitem[$value]);
		}
		return array("catitem" => $array_catitem, "variant" => $array_variant, 'max_date' => $max_date, 'max_date_pikup_1' => $max_date_pikup_1, 'max_date_pikup_2' => $max_date_pikup_2);
	}
	
	private function get_date($balance, $cnt, $pickup = false, $shop = 1){
		global $config, $control;
		
		date_default_timezone_set("Asia/Yekaterinburg");
		if ($balance[0]->stock == "ул. Первомайская, 33")
			$balance_1 = $balance[0]->count;
		else
			$balance_1 = -1;
//		print_r($balance);
		if ($balance[0]->stock == "ул.Стачек, 4")
			$balance_2 = $balance[0]->count;
		elseif ($balance[1]->stock == "ул.Стачек, 4")
			$balance_2 = $balance[1]->count;
		else
			$balance_2 = -1;

		if ((isset($_SESSION["cart"]["craft"])) && (!empty($_SESSION["cart"]["craft"]))) {
			$craft = true;
		} else {
			$craft = false;
		}


		$balance_all = $balance_1 + $balance_2;
		$day1_week = $this->get_day_from_settings($control->settings->day1_sel);
		$day2_week = $this->get_day_from_settings($control->settings->day2_sel);

		$down_week = 7 - (date("w") == 0 ? 7 : date("w"));

		if ((date("w") >= $day1_week) && (date("w") >= $day2_week)) {
			$reson_date = $day1_week + $down_week;
		} elseif ((date("w") >= $day1_week) && (date("w") < $day2_week)) {
			$reson_date = $day2_week - date("w");
		} elseif ((date("w") < $day1_week) && (date("w") < $day2_week)) {
			$reson_date = $day1_week - date("w");
		}

		switch (date("w")) {
			case $day1_week:#если выбран день1
				if ((!$pickup) && (date("G") < 17) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#не самовывоз до 16 (сбор)
					$date_add = "+" . ($reson_date + 1) . " days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					}
				} elseif ((!$pickup) && (((date("G") < 17) && ((($cnt <= $balance_1) && ($shop == 1)) || (($cnt <= $balance_2) && ($shop == 2)))) && (!$craft))) {#не самовывоз до 16 (сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+3 days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+3 days";
					}
				} elseif ((!$pickup) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#не самовывоз после 16 (сбор)
					$date_add = "+" . ($reson_date + 1) . " days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					}
				} elseif ((!$pickup) && (((($cnt <= $balance_1) && ($shop == 1)) || (($cnt <= $balance_2) && ($shop == 2))) && (!$craft))) {#не самовывоз после 16 (не сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+3 days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+3 days";
					}
				} elseif (($pickup) && (date("G") < 17) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#самовывоз до 16 (сбор)
//                    if (date("w") == $day1_week) {
//                        $reson_date = 2;
//                    } elseif (date("w") == $day2_week) {
//                        $reson_date = 2;
//                    }
					$date_add = "+" . ($reson_date + 1) . " days";
					if (($shop == 2) && (date("w", strtotime($date_add)) == 0)) {
						$date_add = "+" . ($reson_date + 2) . " days";
					}
				} elseif (($pickup) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#самовывоз до 16 (сбор)
//                    if (date("w") == $day1_week) {
//                        $reson_date = 2;
//                    } elseif (date("w") == $day2_week) {
//                        $reson_date = 2;
//                    }
					$date_add = "+" . ($reson_date + 1) . " days";
					if (($shop == 2) && (date("w", strtotime($date_add)) == 0)) {
						$date_add = "+" . ($reson_date + 2) . " days";
					}
				} elseif (($pickup) && (($cnt <= $balance_1) && ($shop == 1) && (!$craft))) {#самовывоз из 1 магаза (не сбор)
					$date_add = "+1 days";
				} elseif (($pickup) && (($cnt <= $balance_2) && ($shop == 2) && (!$craft))) {#самовывоз из 2 магаза (не сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 0) {
						$date_add = "+2 days";
					}
				}
				break;
			case $day2_week:#если выбран день2
				if ((!$pickup) && (date("G") < 17) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#не самовывоз до 16 (сбор)
					$date_add = "+" . ($reson_date + 1) . " days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+" . ($reson_date + 2 + 2) . " days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2 + 2) . " days";
					}
				} elseif ((!$pickup) && ((date("G") < 17) && ((($cnt <= $balance_1) && ($shop == 1)) || (($cnt <= $balance_2) && ($shop == 2))) && (!$craft))) {#не самовывоз до 16 (сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+3 days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+3 days";
					}
				} elseif ((!$pickup) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#не самовывоз после 16 (сбор)
					$date_add = "+" . ($reson_date + 1) . " days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					}
				} elseif ((!$pickup) && (((($cnt <= $balance_1) && ($shop == 1)) || (($cnt <= $balance_2) && ($shop == 2))) && (!$craft))) {#не самовывоз после 16 (не сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+3 days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+3 days";
					}
				} elseif (($pickup) && (date("G") < 17) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#самовывоз до 16 (сбор)
//					if (date("w") == $day1_week) {
//						$reson_date = 2;
//					} elseif (date("w") == $day2_week) {
//						$reson_date = 2;
//					}
					$date_add = "+" . ($reson_date + 1) . " days";
					if (($shop == 2) && (date("w", strtotime($date_add)) == 0)) {
						$date_add = "+" . ($reson_date + 2) . " days";
					}
//					else {
//						$date_add = "+" . $reson_date . " days";
//					}
				} elseif (($pickup) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#самовывоз до 16 (сбор)
//					if (date("w") == $day1_week) {
//						$reson_date = 2;
//					} elseif (date("w") == $day2_week) {
//						$reson_date = 2;
//					}
					$date_add = "+" . ($reson_date + 1) . " days";
					if (($shop == 2) && (date("w", strtotime($date_add)) == 0)) {
						$date_add = "+" . ($reson_date + 2) . " days";
					}
//					else {
//						$date_add = "+" . $reson_date . " days";
//					}
				} elseif (($pickup) && (($cnt <= $balance_1) && ($shop == 1) && (!$craft))) {#самовывоз из 1 магаза (не сбор)
					$date_add = "+1 days";
				} elseif (($pickup) && (($cnt <= $balance_2) && ($shop == 2) && (!$craft))) {#самовывоз из 2 магаза (не сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 0) {
						$date_add = "+2 days";
					}
				}
				break;
			default:# любой другой день
				if ((!$pickup) && (date("G") < 17) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#не самовывоз до 16 (сбор)
					$date_add = "+" . ($reson_date + 1) . " days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					}
				} elseif ((!$pickup) && (date("G") < 17) && (((($cnt <= $balance_1) && ($shop == 1)) || (($cnt <= $balance_2) && ($shop == 2))) && (!$craft))) {#не самовывоз до 16 (сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+3 days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+3 days";
					}
				} elseif ((!$pickup) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#не самовывоз после 16 (сбор)

					if ((date("w") + 1) == $day2_week) {
						if ($day1_week < date("w")) {
							$reson_date = 7 - date("w") + $day1_week;
						} else {
							$reson_date = $day1_week - date("w");
						}
					}
					if ((date("w") + 1) == $day1_week) {
						if ($day2_week < date("w")) {
							$reson_date = 7 - date("w") + $day2_week;
						} else {
							$reson_date = $day2_week - date("w");
						}
					}
					$date_add = "+" . ($reson_date + 1) . " days";

					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2 + 1) . " days";
					}
//					echo $balance_2 . "-" . $cnt . "-" . $balance_1."><";
				} elseif ((!$pickup) && (((($cnt <= $balance_1) && ($shop == 1)) || (($cnt <= $balance_2) && ($shop == 2))) && (!$craft))) {#не самовывоз после 16 (не сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 6) {
						$date_add = "+3 days";
					} elseif (date("w", strtotime($date_add)) == 0) {
						$date_add = "+3 days";
					}
				} elseif (($pickup) && (date("G") < 17) && ((((($cnt > $balance_1) && ($shop == 1)) || (($cnt > $balance_2) && ($shop == 2)))) || ($craft))) {#самовывоз до 16 (сбор)
//					if (date("w") == $day1_week) {
//						$reson_date = 2;
//					} elseif (date("w") == $day2_week) {
//						$reson_date = 2;
//					}
					$date_add = "+" . ($reson_date + 1) . " days";
					if (($shop == 2) && (date("w", strtotime($date_add)) == 0)) {
						$date_add = "+" . ($reson_date + 2) . " days";
					}
//					else {
//						$date_add = "+" . $reson_date . " days";
//					}
				} elseif (($pickup) && (($cnt <= $balance_1) && ($shop == 1) && (!$craft))) {#самовывоз из 1 магаза (не сбор)
					$date_add = "+1 days";
//					if (date("w", strtotime($date_add)) == 6) {
//						$date_add = "+3 days";
//					} elseif (date("w", strtotime($date_add)) == 0) {
//						$date_add = "+3 days";
//					}
				} elseif (($pickup) && ((($cnt > $balance_1) && ($shop == 1)) || ($craft))) {#самовывоз из 1 магаза (сбор)
					if ((date("w") + 1) == $day2_week) {
						if ($day1_week < date("w")) {
							$reson_date = 7 - date("w") + $day1_week;
						} else {
							$reson_date = $day1_week - date("w");
						}
					}
					if ((date("w") + 1) == $day1_week) {
						if ($day2_week < date("w")) {
							$reson_date = 7 - date("w") + $day2_week;
						} else {
							$reson_date = $day2_week - date("w");
						}
					}
					$date_add = "+" . ($reson_date + 1) . " days";
				} elseif (($pickup) && (($cnt <= $balance_2) && ($shop == 2) && (!$craft))) {#самовывоз из 2 магаза (не сбор)
					$date_add = "+1 days";
					if (date("w", strtotime($date_add)) == 0) {
						$date_add = "+2 days";
					}
				} elseif (($pickup) && ((($cnt > $balance_2) && ($shop == 2)) || ($craft))) {#самовывоз из 2 магаза (сбор)
					if ((date("w") + 1) == $day2_week) {
						if ($day1_week < date("w")) {
							$reson_date = 7 - date("w") + $day1_week;
						} else {
							$reson_date = $day1_week - date("w");
						}
					}
					if ((date("w") + 1) == $day1_week) {
						if ($day2_week < date("w")) {
							$reson_date = 7 - date("w") + $day2_week;
						} else {
							$reson_date = $day2_week - date("w");
						}
					}
					$date_add = "+" . ($reson_date + 1) . " days";
					if (date("w", strtotime($date_add)) == 0) {
						$date_add = "+" . ($reson_date + 2) . " days";
					}
				}
				break;
		}
		return date("Y-m-d", strtotime($date_add));
	}
	
	private function get_day_from_settings($word){
		switch ($word) {
			case "Пн":
				$w = 1;
				break;
			case "Вт":
				$w = 2;
				break;
			case "Ср":
				$w = 3;
				break;
			case "Чт":
				$w = 4;
				break;
			case "Пт":
				$w = 5;
				break;
			case "Сб":
				$w = 6;
				break;
			case "Вс":
				$w = 0;
				break;
		}

		return $w;
	}
	
	public
	static function countPrice($format = true, $oldpriceshow = false)
	{
		$id_cat = array();
		$id_variant = array();
		$variant = array();
		$sql = "SELECT * 
				FROM `payment`  
				WHERE `id_order` = '".(int)$_GET['payment']."'
				LIMIT 0,1
				";
		$res = sql::fetch_assoc(sql::query($sql));		
		$orderInfo = unserialize($res['goods_info']);
		
		$cart = (array)$orderInfo;		

		foreach ($cart as $key => $value) {
			if (!isset($value["variant"])) {
				$id_cat[] = $key;
			} else {
				foreach ($value["variant"] as $key => $value) {
					$id_variant[] = $key;
					$variant[$key] = $value;
				}
			}
		}

		$list = new Listing("catitem", "blocks", "40", "id in ('" . implode("', '", $id_cat) . "') AND ");
		$list->getList();
		$list->getItem();
		$total_price = 0;
		$total_oldprice = 0;
		foreach ($list->item as $value) {
			$total_price += $value->price * $cart[$value->id]["count"];
			$total_oldprice += $value->oldprice ?   $value->oldprice * $cart[$value->id]["count"] :  $value->price * $cart[$value->id]["count"];
		}

		if (count($id_variant)) {
			$list_variant = new Listing("variant", "blocks", "all", "id in ('" . implode("', '", $id_variant) . "') AND ");
			$list_variant->getList();
			$list_variant->getItem();
			foreach ($list_variant->item as $value_variant) {
				$total_price += $value_variant->price * $variant[$value_variant->id]["count"];
                $total_oldprice += $value_variant->oldprice ?   $value_variant->oldprice * $variant[$value_variant->id]["count"] :  $value_variant->price * $variant[$value_variant->id]["count"];
			}
		}
		setcookie("cart", json_encode($cart), time() + 86400, "/");
		$_SESSION['cart'] = $cart;
		if ($format) {
			$total_price = number_format($total_price, 2, ".", " ");
			$total_oldprice = number_format($total_oldprice, 2, ".", " ");
        }
        
        if ($oldpriceshow) {
            return array($total_price, $total_oldprice);
        } else {
            return $total_price;
        }
	}
}
?>