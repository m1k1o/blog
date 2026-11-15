<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Image
{
	const THUMB_W = 476;
	const THUMB_H = 476;

	const VIDEO_EXTENSIONS = ['mp4', 'm4v', 'mov', 'webm', 'ogv'];

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

		$exif = @exif_read_data($path);
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

	private static function heic_supported(){
		return extension_loaded('imagick') && !empty(Imagick::queryFormats('HEIC'));
	}

	private static function heic_to_jpeg($source_path, $target_path){
		try {
			$img = new Imagick();

			// Force HEIC decoder, so that no other ImageMagick coder is used for untrusted input
			$img->readImage('heic:'.$source_path);
			$img->setImageFormat('jpeg');
			$img->setImageCompressionQuality(90);

			// Orientation is already applied by decoder, remove metadata so that it is not applied twice
			$img->stripImage();

			return $img->writeImage($target_path);
		} catch (ImagickException $e) {
			return false;
		}
	}

	private static function thumb($source_path, $thumb_path){
		ini_set('memory_limit', '128M');

		$source_details = getimagesize($source_path);
		if(!$source_details || !$source_details[0] || !$source_details[1]){
			return false;
		}

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
		if ($old_image === false) return false;

		$new_image = imagecreatetruecolor($new_w, $new_h);
		imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $new_w, $new_h, $source_w, $source_h);

		$new_image = self::fix_orientation($source_path, $new_image);
		$old_image = self::fix_orientation($source_path, $old_image);

		return $imgt($new_image, $thumb_path)
			&& $imgt($old_image, $source_path);
	}

	// Delete uploaded image and its thumbnail, if it is not used by any post
	public static function delete_unused($path){
		// Only images uploaded to the blog can be deleted
		$images = DB::get_instance()->query("
			SELECT `path`, `thumb`
			FROM `images`
			WHERE `path` = ?
		", $path)->all();

		if(empty($images)){
			return false;
		}

		// The same image can be used by multiple posts, because uploads are deduplicated.
		// Search by file name only, so that differently encoded paths are matched as well.
		$like_match = "LIKE ".DB::concat("'%'", "?", "'%'");
		$name = basename($path);

		$used = DB::get_instance()->query("
			SELECT COUNT(*) AS `count`
			FROM `posts`
			WHERE `content` $like_match
			OR `plain_text` $like_match
		", $name, $name)->first("count");

		if($used){
			return false;
		}

		foreach($images as $image){
			foreach([$image["path"], $image["thumb"]] as $file){
				if($file && is_file($file)){
					unlink($file);
				}
			}
		}

		DB::get_instance()->query("
			DELETE FROM `images`
			WHERE `path` = ?
		", $path);

		return true;
	}

	private static function check_upload(){
		if(!$_FILES){
			// Request body is discarded by PHP, when it is larger than allowed
			if(!empty($_SERVER['CONTENT_LENGTH'])){
				throw new Exception("The uploaded file exceeds the post_max_size directive in php.ini.");
			}

			throw new Exception("No file.");
		}

		$error = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
		if($error !== UPLOAD_ERR_OK){
			throw new Exception(self::PHP_FILE_UPLOAD_ERRORS[$error] ?? "File could not be uploaded.");
		}
	}

	// Images always have a thumbnail, videos never have one
	private static function find_duplicate($md5, $video = false){
		return DB::get_instance()->query("
			SELECT `path`, `thumb`
			FROM `images`
			WHERE `md5` = ?
			AND `status` = 1
			AND `thumb` IS ".($video ? "NULL" : "NOT NULL")."
			LIMIT 1
		", $md5)->first();
	}

	public static function upload(){
		self::check_upload();

		// Create MD5
		$md5 = md5_file($_FILES['file']['tmp_name']);

		// Find duplicate
		if($d = self::find_duplicate($md5)){
			return $d;
		}

		// Get metadata
		$name = $_FILES['file']['name'];
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

		// Validate extension against supported image types
		$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'wbmp', 'bmp', 'heic', 'heif'];
		if (!in_array($ext, $allowed_extensions, true)) {
			throw new Exception("File type not allowed.");
		}

		// HEIC images can not be displayed by most browsers, convert them to JPEG
		$is_heic = in_array($ext, ['heic', 'heif'], true);
		if ($is_heic) {
			if (!self::heic_supported()) {
				throw new Exception("HEIC images are not supported, Imagick extension with HEIC support is required.");
			}

			$ext = 'jpg';
		}

		return self::save($_FILES['file']['tmp_name'], $name, $ext, $md5, $is_heic);
	}

	// Store image downloaded from another server, source file is moved unless it is a duplicate
	public static function import($source_path, $name){
		// Extension is taken from content, only types supported by thumbnails are allowed
		$details = @getimagesize($source_path);
		$ext = $details ? image_type_to_extension($details[2], false) : false;
		if (!in_array($ext, ['jpeg', 'png', 'gif', 'webp', 'wbmp', 'bmp'], true)) {
			throw new Exception("File is not valid image.");
		}

		// Create MD5
		$md5 = md5_file($source_path);

		// Find duplicate
		if($d = self::find_duplicate($md5)){
			return $d;
		}

		return self::save($source_path, substr($name, 0, 255), $ext, $md5);
	}

	// Store image in images directory and create its thumbnail, source file is moved
	private static function save($source_path, $name, $ext, $md5, $is_heic = false){
		// Ensure, that directories exists
		$_images_path = Config::get('images_path');
		$_thumbnails_path = Config::get('thumbnails_path');
		if(
			(!is_dir($_images_path) && !mkdir($_images_path, 0755, true)) ||
			(!is_dir($_thumbnails_path) && !mkdir($_thumbnails_path, 0755, true))
		){
			throw new Exception("Images or thumbnails directory could not be created.");
		}

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

		// Save path, moved file gets the same permissions as uploaded file would get
		$saved = is_uploaded_file($source_path)
			? move_uploaded_file($source_path, $path)
			: rename($source_path, $path) && chmod($path, 0666 & ~umask());

		if(!$saved){
			throw new Exception("File could not be saved.");
		}

		// Convert HEIC to JPEG
		if($is_heic && !self::heic_to_jpeg($path, $path)){
			unlink($path);
			throw new Exception("File is not valid image.");
		}

		// Create thumb
		if(!self::thumb($path, $thumb)){
			unlink($path);
			if(file_exists($thumb)){
				unlink($thumb);
			}
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

	private static function is_video($path){
		$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
		return is_string($mime) && str_starts_with($mime, 'video/');
	}

	public static function upload_video(){
		self::check_upload();

		// Get metadata
		$name = $_FILES['file']['name'];
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

		// Validate extension against supported video types
		if (!in_array($ext, self::VIDEO_EXTENSIONS, true)) {
			throw new Exception("File type not allowed.");
		}

		if (!extension_loaded('fileinfo')) {
			throw new Exception("Videos are not supported, fileinfo extension is required.");
		}

		// Check content, so that only videos are stored with video extension
		if (!self::is_video($_FILES['file']['tmp_name'])) {
			throw new Exception("File is not valid video.");
		}

		// Create MD5
		$md5 = md5_file($_FILES['file']['tmp_name']);

		// Find duplicate
		if($d = self::find_duplicate($md5, true)){
			return ["path" => $d["path"]];
		}

		// Videos are stored together with images, they have no thumbnail
		$_images_path = Config::get('images_path');
		if(!is_dir($_images_path) && !mkdir($_images_path, 0755, true)){
			throw new Exception("Images directory could not be created.");
		}

		// Save to DB
		$id = DB::get_instance()->insert('images', [
			'name' => $name,
			'type' => $ext,
			'md5' => $md5,
			'datetime' => 'NOW()',
			'status' => 0,
		])->last_id();

		// Create path name
		$path = $_images_path.dechex($id).self::random_str(3).".".$ext;

		// Save path
		if(!move_uploaded_file($_FILES['file']['tmp_name'], $path)){
			throw new Exception("File could not be saved.");
		}

		// Save to DB
		DB::get_instance()->update('images', [
			'path' => $path,
			'status' => 1,
		], "WHERE `id` = ?", $id);

		return [
			"path" => $path
		];
	}
}