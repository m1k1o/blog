<?php

// Define PROJECT PATH
define('PROJECT_PATH', dirname(__FILE__));

// Load Autoloader
require "lib/splclassloader.class.php";
$classLoader = new SplClassLoader(null, PROJECT_PATH.'/lib');
$classLoader->setFileExtension('.class.php');
$classLoader->register();

// Start session
session_start();