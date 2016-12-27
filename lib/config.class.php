<?php

class Config
{
	private static $_settings = array();
	
	private static function init() {
		$config_file = PROJECT_PATH.'/config.ini';
		
		if (!is_readable($config_file)) {
			throw new ConfigException('Cannot read config file');
		}
		
		self::$_settings = parse_ini_file($config_file);
		$custom_config = PROJECT_PATH.'/custom.ini';
		
		if (is_readable($custom_config)) {
			$custom = parse_ini_file(PROJECT_PATH.'/custom.ini');
			if ($custom !== false) {
				self::$_settings = array_merge(self::$_settings, $custom);
			}
		}
	}
	
	public static function get($key) {
		if (empty(self::$_settings)) {
			self::init();
		}
		
		if (!array_key_exists($key, self::$_settings)) {
			throw new ConfigException('Key "'.$key.'" not found in settings');
		}
		
		return self::$_settings[$key];
	}
	
	public static function get_safe($key, $default = '') {
		try {
			$value = self::get($key);
		} catch (ConfigException $e) {
			$value = $default;
		}
		
		return $value;
	}
	
	public static function exist($key) {
		if (empty(self::$_settings)) {
			self::init();
		}
		
		if (!array_key_exists($key, self::$_settings)) {
			return false;
		}
		
		return true;
	}
}

class ConfigException extends Exception {}