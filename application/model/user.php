<?php
namespace Model;

class User extends \Core\Model
{
	const SESSION_NAME = "logged_in";
	
	private $_force_login;
	private $_nick;
	private $_pass;

	public function __construct(){
		if($this->_force_login = \Core\Config::get_safe("force_login", false)){
			$this->_nick = \Core\Config::get("nick");
			$this->_pass = \Core\Config::get_safe("pass", "");
		}
	}

	private function make_hash($nick, $pass){
		return md5($nick.$pass);
	}

	public function is_logged_in(){
		return !$this->_force_login || (!empty($_SESSION[User::SESSION_NAME]) && $_SESSION[User::SESSION_NAME] == $this->make_hash($this->_nick, $this->_pass));
	}

	public function login($nick, $pass){
		if(!$this->_force_login){
			return true;
		}
		
		if($this->is_logged_in()){
			throw new Exception("You are already logged in.");
		}
		
		if($this->_nick == $nick && $this->_pass == $pass){
			$_SESSION[User::SESSION_NAME] = $this->make_hash($nick, $pass);
			return true;
		}
		
		\Core\Log::put("login_fails", $nick);
		throw new Exception("The nick or password is incorrect.");
	}
	
	public function logout(){
		if(!$this->_force_login){
			throw new Exception("You can't log out. There is no account.");
		}
		
		if(!$this->is_logged_in()){
			throw new Exception("You are not even logged in.");
		}
		
		$_SESSION[User::SESSION_NAME] = false;
		return true;
	}
}