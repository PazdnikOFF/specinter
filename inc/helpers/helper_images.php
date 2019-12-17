<?php
class helper_images {

	public static function processImages($image) {
		
		if (strpos($image, ";") > -1) {
			$images = explode(";", $image);
		}
		else {
			$images = array($image);
		}
        
		$newImages = array();

		foreach ($images as $key => $image) {
			if ($image) {
//				echo $image;
				$needToCreateTh = true;
/*if(isset($_COOKIE['developer'])){
	echo"<pre>";
	var_dump(file_exists(DOC_ROOT."/files/0/".$image));
	echo"</pre>";
	die();
}*/ 
				$fe = file_exists(DOC_ROOT."/images/real/1/".$image);
//				var_dump(file_exists(DOC_ROOT."/images/real/0/".$image));
//
//				echo "---------";
//				var_dump(file_exists(DOC_ROOT."/images/real/1/".$image));
//				var_dump($fe);
//				echo "---------";
//				echo $image;

//				echo $image;
				// echo "\r\n";

				if ($fe) {
					$needToCreateTh = false;


					$time1 = filemtime(DOC_ROOT."/images/real/0/".$image);
					$time2 = filemtime(DOC_ROOT."/images/real/1/".$image);

					if ($time1 > $time2) {
						$needToCreateTh = true;
					}
				}
				else {
					$fe2 = file_exists(DOC_ROOT."/images/real/0/".$image);
					if (!$fe2) {
						$needToCreateTh = false;
						$image = "";
					}else{
						//заглушка
						$fe22 = is_file(DOC_ROOT."/images/real/1/".$image);
//				var_dump($fe22);
						if (!$fe22) {
							$needToCreateTh = false;
							$image = "";
						}
					}
				}

				//заглушка
				$fe22 = is_file(DOC_ROOT."/images/real/2/".$image);
//				var_dump($fe22);
				if (!$fe22) {
					$needToCreateTh = false;
					$image = "";
				}

				$needToCreateTh = false;

				if ($needToCreateTh) {
					self::createTh($image);
				}

				if ($image != "") {
					$tempImage = new StdClass();
					$tempImage->image = $image;
					$newImages[] = $tempImage;
				}

			}
			else {
				$image = "";
			}
		}



		return $newImages;
	}

	// public static function createTh($filename) {
	// 	self::resize_image($filename, "452x452", 'temp');

	// 	$n = strrpos($filename, ".");
	// 	$ext = substr($filename, $n);

	// 	$format = exif_imagetype(DOC_ROOT."/images/real/0/$filename");

	// 	if ($format === 3) {
	// 		$format = "PNG";
	// 	}
	// 	else {
	// 		$format = "JPG";
	// 	}

	// 	switch($format) {
	// 		case 'JPG':
	// 			$sourceImage = ImageCreateFromJpeg(DOC_ROOT . '/images/real/temp/' . $filename);
	// 			break;
	// 		case 'PNG':
	// 			$sourceImage = ImageCreateFromPng(DOC_ROOT . '/images/real/temp/' . $filename);
	// 			ImageAlphaBlending($sourceImage, false);
	// 			imageSaveAlpha($sourceImage, true);
	// 			break;
	// 	}

	// 	$size = GetImageSize(DOC_ROOT . '/images/real/temp/' . $filename);

	// 	$currentDimensions = array('width'=>$size[0],'height'=>$size[1]);



	// 	// Накладываем на белый фон, чтобы были поля (для кадрирования)
	// 	$whiteImage = imagecreatetruecolor($currentDimensions['width'] + 300, $currentDimensions['height'] + 300);
	// 	ImageFilledRectangle($whiteImage, 0, 0, $currentDimensions['width'] + 300, $currentDimensions['height'] + 300, 0xffffff);

	// 	imagecopy($whiteImage, $sourceImage, 150, 150, 0, 0, $currentDimensions['width'], $currentDimensions['height']);

	// 	switch($format) {
	// 		case 'JPG':
	// 			ImageJpeg($whiteImage, DOC_ROOT."/images/real/temp/".$filename, 98);
	// 			break;

	// 		case 'PNG':
	// 			imagealphablending($whiteImage, false);
	// 			imageSaveAlpha($whiteImage, true);
	// 			ImagePng($whiteImage, DOC_ROOT."/images/real/temp/".$filename);
	// 			break;
	// 	}

	// 	// touch(DOC_ROOT."/images/$filename");

	// 	$image = new sys_images(DOC_ROOT."/images/real/temp/".$filename);
	// 	$image->cropFromCenterDif(452, 378);
	// 	file::checkDir(DOC_ROOT."/images/real/1/");
	// 	$image->save(DOC_ROOT."/images/real/1/".$filename);

	// 	$image = new sys_images(DOC_ROOT."/images/real/temp/".$filename);
	// 	$image->resize(223, 223);
	// 	$image->cropFromCenterDif(223, 128);
	// 	file::checkDir(DOC_ROOT."/images/real/2/");
	// 	$image->save(DOC_ROOT."/images/real/2/".$filename);
	// }

	private function createTh($filename) {

		$n = strrpos($filename, ".");
		$ext = substr($filename, $n);

		$ext = strtolower($ext);

		// check if jpg
		if($ext =='.jpg' || $ext == '.jpeg') {
			$format = 'JPG';
		}
		// check if png
		elseif($ext == '.png') {
			$format = 'PNG';
		}
		else {
			return;
		}

		$lastSegment = explode("/", $filename);
		$lastSegment = $lastSegment[0];

		exec("mogrify -resize 452x378 -background white -gravity center -extent 452x378 -path ".DOC_ROOT."/images/real/1/$lastSegment ".DOC_ROOT."/images/real/0/$filename");

		file::checkDir(DOC_ROOT."/images/real/1/".$filename, 0777, true);

		exec("mogrify -resize 223x128 -background white -gravity center -extent 223x128 -path ".DOC_ROOT."/images/real/2/$lastSegment ".DOC_ROOT."/images/real/0/$filename");

		file::checkDir(DOC_ROOT."/images/real/2/".$filename, 0777, true);
	}
}
?>