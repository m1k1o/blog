<?php

class Log
{
	private static $_files = [
		"ajax_access", 
		"ajax_errors", 
		"login_fails", 
		"visitors"
	];
	
	private static $_path = 'data/logs/';
	
	public static function put($_file, $_text = null){
		if(!Config::get_safe("logs", false) || !in_array($_file, static::$_files)){
			return ;
		}
		
		if(false === file_put_contents(PROJECT_PATH.static::$_path.$_file.".log", self::line($_text), FILE_APPEND)){
			trigger_error(sprint("Can't write to %s.log file.", $_file), E_USER_NOTICE);
		}
	}
	
	private static function line($_text = null){
		return date('Y-m-d H:i:s')."\t".$_SERVER["REMOTE_ADDR"]."\t".$_SERVER["HTTP_USER_AGENT"].($_text ? "\t".$_text : "").PHP_EOL;
	}
}