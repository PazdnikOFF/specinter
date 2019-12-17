<?php

class file {
	public function checkDir($dirName, $rights=0777) {
		$direct = "";
		$dir = explode("/",$dirName);
		for ($i = 0; $i < count($dir); $i++) {
			$direct .= $dir[$i];
			if (!is_dir($direct)) {
				mkdir($direct, $rights);
			}
			$direct .="/";
		}
		return $direct;
	}


	public function createFile($file_name, $dirName, $content, $rights=0777) {
		if (file::checkDir($dirName)) {
			$file = $dirName."/".$file_name;
			$fd = fopen($file, "w");
			flock($fd, LOCK_EX);
			fputs($fd, $content);
			fflush($fd);
			fclose($fd);
			chmod("$file", $rights);
		}
	}

	public function delFile($file_name, $dirName) {
		$d = opendir($dirName);

		while ( ($e = readdir($d)) !== false ) {
			if (is_dir($dirName.$e)) {
				$c = opendir($dirName."/".$e);
				while ( ($f = readdir($c) !== false )) {
					if (stristr($fname, $f) && strlen($f) == strlen($fname)) {
						unlink($dirName.$e."/".$f);
					}
				}
			}
		}
	}

	public function readFile($file_name ,$dirName) {
		return file_get_contents($dirName."/".$filname);
	}

	public function copyFile($file_name, $dirName, $dirName_to, $file_name_new) {
		if (!$file_name_new) $file_name_new = all::get_random_name().".".strtoupper(substr($file_name,-3));

		file::createFile($file_name_new, file::checkDir($dirName_to), file::readFile($file_name, $dirName));
		return $file_name_new;
	}


	public function filesize($path) {
		$size = filesize($path);
		if (($size/1024) < 1) {
			$text = $size.' б';
		}
		else {
			if (($size/(1024*1024)) < 1) {
				$text = round($size/(1024), 2).' кб';
			}
			else {
				$text = round($size/(1024*1024), 4).' Мб';
			}
		}
		return $text;
	}


	public function getFileName($url) {
		$path = explode("/", $url);
		if (strstr($path[count($path)-1], '.')) {
			return $path[count($path)-1];
		}
	}


	public function getParentName($url) {
		$path = explode("/", $url);
		if (strstr($path[count($path)-1], '.') || $path[count($path)-1] == "") {
			return $path[count($path)-2];
		}
		else {
			return $path[count($path)-1];
		}
	}


	public function getPath($url) {
		$path = explode("/", $url);
		if (strstr($path[count($path)-1], '.')) {
			return str_replace($path[count($path)-1], "", $url);
		}
		elseif ($path[count($path)-1] == "") {
			return $url;
		}
		else return $url."/";
	}


	public function RecurseDir($basedir, $AllDirectories = array()) {
		// Create array for current directories contents
		$ThisDir = array();
		// switch to the directory we wish to scan
		chdir($basedir);
		$current = getcwd();
		// open current directory for reading
		$handle = opendir(".");

		while ($file = readdir($handle)) {
			// Don't add special directories '..' or '.' to the list
			if (($file != '..') & ($file != '.')) {
				if (is_dir($file)) {
					// build an array of contents for this directory
					array_push($ThisDir, $current.'/'.$file);
				}
			}
		}

		closedir($handle);
		// Loop through each directory,  run RecurseDir function on each one
		foreach ($ThisDir as $key=>$var) {
			array_push($AllDirectories, $var);
			$AllDirectories = self::RecurseDir($var, $AllDirectories);
		}

		// make sure we go back to our origin
		chdir($basedir);
		return $AllDirectories;
	}

	public function removeDirRec($dir) {
		if ($objs = glob($dir."/*")) {
			foreach($objs as $obj) {
				is_dir($obj) ? removeDirRec($obj) : unlink($obj);
			}
		}
		rmdir($dir);
	}
}
?>