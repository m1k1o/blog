<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Config
{
	const CONFIG = 'config.ini';
	const CUSTOM = 'data/config.ini';
	const CUSTOM_FALLBACK = 'custom.ini';

	private static $_settings = null;

	private static function init(){
		$config_file = PROJECT_PATH.self::CONFIG;
		if(!is_readable($config_file)){
			throw new ConfigException('Cannot read config file.');
		}

		self::$_settings = parse_ini_file($config_file);
		if(self::$_settings === false){
			throw new ConfigException('Cannot parse config file.');
		}

		$config_file = PROJECT_PATH.self::CUSTOM;
		if(is_readable($config_file)){
			$custom = parse_ini_file($config_file);
			if($custom !== false){
				self::$_settings = array_merge(self::$_settings, $custom);
			}
		}

		// Fallback for legacy versions
		elseif(is_readable($config_file = PROJECT_PATH.self::CUSTOM_FALLBACK)){
			$custom = parse_ini_file($config_file);
			if($custom !== false){
				// Fallback for old direcotry structure
				if(!array_key_exists('images_path', $custom) && !array_key_exists('thumbnails_path', $custom)){
					$custom['images_path'] = 'i/';
					$custom['thumbnails_path'] = 't/';
				}

				self::$_settings = array_merge(self::$_settings, $custom);
			}
		}

		// From envs
		$envs = getenv();
		foreach($envs as $key => $value){
			if(substr($key, 0, 5) !== "BLOG_"){
				$key = strtolower(substr($key, 5));

				if($value === 'true'){
					$value = true;
				}
				elseif($value === 'false'){
					$value = false;
				}

				self::$_settings[$key] = $value;
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