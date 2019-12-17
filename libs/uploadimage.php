<?php

$docroot = $_SERVER['DOCUMENT_ROOT'];
if (substr($docroot, -1) == "/") {
	$docroot = substr($docroot, 0, strlen($docroot)-1);
}
define("DOC_ROOT", $docroot);

include_once('../libs/images.php');
include_once('../inc/var.php');
include_once('../inc/libs/sql.php');
include_once('../inc/libs/all.php');


if (isset($_POST['mode'])) {
	if ($_POST['mode'] == 'crop') {
		cropImage();
	}
	if ($_POST['mode'] == 'delete') {
		deleteImage();
	}
}
else {
	if (!empty($_FILES)) {
		$tempFile = $_FILES['file']['tmp_name'];

		$fn = $_FILES['file']['name'];


		$n = strrpos($fn, ".");
		$ext = substr($fn, $n);
		$fn = (rand(0, 255) + 256 * rand(0, 255) + 65536 * rand(0, 255)) . time() . $ext;

		$targetPath = DOC_ROOT . '/files/0/';

		$targetFile =  $targetPath . $fn;

		move_uploaded_file($tempFile, $targetFile);

		resize_image($fn, "600x800", 'temp');
		resize_image($fn, "122x91", 'tmb');


		//check if jpg
		if(stristr(strtolower($ext),'.jpg') || stristr(strtolower($ext),'.jpeg')) $format = 'JPG';
		//check if png
		elseif(stristr(strtolower($ext),'.png')) $format = 'PNG';

		else {
			die("unknown format");
		}



		switch($format) {
			case 'JPG':
				$sourceImage = ImageCreateFromJpeg(DOC_ROOT . '/files/temp/' . $fn);
				break;
			case 'PNG':
				$sourceImage = ImageCreateFromPng(DOC_ROOT . '/files/temp/' . $fn);
				ImageAlphaBlending($sourceImage, false);
				imageSaveAlpha($sourceImage, true);
				break;
		}



		$size = GetImageSize(DOC_ROOT . '/files/temp/' . $fn);

		$currentDimensions = array('width'=>$size[0],'height'=>$size[1]);


		//Накладываем на белый фон, чтобы были поля (для кадрирования)
		$whiteImage = imagecreatetruecolor($currentDimensions['width'] + 400, $currentDimensions['height'] + 400);
		ImageFilledRectangle($whiteImage, 0, 0, $currentDimensions['width'] + 400, $currentDimensions['height'] + 400, 0xffffff);

		imagecopy($whiteImage, $sourceImage, 200, 200, 0, 0, $currentDimensions['width'], $currentDimensions['height']);

		switch($format) {
	        case 'JPG':
				ImageJpeg($whiteImage, DOC_ROOT."/files/temp/".$fn, 98);
				break;

	        case 'PNG':
				imagealphablending($whiteImage, false);
				imageSaveAlpha($whiteImage, true);
				ImagePng($whiteImage, DOC_ROOT."/files/temp/".$fn);
				break;
		}

		die($fn);
	}
}

//Ресайз изображения
function resize_image($data, $val, $resized) {
	$mimes = explode("x", $val);
	$image = new sys_images(DOC_ROOT."/files/0/".$data);
	$image->resize($mimes[0], $mimes[1]);
	$image->save(DOC_ROOT."/files/".$resized."/".$data);
}

function cropImage() {
	$x1 = $_POST['x1'] + 1 - 1;
	$x2 = $_POST['x2'] + 1 - 1;
	$y1 = $_POST['y1'] + 1 - 1;
	$y2 = $_POST['y2'] + 1 - 1;
	$w = $_POST['w'] + 1 - 1;
	$h = $_POST['h'] + 1 - 1;
	$realW = $_POST['realW'];
	$realH = $_POST['realH'];
	$resize = $_POST['resize'] + 1 - 1;

	$fileName = $_POST['fileName'];


	$image = new sys_images(DOC_ROOT."/files/temp/".$fileName);
	$image->crop($x1, $y1, $w, $h);
	$image->resize($realW, $realH);
	$image->save(DOC_ROOT."/files/$resize/".$fileName);

	die();
}

function deleteImage() {
	$fileName = $_POST['fileName'];

	@unlink(DOC_ROOT."/files/0/".$fileName);
	@unlink(DOC_ROOT."/files/1/".$fileName);
	@unlink(DOC_ROOT."/files/2/".$fileName);
	@unlink(DOC_ROOT."/files/3/".$fileName);
	@unlink(DOC_ROOT."/files/4/".$fileName);
	@unlink(DOC_ROOT."/files/5/".$fileName);
	@unlink(DOC_ROOT."/files/6/".$fileName);
	@unlink(DOC_ROOT."/files/7/".$fileName);
	@unlink(DOC_ROOT."/files/8/".$fileName);
	@unlink(DOC_ROOT."/files/9/".$fileName);
	@unlink(DOC_ROOT."/files/temp/".$fileName);
	@unlink(DOC_ROOT."/files/tmb/".$fileName);
}
?>