<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Config
{
	const CONFIG = 'config.ini';
	const CUSTOM = 'custom.ini';

	private static $_settings = null;

	private static function init(){
		$config_file = PROJECT_PATH.self::CONFIG;

		if(!is_readable($config_file)){
			throw new ConfigException('Cannot read config file.');
		}

		self::$_settings = parse_ini_file($config_file);
		$custom_config = PROJECT_PATH.self::CUSTOM;

		if(is_readable($custom_config)){
			$custom = parse_ini_file($custom_config);
			if($custom !== false){
				self::$_settings = array_merge(self::$_settings, $custom);
			}
		}
	}

	public static function get($key){
		if(self::$_settings === null){
			self::init();
		}

		if(!array_key_exists($key, self::$_settings)){
			throw new ConfigException(sprintf('Key "%s" not found in settings.', $key));
		}

		return self::$_settings[$key];
	}

	public static function get_safe($key, $default = ''){
		try {
			$value = self::get($key);
		} catch (ConfigException $e) {
			$value = $default;
		}

		return $value;
	}
}

class ConfigException extends Exception {}