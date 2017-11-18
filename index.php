<?php
/**
 * Main entry point file
 *
 * All requests begins here (except ajax)
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
	if(defined('_X_OFFLINE') && _X_OFFLINE) {
		require_once('offline.php');
		die();
	}//if(defined('_X_OFFLINE') && _X_OFFLINE)
	/* Let browser know that response is utf-8 encoded */
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
	header('Pragma: no-cache'); // HTTP 1.0.
	header('Expires: 0'); // Proxies.
	header('X-Frame-Options: GOFORIT');
	header('Content-Language: en');
	if(in_array('globals',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	if(in_array('_post',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	if(array_key_exists('phpnfo',$_GET) && $_GET['phpnfo']==1) { phpinfo(); die(); }
	require_once(_X_ROOT_PATH._X_APP_PATH._X_CONFIG_PATH.'/configuration.php');
	$app = PAFApp::GetInstance();
	//Require html/php file
	$app->SessionCommit();
?>