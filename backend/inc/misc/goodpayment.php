<?php
require_once(BACKEND_ROOT."/inc/modules/call.php");
class goodpayment {

	function Make($wrapper){
		global $control;


		$sign = md5($wrapper);
		phpFastCache::$storage = "auto";
		$content = phpFastCache::get($sign);

		if ($content == null) {
			$page = $control->settings;

			$call = new call();
			$page->scriptCall = $call->formCall();

			if(isset($_GET['paymentOk']) && $_GET['paymentOk'] = '1/'){
				$page->paymentOk = '1';
			}

			$text = sprintt($page, 'templates/misc/'.$wrapper);

			// �������� �� 24 ����
			// phpFastCache::set($sign, $text, 86400);
		}else {
			$text = $content;
		}
		return $text;
	}
}
?>
