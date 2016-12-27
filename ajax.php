<?php
include 'common.php';

function error($msg){
	if(Config::get_safe("logs", false))
		file_put_contents('logs/ajax_errors.log', date('Y-m-d H:i:s')."\t".$_SERVER["REMOTE_ADDR"]."\t".$_SERVER["HTTP_USER_AGENT"]."\t".$msg.PHP_EOL, FILE_APPEND);
	header('Content-Type: application/json');
	echo json_encode(["error" => true, "msg" => $msg]);
	exit;
}

// Check if exists token
if(empty($_SESSION['token'])){
	error("Direct access violation.");
}

// Validate token
$headers = apache_request_headers();
if(isset($headers['CsrfToken']) && !empty($_SESSION['token'])){
	if($headers['CsrfToken'] !== $_SESSION['token']) {
		error("Wrong CSRF token.");
	}
} else {
	error("No CSRF token.");
}

// Prepare inputs
$r = array_merge(@$_POST, @$_GET);
$f = ['Post', @$r["action"]];

// If method exists
if(is_callable($f)){
	$c = call_user_func($f, $r);
	if(Config::get_safe("logs", false))
		file_put_contents('logs/ajax_access.log', date('Y-m-d H:i:s')."\t".$_SERVER["REMOTE_ADDR"]."\t".$_SERVER["HTTP_USER_AGENT"]."\t".@$r["action"].PHP_EOL, FILE_APPEND);
} else {
	error("Method was not found.");
}

// Flush
header('Content-Type: application/json');
echo json_encode($c);
exit;