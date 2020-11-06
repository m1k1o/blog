<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Lang
{
	const PATH = 'lang/';

	private static $_dictionary = null;

	public static function load($lang = 'en'){
		$lang_file = APP_PATH.self::PATH.$lang.'.ini';
		if(preg_match('/^[a-z]+$/', $lang) && is_readable($lang_file)){
			self::$_dictionary = parse_ini_file($lang_file);
		}
	}

	public static function get($key){
		if(is_null(self::$_dictionary) || !array_key_exists($key, self::$_dictionary)){
			return $key;
		}

		return self::$_dictionary[$key];
	}
}

function __($key){
	return Lang::get($key);
}
