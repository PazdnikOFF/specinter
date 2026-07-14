<?php
/**
 * Typography Class
 */
class typography {

	//Working text
	var $text;

	function typography($text) {
		$this->text = $text;
	}

	/**
	 * cleanUp
	 *
	 * This function cleans text:
	 * 	- Removes microsoft word tags.
	 * 	- Removes style tags.
	 * 	- Removes font tags.
	 *  - Removes whitespaces before punctuation signs and adds them after
	 *
	 * @access	public
	 * @return	null
	 */
	function cleanUp($charset = 'cp1251') {
		$contents = $this->text;


		//remove entities
		$contents = html_entity_decode($contents, ENT_NOQUOTES, $charset);

		//remove comments
		$contents = preg_replace("/(<!--[^(-->)])(.*)(-->)/Uis", "", $contents);

		//remove style tag
		$contents = preg_replace("/<style[^>]*>.*(<\/style>)/Uis", "", $contents);

		//remove link tag
		$contents = preg_replace("/<link[^>]*>/i", "", $contents);

		//remove meta tag
		$contents = preg_replace("/<meta[^>]*>/i", "", $contents);

		//remove span|p|b|strong|em|font attributes
		//$contents = preg_replace("/<(p|span|b|strong|em|font) [^>]*>/si", "<$1>", $contents);

		//remove span tag
		//$contents = preg_replace("/(<span>|<\/span>)/i", "", $contents);

		//remove font tag
		$contents = preg_replace("/(<font>|<\/font>)/i", "", $contents);

		//remove <o:p> tag
		$contents = preg_replace("/(<o:p>|<\/o:p>)/i", "", $contents);

		//remove <p>&nbsp;</p> with <br/>
		//$contents = preg_replace("/<p>(&nbsp;|\s)<\/p>/is", "<br/>", $contents);

		//remove empty tags
		//$contents = preg_replace("/<(span|b|strong|em)[^>]*>\s*<\/(span|b|strong|em)>/Uis", "", $contents);

		//remove <p> wrap of <div> elements
		$contents = preg_replace("/<p[^>]*>\s*(<div[^>]*>(.*)<\/div>)\s*<\/p>/Uis", "$1", $contents);

		//remove \n with <br/>
		$contents = str_replace("\n", " ", $contents);

		//remove whitespaces before punctuation signs and adds after if not exist
		/*preg_match_all("/>([^<]*)</Uis", $contents, $innerTags);
		foreach ($innerTags[1] as $item) {
			$item2 = preg_replace("/\s([\.]{3}|[\.,:;]{1})/Uis", "$1", $item);
			$item2 = preg_replace("/([^(&shy;)])([\.]{3}|[\.,;:]{1})([^\s\.]){1}/Uis", "$1$2 $3", $item2);
			$contents = str_replace($item, $item2, $contents);
		}*/

		$this->text = $contents;
	}

	/**
	 * wrapList
	 *
	 * This function wrap inner html of ol -> li tags to colorize numbers.
	 *
	 * @access	public
	 * @param	string tag name  (default is span}
	 * @return	string
	 */
	function wrapList($tagName = "span") {
		$contents = $this->text;
		preg_match_all("/<ol[^>]*>(.*)<\/ol>/Uis", $contents, $ol);

		foreach ($ol[1] as $key=>$value) {
			$newli[] = preg_replace("/<li(.*)>(.*)<\/li>/Uis", "<li\\1><".$tagName.">\\2</".$tagName."></li>", $value);
		}

		foreach ($ol[1] as $key => $value) {
			$contents = str_replace($ol[1], $newli, $contents);
		}

		$this->text = $contents;
	}


	/**
	 * transferLines
	 *
	 * This function makes transfers
	 *
	 * @access	public
	 * @return	null
	 */
	function transferLines() {
		$contents = $this->text;

		preg_match_all("/>([^<]*)</Uis", $contents, $innerTags);
		if (count($innerTags[1]) > 0) {
			foreach ($innerTags[1] as $pitem) {
				$words = preg_split("/ /", $pitem);
				$newWords = array();
				foreach ($words as $word){
					if (mb_strlen($word) > 4) {
						$newWords[] = $this->getSlogs(trim($word));
					}
					else $newWords[] = $word;
				}

				$newp = implode(" ", $newWords);


				$contents = str_replace($pitem, $newp, $contents);
				$this->text = $contents;
			}
		}

		else {
			$words = preg_split("/ /", $contents);
			$newWords = array();
			foreach ($words as $word){
				if (mb_strlen($word) > 4) {
					$newWords[] = $this->getSlogs(trim($word));
				}
				else $newWords[] = $word;
			}

			$newp = implode(" ", $newWords);


			$contents = $newp;
			$this->text = $contents;
		}
	}

