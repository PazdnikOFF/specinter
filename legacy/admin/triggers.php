<?php

class triggers {
	/* Отправляет уведомление о смне статуса заказа
	 * закзачику
	 * $id - id заказа
	 */
	public function updateStatus($id) {
		global $control;

		$info = all::b_data_all($id, "order");
		if (!$info) {
			die("error");
		}


		// Дата
		$date = explode(" ", $info->date);
		$date = explode("-", $date[0]);
		$info->date = $date[2].".".$date[1].".".$date[0];

		// Статус заказа
		$info->status = all::b_data_all($info->status_sel, "orderstatus")->status;
		// die($info->status);


		$email = $info->email;

		$to_email->domain = $_SERVER["SERVER_NAME"];

		// Генерация текста для письма
		$mailInfo = all::b_data_all(20, "sitetext");
		$mailInfo->text =  nl2br($mailInfo->text);
		$mailInfo->text = str_replace("{id}", $id, $mailInfo->text);
		$mailInfo->text = str_replace("{name}", $info->name, $mailInfo->text);
		$mailInfo->text = str_replace("{date}", $info->date, $mailInfo->text);
		$mailInfo->text = str_replace("{status}", $info->status, $mailInfo->text);
		$to_email->text = $mailInfo->text;

		$to_email->theme = "Статус заказа изменен";

		$sitename = $control->settings->sitename;

		// Отправка письма админу
		$msg = sprintt($to_email, 'mailtemplates/touser/updateStatus.html');
		$result = all::send_mail($email, $to_email->theme, $msg, false, false, $sitename);
		die("Статус заказа изменен, сообщение отправлено заказчику.");
	}
}
?>