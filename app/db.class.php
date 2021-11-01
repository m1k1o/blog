<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

// v3.43 (+ query counter)
class DB
{
	private static $_instance = null;

	private $_PDO;
	private $_query;

	private $_query_counter;

	// Handle instances
	public final static function get_instance(){
		if(self::$_instance === null){
			self::$_instance = new static();
		}

		return self::$_instance;
	}

	public static function connection() {
		return Config::get_safe('db_connection', 'sqlite');
	}

	// CONCAT() does not exist in SQLite, using || instead
	// for postgres, ERROR: could not determine data type of parameter $1
	public final static function concat(){
		$values = func_get_args();

		if(DB::connection() === 'sqlite' || DB::connection() === 'postgres') {
			return implode(" || ", $values);
		} else {
			return 'CONCAT('.implode(", ", $values).')';
		}
	}

	// Initialise PDO object
	private final function __construct(){
		switch(DB::connection()) {
			case 'mysql':
				$this->mysql_connect();
				break;
			case 'postgres':
				$this->postgres_connect();
				break;
			case 'sqlite':
				$this->sqlite_connect();
				break;
		}
	}

	private final function mysql_connect(){
		$host = Config::get_safe('mysql_host', false);
		$port = Config::get_safe('mysql_port', false);
		$socket = Config::get_safe('mysql_socket', false);

		if($socket === false && $host === false){
			throw new DBException("Mysql host or socket must be defined.");
		}

		// Try to connect
		try {
			$this->_PDO = new \PDO(
				// Server
				'mysql:'.
					($socket !== false
						? 'unix_socket='.$socket
						: 'host='.$host.($port !== false ? ';port='.$port : '')
					).
				// DB
				';dbname='.Config::get('db_name').
				// Charset
				';charset=utf8',
				// Username
				Config::get('mysql_user'),
				// Password
				Config::get_safe('mysql_pass', ''),
				// Set attributes
				[
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_EMULATE_PREPARES => false
				]
			);

			$this->_PDO->exec(
				// Set charset
				'SET NAMES utf8;'.

				// Set timezone
				'SET time_zone="'.date('P').'";'
			);
		} catch (PDOException $e) {
			throw new DBException($e->getMessage());
		}
	}

	private final function postgres_connect(){
		$host = Config::get_safe('postgres_host', false);
		$port = Config::get_safe('postgres_port', false);
		$socket = Config::get_safe('postgres_socket', false);

		if($socket === false && $host === false){
			throw new DBException("Postgres host or socket must be defined.");
		}

		// Try to connect
		try {
			$this->_PDO = new \PDO(
				// Server
				'pgsql:'.
					($socket !== false
						? 'unix_socket='.$socket
						: 'host='.$host.($port !== false ? ';port='.$port : '')
					).
				// DB
				';dbname='.Config::get('db_name').
				// Charset
				';options=\'--client_encoding=UTF8\'',
				// Username
				Config::get('postgres_user'),
				// Password
				Config::get_safe('postgres_pass', ''),
				// Set attributes
				[
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_EMULATE_PREPARES => false
				]
			);

			$this->_PDO->exec(
				// Set timezone
				'SET TIME ZONE "'.date('e').'";'
			);
		} catch (PDOException $e) {
			throw new DBException($e->getMessage());
		}
	}

	private final function sqlite_connect(){
		$sqlite_db = PROJECT_PATH.Config::get_safe('sqlite_db', "data/sqlite.db");

		// First run of sqlite
		if(!file_exists($sqlite_db)) {
			if(!is_writable(dirname($sqlite_db))) {
				throw new DBException("Sqlite database directory must me writable.");
			}

			if(!touch($sqlite_db)) {
				throw new DBException("Cannot create sqlite database file.");
			}

			// Inilialize SQL schema
			$sql_schema = file_get_contents(APP_PATH."db/sqlite/01_schema.sql");

			try {
				$this->_PDO = new \PDO("sqlite:".$sqlite_db, null, null, [
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
				]);
				$this->_PDO->exec($sql_schema);
			} catch (PDOException $e) {
				$this->_PDO = null;
				unlink($sqlite_db);

				throw new DBException($e->getMessage());
			}

			return ;
		}

		// Try to connect
		try {
			$this->_PDO = new \PDO("sqlite:".$sqlite_db, null, null, [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
			]);
		} catch (PDOException $e) {
			throw new DBException($e->getMessage());
		}
	}

	// Just flattern array to be binded : [key1, key2, [key3, [key4]]] => [key1, key2, key3, key4]
	private final function bind_value($key, $value){
		if(is_array($value)){
			foreach($value as $one_value){
				$key = $this->bind_value($key, $one_value);
			}

			return $key;
		}

		// BUG: Force strings to be UTF-8
		// remove all 4-bytes characters.
		if(is_string($value)){
			$value = preg_replace('/[\xF0-\xF7].../s', '', $value);
		}

		$this->_query->bindValue($key, $value);
		return ++$key;
	}