	/**
	 * getTypeVoice
	 *
	 * This function gets type of character
	 *
	 * @access	private
	 * @param	string character sign
	 * @param	string check for specific type (name) or not(false) (default false)
	 * @return	number or bool(false)
	 */
	function getTypeVoice($chr, $need = false) {
		$chr = mb_strtolower($chr);

		/*$ringNoise = array("к", "х", "п", "ф", "т", "с", "ш", "щ", "ч", "ц" , "б");
		$deafNoise = array("г", "й", "б", "в", "д", "з", "ж");
		$sonor = array("р", "л", "м", "н", "й");
		$fuckingShit = array("ь", "ъ");
		$vowels = array("а", "о", "у", "ы", "э", "я", "ё", "ю", "и", "е");*/

		$ringNoise = array("к", "х", "п", "ф", "т", "с", "ш", "щ", "ч", "ц", "б", "b", "c", "f", "h", "k", "p", "q", "s", "t", "w", "x");
		$deafNoise = array("г", "й", "б", "в", "д", "з", "ж", "b", "d", "g", "j", "v", "z");
		$sonor = array("р", "л", "м", "н", "й", "l", "m", "n", "r");
		$fuckingShit = array("ь", "ъ");
		$vowels = array("а", "о", "у", "ы", "э", "я", "ё", "ю", "и", "е", "a", "e", "i", "o", "u", "y");

		if ($need == false || $need == "ringNoise") {
			foreach ($ringNoise as $sign) {
				if ($chr == $sign) {
					return 2;
				}
			}
		}

		if ($need == false || $need == "fuckingShit") {
			foreach ($fuckingShit as $sign) {
				if ($chr == $sign) {
					return 4.5;
				}
			}
		}

		if ($need == false || $need == "sonor") {
			foreach ($sonor as $sign) {
				if ($chr == $sign) {
					return 3;
				}
			}
		}

		if ($need == false || $need == "deafNoise") {
			foreach ($deafNoise as $sign) {
				if ($chr == $sign) {
					return 2;
				}
			}
		}

		if ($need == false || $need == "vowels") {

			foreach ($vowels as $sign) {
				if ($chr == $sign) {
					return 4;
				}
			}
		}
		return 5;
	}

	/**
	 * countSlogs
	 *
	 * This function counts slogs* in word
	 *
	 * @access	private
	 * @param	string word
	 * @return	number
	 */
	function countSlogs($word) {
		$wordWork = $word;

		$i = 0;
		while(mb_strlen($wordWork) > 0) {
			$sign = mb_strtolower(mb_substr($wordWork, 0, 1));
			$res = $this->getTypeVoice($sign, "vowels");

			if ($res == 4) {
				$i++;
			}
			$wordWork = mb_substr($wordWork, 1);
		}

		return $i;
	}

	/**
	 * getSlogs
	 *
	 * This function returns word with shy's
	 *
	 * @access	private
	 * @param	string word
	 * @return	string
	 */
	function getSlogs($word) {
		$wordWork = $wordNotWork = mb_strtolower($word);
		$countSlogs = $this->countSlogs($word);
		if ($countSlogs == 0) {
			return $word;
		}

		$newWord = array();

		while(mb_strlen($wordWork) > 0) {
			$sign = mb_strtolower(mb_substr($wordWork, 0, 1));
			$res = $this->getTypeVoice($sign);
			$newWord[] = $res;
			$wordWork = mb_substr($wordWork, 1);
		}



		$i = 0;
		$j = 0;
		foreach ($newWord as $key=>$num) {
			if ($num == false) continue;
			if ($i == 0) {
				$slogs[$j][] = $num;
				$i ++;
				continue;
			}

			if ($num > $newWord[$key-1]) {
				$slogs[$j][] = $num;
				$i ++;
				continue;
			}

			if ($num <= $newWord[$key-1]) {
				$j ++;
				$slogs[$j][] = $num;
				$i ++;
			}
		}


		while (count($slogs) > $countSlogs) {

			foreach ($slogs as $i => $slog) {
				$hasVowels = false;
				foreach ($slog as $char) {
					if ($char == 4) {
						$hasVowels = true;
						break;
					}
				}
				if ($hasVowels == false) {
					if ($slog == $slogs[0]) {
						$j = 1;
						while (!isset($slogs[$i+$j])) {
							$j++;
						}
						$slogs[$i] = array_merge($slog, $slogs[$i+$j]);
						unset($slogs[$i+$j]);
						break;
					}
				}


				if ($hasVowels == false) {
					$j = 1;
					while (!isset($slogs[$i-$j])) {
						$j++;
					}

					$slogs[$i-$j] = array_merge($slogs[$i-$j], $slogs[$i]);
					unset($slogs[$i]);
					break;
				}
			}
		}

		$j = 0;

		foreach($slogs as $i => $slog) {
			$salt = 0;
			$count = count($slog);
			if (end($slog) == 5) {
				$salt = 1;
				$count --;
			}

			$newSlogs[$j] = $count;
			$j++;
		}


		/*Нельзя переносить одну букву*/
		/*В конце слова*/
		if ($newSlogs[count($newSlogs)-1] == 1) {
			$newSlogs[count($newSlogs)-2] += 1;
			if ($salt == 1) $newSlogs[count($newSlogs)-2] += 1;
			unset($newSlogs[count($newSlogs)-1]);
		}
		else {
			if ($salt == 1) $newSlogs[count($newSlogs)-1] += 1;
		}

		//С начала слова
		if ($newSlogs[0] == 1) {
			$newSlogs[1] += 1;
			unset($newSlogs[0]);
		}



		$i = 0;
		$newWord = "";

		foreach ($newSlogs as $slog) {
			$newWord .= mb_substr($word, $i, $slog)."&shy;";
			$i += $slog;
		}
		$newWord = mb_substr($newWord, 0, mb_strlen($newWord)-5);
		return $newWord;


	}

}
// END Typography Class

?>