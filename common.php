<?php

// Define PROJECT PATH
define('PROJECT_PATH', dirname(__FILE__).'/');
define('APP_PATH', PROJECT_PATH.'app/');

// Load Autoloader
require APP_PATH."splclassloader.class.php";
$classLoader = new SplClassLoader(null, APP_PATH);
$classLoader->setFileExtension('.class.php');
$classLoader->register();

// Language
Lang::load(empty($_GET["hl"]) ? Config::get("lang") : $_GET["hl"]);

// Start session
session_start();