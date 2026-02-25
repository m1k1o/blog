<?php
include 'common.php';

// Create token
if(empty($_SESSION['token'])){
	if(function_exists('random_bytes')){
		$_SESSION['token'] = bin2hex(random_bytes(5));
	} else {
		$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(5));
	}
}

function escape($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

Log::put("visitors");

$hours = '';
for($h=0;$h<24;$h++){
	$hours .= sprintf('<option value="%d">%02d</option>', $h, $h);
}

$minutes = '';
for($m=0;$m<60;$m+=10){
	$minutes .= sprintf('<option value="%d">%02d</option>', $m, $m);
}

$header_path = PROJECT_PATH.Config::get_safe("header", 'data/header.html');
if(file_exists($header_path)){
	$header = file_get_contents($header_path);
} else {
	$header = '';
}

// Translate styles into html
$styles = Config::get_safe("styles", []);
$styles_html = '';
if(!empty($styles)){
	if(!is_array($styles)){
		$styles = [$styles];
	}

	$styles = array_unique($styles);
	$styles = array_map('escape', $styles);
	$styles_html = '<link href="'.implode('" rel="stylesheet" type="text/css"/>'.PHP_EOL.'<link href="', $styles).'" rel="stylesheet" type="text/css"/>'.PHP_EOL;
}

// Translate script urls into html
$scripts = Config::get_safe("scripts", []);
$scripts_html = '';
if(!empty($scripts)){
	if(!is_array($scripts)){
		$scripts = [$scripts];
	}

	$scripts = array_unique($scripts);
	$scripts = array_map('escape', $scripts);
	$scripts_html = '<script src="'.implode('" type="text/javascript"></script>'.PHP_EOL.'<script src="', $scripts).'" type="text/javascript"></script>'.PHP_EOL;
}

// Use version suffix in URLs to prevent cache
$versionSuffix = '';
if (Config::get_safe("version", false)) {
	$versionSuffix = '?v='.rawurlencode(Config::get("version"));
}

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo escape(Config::get("title")); ?></title>

	<meta name="robots" content="noindex, nofollow">

	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<link href="static/styles/main.css<?php echo $versionSuffix?>" rel="stylesheet" type="text/css" />
	<link href="static/styles/<?php echo rawurlencode(Config::get_safe("theme", "theme01")); ?>.css<?php echo $versionSuffix?>" rel="stylesheet" type="text/css" />
	<!-- Custom overrides for the clickable paperclip & file preview -->
	<link href="static/styles/custom1.css<?php echo $versionSuffix?>" rel="stylesheet" type="text/css" />

	<link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">

	<link href="static/styles/lightbox.css" rel="stylesheet" type="text/css" />

	<link href="static/styles/sticky_posts.css<?php echo $versionSuffix?>" rel="stylesheet" type="text/css" />

	<?php echo Config::get_safe("highlight", false) ? '<link href="static/styles/highlight-monokai-sublime.css" rel="stylesheet" type="text/css" />'.PHP_EOL : ''; ?>
	<style>
	#emojiPicker {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-around;
		gap: 4px;
		padding: 6px;
		border-radius: 8px;
		background: #f8f8f8;
		border: 1px solid #ddd;
		font-size: 22px;
		margin-top: 8px;
	}
	#emojiPicker .emoji {
		cursor: pointer;
		transition: transform 0.1s;
		user-select: none;
		padding: 4px;
	}
	#emojiPicker .emoji:hover {
		transform: scale(1.3);
		background: #e8e8e8;
		border-radius: 4px;
	}
	#emojiPicker .emoji:active {
		transform: scale(1.1);
	}
	textarea.e_text, textarea#postText {
		height: 300px !important;
		max-height: 300px !important;
		overflow-y: auto !important;
		resize: none !important; /* No resize, use scrolling instead */
	}
	/* small file preview styling (matching image preview look) */
	.file-preview-container { display:none; margin-top:8px; }
	.file-preview-item { display:inline-block; margin-right:8px; padding:6px 8px; border:1px solid #e6e6e6; border-radius:6px; background:#fafafa; }
	.file-preview-item .file-name { display:inline-block; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:middle; }
	.file-preview-item .remove-file-btn { margin-left:6px; border:0; background:transparent; color:#888; cursor:pointer; font-size:14px; }
	</style>
	<?php echo $styles_html; ?>
</head>
<body>
	<div id="dd_mask" class="mask"></div>
	<div id="prepared" style="display:none;"
	     data-show-less-text="<?php echo __("Weniger Anzeigen"); ?>"
	     data-delete-permanent-title="<?php echo __("Endgültig Löschen"); ?>"
	     data-delete-permanent-body="<?php echo __("Dieser Beitrag wird endgültig gelöscht und kann nicht wiederhergestellt werden. Zugehörige Bilder werden ebenfalls gelöscht."); ?>"
	     data-delete-permanent-btn="<?php echo __("Endgültig Löschen"); ?>">
		<!-- Show More Button -->
		<a class="show_more"><?php echo __("Mehr Anzeigen"); ?></a>

		<!-- Login Button -->
		<button type="button" class="button blue login_btn"><?php echo __("Login"); ?></button>

		<!-- Logout Button -->
		<button type="button" class="button gray logout_btn"><?php echo __("Logout"); ?></button>

		<!-- Login Modal -->
		<div class="modal login_modal">
			<div class="modal-dialog" style="max-width: 350px;">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title"><?php echo __("Login"); ?></h4>
					</div>
					<form>
						<div class="modal-body login-form">
							<input name="username" type="text" autocomplete="username" class="nick" placeholder="<?php echo __("Nick"); ?>">
							<input name="password" type="password" autocomplete="current-password" class="pass" placeholder="<?php echo __("Password"); ?>">
						</div>
						<div class="modal-footer">
							<div class="buttons">
								<a class="button gray close"><?php echo __("Cancel"); ?></a>
								<button type="button" class="button blue do_login"><?php echo __("Login"); ?></button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Post Link -->
		<a class="b_link" target="_blank">
			<div class="thumb">
				<img class="thumb_imglink">
				<div class="play"></div>
			</div>
			<div class="info has_thumb">
				<div class="title"></div>
				<div class="desc"></div>
				<div class="host"></div>
			</div>
		</a>

		<!-- Post Image Link -->
		<a class="b_imglink">
			<img>
			<div class="ftr">
				<div class="host"></div>
				<i class="exit"></i>
				<div class="desc"></div>
			</div>
		</a>

		<!-- Post Image -->
		<a class="b_img"><img></a>

		<!-- New Post -->
		<div class="b_post new_post">
			<div class="modal-header">
				<h4 class="modal-title"><?php echo __("Post"); ?></h4>
			</div>
			<div class="edit-form"></div>
		</div>

		<!-- Post Tools -->
		<ul class="b_dropdown post_tools">
			<li class="normal-only"><a class="edit_post">✏️ <?php echo __("Edit Post"); ?></a></li>
			<li class="normal-only"><a class="edit_date">📅 <?php echo __("Change Date"); ?></a></li>
			<li class="normal-only">
				<a class="sticky_post">📌 <?php echo __("Mark as Sticky"); ?></a>
				<a class="unsticky_post">📍 <?php echo __("Remove Sticky"); ?></a>
			</li>
			<li class="normal-only">
				<a class="hide">👁️‍🗨️ <?php echo __("Hide from Timeline"); ?></a>
				<a class="show">👁️ <?php echo __("Show on Timeline"); ?></a>
			</li>
			<li class="normal-only"><a class="delete_post">🗑️ <?php echo __("Delete Post"); ?></a></li>
			<!-- Trash-only options -->
			<li class="trash-only" style="display:none;"><a class="restore_post">♻️ <?php echo __("Restore Post"); ?></a></li>
			<li class="trash-only" style="display:none;"><a class="permanent_delete_post">🗑️ <?php echo __("Delete Permanently"); ?></a></li>
		</ul>

		<!-- Edit Modal -->
		<div class="modal edit_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title"><?php echo __("Edit Post"); ?></h4>
					</div>
					<div class="edit_form">
						<div class="modal-body drop_space">
							<div class="e_drag"><span><?php echo __("Drag photos here"); ?></span></div>
							<div class="e_drop"><span><?php echo __("Drop photos here"); ?></span></div>
							<img src="<?php echo escape(Config::get("pic_small")); ?>" width="40" height="40" class="e_profile">
							
							<!-- Multi-Image Upload Preview Container -->
							<div class="image-preview-container" style="display:none;"></div>
							<div class="multi-upload-info" style="display:none;">
								<span class="image-count"></span>
							</div>

							<!-- Multi-File Upload Preview Container -->
							<div class="file-preview-container" style="display:none;"></div>
							
						<div class="t_area">
							<textarea id="postText" class="e_text" placeholder="<?php echo __("What's on your mind?"); ?>"></textarea>
						</div>

						<!-- Markdown Toolbar -->
						<div style="display:flex; justify-content: center; gap:6px; padding:8px 0; flex-wrap:wrap; border: 1px solid #b8daed; background: #fff; margin-bottom: 8px; border-radius: 15px;">
							<span style="font-size: 11px; font-weight: bold; color: #0066cc; align-self: center; margin-right: 8px;">MARKDOWN:</span>
							
							<!-- Text Formatting -->
							<button type="button" class="markdown-btn" data-md="bold" title="<?php echo __("Bold"); ?>" style="font-weight:bold; padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">B</button>
							<button type="button" class="markdown-btn" data-md="italic" title="<?php echo __("Italic"); ?>" style="font-style:italic; padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">I</button>
							<button type="button" class="markdown-btn" data-md="strike" title="<?php echo __("Strikethrough"); ?>" style="text-decoration:line-through; padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">S</button>
							
							<span style="border-right: 1px solid #b8daed; margin: 0 4px;"></span>
							
							<!-- Headings -->
							<button type="button" class="markdown-btn" data-md="h1" title="<?php echo __("Heading 1"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:13px; font-weight:bold;">H1</button>
							<button type="button" class="markdown-btn" data-md="h2" title="<?php echo __("Heading 2"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:12px; font-weight:bold;">H2</button>
							<button type="button" class="markdown-btn" data-md="h3" title="<?php echo __("Heading 3"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px; font-weight:bold;">H3</button>
							
							<span style="border-right: 1px solid #b8daed; margin: 0 4px;"></span>
							
							<!-- Links & Images -->
							<button type="button" class="markdown-btn" data-md="link" title="<?php echo __("Link"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px;">🔗</button>
							<button type="button" class="markdown-btn" data-md="image" title="<?php echo __("Image"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px;">🖼️</button>
							
							<span style="border-right: 1px solid #b8daed; margin: 0 4px;"></span>
							
							<!-- Code -->
							<button type="button" class="markdown-btn" data-md="code" title="<?php echo __("Inline Code"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-family:monospace; font-size:11px;">`code`</button>
							<button type="button" class="markdown-btn" data-md="codeblock" title="<?php echo __("Code Block"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-family:monospace; font-size:10px;">```</button>
							
							<span style="border-right: 1px solid #b8daed; margin: 0 4px;"></span>
							
							<!-- Lists & Quotes -->
							<button type="button" class="markdown-btn" data-md="ul" title="<?php echo __("List"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">• <?php echo __("List"); ?></button>
							<button type="button" class="markdown-btn" data-md="ol" title="<?php echo __("Numbered List"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">1. List</button>
							<button type="button" class="markdown-btn" data-md="quote" title="<?php echo __("Quote"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">💬</button>
							<button type="button" class="markdown-btn" data-md="hr" title="<?php echo __("Horizontal Line"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px; font-size:11px;">---</button>
							<button type="button" class="markdown-btn" data-md="table" title="<?php echo __("Table"); ?>" style="padding:6px 10px; border:1px solid #b8daed; background:#fff; cursor:pointer; border-radius:10px;">📊</button>
						</div>
						<!-- HTML Toolbar -->
						<div style="display:flex; justify-content: center; gap:6px; padding:8px 0; flex-wrap:wrap; border: 1px solid #d4edda; background: #fff; margin-bottom: 8px; border-radius: 15px;">
							<span style="font-size: 11px; font-weight: bold; color: #28a745; align-self: center; margin-right: 8px;">HTML:</span>
							
							<!-- Alignment -->
							<button type="button" class="html-btn" data-html="center" title="<?php echo __("Center"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">⬆️ Center</button>
							<button type="button" class="html-btn" data-html="right" title="<?php echo __("Right Align"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">➡️ Right</button>
							<button type="button" class="html-btn" data-html="left" title="<?php echo __("Left Align"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">⬅️ Left</button>
							
							<span style="border-right: 1px solid #28a745; margin: 0 4px;"></span>
							
							<!-- Color & Highlighting -->
							<button type="button" class="html-btn" data-html="color" title="<?php echo __("Color"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">🎨 <?php echo __("Color"); ?></button>
							<button type="button" class="html-btn" data-html="mark" title="<?php echo __("Highlight"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">✨ Mark</button>
							
							<span style="border-right: 1px solid #28a745; margin: 0 4px;"></span>
							
							<!-- Text Size -->
							<button type="button" class="html-btn" data-html="small" title="<?php echo __("Small"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px; font-size:9px;">Small</button>
							<button type="button" class="html-btn" data-html="big" title="<?php echo __("Large"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px; font-size:14px;">Big</button>
							
							<span style="border-right: 1px solid #28a745; margin: 0 4px;"></span>
							
							<!-- Special -->
							<button type="button" class="html-btn" data-html="underline" title="<?php echo __("Underline"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px; text-decoration:underline; font-size:11px;">U</button>
							<button type="button" class="html-btn" data-html="sup" title="<?php echo __("Superscript"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">x<sup>2</sup></button>
							<button type="button" class="html-btn" data-html="sub" title="<?php echo __("Subscript"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">H<sub>2</sub>O</button>
							<button type="button" class="html-btn" data-html="spoiler" title="<?php echo __("Spoiler"); ?>" style="padding:6px 10px; border:1px solid #28a745; background:#fff; cursor:pointer; border-radius:10px;">👁️ Spoiler</button>
						</div>
						<!-- Emoji Picker with 44 modern emojis -->
						<div id="emojiPicker" style="display:flex; flex-wrap:wrap; justify-content: space-around; gap:4px; padding:6px; border-radius:8px; background:#fff; border:1px solid #ddd; font-size:22px;">
							<!-- Faces & Emotions -->
							<span class="emoji" data-emoji="😀">😀</span>
							<span class="emoji" data-emoji="😃">😃</span>
							<span class="emoji" data-emoji="😄">😄</span>
							<span class="emoji" data-emoji="😁">😁</span>
							<span class="emoji" data-emoji="😆">😆</span>
							<span class="emoji" data-emoji="😂">😂</span>
							<span class="emoji" data-emoji="🤣">🤣</span>
							<span class="emoji" data-emoji="😊">😊</span>
							<span class="emoji" data-emoji="😇">😇</span>
							<span class="emoji" data-emoji="😍">😍</span>
							<span class="emoji" data-emoji="🥰">🥰</span>
							<span class="emoji" data-emoji="😘">😘</span>
							<span class="emoji" data-emoji="😗">😗</span>
							<span class="emoji" data-emoji="😎">😎</span>
							<span class="emoji" data-emoji="🤩">🤩</span>
							<span class="emoji" data-emoji="🤗">🤗</span>
							<span class="emoji" data-emoji="🤔">🤔</span>
							<span class="emoji" data-emoji="😐">😐</span>
							<span class="emoji" data-emoji="😑">😑</span>
							<span class="emoji" data-emoji="😶">😶</span>
							<span class="emoji" data-emoji="🙄">🙄</span>
							<span class="emoji" data-emoji="😏">😏</span>
							<span class="emoji" data-emoji="😣">😣</span>
							<span class="emoji" data-emoji="😥">😥</span>
							<span class="emoji" data-emoji="😮">😮</span>
							<span class="emoji" data-emoji="🤐">🤐</span>
							<span class="emoji" data-emoji="😯">😯</span>
							<span class="emoji" data-emoji="😪">😪</span>
							<span class="emoji" data-emoji="😫">😫</span>
							<span class="emoji" data-emoji="🥱">🥱</span>
							<span class="emoji" data-emoji="😴">😴</span>
							<span class="emoji" data-emoji="😌">😌</span>
							<span class="emoji" data-emoji="😛">😛</span>
							<span class="emoji" data-emoji="😜">😜</span>
							<span class="emoji" data-emoji="😝">😝</span>
							<span class="emoji" data-emoji="🤤">🤤</span>
							<span class="emoji" data-emoji="😒">😒</span>
							<span class="emoji" data-emoji="😓">😓</span>
							<span class="emoji" data-emoji="😔">😔</span>
							<span class="emoji" data-emoji="😕">😕</span>
							<span class="emoji" data-emoji="🙃">🙃</span>
							<span class="emoji" data-emoji="🫠">🫠</span>
							<span class="emoji" data-emoji="🤑">🤑</span>
							<span class="emoji" data-emoji="😲">😲</span>
						</div>
						</div>
						<div class="e_loading">
							<span class="e_dots"></span>
							<span class="e_dots"></span>
							<span class="e_dots"></span>
							<div class="e_meter"><span></span></div>
						</div>
						<input type="hidden" class="i_content_type">
						<input type="hidden" class="i_content">
						<div class="modal-body content"></div>
						<table class="options_content">
							<tr class="feeling"><th><?php echo __("Feeling"); ?></th><td><input type="text" class="i_feeling" placeholder="<?php echo __("How are you feeling?"); ?>" autocomplete="off"><button class="clear"></button></td></tr>
							<tr class="persons"><th><?php echo __("With"); ?></th><td><input type="text" class="i_persons" placeholder="<?php echo __("Who are you with?"); ?>" autocomplete="off"><button class="clear"></button></td></tr>
							<tr class="location"><th><?php echo __("At"); ?></th><td><input type="text" class="i_location" placeholder="<?php echo __("Where are you?"); ?>" autocomplete="off"><button class="clear"></button></td></tr>
						</table>
						<div class="modal-footer">
							<ul class="options">
								<!-- image upload (existing) -->
								<li class="kepet"><a><span><input type="file" accept="image/*" multiple class="photo_upload" name="file"></span></a></li>

								<!-- file upload (new) - INPUT INSIDE LABEL so icon itself is clickable in all browsers -->
								<li class="file_attach">
									<label class="file-attach-label" title="<?php echo __("Attach file"); ?>">
										<input id="file_upload_input" type="file" class="file_upload" name="file" multiple aria-label="<?php echo __("Attach file"); ?>" />
										<span class="file-icon" aria-hidden="true">📎</span>
									</label>
								</li>

								<li class="feeling"><a></a></li>
								<li class="persons"><a></a></li>
								<li class="location"><a></a></li>
							</ul>
							<div class="buttons">
								<span class="button gray privacy"><span class="cnt"></span><i class="arrow"></i></span>
								<button type="button" class="button blue save"><?php echo __("Save"); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Edit Date Modal -->
		<div class="modal edit_date_modal">
			<div class="modal-dialog small">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title"><?php echo __("Change Date"); ?></h4>
					</div>
					<div class="modal-body">
						<div class="datepicker">
							<input type="hidden" class="year" value="">
							<input type="hidden" class="month" value="">
							<input type="hidden" class="day" value="">
							<input type="hidden" class="month_names" value="<?php echo
								__("January").",".
								__("February").",".
								__("March").",".
								__("April").",".
								__("May").",".
								__("June").",".
								__("July").",".
								__("August").",".
								__("September").",".
								__("October").",".
								__("November").",".
								__("December");
							?>">
						</div>
						<div style="text-align: center;">
							<?php echo __("Time:"); ?>&nbsp;
							<select class="hour">
								<option value="" disabled="1"><?php echo __("Hour:"); ?></option>
								<?php echo $hours; ?>
							</select>&nbsp;:&nbsp;
							<select class="minute">
								<option value="" disabled="1"><?php echo __("Minute:"); ?></option>
								<?php echo $minutes; ?>
							</select>
						</div>
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close"><?php echo __("Cancel"); ?></a>
							<button type="button" class="button blue save"><?php echo __("Save"); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Delete Modal -->
		<div class="modal delete_modal">
			<div class="modal-dialog small">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title"><?php echo __("Delete Post"); ?></h4>
					</div>
					<div class="modal-body" 
						data-trash-text="<?php echo __("This post will be moved to trash. You can restore it later."); ?>"
						data-delete-text="<?php echo __("This post will be permanently deleted and cannot be recovered. Associated images will also be deleted."); ?>">
						<?php echo __("This post will be deleted"); ?>
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close"><?php echo __("Cancel"); ?></a>
							<button type="button" class="button blue delete"><?php echo __("Delete Post"); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Post Row -->
		<div class="b_post post_row">
			<div class="b_overlay">
				<a class="button"><?php echo __("Show hidden content"); ?></a>
			</div>
			<div class="b_header">
				<img src="<?php echo escape(Config::get("pic_small")); ?>" width="40" height="40" class="b_profile">
				<div class="b_desc">
					<div class="b_sharer">
						<span class="b_name"><?php echo escape(Config::get("name")); ?></span><span class="b_options"> - </span><span class="b_feeling"></span><span class="b_with"> <?php echo __("with"); ?> </span><span class="b_persons"></span><span class="b_here"> <?php echo __("here:"); ?> </span><span class="b_location"></span>
					</div>
					<i class="privacy_icon"></i>
					<a class="b_date"></a>
					<a class="b_tools"></a>
				</div>
			</div>
			<div class="b_text"></div>
			<div class="b_content"></div>
		</div>

		<!-- Privacy Settings -->
		<ul class="b_dropdown privacy_settings">
			<li><a class="set" data-val="public"><i class="public"></i><?php echo __("Public"); ?></a></li>
			<li><a class="set" data-val="friends"><i class="friends"></i><?php echo __("Friends"); ?></a></li>
			<li><a class="set" data-val="private"><i class="private"></i><?php echo __("Only me"); ?></a></li>
		</ul>
	</div>

	<div class="bluebar">
		<h1><?php echo escape(Config::get("title")); ?></h1>
	</div>

	<div class="headbar">
		<div class="cover">
			<?php echo $header; ?>
			<div class="overlay"></div>
			<?php echo (Config::get_safe("cover", false) ? '<img src="'.escape(Config::get("cover")).'">' : (empty($header) ? '<div style="padding-bottom: 37%;"></div>' : '')); ?>
			<div class="profile">
				<img src="<?php echo escape(Config::get("pic_big")); ?>">
			</div>
			<div class="name"><?php echo escape(Config::get("name")); ?></div>
		</div>
		<div id="headline"></div>

		<!-- Trash button below logout (only visible when logged in) -->
		<div id="trash_headline_btn" style="display:none; max-width: 1000px; margin: 0 auto 20px auto; text-align: right; background-color: #fff; padding: 0 10px;">
			<button type="button" class="button gray" id="show_trash_btn" style="padding: 8px 16px; font-size: 14px; display: inline-block;">
				🗑️ <?php echo __("Show Trash"); ?> <span class="trash-count" style="color: #666; font-weight: bold;"></span>
			</button>
		</div>
	</div>

	<!-- Trash/Recycle Bin Toggle (only visible when logged in) -->
	<script>
	var trashEnabled = <?php echo User::is_logged_in() ? 'true' : 'false'; ?>;
	var softDeleteEnabled = <?php echo Config::get_safe('SOFT_DELETE', true) ? 'true' : 'false'; ?>;
	var hardDeleteFilesEnabled = <?php echo Config::get_safe('HARD_DELETE_FILES', true) ? 'true' : 'false'; ?>;
	</script>

    <?php if(User::is_logged_in()): ?>
		<?php endif; ?>
			<div id="b_feed">
				<div class="more_posts">
					<a href="#" class="button"><?php echo __("Show all posts"); ?></a>
				</div>
				<div id="posts"></div>
			</div>

	<!-- Trash View (only visible when logged in) -->
	<?php if(User::is_logged_in()): ?>
	<div id="b_trash" style="display: none; max-width: 1000px; margin: 0 auto;">
		<div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
			<h2 style="margin: 0 0 10px 0;">🗑️ <?php echo __("Trash"); ?></h2>
			<p style="color: #666; margin: 0 0 15px 0;"><?php echo __("Posts in trash can be restored or permanently deleted"); ?></p>
			<button type="button" class="button blue" id="hide_trash_btn" style="padding: 10px 20px;">
				⬅️ <?php echo __("Back to Timeline"); ?>
			</button>
		</div>
		<div id="trash_posts"></div>
		<div id="eof_trash" style="text-align: center; padding: 40px; color: #999;">
			<p><?php echo __("End of trash"); ?></p>
		</div>
	</div>
	<?php endif; ?>

	<div id="eof_feed">
		<img src="static/images/zpEYXu5Wdu6.png">
		<p><?php echo escape(Config::get("version")); ?> &copy; 2016-2025<br>
		<?php echo Config::get_safe("footer", false) ? escape(Config::get_safe("footer")) : '<a href="https://github.com/m1k1o/blog" class="link" title="m1k1o/blog github repository" target="_blank">m1k1o/blog</a>'; ?>
		</p>
	</div>
	<script src="static/scripts/jquery-3.5.0.min.js"></script>
	<script>$["\x61\x6A\x61\x78\x53\x65\x74\x75\x70"]({"\x68\x65\x61\x64\x65\x72\x73":{"\x43\x73\x72\x66-\x54\x6F\x6B\x65\x6E":"<?php echo $_SESSION['token'];?>"}});</script>

	<script src="static/scripts/lightbox.js"></script>
	<script src="static/scripts/datepick.js<?php echo $versionSuffix?>"></script>
	<?php echo Config::get_safe("highlight", false) ? '<script src="static/scripts/highlight-10.1.2.min.js"></script><script>hljs.initHighlightingOnLoad();</script>'.PHP_EOL : ''; ?>
	<script src="static/scripts/app.js<?php echo $versionSuffix?>"></script>
	<?php echo $scripts_html; ?>

<script>
// ============================================
// Markdown & Emoji Editor Functionality
// (unchanged - same as before; the file-upload integration happens in static/scripts/app.js)
// ============================================
(function(){
	'use strict';

	// Helper: Find textarea in current context
	function findTextarea(element) {
		let scope = element.closest('.modal') || element.closest('.edit_form') || element.closest('.b_post') || document;
		return scope.querySelector('textarea#postText') 
			|| scope.querySelector('textarea.e_text') 
			|| scope.querySelector('textarea')
			|| document.querySelector('textarea#postText');
	}

	// Insert text at cursor position
	function insertAtCursor(textarea, textBefore, textAfter) {
		if (!textarea) return;
		
		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const text = textarea.value;
		const selectedText = text.substring(start, end);
		
		const newText = text.substring(0, start) + textBefore + selectedText + textAfter + text.substring(end);
		textarea.value = newText;
		
		// Set cursor position
		const newPos = start + textBefore.length + selectedText.length;
		textarea.selectionStart = textarea.selectionEnd = newPos;
		textarea.focus();
		
		// Trigger events for other scripts
		textarea.dispatchEvent(new Event('input', { bubbles: true }));
		textarea.dispatchEvent(new Event('change', { bubbles: true }));
	}

	// Emoji mapping: Emoji → Text code (44 emojis)
	const emojiToCode = {
		'😀': ':grinning:',
		'😃': ':smiley:',
		'😄': ':smile:',
		'😁': ':grin:',
		'😆': ':laughing:',
		'😂': ':joy:',
		'🤣': ':rofl:',
		'😊': ':blush:',
		'😇': ':innocent:',
		'😍': ':heart_eyes:',
		'🥰': ':smiling_face_with_hearts:',
		'😘': ':kissing_heart:',
		'😗': ':kissing:',
		'😎': ':sunglasses:',
		'🤩': ':star_struck:',
		'🤗': ':hugging:',
		'🤔': ':thinking:',
		'😐': ':neutral_face:',
		'😑': ':expressionless:',
		'😶': ':no_mouth:',
		'🙄': ':eye_roll:',
		'😏': ':smirk:',
		'😣': ':persevere:',
		'😥': ':disappointed_relieved:',
		'😮': ':open_mouth:',
		'🤐': ':zipper_mouth:',
		'😯': ':hushed:',
		'😪': ':sleepy:',
		'😫': ':tired_face:',
		'🥱': ':yawning:',
		'😴': ':sleeping:',
		'😌': ':relieved:',
		'😛': ':stuck_out_tongue:',
		'😜': ':stuck_out_tongue_winking_eye:',
		'😝': ':stuck_out_tongue_closed_eyes:',
		'🤤': ':drooling:',
		'😒': ':unamused:',
		'😓': ':sweat:',
		'😔': ':pensive:',
		'😕': ':confused:',
		'🙃': ':upside_down:',
		'🫠': ':melting:',
		'🤑': ':money_mouth:',
		'😲': ':astonished:'
	};

	// Emoji click handler
	document.addEventListener('click', function(e) {
		const emojiEl = e.target.closest('.emoji');
		if (!emojiEl) return;
		
		e.preventDefault();
		const emojiChar = emojiEl.getAttribute('data-emoji') || emojiEl.textContent;
		const textarea = findTextarea(emojiEl);
		if (!textarea) return;

		// Insert emoji code
		const emojiCode = emojiToCode[emojiChar] || emojiChar;
		insertAtCursor(textarea, emojiCode, '');
	});

	// Prevent double clicks
	document.addEventListener('dblclick', function(e) {
		if (e.target.closest('.emoji') || e.target.closest('.markdown-btn') || e.target.closest('.html-btn')) {
			e.preventDefault();
		}
	});

	// Markdown button handler
	document.addEventListener('click', function(e) {
		const btn = e.target.closest('.markdown-btn');
		if (!btn) return;
		
		e.preventDefault();
		const mdType = btn.getAttribute('data-md');
		const textarea = findTextarea(btn);
		if (!textarea) return;
		
		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const selectedText = textarea.value.substring(start, end);
		
		let before = '', after = '';
		
		switch(mdType) {
			case 'bold':
				before = '**';
				after = '**';
				break;
			case 'italic':
				before = '*';
				after = '*';
				break;
			case 'strike':
				before = '~~';
				after = '~~';
				break;
			case 'h1':
				before = '# ';
				after = '';
				break;
			case 'h2':
				before = '## ';
				after = '';
				break;
			case 'h3':
				before = '### ';
				after = '';
				break;
			case 'link':
				const url = prompt('Enter URL:', 'https://');
				if (url) {
					if (selectedText) {
						before = '[';
						after = '](' + url + ')';
					} else {
						before = '[Link Text](' + url + ')';
						after = '';
					}
				}
				break;
			case 'image':
				const imgUrl = prompt('Enter Image URL:', 'https://');
				if (imgUrl) {
					const alt = prompt('Alt text, optional:', 'Image');
					before = '![' + (alt || 'Image') + '](' + imgUrl + ')';
					after = '';
				}
				break;
			case 'code':
				before = '`';
				after = '`';
				break;
			case 'codeblock':
				const lang = prompt('Language, optional, e.g. javascript:', '');
				before = '\n```' + (lang || '') + '\n';
				after = '\n```\n';
				break;
			case 'ul':
				before = '\n- ';
				after = '\n- Item 2\n- Item 3\n';
				break;
			case 'ol':
				before = '\n1. ';
				after = '\n2. Item 2\n3. Item 3\n';
				break;
			case 'quote':
				before = '\n> ';
				after = '\n';
				break;
			case 'hr':
				before = '\n---\n';
				after = '';
				break;
			case 'table':
				before = '\n| Header 1 | Header 2 |\n|----------|----------|\n| Cell 1   | Cell 2   |\n| Cell 3   | Cell 4   |\n';
				after = '';
				break;
		}
		
		if (before !== '' || after !== '') {
			const newText = textarea.value.substring(0, start) + before + selectedText + after + textarea.value.substring(end);
			textarea.value = newText;
			
			const newPos = start + before.length + selectedText.length;
			textarea.selectionStart = textarea.selectionEnd = newPos;
			textarea.focus();
			
			textarea.dispatchEvent(new Event('input', { bubbles: true }));
			textarea.dispatchEvent(new Event('change', { bubbles: true }));
		}
	});

	// HTML button handler
	document.addEventListener('click', function(e) {
		const btn = e.target.closest('.html-btn');
		if (!btn) return;
		
		e.preventDefault();
		const htmlType = btn.getAttribute('data-html');
		const textarea = findTextarea(btn);
		if (!textarea) return;
		
		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const selectedText = textarea.value.substring(start, end);
		
		let before = '', after = '';
		
		switch(htmlType) {
			case 'center':
				before = '<center>';
				after = '</center>';
				break;
			case 'right':
				before = '<div align="right">';
				after = '</div>';
				break;
			case 'left':
				before = '<div align="left">';
				after = '</div>';
				break;
			case 'color':
				const color = prompt('Enter color, e.g. red or #ff0000:', 'red');
				if (color) {
					before = '<span style="color:' + color + '">';
					after = '</span>';
				}
				break;
			case 'mark':
				before = '<mark>';
				after = '</mark>';
				break;
			case 'small':
				before = '<small>';
				after = '</small>';
				break;
			case 'big':
				before = '<big>';
				after = '</big>';
				break;
			case 'underline':
				before = '<u>';
				after = '</u>';
				break;
			case 'sup':
				before = '<sup>';
				after = '</sup>';
				break;
			case 'sub':
				before = '<sub>';
				after = '</sub>';
				break;
			case 'spoiler':
				const title = prompt('Spoiler title:', 'Click to show');
				if (title !== null) {
					before = '<details><summary>' + (title || 'Click to show') + '</summary>\n';
					after = '\n</details>';
				}
				break;
		}
		
		if (before !== '' || after !== '') {
			const newText = textarea.value.substring(0, start) + before + selectedText + after + textarea.value.substring(end);
			textarea.value = newText;
			
			const newPos = start + before.length + selectedText.length;
			textarea.selectionStart = textarea.selectionEnd = newPos;
			textarea.focus();
			
			textarea.dispatchEvent(new Event('input', { bubbles: true }));
			textarea.dispatchEvent(new Event('change', { bubbles: true }));
		}
	});
	console.log("✅ Markdown, HTML & Emoji Editor initialized");
})();
</script>
</body>
</html>