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

    $action = $request["action"];

    // Prüfen, ob Methode in Post existiert
    if(!method_exists('Post', $action)){
        throw new Exception("Method was not found.");
    }

    // Statischer Aufruf der Methode
    $response = Post::$action($request);

    $ajax->set_response($response);

    // Log
    Log::put("ajax_access", $request["action"]);

} catch (Exception $e) {
    $ajax->set_error($e->getMessage());
}

$ajax->json_response();
