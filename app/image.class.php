<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Image
{
	const THUMB_W = 476;
	const THUMB_H = 476;

	const PHP_FILE_UPLOAD_ERRORS = [
		0 => 'There is no error, the file uploaded with success.',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		3 => 'The uploaded file was only partially uploaded.',
		4 => 'No file was uploaded.',
		6 => 'Missing a temporary folder.',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.',
	];

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

		$source_details = getimagesize($source_path);
		$source_w = $source_details[0];
		$source_h = $source_details[1];

		if($source_w > $source_h){
			$new_w = self::THUMB_W;
			$new_h = intval($source_h * $new_w / $source_w);
		} else {
			$new_h = self::THUMB_H;
			$new_w = intval($source_w * $new_h / $source_h);
		}

		switch($source_details[2]){
			case IMAGETYPE_GIF:
				$imgt = "imagegif";
				$imgcreatefrom = "imagecreatefromgif";
				break;

			case IMAGETYPE_JPEG:
				$imgt = "imagejpeg";
				$imgcreatefrom = "imagecreatefromjpeg";
				break;

			case IMAGETYPE_PNG:
				$imgt = "imagepng";
				$imgcreatefrom = "imagecreatefrompng";
				break;

			case IMAGETYPE_WEBP:
				$imgt = "imagewebp";
				$imgcreatefrom = "imagecreatefromwebp";
				break;

			case IMAGETYPE_WBMP:
				$imgt = "imagewbmp";
				$imgcreatefrom = "imagecreatefromwbmp";
				break;

			case IMAGETYPE_BMP:
				$imgt = "imagebmp";
				$imgcreatefrom = "imagecreatefrombmp";
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

		// Ensure, that directories exists
		$_images_path = Config::get('images_path');
		$_thumbnails_path = Config::get('thumbnails_path');
		if(
			(!is_dir($_images_path) && !mkdir($_images_path, 0755, true)) ||
			(!is_dir($_thumbnails_path) && !mkdir($_thumbnails_path, 0755, true))
		){
			throw new Exception("Images or thumbnails directory could not be created.");
		}

		// Get metadata
		$name = $_FILES['file']['name'];
		$ext = pathinfo($name, PATHINFO_EXTENSION);

		// Save to DB
		$id = DB::get_instance()->insert('images', [
			'name' => $name,
			'type' => $ext,
			'md5' => $md5,
			'datetime' => 'NOW()',
			'status' => 0,
		])->last_id();

		// Create path name
		$name = dechex($id).self::random_str(3).".".$ext;
		$path = $_images_path.$name;
		$thumb = $_thumbnails_path.$name;

		// Save path
		if(!move_uploaded_file($_FILES['file']['tmp_name'], $path)){
			throw new Exception(self::PHP_FILE_UPLOAD_ERRORS[$_FILES['file']['error']]);
		}

		// Create thumb
		if(!self::thumb($path, $thumb)){
			unlink($path);
			unlink($thumb);
			throw new Exception("File is not valid image.");
		}

		// Save to DB
		DB::get_instance()->update('images', [
			'path' => $path,
			'thumb' => $thumb,
			'status' => 1,
		], "WHERE `id` = ?", $id);

		return [
			"path" => $path,
			"thumb" => $thumb
		];
	}
}