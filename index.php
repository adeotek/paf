<?php
/**
 * Main entry point file
 *
 * All requests begins here (except ajax)
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0
 * @filesource
 */
    define('_VALID_AAPP_REQ',TRUE);
	require_once('pathinit.php');
	if(defined('_AAPP_OFFLINE') && _AAPP_OFFLINE) {
		require_once('offline.php');
		die();
	}//if(defined('_AAPP_OFFLINE') && _AAPP_OFFLINE)
	/* Let browser know that response is utf-8 encoded */
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
	header('Pragma: no-cache'); // HTTP 1.0.
	header('Expires: 0'); // Proxies.
	header('X-Frame-Options: GOFORIT');
	header('Content-Language: en');
	if(in_array('globals',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	if(in_array('_post',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	require_once(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH._AAPP_CONFIG_PATH.'/Configuration.php');
	$napp = NApp::GetInstance();
	if(array_key_exists('phpnfo',$_GET) && $_GET['phpnfo']==1) { phpinfo(); die(); }
	//Require html/php file
	$app->SessionCommit();
?>