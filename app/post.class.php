<?php

class Post
{
	private static function login_protected(){
		if(!User::is_logged_in()){
			throw new Exception(__("You need to be logged in to perform this action."));
		}
	}

	private static function parse_content($c){
		require_once APP_PATH."jbbcode/Parser.php";

		$parser = new JBBCode\Parser();
		$parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());

		if(Config::get("highlight")){
			$c = str_replace("\t", "  ", $c);
			$c = preg_replace("/\[code(?:=([^\[]+))?\]\s*?(?:\n|\r)?/i", '[code=$1]', $c);
			$c = preg_replace("/\[\/code\]\s*?(?:\n|\r)?/i", '[/code]', $c);

			// Add code definiton
			$parser->addCodeDefinition(new class extends \JBBCode\CodeDefinition {
				public function __construct(){
					parent::__construct();
					$this->setTagName("code");
					$this->setParseContent(false);
					$this->setUseOption(true);
				}

				public function asHtml(\JBBCode\ElementNode $el){
					$content = $this->getContent($el);
					return '<code class="'.$el->getAttribute().'">'.htmlentities($content).'</code>';
				}
			});
		}

		// Custom tags
		$builder = new JBBCode\CodeDefinitionBuilder("goal", "<div class=\"b_goal star\">{param}</div>");
		$parser->addCodeDefinition($builder->build());

		$builder = new JBBCode\CodeDefinitionBuilder("goal", "<div class=\"b_goal {option}\">{param}</div>");
		$builder->setUseOption(true);
		$parser->addCodeDefinition($builder->build());

		if(($tags = Config::get_safe("bbtags", [])) && !empty($tags)){
			foreach($tags as $tag => $content){
				$builder = new JBBCode\CodeDefinitionBuilder($tag, $content);
				$parser->addCodeDefinition($builder->build());
			}
		}

		$parser->parse($c);

		// Visit every text node
		$parser->accept(new class implements \JBBCode\NodeVisitor{
			function visitDocumentElement(\JBBCode\DocumentElement $documentElement){
				foreach($documentElement->getChildren() as $child) {
					$child->accept($this);
				}
			}

			function visitTextNode(\JBBCode\TextNode $textNode){
				$c = $textNode->getValue();
				$c = preg_replace('/\"([^\"]+)\"/i', "„$1\"", $c);
				$c = htmlentities($c);
				$c = preg_replace('/\*([^\*]+)\*/i', "<strong>$1</strong>", $c);
				$c = preg_replace('/(https?\:\/\/[^\" \n]+)/i', "<a href=\"\\0\" target=\"_blank\">\\0</a>", $c);
				$c = preg_replace('/(\#[A-Za-z0-9-_]+)/i', "<span class=\"tag\">\\0</span>", $c);
				$c = nl2br($c);
				$textNode->setValue($c);
			}

			function visitElementNode(\JBBCode\ElementNode $elementNode){
				/* We only want to visit text nodes within elements if the element's
				 * code definition allows for its content to be parsed.
				 */
				if ($elementNode->getCodeDefinition()->parseContent()) {
					foreach ($elementNode->getChildren() as $child) {
						$child->accept($this);
					}
				}
			}
		});

