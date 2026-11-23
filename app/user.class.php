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

		if(Config::get_safe("ldap_enabled", false)){
			return !empty($_SESSION[User::SESSION_NAME]) &&
				$_SESSION[User::SESSION_NAME] === 'admin';
		}

		return !empty($_SESSION[User::SESSION_NAME]) &&
			$_SESSION[User::SESSION_NAME] === hash("crc32", Config::get("nick").Config::get_safe("pass", ""), false);
	}

	public static function login($nick, $pass){
		if(!Config::get_safe("force_login", false)){
			return true;
		}

		if(self::is_logged_in()){
			throw new Exception(__("You are already logged in."));
		}

		if(Config::get_safe("ldap_enabled", false)){
			return static::LDAP_login($nick, $pass);
		} else {
			return static::config_login($nick, $pass);
		}
	}

	private static function config_login($nick, $pass){
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

	private static function LDAP_login($nick, $pass){
		$ldap_host = Config::get("ldap_host");
		$ldap_port = Config::get_safe("ldap_port", 389);
		$ldap_admin_dn = Config::get_safe("ldap_admin_dn", false);
		$ldap_visitor_dn = Config::get_safe("ldap_visitor_dn", false);

		if(!($ds = ldap_connect($ldap_host, $ldap_port))) {
			throw new Exception(__("Could not connect to LDAP server."));
		}

		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		ldap_set_option($ds, LDAP_OPT_NETWORK_TIMEOUT, 10);

		if ($ldap_admin_dn !== false && ldap_bind($ds, "cn=".$nick.",".$ldap_admin_dn, $pass)) {
			$_SESSION[User::SESSION_NAME] = 'admin';
			return ["logged_in" => true, "is_visitor" => false];
		}

		if ($ldap_visitor_dn !== false && ldap_bind($ds, "cn=".$nick.",".$ldap_visitor_dn, $pass)) {
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