<?php

class Post
{
	private static function is_logged_in(){
		if(!Config::get_safe("force_login", false)){
			return true;
		}
		
		return !empty($_SESSION["logged_in"]) && $_SESSION["logged_in"] == md5(Config::get("nick").Config::get_safe("pass", ""));
	}
	
	private static function pirvacy($c){
		if($c == "public" || $c == "friends")
			return $c;
		
		return "private";
	}
	
	private static function parse_content($c){
		//$c = htmlentities($c);
		
		// Links
		$c = preg_replace('/\"([^\"]+)\"/i', "„$1\"", $c);
		
		$c = preg_replace('/(https?\:\/\/[^\" \n]+)/i', "<a href=\"\\0\" target=\"_blank\">\\0</a>", $c);
		$c = preg_replace('/(\#[A-Za-z0-9-_]+)/i', "<span class=\"tag\">\\0</span>", $c);
		
		////Headlines
		//$c = preg_replace('/^\# (.*)$/m', "<h1>$1</h1>", $c);
		//$c = preg_replace('/^\#\# (.*)$/m', "<h2>$1</h2>", $c);
		//$c = preg_replace('/^\#\#\# (.*)$/m', "<h3>$1</h3>", $c);
		
		//$c = preg_replace('/\"([^\"]+)\"/i', "&#x84;&nbsp;<i>$1</i>&nbsp;&#x93;", $c);
		$c = preg_replace('/\*([^\*]+)\*/i', "<strong>$1</strong>", $c);
		
		$c = nl2br($c);
		
		return $c;
	}
	
	private static function get_title($url){
		$str = file_get_contents($url);
		if(strlen($str)>0){
			$str = trim(preg_replace('/\s+/', ' ', $str)); // supports line breaks inside <title>
			preg_match("/\<title\>(.*)\<\/title\>/i",$str,$title); // ignore case
			return $title[1];
		}
	}
	
	private static function random_str($len = 10) {
		$chr = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$chr_len = strlen($chr);
		$random_str = '';
		
		for($i = 0; $i < $len; $i++){
			$random_str .= $chr[rand(0, $chr_len - 1)];
		}
		
		return $random_str;
	}
	
	public static function thumb($source_path, $thumb_path){
		ini_set('memory_limit', '128M');
		
		$thumb_w = 476;
		$thumb_h = 476;
		
		$source_details = getimagesize($source_path); // pass id to thumb name
		$source_w = $source_details[0];
		$source_h = $source_details[1];
		
		if($source_w > $source_h){
			$new_w = $thumb_w;
			$new_h = intval($source_h * $new_w / $source_w);
		} else {
			$new_h = $thumb_h;
			$new_w = intval($source_w * $new_h / $source_h);
		}
		
		//$dest_x = intval(($thumb_w - $new_w) / 2);
		//$dest_y = intval(($thumb_h - $new_h) / 2);
		
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
		//$new_image = imagecreatetruecolor($thumb_w, $thumb_h);
		//imagecopyresized($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_w, $new_h, $source_w, $source_h);
		return $imgt($new_image, $thumb_path);
	}
	
	public static function insert($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		$p = self::pirvacy($r["pirvacy"]);
		$text = self::parse_content($r["text"]);
		$post_id = DB::get_instance()->query(
			"INSERT INTO `posts` ".
			"(`id`, `text`, `plain_text`, `feeling`, `persons`, `location`, `pirvacy`, `content_type`, `content`, `datetime`, `status`) ".
			"VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1);",
			$text, $r["text"], $r["feeling"], $r["persons"], $r["location"], $p, $r["content_type"], $r["content"]
		)->last_id();
		
		return [
			"text" => $text,
			"feeling" => $r["feeling"],
			"persons" => $r["persons"],
			"location" => $r["location"],
			"pirvacy" => $p,
			"content_type" => $r["content_type"],
			"content" => $r["content"],
			"datetime" => date("d M Y H:i"),
			"id" => $post_id
		];
	}

