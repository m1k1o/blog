<?php
include 'common.php';

// Create token
if(empty($_SESSION['token'])){
	if(function_exists('mcrypt_create_iv')){
		$_SESSION['token'] = bin2hex(mcrypt_create_iv(5, MCRYPT_DEV_URANDOM));
	} else {
		$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(5));
	}
}

//$.ajaxSetup({headers:{'Csrf-Token':'token'}});

Log::put("visitors");

$year = intval(date("Y"));
$years = '';
for($y=$year-5;$y<=$year+5;$y++){
	$years .= sprintf('<option>%d</option>', $y);
}

$months = '';
for($m=1;$m<=12;$m++){
	$months .= sprintf('<option value="%d">%02d</option>', $m, $m);
}

$days = '';
for($d=1;$d<=31;$d++){
	$days .= sprintf('<option value="%d">%02d</option>', $d, $d);
}

$hours = '';
for($h=0;$h<=24;$h++){
	$hours .= sprintf('<option value="%d">%02d</option>', $h, $h);
}

$minutes = '';
for($m=0;$m<=60;$m+=10){
	$minutes .= sprintf('<option value="%d">%02d</option>', $m, $m);
}

$header_path = PROJECT_PATH.'data/header.html';
if(file_exists($header_path)){
	$header = file_get_contents($header_path);
} else {
	$header = '';
}

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo Config::get("title"); ?></title>

	<meta name="robots" content="noindex, nofollow">

	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	
	<link href="static/styles/main.css?v=<?php echo Config::get("version"); ?>" rel="stylesheet" type="text/css" />
	<link href="static/styles/design.css?v=<?php echo Config::get("version"); ?>" rel="stylesheet" type="text/css" />
	
	<link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">

	<?php echo Config::get("highlight") ? '<link href="static/styles/highlight.css" rel="stylesheet" type="text/css" />' : ''; ?>