	// Process Query
	// query ($sql)
	// query ($sql, $bind_param_01, $bind_param_02, ...)
	// query ($sql, [$bind_param_01, $bind_param_02, ...])
	public final function query(){
		// Second parm is binded values
		$params = func_get_args();

		// First parameter is sql
		$sql = $params[0];
		unset($params[0]);

		// Replace backticks with " for postgres
		if(DB::connection() === 'postgres') {
			$sql = str_replace("`", '"', $sql);
		}

		// Debug mode
		if(Config::get_safe('debug', false)){
			echo "<!-- ".$sql." + ".json_encode($params)." -->\n";
		}

		// Try to prepare MySQL statement
		try {
			// Prepare PDO statement
			$this->_query = $this->_PDO->prepare($sql);

			// Bind values
			$this->bind_value(1, $params);

			// Execute
			$this->_query->execute();
		} catch (PDOException $e) {
			throw new DBException($e->getMessage());
		}

		$this->_query_counter++;
		return $this;
	}

	// Insert into table
	public final function insert($table_name, $fields = null){
		// If empty line
		if(empty($fields)){
			return $this->query("INSERT INTO `{$table_name}` () VALUES ()");
		}

		// If multiple
		if(isset($fields[0])){
			// Turn array into PDO prepered statement format
			$keys = array_keys($fields[0]);

			// Build query
			$query = "INSERT INTO `{$table_name}` (`".implode('`, `', $keys)."`) VALUES ";

			// Insert values
			$first = true;
			$prepared_data = array();
			foreach($fields as $field){
				if($first){
					$first = false;
				} else {
					$query .= ',';
				}

				end($field);
				$last_key = key($field);

				$query .= '(';
				foreach($field as $key => $value){
					if($value === "NOW()"){
						if(DB::connection() === 'sqlite') {
							$query .= "datetime('now', 'localtime')";
						} else {
							$query .= "NOW()";
						}
					} else {
						$query .= '?';
						$prepared_data[] = $value;
					}

					if($last_key != $key){
						$query .= ',';
					}
				}
				$query .= ')';
			}

			// Execute query
			return $this->query($query, $prepared_data);
		}

		// If only single
		return $this->insert($table_name, array($fields));
	}

	// Update table
	// update ($table_name, $fields)
	// update ($table_name, $fields, $sql)
	// update ($table_name, $fields, $sql, $bind_param_01, $bind_param_02, ...)
	// update ($table_name, $fields, $sql, [$bind_param_01, $bind_param_02, ...])
	public final function update(){
		// Fourt param is binded values
		$params = func_get_args();

		// First is table_name
		$table_name = $params[0];
		unset($params[0]);

		// Second is fields
		$fields = $params[1];
		unset($params[1]);

		// Third is sql
		$sql = $params[2];
		unset($params[2]);

		// If fields are not array, do nothing
		if(!is_array($fields)){
			return $this;
		}

		end($fields);
		$last_key = key($fields);

		// Support for NOW()
		$prepared_data = array();
		$set_data = null;
		foreach($fields as $key => $value){
			if($value === "NOW()"){
				if(DB::connection() === 'sqlite') {
					$set_data .="`{$key}` = datetime('now', 'localtime')";
				} else {
					$set_data .="`{$key}` = NOW()";
				}
			} else {
				$set_data .= "`{$key}` = ?";
				$prepared_data[] = $value;
			}

			if($last_key != $key){
				$set_data .= ',';
			}
		}

		// If params are not array, make it
		if(!is_array($params)){
			$params = array($params);
		}

		// Merge fields array and additional SQL data
		foreach($params as $param){
			$prepared_data[] = $param;
		}

		// Build query
		$query = "UPDATE `{$table_name}` SET {$set_data} ".$sql;

		// Execute query
		return $this->query($query, $prepared_data);
	}

	// Alias for all
	public final function results(){
		trigger_error("Using deprecated method <strong>DB::results();</strong>. Use <strong>DB::all();</strong> instead.");
		return $this->all();
	}

	// Get all rows
	public final function all($type = \PDO::FETCH_ASSOC){
		return $this->_query->fetchAll($type);
	}

	// Get all values to one dimensional array
	public final function columns($column = 0){
		return $this->_query->fetchAll(\PDO::FETCH_COLUMN, $column);
	}

	// Get first row from result
	public final function first($key = null){
		$results = $this->all();

		if($key !== null){
			return @$results[0][$key];
		}

		return @$results[0];
	}

	// Get last inserted ID
	public final function last_id(){
		return $this->_PDO->lastInsertId();
	}

	// Exec
	public final function exec($sql){
		// Try to execute MySQL
		try {
			$this->_PDO->exec($sql);
		} catch (PDOException $e) {
			throw new DBException($e->getMessage());
		}

		return $this;
	}

	public final function total_queries(){
		return $this->_query_counter;
	}
}

// Handle DB errors
class DBException extends Exception{}