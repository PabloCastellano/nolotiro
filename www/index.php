<?php

/**
 * Bootstrap file for nolotiro V2
 *
 * @author  Daniel Remeseiro
 * 
 */
//error_reporting(E_ALL|E_STRICT);
//ini_set('display_errors', 1);
date_default_timezone_set('Europe/Madrid');

// Set the application root path
define('NOLOTIRO_PATH_ROOT', realpath(dirname(__FILE__) . '/../'));

// Set include path
set_include_path(NOLOTIRO_PATH_ROOT . PATH_SEPARATOR . get_include_path());

//the path to Zend Network library :
set_include_path('.' . PATH_SEPARATOR . '../library/');

// Load required files
require_once 'Zend/Controller/Front.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Registry.php';
require_once 'Zend/Db.php';
require_once 'Zend/Db/Table/Abstract.php';
require_once 'Zend/Session/Namespace.php';

// Load Configuration
$config = new Zend_Config_Ini(NOLOTIRO_PATH_ROOT . '/config/nolotiro.ini', 'default');
Zend_Registry::set('config', $config);

// Start Session
$session = new Zend_Session_Namespace('Nolotiro');
Zend_Registry::set('session', $session);

//Setup the ddbb
$dbAdapter = Zend_Db::factory($config->database);
Zend_Db_Table_Abstract::setDefaultAdapter($dbAdapter);

$registry = Zend_Registry::getInstance();
$registry->configuration = $config;
$registry->dbAdapter     = $dbAdapter;

unset($dbAdapter, $registry);



// Set up the front controller and dispatch
try {
	$front = Zend_Controller_Front::getInstance();
	$front->throwExceptions(true);
	$front->setControllerDirectory(NOLOTIRO_PATH_ROOT . '/application/controllers');
	$front->setBaseUrl($config->www->baseurl);
	$front->dispatch();

// Handle controller exceptions (usually 404)
} catch (Zend_Controller_Exception $e) {
	include 'errors/404.phtml';

// Handle all other exceptions
} catch (Exception $e) {
	include 'errors/500.phtml';

}