</head>
<body>
	<div id="dd_mask" class="mask"></div>
	<div id="prepared" style="display:none;">
		<!-- Login Button -->
		<a class="show_more"><?php echo __("Show More"); ?></a>

		<!-- Login Button -->
		<button type="button" class="button blue login_btn"><?php echo __("Login"); ?></button>
		
		<!-- Logout Button -->
		<button type="button" class="button gray logout_btn"><?php echo __("Logout"); ?></button>
		
		<!-- Login Modal -->
		<div class="modal login_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title"><?php echo __("Logout"); ?></h4>
					</div>
					<div class="modal-body">
						<input type="text" class="nick" placeholder="<?php echo __("Nick"); ?>">&nbsp;
						<input type="password" class="pass" placeholder="<?php echo __("Password"); ?>">
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close"><?php echo __("Cancel"); ?></a>
							<button type="button" class="button blue do_login"><?php echo __("Logout"); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Post Link -->
		<a class="b_link" target="_blank">
			<div class="thumb">
				<img class="thumb_imglink">
				<div class="play"></div>
			</div>
			<div class="info right">
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
			<li><a class="edit_post"><?php echo __("Edit Post"); ?></a></li>
			<li><a class="edit_date"><?php echo __("Change Date"); ?></a></li>
			<li><a class="hide"><?php echo __("Hide from Timeline"); ?></a></li>
			<li><a class="delete_post"><?php echo __("Delete Post"); ?></a></li>
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
							<img src="<?php echo Config::get("pic_small"); ?>" width="40" height="40" class="e_profile">
							<!--<div class="e_text" contenteditable="true"></div>-->
							<div class="t_area">
								<textarea class="e_text" placeholder="<?php echo __("What's on your mind?"); ?>"></textarea>
							</div>
						</div>
						<input type="hidden" class="i_content_type">
						<input type="hidden" class="i_content">
						<div class="modal-body content"></div>
						<table class="options_content">
							<tr class="feeling"><th><?php echo __("Feeling"); ?></th><td><input type="text" class="i_feeling" placeholder="<?php echo __("How are you feeling?"); ?>"><button class="clear"></button></td></tr>
							<tr class="persons"><th><?php echo __("With"); ?></th><td><input type="text" class="i_persons" placeholder="<?php echo __("Who are you with?"); ?>"><button class="clear"></button></td></tr>
							<tr class="location"><th><?php echo __("At"); ?></th><td><input type="text" class="i_location" placeholder="<?php echo __("Where are you?"); ?>"><button class="clear"></button></td></tr>
						</table>
						<div class="modal-footer">
							<ul class="options">
								<li class="kepet"><a><span><input type="file" accept="image/*" multiple class="photo_upload" name="file"></span></a></li>
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
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title"><?php echo __("Change Date"); ?></h4>
					</div>
					<div class="modal-body">
						<select class="year">
							<option value="" disabled="1"><?php echo __("Year:"); ?></option>
							<?php echo $years; ?>
						</select>
						<select class="month">
							<option value="" disabled="1"><?php echo __("Month:"); ?></option>
							<?php echo $months; ?>
						</select>
						<select class="day">
							<option value="" disabled="1"><?php echo __("Day:"); ?></option>
							<?php echo $days; ?>
						</select>
						<select class="hour">
							<option value="" disabled="1"><?php echo __("Hour:"); ?></option>
							<?php echo $hours; ?>
						</select>
						<select class="minute">
							<option value="" disabled="1"><?php echo __("Minute:"); ?></option>
							<?php echo $minutes; ?>
						</select>
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
					<div class="modal-body"><?php echo __("This post will be deleted and you'll no longer be able to find it. You can also edit this post if you just want to change something."); ?></div>
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
			<div class="b_header">
				<img src="<?php echo Config::get("pic_small"); ?>" width="40" height="40" class="b_profile">
				<div class="b_desc">
					<div class="b_sharer">
						<span class="b_name"><?php echo Config::get("name"); ?></span><span class="b_options"> - </span><span class="b_feeling"></span><span class="b_with"> <?php echo __("with"); ?> </span><span class="b_persons"></span><span class="b_here"> <?php echo __("here:"); ?> </span><span class="b_location"></span>
					</div>
					<i class="privacy_icon"></i>
					<a class="b_date"></a>
					<a class="b_tools"></a>
				</div>
			</div>
			<div class="b_text"></div>
			<div class="b_content"></div>
		</div>
		
		<!-- Pirvacy Settings -->
		<ul class="b_dropdown privacy_settings">
			<li><a class="set" data-val="public"><i class="public"></i><?php echo __("Public"); ?></a></li>
			<!--<li><a class="set" data-val="friends"><i class="friends"></i><?php echo __("Friends"); ?></a></li>-->
			<li><a class="set" data-val="private"><i class="private"></i><?php echo __("Only me"); ?></a></li>
		</ul>
	</div>
	
	<div class="bluebar">
		<h1><?php echo Config::get("title"); ?></h1>
	</div>
	
	<div class="headbar">
		<div class="cover">
			<?php echo $header; ?>
			<div class="overlay"></div>
			<?php echo (Config::get_safe("cover", false) ? '<img src="'.Config::get("cover").'">' : (empty($header) ? '<div style="padding-bottom: 37%;"></div>' : '')); ?>
			<div class="profile">
				<img src="<?php echo Config::get("pic_big"); ?>">
			</div>
			<div class="name"><?php echo Config::get("name"); ?></div>
		</div>
		<div id="headline"></div>
	</div>
	
	<div id="b_feed">
		<div class="more_posts">
			<a href="#" class="button"><?php echo __("Show all posts"); ?></a>
		</div>
		<div id="posts"></div>
	</div>
	
	<div id="eof_feed">
		<img src="static/images/zpEYXu5Wdu6.png">
		<p><?php echo Config::get("version"); ?> &copy; 2016-2017 <br>Miroslav Šedivý</p>
	</div>
	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<!--<script src="static/scripts/jquery.min.js"></script>-->
	<script>$["\x61\x6A\x61\x78\x53\x65\x74\x75\x70"]({"\x68\x65\x61\x64\x65\x72\x73":{"\x43\x73\x72\x66-\x54\x6F\x6B\x65\x6E":"<?php echo $_SESSION['token'];?>"}});</script>
	<script src="static/scripts/autosize.js"></script>
	<?php echo Config::get("highlight") ? '<script src="static/scripts/highlight.js"></script><script>hljs.initHighlightingOnLoad();</script>' : ''; ?>
	<script src="static/scripts/app.js?v=<?php echo Config::get("version"); ?>"></script>
</body>
</html>