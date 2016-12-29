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
for($h=0;$h<=60;$h++){
	$hours .= sprintf('<option value="%d">%02d</option>', $h, $h);
}

$minutes = '';
for($m=0;$m<=60;$m+=10){
	$minutes .= sprintf('<option value="%d">%02d</option>', $m, $m);
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
</head>
<body>
	<div id="dd_mask" class="mask"></div>
	<div id="prepared" style="display:none;">
		<!-- Login Button -->
		<button type="button" class="button blue login_btn">Login</button>
		
		<!-- Logout Button -->
		<button type="button" class="button gray logout_btn">Logout</button>
		
		<!-- Login Modal -->
		<div class="modal login_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title">Login</h4>
					</div>
					<div class="modal-body">
						<input type="text" class="nick" placeholder="Nick">&nbsp;
						<input type="password" class="pass" placeholder="Password">
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close">Cancel</a>
							<button type="button" class="button blue do_login">Login</button>
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
				<h4 class="modal-title">Post</h4>
			</div>
			<div class="edit-form"></div>
		</div>
		
		<!-- Post Tools -->
		<ul class="b_dropdown post_tools">
			<li><a class="edit_post">Edit Post</a></li>
			<li><a class="edit_date">Change Date</a></li>
			<li><a class="hide">Hide from Timeline</a></li>
			<li><a class="delete_post">Delete</a></li>
		</ul>
		
		<!-- Edit Modal -->
		<div class="modal edit_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title">Edit Post</h4>
					</div>
					<div class="edit_form">
						<div class="modal-body drop_space">
							<div class="e_drag"><span>Drag photos here</span></div>
							<img src="<?php echo Config::get("pic_small"); ?>" width="40" height="40" class="e_profile">
							<div class="e_text" contenteditable="true" placeholder="What's on your mind?"></div>
						</div>
						<input type="hidden" class="i_content_type">
						<input type="hidden" class="i_content">
						<div class="modal-body content"></div>
						<table class="options_content">
							<tr class="feeling"><th>Feeling</th><td><input type="text" class="i_feeling" placeholder="How are you feeling?"><button class="clear"></button></td></tr>
							<tr class="persons"><th>With</th><td><input type="text" class="i_persons" placeholder="Who are you with?"><button class="clear"></button></td></tr>
							<tr class="location"><th>At</th><td><input type="text" class="i_location" placeholder="Where are you?"><button class="clear"></button></td></tr>
						</table>
						<div class="modal-footer">
							<ul class="options">
								<li class="kepet"><a><span><input type="file" accept="image/*" multiple class="photo_upload" name="file"></span></a></li>
								<li class="feeling"><a></a></li>
								<li class="persons"><a></a></li>
								<li class="location"><a></a></li>
							</ul>
							<div class="buttons">
								<span class="button gray pirvacy"><span class="cnt"></span><i class="arrow"></i></span>
								<button type="button" class="button blue save">Save</button>
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
						<h4 class="modal-title">Change date</h4>
					</div>
					<div class="modal-body">
						<select class="year">
							<option value="" disabled="1">Year:</option>
							<?php echo $years; ?>
						</select>
						<select class="month">
							<option value="" disabled="1">Month:</option>
							<?php echo $months; ?>
						</select>
						<select class="day">
							<option value="" disabled="1">Day:</option>
							<?php echo $days; ?>
						</select>
						<select class="hour">
							<option value="" disabled="1">Hour:</option>
							<?php echo $hours; ?>
						</select>
						<select class="minute">
							<option value="" disabled="1">Minute:</option>
							<?php echo $minutes; ?>
						</select>
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close">Cancel</a>
							<button type="button" class="button blue save">Save</button>
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
						<h4 class="modal-title">Delete Post</h4>
					</div>
					<div class="modal-body">This post will be deleted and you'll no longer be able to find it. You can also edit this post if you just want to change something.</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close">Cancel</a>
							<button type="button" class="button blue delete">Delete Post</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Post Row -->
		<div class="b_post post_row">
			<div class="b_inner">
				<div class="b_header">
					<img src="<?php echo Config::get("pic_small"); ?>" width="40" height="40" class="b_profile">
					<div class="b_desc">
						<div class="b_sharer">
							<span class="b_name"><?php echo Config::get("name"); ?></span><span class="b_options"> - </span><span class="b_feeling"></span><span class="b_with"> with </span><span class="b_persons"></span><span class="b_here"> here: </span><span class="b_location"></span>
						</div>
						<i class="pirvacy_icon"></i>
						<a class="b_date"></a>
						<a class="b_tools"></a>
					</div>
				</div>
				<div class="b_text"></div>
				<div class="b_content"></div>
			</div>
			<div class="b_socialbox">
				<?php /*
				<ul class="b_buttons">
					<li><a class="active"><i class="like"></i>Like</a></li>
					<li><a><i class="comment"></i>Comment</a></li>
				</ul>
				<div class="b_comments">
					<div class="b_comment">
						<img src="static/images/profile.jpg" width="32" height="32" class="b_profile" />
						<div class="b_content">
							<div class="b_input">
								<input type="text" placeholder="Write comment ..." />
							</div>
						</div>
					</div>
					<ul>
						<li class="b_comment">
							<img src="static/images/profile.jpg" width="32" height="32" class="b_profile" />
							<div class="b_content">
								<span class="b_name">Max Mustermann</span> Sample Text
								<a class="b_date">12 Oct 2016 07:59</a>
							</div>
							<a class="b_tools"></a>
						</li>
					</ul>
					<div class="b_panel">
						<a class="btn">Show more comments</a>
						<span class="count">2 from 52</span>
					</div>
				</div>
				*/ ?>
			</div>
		</div>
		
		<!-- Pirvacy Settings -->
		<ul class="b_dropdown pirvacy_settings">
			<li><a class="set" data-val="public"><i class="public"></i>Public</a></li>
			<!--<li><a class="set" data-val="friends"><i class="friends"></i>Friends</a></li>-->
			<li><a class="set" data-val="private"><i class="private"></i>Only me</a></li>
		</ul>
	</div>
	
	<div class="bluebar">
		<h1><?php echo Config::get("title"); ?></h1>
	</div>
	
	<div class="headbar">
		<div class="cover">
			<div class="overlay"></div>
			<img src="<?php echo Config::get("cover"); ?>">
			<div class="profile">
				<img src="<?php echo Config::get("pic_big"); ?>">
			</div>
			<div class="name"><?php echo Config::get("name"); ?></div>
		</div>
		<div id="headline"></div>
	</div>
	
	<div id="b_feed">
		<div class="more_posts">
			<a href="#" class="button">Show all posts</a>
		</div>
		<div id="posts"></div>
	</div>
	
	<div id="eof_feed">
		<img src="static/images/zpEYXu5Wdu6.png">
		<p><?php echo Config::get("version"); ?> &copy; 2016 <br>Miroslav Šedivý</p>
	</div>
	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<!--<script src="static/scripts/jquery.min.js"></script>-->
	<script>$["\x61\x6A\x61\x78\x53\x65\x74\x75\x70"]({"\x68\x65\x61\x64\x65\x72\x73":{"\x43\x73\x72\x66-\x54\x6F\x6B\x65\x6E":"<?php echo $_SESSION['token'];?>"}});</script>
	<script src="static/scripts/app.js?v=<?php echo Config::get("version"); ?>"></script>
</body>
</html>