	public static function update($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		$r["pirvacy"] = self::pirvacy($r["pirvacy"]);
		$plain_text = $r["text"];
		$r["text"] = self::parse_content($r["text"]);
		DB::get_instance()->query("UPDATE `posts` SET `text` = ?, `plain_text` = ?, `feeling` = ?, `persons` = ?, `location` = ?, `pirvacy` = ?, `content_type` = ?, `content` = ? WHERE `id` = ? AND `status` = 1", $r["text"], $plain_text, $r["feeling"], $r["persons"], $r["location"], $r["pirvacy"], $r["content_type"], $r["content"], $r["id"]);
		return $r;
	}
	
	public static function hide($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		DB::get_instance()->query("UPDATE `posts` SET `status` = 4 WHERE `id` = ?", $r["id"]);
		return ["done" => true];
	}
	
	public static function delete($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		DB::get_instance()->query("UPDATE `posts` SET `status` = 5 WHERE `id` = ?", $r["id"]);
		return ["done" => true];
	}
	
	public static function edit_data($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		return DB::get_instance()->query("SELECT `plain_text` AS `text`, `feeling`, `persons`, `location`, `pirvacy`, `content_type`, `content` FROM `posts` WHERE `id` = ? AND `status` = 1", $r["id"])->first();
	}
	
	public static function get_date($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		$date = DB::get_instance()->query("SELECT DATE_FORMAT(`datetime`,'%Y %c %e %k %i') AS `date_format` FROM `posts` WHERE `id` = ? AND `status` = 1", $r["id"])->first("date_format");
		$date = array_map("intval", explode(" ", $date));
		$date[4]  = floor($date[4]/10)*10;
		return $date;
	}
	
	public static function set_date($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		$d = $r["date"];
		$datetime = "{$d[0]}/{$d[1]}/{$d[2]} {$d[3]}:{$d[4]}";
		DB::get_instance()->query("UPDATE `posts` SET `datetime` = ? WHERE `id` = ? AND `status` = 1", $datetime, $r["id"]);
		return [
			"datetime" => date("d M Y H:i", strtotime($datetime))
		];
	}
	
