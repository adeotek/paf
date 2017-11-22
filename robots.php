<?php
/**
 * PHP file that serves the robots.txt after setting 'robot' flag in session data.
 *
 * To detect crawlers, the robots.txt is served by this php file
 * (after setting the 'robot' session flag to 1), with the help of a .htaccess rewrite rule.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.0.0
 * @filesource
 */
    define('_VALID_AAPP_REQ',TRUE);
	require_once('pathinit.php');
	require_once(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH._AAPP_CONFIG_PATH.'/Configuration.php');
	PAF\AApp::SessionStart();
	$_SESSION['robot'] = 1;
	PAF\AApp::SessionClose();
	switch(strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')) {
		default:
			echo file_get_contents('robots.txt');
			break;
	}//END switch
	exit;
?>