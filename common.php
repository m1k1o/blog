<?php

// Define PROJECT PATH
define('DS', DIRECTORY_SEPARATOR);
define('PROJECT_PATH', dirname(__FILE__));
define('APP_PATH', PROJECT_PATH.DS.'app');

// Load Autoloader
require APP_PATH.DS."splclassloader.class.php";
$classLoader = new SplClassLoader(null, APP_PATH);
$classLoader->setFileExtension('.class.php');
$classLoader->register();

// Start session
session_start();