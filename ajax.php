<?php
include 'common.php';

$ajax = new Ajax();

try {
	$ajax->token();

	// Prepare inputs
	$request = array_merge(@$_POST, @$_GET);
	if(empty($request["action"])){
		throw new Exception("No action specified.");
	}

	$method = ['Post', $request["action"]];

	// If method exists
	if(!is_callable($method)){
		throw new Exception("Method was not found.");
	}

	// CAll method
	$response = call_user_func($method, $request);
	$ajax->set_response($response);

	// Log
	Log::put("ajax_access", $request["action"]);
} catch (Exception $e) {
	$ajax->set_error($e->getMessage());
}

$ajax->json_response();