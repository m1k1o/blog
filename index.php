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

if(Config::get_safe("logs", false))
	file_put_contents('logs/visitors.log', date('Y-m-d H:i:s')."\t".$_SERVER["REMOTE_ADDR"]."\t".$_SERVER["HTTP_USER_AGENT"].PHP_EOL, FILE_APPEND);
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
</head>
<body>
	<div id="dd_mask" class="mask"></div>
	<div id="prepared" style="display:none;">
		<!-- Login Button -->
		<button type="button" class="button blue login_btn">Anmelden</button>
		
		<!-- Logout Button -->
		<button type="button" class="button gray logout_btn">Abmelden</button>
		
		<!-- Login Modal -->
		<div class="modal login_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title">Anmeldung</h4>
					</div>
					<div class="modal-body">
						<input type="text" class="nick" placeholder="Benutzername">&nbsp;
						<input type="password" class="pass" placeholder="Kennwort">
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close">Abbrechen</a>
							<button type="button" class="button blue do_login">Anmelden</button>
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
				<h4 class="modal-title">Verfasse einen Beitrag</h4>
			</div>
			<div class="edit-form"></div>
		</div>
		
		<!-- Post Tools -->
		<ul class="b_dropdown post_tools">
			<li><a class="edit_post">Beitrag bearbeiten</a></li>
			<li><a class="edit_date">Datum ändern</a></li>
			<li><a class="hide">In der Chronik verbergen</a></li>
			<li><a class="delete_post">Löschen</a></li>
		</ul>
		
		<!-- Edit Modal -->
		<div class="modal edit_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<a class="close"></a>
						<h4 class="modal-title">Beitrag bearbeiten</h4>
					</div>
					<div class="edit_form">
						<div class="modal-body drop_space">
							<div class="e_drag"><span>Fotos hierher ziehen</span></div>
							<img src="<?php echo Config::get("pic_small"); ?>" width="40" height="40" class="e_profile">
							<div class="e_text" contenteditable="true" placeholder="Was machst du gerade?"></div>
						</div>
						<input type="hidden" class="i_content_type">
						<input type="hidden" class="i_content">
						<div class="modal-body content"></div>
						<table class="options_content">
							<tr class="feeling"><th>Fühlen</th><td><input type="text" class="i_feeling" placeholder="Wie fühlst du dich?"><button class="clear"></button></td></tr>
							<tr class="persons"><th>Mit</th><td><input type="text" class="i_persons" placeholder="Wer begleitet dich?"><button class="clear"></button></td></tr>
							<tr class="location"><th>Hier</th><td><input type="text" class="i_location" placeholder="Wo bist du?"><button class="clear"></button></td></tr>
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
								<button type="button" class="button blue save">Speichern</button>
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
						<h4 class="modal-title">Datum ändern</h4>
					</div>
					<div class="modal-body">
						<select class="year"><option value="" disabled="1">Jahr:</option><option value="2016">2016</option><option value="2015">2015</option><option value="2014">2014</option><option value="2013">2013</option><option value="2012">2012</option><option value="2011">2011</option><option value="2010">2010</option><option value="2009">2009</option><option value="2008">2008</option><option value="2007">2007</option><option value="2006">2006</option><option value="2005">2005</option><option value="2004">2004</option><option value="2003">2003</option><option value="2002">2002</option><option value="2001">2001</option><option value="2000">2000</option><option value="1999">1999</option><option value="1998">1998</option><option value="1997">1997</option><option value="1996">1996</option><option value="1995">1995</option><option value="1994">1994</option><option value="1993">1993</option><option value="1992">1992</option><option value="1991">1991</option><option value="1990">1990</option><option value="1989">1989</option><option value="1988">1988</option><option value="1987">1987</option><option value="1986">1986</option><option value="1985">1985</option><option value="1984">1984</option><option value="1983">1983</option><option value="1982">1982</option><option value="1981">1981</option><option value="1980">1980</option><option value="1979">1979</option><option value="1978">1978</option><option value="1977">1977</option><option value="1976">1976</option><option value="1975">1975</option><option value="1974">1974</option><option value="1973">1973</option><option value="1972">1972</option><option value="1971">1971</option><option value="1970">1970</option><option value="1969">1969</option><option value="1968">1968</option><option value="1967">1967</option><option value="1966">1966</option><option value="1965">1965</option><option value="1964">1964</option><option value="1963">1963</option><option value="1962">1962</option><option value="1961">1961</option><option value="1960">1960</option><option value="1959">1959</option><option value="1958">1958</option><option value="1957">1957</option><option value="1956">1956</option><option value="1955">1955</option><option value="1954">1954</option><option value="1953">1953</option><option value="1952">1952</option><option value="1951">1951</option><option value="1950">1950</option><option value="1949">1949</option><option value="1948">1948</option><option value="1947">1947</option><option value="1946">1946</option><option value="1945">1945</option><option value="1944">1944</option><option value="1943">1943</option><option value="1942">1942</option><option value="1941">1941</option><option value="1940">1940</option><option value="1939">1939</option><option value="1938">1938</option><option value="1937">1937</option><option value="1936">1936</option><option value="1935">1935</option><option value="1934">1934</option><option value="1933">1933</option><option value="1932">1932</option><option value="1931">1931</option><option value="1930">1930</option><option value="1929">1929</option><option value="1928">1928</option><option value="1927">1927</option><option value="1926">1926</option><option value="1925">1925</option><option value="1924">1924</option><option value="1923">1923</option><option value="1922">1922</option><option value="1921">1921</option><option value="1920">1920</option><option value="1919">1919</option><option value="1918">1918</option><option value="1917">1917</option><option value="1916">1916</option><option value="1915">1915</option><option value="1914">1914</option><option value="1913">1913</option><option value="1912">1912</option><option value="1911">1911</option><option value="1910">1910</option><option value="1909">1909</option><option value="1908">1908</option><option value="1907">1907</option><option value="1906">1906</option><option value="1905">1905</option></select>
						<select class="month"><option value="" disabled="1">Monat:</option><option value="12">Dezember</option><option value="11">November</option><option value="10">Oktober</option><option value="9">September</option><option value="8">August</option><option value="7">Juli</option><option value="6">Juni</option><option value="5">Mai</option><option value="4">April</option><option value="3">März</option><option value="2">Februar</option><option value="1">Januar</option></select>
						<select class="day"><option value="" disabled="1">Tag:</option><option value="31">31</option><option value="30">30</option><option value="29">29</option><option value="28">28</option><option value="27">27</option><option value="26">26</option><option value="25">25</option><option value="24">24</option><option value="23">23</option><option value="22">22</option><option value="21">21</option><option value="20">20</option><option value="19">19</option><option value="18">18</option><option value="17">17</option><option value="16">16</option><option value="15">15</option><option value="14">14</option><option value="13">13</option><option value="12">12</option><option value="11">11</option><option value="10">10</option><option value="9">9</option><option value="8">8</option><option value="7">7</option><option value="6">6</option><option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option></select>
						<select class="hour"><option value="" disabled="1">Stunde:</option><option value="0">00</option><option value="1">01</option><option value="2">02</option><option value="3">03</option><option value="4">04</option><option value="5">05</option><option value="6">06</option><option value="7">07</option><option value="8">08</option><option value="9">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option></select>
						<select class="minute"><option value="" disabled="1">Minute:</option><option value="0">00</option><option value="10">10</option><option value="20">20</option><option value="30">30</option><option value="40">40</option><option value="50">50</option></select>
					</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close">Abbrechen</a>
							<button type="button" class="button blue save">Speichern</button>
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
						<h4 class="modal-title">Beitrag löschen</h4>
					</div>
					<div class="modal-body">Dieser Beitrag wird gelöscht und du wirst ihn nicht mehr finden können. Du kannst den Beitrag auch bearbeiten, wenn du nur etwas ändern möchtest.</div>
					<div class="modal-footer">
						<div class="buttons">
							<a class="button gray close">Abbrechen</a>
							<button type="button" class="button blue delete">Beitrag löschen</button>
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
						<span class="b_name"><?php echo Config::get("name"); ?></span><span class="b_options"> - </span><span class="b_feeling"></span><span class="b_with"> mit </span><span class="b_persons"></span><span class="b_here"> hier: </span><span class="b_location"></span>
					</div>
					<i class="pirvacy_icon"></i>
					<a class="b_date"></a>
					<a class="b_tools"></a>
				</div>
			</div>
			<div class="b_text"></div>
			<div class="b_content"></div>
		</div>
		
		<!-- Pirvacy Settings -->
		<ul class="b_dropdown pirvacy_settings">
			<li><a class="set" data-val="public"><i class="public"></i>Öffentlich</a></li>
			<li><a class="set" data-val="friends"><i class="friends"></i>Friends</a></li>
			<li><a class="set" data-val="private"><i class="private"></i>Nur ich</a></li>
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