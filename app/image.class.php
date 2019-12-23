<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Image
{
	const IMAGES = 'i/';
	const THUMBS = 't/';

	private static function random_str($len = 10){
		$chr = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$chr_len = strlen($chr);
		$random_str = '';

		for($i = 0; $i < $len; $i++){
			$random_str .= $chr[rand(0, $chr_len - 1)];
		}

		return $random_str;
	}

	private static function fix_orientation($path, $img){
		if(!function_exists('exif_read_data')){
			return $img;
		}

		$exif = exif_read_data($path);
		if(!$exif || !isset($exif['Orientation'])){
			return $img;
		}

		$deg = 0;
		switch($exif['Orientation']){
			case 3:
				$deg = 180;
				break;
			case 6:
				$deg = 270;
				break;
			case 8:
				$deg = 90;
				break;
		}

		if($deg){
			return imagerotate($img, $deg, 0);
		}

		return $img;
	}

	private static function thumb($source_path, $thumb_path){
		ini_set('memory_limit', '128M');

		$thumb_w = 476;
		$thumb_h = 476;

		$source_details = getimagesize($source_path);
		$source_w = $source_details[0];
		$source_h = $source_details[1];

		if($source_w > $source_h){
			$new_w = $thumb_w;
			$new_h = intval($source_h * $new_w / $source_w);
		} else {
			$new_h = $thumb_h;
			$new_w = intval($source_w * $new_h / $source_h);
		}

		switch($source_details[2]){
			case IMAGETYPE_GIF:
				$imgt = "ImageGIF";
				$imgcreatefrom = "ImageCreateFromGIF";
				break;

			case IMAGETYPE_JPEG:
				$imgt = "ImageJPEG";
				$imgcreatefrom = "ImageCreateFromJPEG";
				break;

			case IMAGETYPE_PNG:
				$imgt = "ImagePNG";
				$imgcreatefrom = "ImageCreateFromPNG";
				break;

			default:
				return false;
		}

		$old_image = $imgcreatefrom($source_path);
		$new_image = imagecreatetruecolor($new_w, $new_h);
		imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $new_w, $new_h, $source_w, $source_h);

		$new_image = self::fix_orientation($source_path, $new_image);
		$old_image = self::fix_orientation($source_path, $old_image);

		$imgt($new_image, $thumb_path);
		$imgt($old_image, $source_path);
		return true;
	}

	public static function upload(){
		if(!$_FILES){
			throw new Exception("No file.");
		}

		// Create MD5
		$md5 = md5_file($_FILES['file']['tmp_name']);

		// Find duplicate
		if($d = DB::get_instance()->query("SELECT `path`, `thumb` FROM `images` WHERE `md5` = ? AND `status` = 1 LIMIT 1", $md5)->first()){
			return $d;
		}

		// Get metadata
		$name = $_FILES['file']['name'];
		$ext = pathinfo($name, PATHINFO_EXTENSION);

		// Save to DB
		$id = DB::get_instance()->query(
			"INSERT INTO `images` ".
			"(`id`, `name`, `path`, `thumb`, `type`, `md5`, `datetime`, `status`) ".
			"VALUES (NULL, ?, NULL, NULL, ?, ?, NOW(), 0);",
			$name, $ext, $md5
		)->last_id();

		// Create path name
		$name = dechex($id).self::random_str(3).".".$ext;
		$path = self::IMAGES.$name;
		$thumb = self::THUMBS.$name;

		// Save path
		if(!move_uploaded_file($_FILES['file']['tmp_name'], $path)){
			throw new Exception("Can't write to image folders `i` and `t`.");
		}

		// Create thumb
		if(!self::thumb($path, $thumb)){
			unlink($path);
			unlink($thumb);
			throw new Exception("File is not image.");
		}

		// Save to DB
		DB::get_instance()->query("UPDATE `images` SET `path` = ?, `thumb` = ?, `status` = 1 WHERE `id` = ?", $path, $thumb, $id);
		return [
			"path" => $path,
			"thumb" => $thumb
		];
	}
}