<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Config
{
	const CONFIG = 'config.ini';
	const CUSTOM = 'data/config.ini';
	const CUSTOM_FALLBACK = 'custom.ini';
	const ENV_PREFIX = 'BLOG_';

	private static $_settings = null;

	private static function init(){
		$config_file = PROJECT_PATH.self::CONFIG;
		if(!is_readable($config_file)){
			throw new ConfigException('Cannot read config file.');
		}

		$default_settings = parse_ini_file($config_file);
		if($default_settings === false){
			throw new ConfigException('Cannot parse config file.');
		}

		$custom_settings = [];
		if(is_readable($config_file = PROJECT_PATH.self::CUSTOM)){
			$custom = parse_ini_file($config_file);
			if($custom !== false){
				$custom_settings = $custom;
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

				$custom_settings = array_merge($custom_settings, $custom);
			}
		}

		// Fallback for versions, where mysql was default
		if(!array_key_exists('db_connection', $custom_settings) && array_key_exists('mysql_user', $custom_settings) &&
			(array_key_exists('mysql_socket', $custom_settings) || array_key_exists('mysql_host', $custom_settings))) {
			$custom_settings['db_connection'] = 'mysql';
		}

		// Merge default and custom settings
		self::$_settings = array_merge($default_settings, $custom_settings);

		// From envs
		$envs = getenv();
		$env_prefix_len = strlen(self::ENV_PREFIX);
		foreach($envs as $key => $value){
			if(substr($key, 0, $env_prefix_len) === self::ENV_PREFIX){
				$key = strtolower(substr($key, $env_prefix_len));

				if($value === 'true'){
					$value = true;
				}
				elseif($value === 'false'){
					$value = false;
				}

				// Associative arrays in environment variables
				if($key === 'visitor' || $key === 'friends'){
					$value = self::parse_env_assoc($value);
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

	// Parse associative array from string in format key:value
	private static function parse_env_assoc($data){
		if(!preg_match_all("/([^\s]+):([^\s]+)/s", $data, $matches)){
			return [];
		}

		list($_, $keys, $values) = $matches;

		$array = [];
		foreach ($values as $key => $value) {
			$array[$keys[$key]] = $value;
		}

		return $array;
	}
}

class ConfigException extends Exception {}