<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Log
{
	private static $_files = [
		"ajax_access", 
		"ajax_errors", 
		"login_fails", 
		"visitors"
	];

	public static function put($_file, $_text = null){
		if(!Config::get_safe("logs", false) || !in_array($_file, static::$_files)){
			return ;
		}

		$_logs_path = Config::get('logs_path');
		if(!is_dir($_logs_path) && !mkdir($_logs_path, 755, true)){
			die("Logs directory could not be created.");
		}

		if(false === file_put_contents($_logs_path.$_file.".log", self::line($_text), FILE_APPEND) && Config::get_safe('debug', false)){
			die(sprintf("Can't write to %s.log file.", $_file));
		}
	}

	private static function escape($_text = null){
		return preg_replace("/[\n\r\t]/", "-", $_text);
	}

	private static function line($_text = null){
		return trim(
			date('Y-m-d H:i:s')."\t".
			self::escape($_SERVER["REMOTE_ADDR"])."\t".
			self::escape($_SERVER["HTTP_USER_AGENT"])."\t".
			self::escape($_text)
		).PHP_EOL;
	}
}