		return $parser->getAsHtml();
	}

	private static function raw_data($raw_input){
		$default_input = [
			"text" => '',
			"plain_text" => '',
			"feeling" => '',
			"persons" => '',
			"location" => '',
			"content_type" => '',
			"content" => '',
			"privacy" => ''
		];

		// Handle only allowed keys
		$raw_output = array();
		foreach($default_input as $key => $def){
			// Key exists in input
			if(array_key_exists($key, $raw_input)){
				$raw_output[$key] = $raw_input[$key];
			} else {
				$raw_output[$key] = $default_input[$key];
			}
		}

		if($raw_output['privacy'] != "public" && $raw_output['privacy'] != "friends"){
			$raw_output['privacy'] =  "private";
		}

		return $raw_output;
	}

	public static function insert($r){
		self::login_protected();

		$data = self::raw_data($r);

		if(empty($data['text'])){
			throw new Exception(__("No data."));
		}

		$data['plain_text'] = $data['text'];
		$data['text'] = self::parse_content($data['text']);
		$data['datetime'] = 'NOW()';
		$data['status'] = '1';

		$data['id'] = DB::get_instance()->insert('posts', $data)->last_id();

		$data['datetime'] = date("d M Y H:i");
		unset($data['plain_text']);

		return $data;
	}

	public static function update($r){
		self::login_protected();

		$data = self::raw_data($r);

		$data['plain_text'] = $data['text'];
		$data['text'] = self::parse_content($data['text']);

		DB::get_instance()->update('posts', $data, "WHERE `id` = ? AND `status` = 1", $r["id"]);

		unset($data['plain_text']);

		return $data;
	}

	public static function hide($r){
		self::login_protected();

		DB::get_instance()->query("UPDATE `posts` SET `status` = 4 WHERE `id` = ?", $r["id"]);
		return true;
	}

	public static function delete($r){
		self::login_protected();

		DB::get_instance()->query("UPDATE `posts` SET `status` = 5 WHERE `id` = ?", $r["id"]);
		return true;
	}

	public static function edit_data($r){
		self::login_protected();

		return DB::get_instance()->query("SELECT `plain_text`, `feeling`, `persons`, `location`, `privacy`, `content_type`, `content` FROM `posts` WHERE `id` = ? AND `status` = 1", $r["id"])->first();
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
		return [ "datetime" => date("d M Y H:i", strtotime($datetime)) ];
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

	public static function upload_image(){
		self::login_protected();

		return Image::upload();
	}

	public static function load($r){
		$until = [];
		if(preg_match("/^[0-9]{4}-[0-9]{2}$/", $r["filter"]["until"])){
			$until = $r["filter"]["until"]."-01 00:00";
		}

		if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $r["filter"]["until"])){
			$until = $r["filter"]["until"]." 23:59";
		}

		$id = [];
		if($r["filter"]["id"]){
			$id = intval($r["filter"]["id"]);
		}

		$tag = [];
		if(preg_match("/^[A-Za-z0-9-_]+$/", $r["filter"]["tag"])){
			$tag = '#'.$r["filter"]["tag"];
		}

		$loc = [];
		if($r["filter"]["loc"]){
			$loc = $r["filter"]["loc"];
		}

		$person = [];
		if($r["filter"]["person"]){
			$person = $r["filter"]["person"];
		}

		return DB::get_instance()->query(
			"SELECT `id`, `text`, `feeling`, `persons`, `location`, `privacy`, `content_type`, `content`, DATE_FORMAT(`posts`.`datetime`,'%d %b %Y %H:%i') AS `datetime` ".
			"FROM `posts` ".
			"WHERE ".
				(!User::is_logged_in() ? (User::is_visitor() ? "`privacy` IN ('public', 'friends') AND " : "`privacy` = 'public' AND ") : "").
				($until ? "`posts`.`datetime` < DATE_ADD(?, INTERVAL +1 MONTH) AND " : "").
				($id ? "`id` = ? AND " : "").
				($tag ? "`plain_text` LIKE CONCAT('%', ?, '%') AND " : "").
				($loc ? "`location` LIKE CONCAT('%', ?, '%') AND " : "").
				($person ? "`persons` LIKE CONCAT('%', ?, '%') AND " : "").
				"`status` = 1 ".
			"ORDER BY `posts`.`datetime` DESC ".
			"LIMIT ? OFFSET ?", $until, $id, $tag, $loc, $person, $r["limit"], $r["offset"]
		)->all();
	}

	public static function login($r){
		return User::login($r["nick"], $r["pass"]);
	}

	public static function logout(){
		return User::logout();
	}

	public static function handshake($r){
		return ["logged_in" => User::is_logged_in(), "is_visitor" => User::is_visitor()];
	}
}