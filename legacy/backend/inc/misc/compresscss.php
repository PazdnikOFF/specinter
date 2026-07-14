<?php

class compresscss {

	function Make($wrapper) {
		$cssFiles = array(
			'/scripts/jquery.arcticmodal-0.3/jquery.arcticmodal-0.3.css',
			'/scripts/bxslider/jquery.bxslider.css',
			'/scripts/formstyler/jquery.formstyler.css',
			'/scripts/responsive-tables/responsive-tables.css',
			'/scripts/scrollpane/jquery.jsscrollpane.css',
			'/scripts/lightGallery/lightGallery.css',
			'/scripts/magnific-popup.css',
			'/scripts/kladr.css',
			'/scripts/slick/slick.css',
		);


		$fileAge = 0;
		foreach($cssFiles as $cssFile){
			$cssFile = DOC_ROOT.$cssFile;
			$fileAge += filemtime($cssFile);
		}

		$cssCache = DOC_ROOT."/cache/css.txt";
		$cssFile = DOC_ROOT."/cache/compressed.css";

		if (file_exists($cssCache)) {
			$oldFileAge = file_get_contents($cssCache);
			if($oldFileAge == "" || $fileAge > $oldFileAge){
				$handle = fopen($cssCache, "w");
				fwrite($handle, $fileAge);
				$this->flushCache();
			}
		}
		else {
			$handle = fopen($cssCache, "w");
			fwrite($handle,$fileAge);
			$this->flushCache();
		}



		$totalFiles = 0;

		// CSS COMPRESS
		$css = '';

		if (!file_exists($cssFile)) {

			$handle = fopen($cssFile, 'w');
			$compressedCss = '';
			foreach ($cssFiles as $cssFile) {
				$cssFile = DOC_ROOT.$cssFile;
				$compressedCss .= file_get_contents($cssFile)."\n\n\n";
			}

			fwrite($handle, $compressedCss);
		}
		$css = "<link rel='stylesheet' href='/cache/compressed.css'>";
		return $css;

	}

	// FUNCTION FLUSHCACHE
	private function flushCache() {
		$cssFile = DOC_ROOT."/cache/compressed.css";
		if (file_exists($cssFile)) {
			$handle = fopen($cssFile, "w") or die("Can't flush cache of javascript files");
			fclose($handle);
			unlink($cssFile) or die("Can't flush cache of javascript files");
		}
	}
}
?>