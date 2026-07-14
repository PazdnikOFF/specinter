<?php

class compressjs {

	function Make($wrapper) {
		$jsFiles = array(
			'/scripts/jquery.arcticmodal-0.3/jquery.arcticmodal-0.3.min.js',
			'/scripts/bxslider/jquery.bxslider.min.js',
			'/scripts/formstyler/jquery.formstyler.min.js',
			'/scripts/responsive-tables/responsive-tables.js',
			'/scripts/scrollpane/jquery.mousewheel.pack.js',
			'/scripts/scrollpane/jquery.jscrollpane.min.js',
			'/scripts/lightGallery/lightGallery.min.js',
			'/scripts/custom.js',
			'/scripts/cart.js',
			'/scripts/trunk8.js',
			'/scripts/jquery.magnific-popup.min.js',
			'/scripts/jquery.kladr.min.js',
			'/scripts/slick/slick.min.js',
		);

		$fileAge = 0;
		foreach($jsFiles as $jsFile){
			$jsFile = DOC_ROOT.$jsFile;
			$fileAge += filemtime($jsFile);
		}

		$jsCache = DOC_ROOT."/cache/js.txt";
		$jsFile = DOC_ROOT."/cache/compressed.js";

		if (file_exists($jsCache)) {
			$oldFileAge = file_get_contents($jsCache);
			if($oldFileAge == "" || $fileAge > $oldFileAge){
				$handle = fopen($jsCache, "w");
				fwrite($handle, $fileAge);
				$this->flushCache();
			}
		}
		else {
			$handle = fopen($jsCache, "w");
			fwrite($handle,$fileAge);
			$this->flushCache();
		}



		$totalFiles = 0;

		// JAVASCRIPT COMPRESS
		$js = '';

		if (!file_exists($jsFile)) {

			$handle = fopen($jsFile, 'w');
			$compressedJs = '';
			foreach ($jsFiles as $jsFile) {
				$jsFile = DOC_ROOT.$jsFile;
				$compressedJs .= ";".file_get_contents($jsFile)."\n\n\n";
			}

			fwrite($handle, $compressedJs);
		}
		$js = "<script src='/cache/compressed.js'></script>";
		return $js;

	}

	// FUNCTION FLUSHCACHE
	private function flushCache() {
		$jsFile = DOC_ROOT."/cache/compressed.js";
		if (file_exists($jsFile)) {
			$handle = fopen($jsFile, "w") or die("Can't flush cache of javascript files");
			fclose($handle);
			unlink($jsFile) or die("Can't flush cache of javascript files");
		}
	}
}
?>