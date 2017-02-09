<?php

// Define PROJECT PATH
define('PROJECT_PATH', dirname(__FILE__));
define('APP_PATH', PROJECT_PATH.'/application');

// Load Autoloader
require APP_PATH."core/splclassloader.class.php";
$classLoader = new \Core\SplClassLoader(null, APP_PATH);
$classLoader->setFileExtension('.class.php');
$classLoader->register();

// Start session
session_start();