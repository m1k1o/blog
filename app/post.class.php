<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Post
{
	private static function login_protected(){
		if(!User::is_logged_in()){
			throw new Exception(__("You need to be logged in"));
		}
	}

	private static function parse_content($c){
		// Step 1: Extract and preserve user's HTML tags with unique placeholders
		$html_tags = [];
		$placeholder_count = 0;
		
		$c = preg_replace_callback('/<([^>]+)>/i', function($matches) use (&$html_tags, &$placeholder_count) {
			$placeholder = '§§§HTMLTAG' . $placeholder_count . '§§§';
			$html_tags[$placeholder] = $matches[0];
			$placeholder_count++;
			return $placeholder;
		}, $c);
		
		// Normalize line endings
		$c = str_replace(["\r\n", "\r"], "\n", $c);

		// Step 2: Process Markdown syntax
		
		// Headers (must be at start of line)
		$c = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $c);
		$c = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $c);
		$c = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $c);
		
		// Bold: **text**
		$c = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $c);
		
		// Italic: *text*
		$c = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/s', '<em>$1</em>', $c);
		
		// Strikethrough: ~~text~~
		$c = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $c);
		
		// Links: [text](url)
		$c = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/s', function($m){
			$text = $m[1];
			$url = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
			return '<a href="'.$url.'" target="_blank">'.$text.'</a>';
		}, $c);
		
		// Images: ![alt](url)
		$c = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/s', function($m){
			$alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
			$src = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
			return '<img src="'.$src.'" alt="'.$alt.'" style="max-width:100%;">';
		}, $c);
		
		// Code: `code`
		$c = preg_replace_callback('/`([^`]+)`/u', function($m){
			return '<code>'.htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8').'</code>';
		}, $c);
		
		// Lists (unordered and ordered)
		$c = preg_replace('/^[\-\*]\s+(.+)$/m', '<li>$1</li>', $c);
		$c = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $c);
		// Wrap consecutive <li> groups into <ul>
		$c = preg_replace('/(<li>.*?<\/li>\s*)+/s', '<ul>$0</ul>', $c);
		
		// Blockquotes
		$c = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $c);
		
		// Horizontal rule
		$c = preg_replace('/^(-{3,}|\*{3,})$/m', '<hr>', $c);
		
		// Auto-link plain URLs
		$c = preg_replace('/(?<!["\'=\/])\b(https?:\/\/[^\s<]+)\b/i', '<a href="$1" target="_blank">$1</a>', $c);
		
		// Hashtags
		$c = preg_replace('/(\#[A-Za-z0-9\-_]+)(\s|$)/u', '<span class="tag">$1</span>$2', $c);
		
		// Convert newlines to <br> for remaining text
		$c = nl2br($c);

		// Step 3: Restore user's original HTML tags
		foreach ($html_tags as $placeholder => $tag) {
			$c = str_replace($placeholder, $tag, $c);
		}
		
		return $c;
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
		$emoji_map = [
			':grinning:' => '😀',
			':smiley:' => '😃',
			':smile:' => '😄',
			':grin:' => '😁',
			':laughing:' => '😆',
			':joy:' => '😂',
			':rofl:' => '🤣',
			':blush:' => '😊',
			':innocent:' => '😇',
			':heart_eyes:' => '😍',
			':smiling_face_with_hearts:' => '🥰',
			':kissing_heart:' => '😘',
			':kissing:' => '😗',
			':sunglasses:' => '😎',
			':star_struck:' => '🤩',
			':hugging:' => '🤗',
			':thinking:' => '🤔',
			':neutral_face:' => '😐',
			':expressionless:' => '😑',
			':no_mouth:' => '😶',
			':eye_roll:' => '🙄',
			':smirk:' => '😏',
			':persevere:' => '😣',
			':disappointed_relieved:' => '😥',
			':open_mouth:' => '😮',
			':zipper_mouth:' => '🤐',
			':hushed:' => '😯',
			':sleepy:' => '😪',
			':tired_face:' => '😫',
			':yawning:' => '🥱',
			':sleeping:' => '😴',
			':relieved:' => '😌',
			':stuck_out_tongue:' => '😛',
			':stuck_out_tongue_winking_eye:' => '😜',
			':stuck_out_tongue_closed_eyes:' => '😝',
			':drooling:' => '🤤',
			':unamused:' => '😒',
			':sweat:' => '😓',
			':pensive:' => '😔',
			':confused:' => '😕',
			':upside_down:' => '🙃',
			':melting:' => '🫠',
			':money_mouth:' => '🤑',
			':astonished:' => '😲',
			':heart:' => '❤️',
			':broken_heart:' => '💔',
			':fire:' => '🔥',
			':star:' => '⭐',
			':check:' => '✅',
			':cross:' => '❌',
			':thumbs_up:' => '👍',
			':thumbs_down:' => '👎',
			':clap:' => '👏',
			':party:' => '🥳',
			':rocket:' => '🚀',
			':zap:' => '⚡',
			':warning:' => '⚠️',
			':tada:' => '🎉',
			':coffee:' => '☕',
			':cake:' => '🍰',
			':sun:' => '☀️',
			':moon:' => '🌙',
			':cloud:' => '☁️',
			':rainbow:' => '🌈',
			':flower:' => '🌸',
			':dog:' => '🐶',
			':cat:' => '🐱',
		];
		foreach($emoji_map as $code => $emoji){
			$data['text'] = str_replace($code, $emoji, $data['text']);
		}

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
		$emoji_map = [
			':grinning:' => '😀',
			':smiley:' => '😃',
			':smile:' => '😄',
			':grin:' => '😁',
			':laughing:' => '😆',
			':joy:' => '😂',
			':rofl:' => '🤣',
			':blush:' => '😊',
			':innocent:' => '😇',
			':heart_eyes:' => '😍',
			':smiling_face_with_hearts:' => '🥰',
			':kissing_heart:' => '😘',
			':kissing:' => '😗',
			':sunglasses:' => '😎',
			':star_struck:' => '🤩',
			':hugging:' => '🤗',
			':thinking:' => '🤔',
			':neutral_face:' => '😐',
			':expressionless:' => '😑',
			':no_mouth:' => '😶',
			':eye_roll:' => '🙄',
			':smirk:' => '😏',
			':persevere:' => '😣',
			':disappointed_relieved:' => '😥',
			':open_mouth:' => '😮',
			':zipper_mouth:' => '🤐',
			':hushed:' => '😯',
			':sleepy:' => '😪',
			':tired_face:' => '😫',
			':yawning:' => '🥱',
			':sleeping:' => '😴',
			':relieved:' => '😌',
			':stuck_out_tongue:' => '😛',
			':stuck_out_tongue_winking_eye:' => '😜',
			':stuck_out_tongue_closed_eyes:' => '😝',
			':drooling:' => '🤤',
			':unamused:' => '😒',
			':sweat:' => '😓',
			':pensive:' => '😔',
			':confused:' => '😕',
			':upside_down:' => '🙃',
			':melting:' => '🫠',
			':money_mouth:' => '🤑',
			':astonished:' => '😲',
			':heart:' => '❤️',
			':broken_heart:' => '💔',
			':fire:' => '🔥',
			':star:' => '⭐',
			':check:' => '✅',
			':cross:' => '❌',
			':thumbs_up:' => '👍',
			':thumbs_down:' => '👎',
			':clap:' => '👏',
			':party:' => '🥳',
			':rocket:' => '🚀',
			':zap:' => '⚡',
			':warning:' => '⚠️',
			':tada:' => '🎉',
			':coffee:' => '☕',
			':cake:' => '🍰',
			':sun:' => '☀️',
			':moon:' => '🌙',
			':cloud:' => '☁️',
			':rainbow:' => '🌈',
			':flower:' => '🌸',
			':dog:' => '🐶',
			':cat:' => '🐱',	
		];
		foreach($emoji_map as $code => $emoji){
			$data['text'] = str_replace($code, $emoji, $data['text']);
		}

		$data['text'] = self::parse_content($data['text']);

		DB::get_instance()->update('posts', $data, "WHERE `id` = ? AND `status` <> 5", $r["id"]);

		unset($data['plain_text']);

		return $data;
	}

	public static function hide($r){
		self::login_protected();

		DB::get_instance()->query("
			UPDATE `posts`
			SET `status` = 4
			WHERE `id` = ?
			AND `status` <> 5
		", $r["id"]);
		return true;
	}

	public static function show($r){
		self::login_protected();

		DB::get_instance()->query("
			UPDATE `posts`
			SET `status` = 1
			WHERE `id` = ?
			AND `status` <> 5
		", $r["id"]);
		return true;
	}

	/**
	 * Helper method to delete media associated with a post (images/files/mixed)
	 * @param array $post Post data containing content_type and content
	 */
	private static function delete_post_images($post){
		try {
			$content = json_decode($post['content'], true);

			// single image
			if ($post['content_type'] === 'image' && isset($content['path'])) {
				if (isset($content['path']) && file_exists($content['path'])) {
					@unlink($content['path']);
				}
				if (isset($content['thumb']) && file_exists($content['thumb'])) {
					@unlink($content['thumb']);
				}
				if (Config::get_safe('AUTO_CLEANUP_IMAGES', false)) {
					DB::get_instance()->query("
						UPDATE `images`
						SET `status` = 5
						WHERE `path` = ? OR `thumb` = ?
					", $content['path'], $content['thumb']);
				}
			}

			// multiple images
			if ($post['content_type'] === 'images' && is_array($content)) {
				foreach ($content as $image) {
					if (isset($image['path']) && file_exists($image['path'])) {
						@unlink($image['path']);
					}
					if (isset($image['thumb']) && file_exists($image['thumb'])) {
						@unlink($image['thumb']);
					}
					if (Config::get_safe('AUTO_CLEANUP_IMAGES', false)) {
						DB::get_instance()->query("
							UPDATE `images`
							SET `status` = 5
							WHERE `path` = ? OR `thumb` = ?
						", $image['path'], $image['thumb']);
					}
				}
			}

			// files
			if ($post['content_type'] === 'files' && is_array($content)) {
				foreach ($content as $file) {
					if (isset($file['path']) && file_exists($file['path'])) {
						@unlink($file['path']);
					}
					// Optionally update DB file table if present
				}
			}

			// mixed: { images: [...], files: [...] }
			if ($post['content_type'] === 'mixed' && is_array($content)) {
				if (isset($content['images']) && is_array($content['images'])) {
					foreach ($content['images'] as $image) {
						if (isset($image['path']) && file_exists($image['path'])) {
							@unlink($image['path']);
						}
						if (isset($image['thumb']) && file_exists($image['thumb'])) {
							@unlink($image['thumb']);
						}
						if (Config::get_safe('AUTO_CLEANUP_IMAGES', false)) {
							DB::get_instance()->query("
								UPDATE `images`
								SET `status` = 5
								WHERE `path` = ? OR `thumb` = ?
							", $image['path'], $image['thumb']);
						}
					}
				}
				if (isset($content['files']) && is_array($content['files'])) {
					foreach ($content['files'] as $file) {
						if (isset($file['path']) && file_exists($file['path'])) {
							@unlink($file['path']);
						}
					}
				}
			}
		} catch (Exception $e) {
			// Log error but continue with post deletion
			error_log("Failed to delete media for post: " . $e->getMessage());
		}
	}

	/**
	 * Upload generic file attachment
	 * Returns JSON metadata: valid, path (server), url (relative), name, size, mime
	 */
	public static function upload_file(){
		self::login_protected();

		if(!isset($_FILES['file'])){
			return ["error" => true, "msg" => __("No file uploaded.")];
		}

		$file = $_FILES['file'];
		if($file['error'] !== UPLOAD_ERR_OK){
			return ["error" => true, "msg" => __("Upload error code: ") . $file['error']];
		}

		// Max size default: 5 GB -> 5 * 1024 * 1024 * 1024
		$maxSize = Config::get_safe('MAX_FILE_UPLOAD', 5 * 1024 * 1024 * 1024);
		if($file['size'] > $maxSize){
			return ["error" => true, "msg" => __("File too large. Max ") . ($maxSize/1024/1024/1024) . "GB"];
		}

		// Mime detection
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		// Optional whitelist (if set in config)
		$allowed = Config::get_safe('ALLOWED_FILE_TYPES', false);
		if(is_array($allowed) && count($allowed) > 0 && !in_array($mime, $allowed)){
			return ["error" => true, "msg" => __("File type not allowed: ") . $mime];
		}

		$uploadDir = PROJECT_PATH . 'uploads/files/';
		if(!is_dir($uploadDir)){
			@mkdir($uploadDir, 0755, true);
		}

		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
		$base = bin2hex(random_bytes(8));
		$destName = $base . ($ext ? '.' . $ext : '');
		$destPath = $uploadDir . $destName;

		if(!move_uploaded_file($file['tmp_name'], $destPath)){
			return ["error" => true, "msg" => __("Failed to move uploaded file.")];
		}

		$relativeUrl = 'uploads/files/' . $destName;

		return [
			"valid" => true,
			"path" => $destPath,
			"url" => $relativeUrl,
			"name" => $file['name'],
			"size" => intval($file['size']),
			"mime" => $mime
		];
	}

	public static function delete($r){
		self::login_protected();

		$soft_delete = Config::get_safe('SOFT_DELETE', true);
		$hard_delete_files = Config::get_safe('HARD_DELETE_FILES', true);
		
		// Get post content before deletion
		$post = DB::get_instance()->query("
			SELECT `content_type`, `content`
			FROM `posts`
			WHERE `id` = ?
		", $r["id"])->first();

		if (!$post) {
			throw new Exception("Post not found.");
		}

		// SOFT DELETE: Just set status to 5 (trash)
		if ($soft_delete) {
			DB::get_instance()->query("
				UPDATE `posts`
				SET `status` = 5
				WHERE `id` = ?
			", $r["id"]);
			
			return [
				"soft_deleted" => true, 
				"can_restore" => true,
				"can_permanent_delete" => $hard_delete_files
			];
		}

		// HARD DELETE: Permanently remove from database
		$should_delete_files = $hard_delete_files || !$soft_delete;
		
		if ($should_delete_files && in_array($post['content_type'], ['image', 'images', 'files', 'mixed'])) {
			self::delete_post_images($post);
		}

		// Delete post from database (hard delete)
		DB::get_instance()->query("
			DELETE FROM `posts`
			WHERE `id` = ?
		", $r["id"]);

		return [
			"hard_deleted" => true, 
			"files_deleted" => $should_delete_files,
			"can_restore" => false
		];
	}

	public static function permanent_delete($r){
		self::login_protected();

		$hard_delete_files = Config::get_safe('HARD_DELETE_FILES', true);
		
		$post = DB::get_instance()->query("
			SELECT `content_type`, `content`
			FROM `posts`
			WHERE `id` = ?
			AND `status` = 5
		", $r["id"])->first();

		if (!$post) {
			throw new Exception("Post not found in trash.");
		}

		if ($hard_delete_files && in_array($post['content_type'], ['image', 'images', 'files', 'mixed'])) {
			self::delete_post_images($post);
		}

		DB::get_instance()->query("
			DELETE FROM `posts`
			WHERE `id` = ?
			AND `status` = 5
		", $r["id"]);

		return ["permanently_deleted" => true, "files_deleted" => $hard_delete_files];
	}

	public static function restore($r){
		self::login_protected();

		DB::get_instance()->query("
			UPDATE `posts`
			SET `status` = 1
			WHERE `id` = ?
			AND `status` = 5
		", $r["id"]);
		
		return ["restored" => true];
	}

	public static function list_trash($r){
		self::login_protected();

		if (DB::connection() === 'sqlite') {
			$datetime = "strftime('%d %m %Y %H:%M', `posts`.`datetime`)";
		} else if (DB::connection() === 'postgres') {
			$datetime = "to_char(posts.datetime,'DD Mon YYYY HH24:MI')";
		} else {
			$datetime = "DATE_FORMAT(`posts`.`datetime`,'%d %b %Y %H:%i')";
		}

		return DB::get_instance()->query("
			SELECT
				`id`, `text`, `feeling`, `persons`, `location`, `privacy`, `content_type`, `content`,
				$datetime AS `datetime`
			FROM `posts`
			WHERE `status` = 5
			ORDER BY `posts`.`datetime` DESC
			LIMIT ? OFFSET ?
		", $r["limit"] ?? 50, $r["offset"] ?? 0)->all();
	}

	public static function toggle_sticky($r){
		self::login_protected();

		$current = DB::get_instance()->query("
			SELECT `is_sticky`
			FROM `posts`
			WHERE `id` = ?
		", $r["id"])->first();

		$new_status = $current['is_sticky'] ? 0 : 1;

		DB::get_instance()->query("
			UPDATE `posts`
			SET `is_sticky` = ?
			WHERE `id` = ?
		", $new_status, $r["id"]);

		return ["is_sticky" => $new_status];
	}

	public static function edit_data($r){
		self::login_protected();

		return DB::get_instance()->query("
			SELECT `plain_text`, `feeling`, `persons`, `location`, `privacy`, `content_type`, `content`
			FROM `posts`
			WHERE `id` = ?
			AND `status` <> 5
		", $r["id"])->first();
	}

	public static function get_date($r){
		self::login_protected();

		if (DB::connection() === 'sqlite') {
			$datetime = "strftime('%Y %m %d %H %M', `posts`.`datetime`)";
		} else if (DB::connection() === 'postgres') {
			$datetime = "to_char(datetime,'YYYY MM DD HH24 MI')";
		} else {
			$datetime = "DATE_FORMAT(`datetime`,'%Y %c %e %k %i')";
		}

		$date = DB::get_instance()->query("
			SELECT $datetime AS `date_format`
			FROM `posts`
			WHERE `id` = ?
			AND `status` <> 5
		", $r["id"])->first("date_format");
		$date = array_map("intval", explode(" ", $date));
		$date[4]  = floor($date[4]/10)*10;
		return $date;
	}

	public static function set_date($r){
		self::login_protected();

		$d = $r["date"];
		if (DB::connection() === 'sqlite') {
			$datetime = vsprintf("%04d-%02d-%02d %02d:%02d", $d);
		} else {
			$datetime = vsprintf("%04d/%02d/%02d %02d:%02d", $d);
		}

		DB::get_instance()->query("
			UPDATE `posts`
			SET `datetime` = ?
			WHERE `id` = ?
			AND `status` <> 5
		", $datetime, $r["id"]);
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
		$curl_request_url = $l;

		// Get content
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Proxycat/1.1)");
		curl_setopt($ch, CURLOPT_REFERER, '');
		curl_setopt($ch, CURLOPT_TIMEOUT, 7); // 7sec

		// Proxy settings
		if($proxy = Config::get_safe("proxy", false)){
			$proxytype = Config::get_safe("proxytype", false);
			$proxyauth = Config::get_safe("proxyauth", false);
			if($proxytype === 'URL_PREFIX'){
				$curl_request_url = $proxy.$curl_request_url;

				if($proxyauth){
					curl_setopt($ch, CURLOPT_USERPWD, $proxyauth);
				}
			} else {
				curl_setopt($ch, CURLOPT_PROXY, $proxy);

				if($proxyauth){
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
				}

				switch ($proxytype) {
					case 'CURLPROXY_SOCKS4':
						$proxytype = CURLPROXY_SOCKS4;
						break;
					case 'CURLPROXY_SOCKS5':
						$proxytype = CURLPROXY_SOCKS5;
						break;
					case 'CURLPROXY_HTTP':
					default:
						$proxytype = CURLPROXY_HTTP;
						break;
				}

				curl_setopt($ch, CURLOPT_PROXYTYPE, $proxytype);
			}
		}

		curl_setopt($ch, CURLOPT_URL, $curl_request_url);
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
				$content["is_video"] = (preg_match("/video/", $c));
			}

			if($n == 'twitter:image:src' || $p == 'og:image'){
				// Absolute url
				if(preg_match("/^(https?:)?\/\//", $c)) {
					$content["thumb"] = $c;
				}

				// Relative url from root
				elseif(preg_match("/^\//", $c)) {
					preg_match("/^((?:https?:)?\/\/([^\/]+))(\/|$)/", $l, $m);
					$content["thumb"] = $m[1].'/'.$c;
				}

				// Relative url from current directory
				else {
					preg_match("/^((?:https?:)?\/\/[^\/]+.*?)(\/[^\/]*)?$/", $l, $m);
					$content["thumb"] = $m[1].'/'.$c;
				}
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
		$from = [];
		if(preg_match("/^[0-9]{4}-[0-9]{2}$/", @$r["filter"]["from"])){
			$from = $r["filter"]["from"]."-01 00:00";
		}

		if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", @$r["filter"]["from"])){
			$from = $r["filter"]["from"]." 00:00";
		}

		$to = [];
		if(preg_match("/^[0-9]{4}-[0-9]{2}$/", @$r["filter"]["to"])){
			$to = $r["filter"]["to"]."-01 00:00";
		}

		if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", @$r["filter"]["to"])){
			$to = $r["filter"]["to"]." 00:00";
		}

		$id = [];
		if(@$r["filter"]["id"]){
			$id = intval($r["filter"]["id"]);
		}

		$tag = [];
		if(preg_match("/^[A-Za-z0-9-_]+$/", @$r["filter"]["tag"])){
			$tag = '#'.$r["filter"]["tag"];
		}

		$loc = [];
		if(@$r["filter"]["loc"]){
			$loc = $r["filter"]["loc"];
		}

		$person = [];
		if(@$r["filter"]["person"]){
			$person = $r["filter"]["person"];
		}

		if (DB::connection() === 'sqlite') {
			$datetime = "strftime('%d %m %Y %H:%M', `posts`.`datetime`)";
		} else if (DB::connection() === 'postgres') {
			$datetime = "to_char(posts.datetime,'DD Mon YYYY HH24:MI')";
		} else {
			$datetime = "DATE_FORMAT(`posts`.`datetime`,'%d %b %Y %H:%i')";
		}

		$like_match = "LIKE ".DB::concat("'%'", "?", "'%'");

		return DB::get_instance()->query("
			SELECT
				`id`, `text`, `feeling`, `persons`, `location`, `privacy`, `content_type`, `content`,
				$datetime AS `datetime`, (`status` <> 1) AS `is_hidden`, `is_sticky`
			FROM `posts`
			WHERE ".
				(!User::is_logged_in() ? (User::is_visitor() ? "`privacy` IN ('public', 'friends') AND " : "`privacy` = 'public' AND ") : "").
				($from ? "`posts`.`datetime` > ? AND " : "").
				($to ? "`posts`.`datetime` < ? AND " : "").
				($id ? "`id` = ? AND " : "").
				($tag ? "`plain_text` $like_match AND " : "").
				($loc ? "`location` $like_match AND " : "").
				($person ? "`persons` $like_match AND " : "").
				"`status` <> 5
			ORDER BY `is_sticky` DESC, `posts`.`datetime` ".(@$r["sort"] == 'reverse' ? "ASC" : "DESC")."
			LIMIT ? OFFSET ?
			", $from, $to, $id, $tag, $loc, $person, $r["limit"], $r["offset"]
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