	public static function parse_link($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		$l = $r["link"];
		
		preg_match('/^https?:\/\/([^:\/\s]+)([^\/\s]*\/)([^\.\s]+)\.(jpe?g|png|gif)((\?|\#)(.*))?$/i', $l, $img);
		if($img){
			return [
				"valid" => true,
				"content_type" => "img_link",
				"content" => [
					"src" => $l,
					"host" => $img[1]
				]
			];
		}
		
		preg_match('/^https?:\/\/(www\.)?([^:\/\s]+)(.*)?$/i', $l, $url);
		
		// Get content
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $l);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Proxycat/1.1)");
		curl_setopt($ch, CURLOPT_REFERER, '');
		$html = curl_exec($ch);
		curl_close($ch);
		
		// Parse
		$doc = new DOMDocument();
		@$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
		
		// Get title
		$nodes = $doc->getElementsByTagName('title');
		$title = $nodes->item(0)->nodeValue;
		
		// Content
		$content = [
			"link" => $l,
			"title" => ($title ? $title : $url[2]),
			"is_video" => false,
			"host" => $url[2]
		];
		
		// Metas
		$metas = $doc->getElementsByTagName('meta');
		for($i = 0; $i < $metas->length; $i++){
			$meta = $metas->item($i);
			
			$n = $meta->getAttribute('name');
			$p = $meta->getAttribute('property');
			$c = $meta->getAttribute('content');
			
			if($n == 'twitter:description' || $p == 'og:description' || $n == 'description')
				$content["desc"] = substr($c, 0, 180);
			
			if($n == 'twitter:title' || $p == 'og:title' || $p == 'title')
				$content["title"] = $c;
			
			if($p == 'og:url')
				$content["link"] = $c;
			
			if($p == 'og:type')
				$content["is_video"] = ($c == "video");
			
			if($n == 'twitter:image:src' || $p == 'og:image')
				$content["thumb"] = $c;
			
			if($n == 'twitter:domain')
				$content["host"] = $c;
		}
		
		return [
			"valid" => true,
			"content_type" => "link",
			"content" => $content
		];
	}
	
	public static function upload_image($r){
		if(!self::is_logged_in()){
			return ["error" => true, "msg" => "You need to be logged in to perform this action."];
		}
		
		$photo = null;
		$ext = null;
		
		if($r["data"]){
			preg_match('/^data\:image\/(jpe?g|png|gif)\;base64,(.*)$/', $r["data"], $m);
			
			if(!$m)
				return ["error" => true, "msg" => "invalid file"];
			
			$ext = $m[1];
			if($ext == "jpeg") $ext = "jpg";
			
			// Decode photo
			$photo = base64_decode($m[2]);
		}
		
		if($_FILES){
			$photo = file_get_contents($_FILES["file"]["tmp_name"]);
			$r["name"] = $_FILES['file']['name'];
			$ext = pathinfo($r["name"], PATHINFO_EXTENSION);
		}
		
		if(!$_FILES && !$r["data"])
			return ["error" => true, "msg" => "no file"];
		
		// Create MD5
		$md5 = md5($photo);
		
		// Find duplicate
		if($d = DB::get_instance()->query("SELECT `path`, `thumb` FROM `images` WHERE `md5` = ? AND `status` = 1 LIMIT 1", $md5)->first())
			return $d;
		
		// Save to DB
		$id = DB::get_instance()->query(
			"INSERT INTO `images` ".
			"(`id`, `name`, `path`, `thumb`, `type`, `md5`, `datetime`, `status`) ".
			"VALUES (NULL, ?, NULL, NULL, ?, ?, NOW(), 1);",
			$r["name"], $ext, $md5
		)->last_id();
		
		// Create path name
		$name = dechex($id).self::random_str(3).".".$ext;
		$path = 'i/'.$name;
		$thumb = 't/'.$name;
		
		// Save path
		file_put_contents($path, $photo);
		
		// Create thumb
		self::thumb($path, $thumb);
		
		// Save to DB
		DB::get_instance()->query("UPDATE `images` SET `path` = ?, `thumb` = ? WHERE `id` = ?", $path, $thumb, $id);
		return ["path" => $path, "thumb" => $thumb];
	}
	
	public static function load($r){
		$until = null;
		if(preg_match("/^([0-9]{4})-([0-9]{2})$/", $r["filter"]["until"])){
			$until = $r["filter"]["until"]."-01 00:00";
		}
		
		$id = null;
		if($r["filter"]["id"]){
			$id = intval($r["filter"]["id"]);
		}
		
		return DB::get_instance()->query(
			"SELECT `id`, `text`, `feeling`, `persons`, `location`, `pirvacy`, `content_type`, `content`, DATE_FORMAT(`posts`.`datetime`,'%d %b %Y %H:%i') AS `datetime` ".
			"FROM `posts` ".
			"WHERE ".
				(!self::is_logged_in() ? "`pirvacy` = 'public' AND " : "").
				($until ? "`posts`.`datetime` < DATE_ADD('{$until}', INTERVAL +1 MONTH) AND " : "").
				($id ? "`id` = {$id} AND " : "").
				"`status` = 1 ".
			"ORDER BY `posts`.`datetime` DESC ".
			"LIMIT ? OFFSET ?", $r["limit"], $r["offset"]
		)->all();
	}
	
	public static function login($r){
		if(!Config::get_safe("force_login", false))
			return ["error" => false];
		
		if(self::is_logged_in())
			return ["error" => true, "msg" => "You are already logged in."];
		
		if(Config::get("nick") == $r["nick"] && Config::get_safe("pass", "") == $r["pass"]){
			$_SESSION["logged_in"] = md5($r["nick"].$r["pass"]);
			return ["error" => false];
		}
		
		if(Config::get_safe("logs", false))
			file_put_contents('logs/login_fails.log', date('Y-m-d H:i:s')."\t".$_SERVER["REMOTE_ADDR"]."\t".$_SERVER["HTTP_USER_AGENT"]."\t".$r["nick"].PHP_EOL, FILE_APPEND);
		
		return ["error" => true, "msg" => "The nick or password is incorrect."];
	}
	
	public static function logout($r){
		if(!Config::get_safe("force_login", false))
			return ["error" => true, "msg" => "You can't log out. There is no account."];
		
		if(!self::is_logged_in())
			return ["error" => true, "msg" => "You are not even logged in."];
		
		$_SESSION["logged_in"] = false;
		return ["error" => false];
	}
	
	public static function handshake($r){
		return ["logged_in" => self::is_logged_in()];
	}
}