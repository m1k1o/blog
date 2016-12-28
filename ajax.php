<?php
include 'common.php';

function error($msg){
	Log::put("ajax_errors", $msg);
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
if(isset($headers['Csrf-Token']) && !empty($_SESSION['token'])){
	if($headers['Csrf-Token'] !== $_SESSION['token']) {
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
	Log::put("ajax_access", @$r["action"]);
} else {
	error("Method was not found.");
}

// Flush
header('Content-Type: application/json');
echo json_encode($c);
exit;