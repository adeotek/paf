<?php
/**
 * Ajax requests entry point file
 *
 * All ajax request have this file as target.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
 * @filesource
 */
	define('_VALID_AAPP_REQ',TRUE);
	require_once('pathinit.php');
	if(defined('_AAPP_OFFLINE') && _AAPP_OFFLINE) { die('OFFLINE!'); }
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
	$dont_keep_alive = get_array_param($_GET,'dnka',get_array_param($_POST,'do_not_keep_alive',FALSE,'bool'),'bool');
	$app = PAF\AApp::GetInstance(TRUE,array('namespace'=>$cnamespace),TRUE,$dont_keep_alive);
	$app->ExecuteARequest();
?>