<?php
require_once(DOC_ROOT."/inc/modules/users.php");
class authblock {

	function Make($wrapper) {

		if (isset($_SESSION['uid'])) {
			$page->auth = true;
			$page->cabinetUrl = all::getUrl(34);
		}
		else {
			$page->auth = false;
		}

		// Форма регистрации
		$reg = new users();
		$page->scriptReg = $reg->formReg();

		// Форма восстановления пароля

		$page->scriptRem = $reg->formRem();
		
		$page->cabinetUrl = all::getUrl(49);
		
		$text = sprintt($page, 'templates/misc/'.$wrapper);
		return $text;
	}

}
?>