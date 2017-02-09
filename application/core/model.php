<?php
namespace Core;

abstract class Model
{
	protected $_DB;
	protected $_safe_input;

	private static $_instance = null;

	public final static function get_instance(){
		if(self::$_instance == null){
			self::$_instance = new static();
		}
		
		return self::$_instance;
	}

	protected function __construct(){
		$this->_DB = DB::get_instance();
	}

	protected function input(&$data){
		foreach($data as $key => &$value){
			if(!array_key_exists($key, $this->_safe_input)){
				unset($value);
			}
		}
	}
}