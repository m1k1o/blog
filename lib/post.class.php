<?php

class Post
{
	private static function login_protected(){
		if(!User::is_logged_in()){
			throw new Exception("You need to be logged in to perform this action.");
		}
	}
	
	private static function pirvacy($c){
		if($c == "public" || $c == "friends"){
			return $c;
		}
		
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
	
	public static function insert($r){
		self::login_protected();
		
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
		self::login_protected();
		
		$r["pirvacy"] = self::pirvacy($r["pirvacy"]);
		$plain_text = $r["text"];
		$r["text"] = self::parse_content($r["text"]);
		DB::get_instance()->query("UPDATE `posts` SET `text` = ?, `plain_text` = ?, `feeling` = ?, `persons` = ?, `location` = ?, `pirvacy` = ?, `content_type` = ?, `content` = ? WHERE `id` = ? AND `status` = 1", $r["text"], $plain_text, $r["feeling"], $r["persons"], $r["location"], $r["pirvacy"], $r["content_type"], $r["content"], $r["id"]);
		return $r;
	}
	
	public static function hide($r){
		self::login_protected();
		
		DB::get_instance()->query("UPDATE `posts` SET `status` = 4 WHERE `id` = ?", $r["id"]);
		return ["done" => true];
	}
	
	public static function delete($r){
		self::login_protected();
		
		DB::get_instance()->query("UPDATE `posts` SET `status` = 5 WHERE `id` = ?", $r["id"]);
		return ["done" => true];
	}
	
	public static function edit_data($r){
		self::login_protected();
		
		return DB::get_instance()->query("SELECT `plain_text` AS `text`, `feeling`, `persons`, `location`, `pirvacy`, `content_type`, `content` FROM `posts` WHERE `id` = ? AND `status` = 1", $r["id"])->first();
	}
	
	public static function get_date($r){
		self::login_protected();
		
		$date = DB::get_instance()->query("SELECT DATE_FORMAT(`datetime`,'%Y %c %e %k %i') AS `date_format` FROM `posts` WHERE `id` = ? AND `status` = 1", $r["id"])->first("date_format");
		$date = array_map("intval", explode(" ", $date));
		$date[4]  = floor($date[4]/10)*10;
		return $date;
	}
	
	public static function set_date($r){
		self::login_protected();
		
		$d = $r["date"];
		$datetime = "{$d[0]}/{$d[1]}/{$d[2]} {$d[3]}:{$d[4]}";
		DB::get_instance()->query("UPDATE `posts` SET `datetime` = ? WHERE `id` = ? AND `status` = 1", $datetime, $r["id"]);
		return [
			"datetime" => date("d M Y H:i", strtotime($datetime))
		];
	}
	
	public static function parse_link($r){
		self::login_protected();
		
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
			
			if($n == 'twitter:description' || $p == 'og:description' || $n == 'description'){
				$content["desc"] = substr($c, 0, 180);
			}
			
			if($n == 'twitter:title' || $p == 'og:title' || $p == 'title'){
				$content["title"] = $c;
			}
			
			if($p == 'og:url'){
				$content["link"] = $c;
			}
			
			if($p == 'og:type'){
				$content["is_video"] = ($c == "video");
			}
			
			if($n == 'twitter:image:src' || $p == 'og:image'){
				$content["thumb"] = $c;
			}
			
			if($n == 'twitter:domain'){
				$content["host"] = $c;
			}
		}
		
		return [
			"valid" => true,
			"content_type" => "link",
			"content" => $content
		];
	}
	
	public static function upload_image($r){
		self::login_protected();
		
		return Image::upload($r["name"], $r["data"]);
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
				(!User::is_logged_in() ? "`pirvacy` = 'public' AND " : "").
				($until ? "`posts`.`datetime` < DATE_ADD('{$until}', INTERVAL +1 MONTH) AND " : "").
				($id ? "`id` = {$id} AND " : "").
				"`status` = 1 ".
			"ORDER BY `posts`.`datetime` DESC ".
			"LIMIT ? OFFSET ?", $r["limit"], $r["offset"]
		)->all();
	}
	
	public static function login($r){
		return User::login($r["nick"], $r["pass"]);
	}
	
	public static function logout(){
		return User::logout();
	}
	
	public static function handshake($r){
		return ["logged_in" => User::is_logged_in()];
	}
}