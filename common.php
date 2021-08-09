<?php

// Define PROJECT PATH
define('PROJECT_PATH', dirname(__FILE__).'/');
define('APP_PATH', PROJECT_PATH.'app/');

// Load Autoloader
require APP_PATH."splclassloader.class.php";
$classLoader = new SplClassLoader(null, APP_PATH);
$classLoader->setFileExtension('.class.php');
$classLoader->register();

// In debug mode, display errors
if(Config::get_safe('debug', false)){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	// Check extensions
	$required = ['curl', 'PDO', 'pdo_mysql', 'gd', 'exif'];
	$loaded = get_loaded_extensions();
	if($missing = array_diff($required, $loaded)){
		die("Missing extensions, please install: ".implode(", ", $missing));
	}
}

// Language
Lang::load(empty($_GET["hl"]) ? Config::get("lang") : $_GET["hl"]);

// Timezone
if(false !== ($TZ = Config::get_safe('timezone', getenv('TZ')))) {
	date_default_timezone_set($TZ);
	ini_set('date.timezone', $TZ);
}

// Start session
ini_set('session.cookie_httponly', 1);
session_start();
