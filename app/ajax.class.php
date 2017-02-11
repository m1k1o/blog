<?php

class Ajax
{
	private $_response = null;
	
	public function set_error($msg = null){
		$this->_response = [
			"error" => true,
			"msg" => $msg
		];
		
		// Log
		Log::put("ajax_errors", $msg);
	}
	
	public function token(){
		if(empty($_SESSION['token'])){
			throw new Exception("Direct access violation.");
		}
		
		$headers = apache_request_headers();
		if(!isset($headers['Csrf-Token']) || empty($_SESSION['token'])){
			throw new Exception("No CSRF token.");
		}
		
		if($headers['Csrf-Token'] !== $_SESSION['token']){
			throw new Exception("Wrong CSRF token.");
		}
	}
	
	public function set_response($response = null){
		$this->_response = $response;
	}
	
	public function json_response(){
		ob_clean();
		header('Content-Type: application/json');
		echo json_encode($this->_response);
	}
}