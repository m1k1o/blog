<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class user
{
	const SESSION_NAME = "logged_in";

	public static function is_visitor(){
		if(!Config::get_safe("force_login", false)){
			return true;
		}

		return !empty($_SESSION[User::SESSION_NAME]) && $_SESSION[User::SESSION_NAME] === 'visitor';
	}

	public static function is_logged_in(){
		if(!Config::get_safe("force_login", false)){
			return true;
		}

		return !empty($_SESSION[User::SESSION_NAME]) && $_SESSION[User::SESSION_NAME] === hash("crc32", Config::get("nick").Config::get_safe("pass", ""), false);
	}

	public static function login($nick, $pass){
		if(!Config::get_safe("force_login", false)){
			return true;
		}

		if(self::is_logged_in()){
			throw new Exception(__("You are already logged in."));
		}

		if(Config::get("nick") === $nick && Config::get_safe("pass", "") === $pass){
			$_SESSION[User::SESSION_NAME] = hash("crc32", $nick.$pass, false);
			return ["logged_in" => true, "is_visitor" => false];
		}

		// Legacy: Visitors and Friends.
		$visitors = array_merge(
			Config::get_safe("friends", []),
			Config::get_safe("visitor", [])
		);
		if(!empty($visitors) && isset($visitors[$nick]) && $visitors[$nick] === $pass){
			$_SESSION[User::SESSION_NAME] = 'visitor';
			return ["logged_in" => false, "is_visitor" => true];
		}

		Log::put("login_fails", $nick);
		throw new Exception(__("The nick or password is incorrect."));
	}

	public static function logout(){
		if(!Config::get_safe("force_login", false)){
			throw new Exception(__("You can't log out. There is no account."));
		}

		if(!self::is_logged_in() && !self::is_visitor()){
			throw new Exception(__("You are not even logged in."));
		}

		$_SESSION[User::SESSION_NAME] = false;
		return true;
	}
}