<?php
/**
 * PHP file that serves the robots.txt after setting 'robot' flag in session data.
 *
 * To detect crawlers, the robots.txt is served by this php file
 * (after setting the 'robot' session flag to 1), with the help of a .htaccess rewrite rule.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2010 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
 * @filesource
 */
    define('_X_VREQ',TRUE);
	require_once('pconfig.inc');
	require_once(_X_ROOT_PATH._X_APP_PATH._X_CONFIG_PATH.'/configuration.php');
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