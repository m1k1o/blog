<?php
include 'common.php';

try {
	Export::download($_GET);
} catch (Exception $e) {
	http_response_code(400);
	header('Content-Type: text/plain; charset=utf-8');
	echo $e->getMessage();
}