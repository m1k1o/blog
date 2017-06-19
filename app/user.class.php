<?php

class user
{
	const SESSION_NAME = "logged_in";
	
	public static function is_logged_in(){
		if(!Config::get_safe("force_login", false)){
			return true;
		}
		
		return !empty($_SESSION[User::SESSION_NAME]) && $_SESSION[User::SESSION_NAME] == md5(Config::get("nick").Config::get_safe("pass", ""));
	}
	
	public static function login($nick, $pass){
		if(!Config::get_safe("force_login", false)){
			return true;
		}
		
		if(self::is_logged_in()){
			throw new Exception(__("You are already logged in."));
		}
		
		if(Config::get("nick") == $nick && Config::get_safe("pass", "") == $pass){
			$_SESSION[User::SESSION_NAME] = md5($nick.$pass);
			return true;
		}
		
		Log::put("login_fails", $nick);
		throw new Exception(__("The nick or password is incorrect."));
	}
	
	public static function logout(){
		if(!Config::get_safe("force_login", false)){
			throw new Exception(__("You can't log out. There is no account."));
		}
		
		if(!self::is_logged_in()){
			throw new Exception(__("You are not even logged in."));
		}
		
		$_SESSION[User::SESSION_NAME] = false;
		return true;
	}
}