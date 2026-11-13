<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

// Export posts as a static page, that is packed in a zip archive
// together with uploaded images and videos, so that it can be archived
class Export extends Post
{
	// Uploaded images and videos are already compressed
	const STORED_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'm4v', 'mov', 'webm', 'ogv'];

	private static function escape($str){
		return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
	}

	// Only existing files of the blog with a relative path (not URLs) can be added to the archive
	private static function is_local_file($path){
		$path = (string)$path;
		if($path === '' || preg_match('/^[a-z][a-z0-9+.-]*:|^\/|\\\\|[?#]/i', $path)){
			return false;
		}

		if(in_array('..', explode('/', $path), true)){
			return false;
		}

		return is_file(PROJECT_PATH.$path);
	}

	// Files are added under the same path, so that relative URLs work in the archive as well
	private static function add_file($zip, &$added, $path){
		if(isset($added[$path]) || !self::is_local_file($path)){
			return;
		}

		if(!$zip->addFile(PROJECT_PATH.$path, $path)){
			throw new Exception("File could not be added to the archive.");
		}

		if(in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::STORED_TYPES, true)){
			$zip->setCompressionName($path, ZipArchive::CM_STORE);
		}

		$added[$path] = true;
	}

	private static function add_directory($zip, &$added, $directory){
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(PROJECT_PATH.$directory, FilesystemIterator::SKIP_DOTS)
		);

		foreach($files as $file){
			if($file->isFile()){
				$path = $directory.'/'.substr($file->getPathname(), strlen(PROJECT_PATH.$directory) + 1);
				self::add_file($zip, $added, str_replace(DIRECTORY_SEPARATOR, '/', $path));
			}
		}
	}

	// Uploaded images (including thumbnails) and videos used by the post
	private static function uploads($content){
		$files = [];
		foreach(self::content_images($content) as $path){
			// Only files uploaded to the blog are exported
			$image = DB::get_instance()->query("
				SELECT `path`, `thumb`
				FROM `images`
				WHERE `path` = ?
				LIMIT 1
			", $path)->first();

			if($image){
				$files[] = $image["path"];
				if($image["thumb"]){
					$files[] = $image["thumb"];
				}
			}
		}

		return $files;
	}

	// HTML of post content, see `cnt_funcs` in app.js
	private static function content_html($type, $content, &$lightboxes){
		$c = json_decode((string)$content, true);
		if(!is_array($c)){
			return '';
		}

		switch($type){
			case 'link':
				$thumb = '';
				if(!empty($c["thumb"])){
					$thumb =
						'<div class="thumb">'.
							'<img class="thumb_imglink" src="'.self::escape($c["thumb"]).'">'.
							(!empty($c["is_video"]) ? '<div class="play"></div>' : '').
						'</div>';
				}

				return
					'<a class="b_link" target="_blank" href="'.self::escape($c["link"] ?? '').'">'.
						$thumb.
						'<div class="info'.($thumb ? ' has_thumb' : '').'">'.
							'<div class="title">'.self::escape($c["title"] ?? '').'</div>'.
							'<div class="desc">'.self::escape($c["desc"] ?? '').'</div>'.
							'<div class="host">'.self::escape($c["host"] ?? '').'</div>'.
						'</div>'.
					'</a>';

			case 'img_link':
				return
					'<a class="b_imglink" href="'.self::escape($c["src"] ?? '').'">'.
						'<img src="'.self::escape($c["src"] ?? '').'">'.
						'<div class="ftr">'.
							'<div class="host">'.self::escape($c["host"] ?? '').'</div>'.
							'<i class="exit"></i>'.
							'<div class="desc"></div>'.
						'</div>'.
					'</a>';

			case 'image':
				return
					'<a class="b_img" href="'.self::escape($c["path"] ?? '').'" data-lightbox="image-'.$lightboxes++.'">'.
						'<img src="'.self::escape($c["thumb"] ?? '').'">'.
					'</a>';

			case 'images':
				$c = array_values(array_filter($c, 'is_array'));
				$group = 'images-'.$lightboxes++;
				$html = '<div class="b_imgs count-'.min(count($c), 4).'">';
				foreach($c as $i => $image){
					$html .=
						'<a class="b_img" href="'.self::escape($image["path"] ?? '').'" data-lightbox="'.$group.'">'.
							'<img src="'.self::escape($image["thumb"] ?? '').'">'.
							// Only first four images are visible, last of them shows how many are left
							($i == 3 && count($c) > 4 ? '<span class="more">+'.(count($c) - 4).'</span>' : '').
						'</a>';
				}

				return $html.'</div>';

			case 'video':
				return
					'<div class="b_video">'.
						'<video controls preload="metadata" playsinline src="'.self::escape($c["path"] ?? '').'"></video>'.
					'</div>';
		}

		return '';
	}

	// HTML of a single post, see `post_row` in index.php
	private static function post_html($post, &$lightboxes){
		$sharer = '<span class="b_name">'.self::escape(Config::get("name")).'</span>';
		if($post["feeling"] || $post["persons"] || $post["location"]){
			$sharer .= '<span class="b_options"> - </span><span class="b_feeling">'.self::escape($post["feeling"]).'</span>';
		}

		if($post["persons"]){
			$sharer .= '<span class="b_with"> '.__("with").' </span><span class="b_persons">'.self::escape($post["persons"]).'</span>';
		}

		if($post["location"]){
			$sharer .= '<span class="b_here"> '.__("here:").' </span><span class="b_location">'.self::escape($post["location"]).'</span>';
		}

		$datetime = '';
		$timestamp = strtotime((string)$post["datetime"]);
		if($timestamp !== false){
			$datetime = self::format_datetime($timestamp);
		}

		$content = self::content_html($post["content_type"], $post["content"], $lightboxes);

		return
			'<div class="b_post" id="post-'.intval($post["id"]).'">'.PHP_EOL.
				'<div class="b_header">'.
					'<img src="'.self::escape(Config::get("pic_small")).'" width="40" height="40" class="b_profile">'.
					'<div class="b_desc">'.
						'<div class="b_sharer">'.$sharer.'</div>'.
						'<i class="privacy_icon '.self::escape($post["privacy"]).'" title="Shared with: '.self::escape($post["privacy"]).'"></i> '.
						'<a class="b_date" href="#post-'.intval($post["id"]).'">'.self::escape($datetime).'</a>'.
					'</div>'.
				'</div>'.PHP_EOL.
				// Text is stored as HTML, it is shown the same way on the timeline
				'<div class="b_text">'.$post["text"].'</div>'.PHP_EOL.
				($content ? '<div class="b_content">'.$content.'</div>'.PHP_EOL : '').
			'</div>'.PHP_EOL;
	}

	// Static page with posts, see index.php
	private static function page_html($posts, $styles, $scripts){
		$lightboxes = 0;
		$posts_html = '';
		foreach($posts as $post){
			$posts_html .= self::post_html($post, $lightboxes);
		}

		$styles_html = '';
		foreach($styles as $style){
			$styles_html .= '<link href="'.self::escape($style).'" rel="stylesheet" type="text/css" />'.PHP_EOL;
		}

		$scripts_html = '';
		foreach($scripts as $script){
			$scripts_html .= '<script src="'.self::escape($script).'"></script>'.PHP_EOL;
		}

		// Code is highlighted the same way as on the timeline
		if(Config::get_safe("highlight", false)){
			$scripts_html .= '<script>$(".b_post code").each(function(i, block){ hljs.highlightBlock(block); });</script>'.PHP_EOL;
		}

		$cover = Config::get_safe("cover", false)
			? '<img src="'.self::escape(Config::get("cover")).'">'
			: '<div style="padding-bottom: 37%;"></div>';

		$footer = Config::get_safe("footer", false)
			? self::escape(Config::get_safe("footer"))
			: '<a href="https://github.com/m1k1o/blog" class="link" title="m1k1o/blog github repository" target="_blank">m1k1o/blog</a>';

		return
			'<!DOCTYPE html>'.PHP_EOL.
			'<html>'.PHP_EOL.
			'<head>'.PHP_EOL.
				'<meta charset="utf-8">'.PHP_EOL.
				'<title>'.self::escape(Config::get("title")).'</title>'.PHP_EOL.
				'<meta name="robots" content="noindex, nofollow">'.PHP_EOL.
				'<meta content="width=device-width, initial-scale=1.0" name="viewport" />'.PHP_EOL.
				$styles_html.
			'</head>'.PHP_EOL.
			'<body>'.PHP_EOL.
				'<div class="bluebar"><h1>'.self::escape(Config::get("title")).'</h1></div>'.PHP_EOL.
				'<div class="headbar">'.
					'<div class="cover">'.
						'<div class="overlay"></div>'.
						$cover.
						'<div class="profile"><img src="'.self::escape(Config::get("pic_big")).'"></div>'.
						'<div class="name">'.self::escape(Config::get("name")).'</div>'.
					'</div>'.
					'<div id="headline"></div>'.
				'</div>'.PHP_EOL.
				'<div id="b_feed">'.PHP_EOL.
					'<div id="posts">'.PHP_EOL.$posts_html.'</div>'.PHP_EOL.
				'</div>'.PHP_EOL.
				'<div id="eof_feed">'.
					'<img src="static/images/zpEYXu5Wdu6.png">'.
					'<p>'.self::escape(Config::get_safe("version", "")).'<br>'.$footer.'</p>'.
				'</div>'.PHP_EOL.
				$scripts_html.
			'</body>'.PHP_EOL.
			'</html>'.PHP_EOL;
	}

	// Create archive with posts matching the filter and send it to the browser
	public static function download($r){
		self::login_protected();

		if(!class_exists('ZipArchive')){
			throw new Exception("Export requires the PHP zip extension.");
		}

		// Export of many images and videos can take a while
		if(function_exists('set_time_limit')){
			set_time_limit(0);
		}

		// Other requests of this session should not wait for the export
		session_write_close();

		list($where, $values) = self::filter_query($r["filter"] ?? []);

		$posts = DB::get_instance()->query("
			SELECT `id`, `text`, `feeling`, `persons`, `location`, `privacy`, `content_type`, `content`, `datetime`
			FROM `posts`
			WHERE $where
			ORDER BY `posts`.`datetime` ".(@$r["sort"] == 'reverse' ? "ASC" : "DESC")."
			", $values
		)->all();

		$theme = Config::get_safe("theme", "theme01");
		if(!preg_match('/^[A-Za-z0-9_-]+$/', $theme)){
			$theme = "theme01";
		}

		$styles = ["static/styles/main.css", "static/styles/".$theme.".css", "static/styles/lightbox.css"];
		$scripts = ["static/scripts/jquery-3.5.0.min.js", "static/scripts/lightbox.js"];
		if(Config::get_safe("highlight", false)){
			$styles[] = "static/styles/highlight-monokai-sublime.css";
			$scripts[] = "static/scripts/highlight-10.1.2.min.js";
		}

		// Custom styles
		$custom = Config::get_safe("styles", []);
		$styles = array_unique(array_merge($styles, is_array($custom) ? $custom : [$custom]));

		$file = tempnam(sys_get_temp_dir(), 'export');
		if($file === false){
			throw new Exception("Temporary file could not be created.");
		}

		// Archive is removed even if the download is aborted
		register_shutdown_function(function() use ($file){
			if(is_file($file)){
				unlink($file);
			}
		});

		$zip = new ZipArchive();
		if($zip->open($file, ZipArchive::OVERWRITE) !== true){
			throw new Exception("Archive could not be created.");
		}

		$zip->addFromString("index.html", self::page_html($posts, $styles, $scripts));

		$added = [];
		foreach(array_merge($styles, $scripts) as $path){
			self::add_file($zip, $added, $path);
		}

		// Images used by styles and the default profile pictures
		self::add_directory($zip, $added, "static/images");

		foreach(["pic_small", "pic_big", "cover"] as $key){
			self::add_file($zip, $added, Config::get_safe($key, ""));
		}

		foreach($posts as $post){
			foreach(self::uploads($post["content"]) as $path){
				self::add_file($zip, $added, $path);
			}
		}

		if(!$zip->close()){
			throw new Exception("Archive could not be created.");
		}

		// Large archives are not kept in memory
		while(ob_get_level() > 0){
			ob_end_clean();
		}

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="blog-'.date('Y-m-d').'.zip"');
		header('Content-Length: '.filesize($file));
		readfile($file);
	}
}