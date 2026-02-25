<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Ajax
{
	private $_response = null;

	public function __construct(){
		ob_start();
	}

	public function set_error($msg = null){
		$this->_response = [
			"error" => true,
			"msg" => $msg
		];

		// Include debug info
		if(ob_get_length() > 0 && Config::get_safe('debug', false)){
			$this->_response["debug"] = ob_get_clean();
		}

		// Log
		Log::put("ajax_errors", $msg);
	}

	public function token(){
		if(empty($_SESSION['token'])){
			throw new Exception("Direct access violation.");
		}

		$headers = apache_request_headers();
		if(!isset($headers['Csrf-Token']) && !isset($headers['csrf-token'])){
			throw new Exception("No CSRF token.");
		}

		if($headers['Csrf-Token'] !== $_SESSION['token'] && $headers['csrf-token'] !== $_SESSION['token']){
			throw new Exception("Wrong CSRF token.");
		}
	}

	public function set_response($response = null){
		$this->_response = $response;
	}

	public function json_response(){
		if(ob_get_length() > 0) {
			ob_clean();
		}

		header('Content-Type: application/json; charset=utf-8');
		// IMPORTANT: JSON_UNESCAPED_UNICODE ensures emojis are transmitted correctly
		echo json_encode($this->_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}