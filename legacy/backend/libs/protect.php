<?php
class protect {

	function p_code($txt, $hash='12345') {
		$txt = StripSlashes($txt);
		$en = "";
		for ($i = 0; $i < strlen($txt); $i++) {
			$en .= base_convert(ord($txt[$i]), 25, 5).chr($hash);
		}
		return $en;
	}

	function p_encode($txt, $hash='12345') {
		$array = explode(chr($hash), $txt);
		$de;

		while(list(,$char) = each($array)) {
			if ($char != "") {
				$de .= chr(base_convert($char, 5, 25));
			}
		}
		return $de;
	}

	function p_pass($length = 8) {
		$foo = array_merge
        (
                range("0", "9"),
                range("A", "Z")
		);
        for ($i; $i < $length; $i++) {
			$rand_char = array_rand($foo);
			$string .= $foo[$rand_char];
        }
        return($string);
	}

	function text_md5($text) {
	    global $config;
		$text2 = md5(base64_encode($config['md5'].$text));
		return $text2;
	}
}
?>