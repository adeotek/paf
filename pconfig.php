<?php
/**
 * PAF (PHP AJAX Framework) paths configuration file.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
 * @filesource
 */
	if(!defined('_X_VREQ') || _X_VREQ!==TRUE) { die('Invalid request!'); }
// Define offline mode on/off
	if(file_exists('.offline') && trim(file_get_contents('.offline'))=='1') {
		define('_X_OFFLINE',TRUE);
	} else {
		define('_X_OFFLINE',FALSE);
	}//if(file_exists('.offline') && trim(file_get_contents('.offline'))=='1')
// Paths constants definition (Editable zone)
	define('_X_APP_PATH','');
	define('_X_CONFIG_PATH','');
	define('_X_WEB_ROOT_PATH','');
	define('_X_PUBLIC_PATH','');
// END Paths constants definition (Editable zone)
	$x_root_path = dirname(__FILE__).'/';
	if(strlen(_X_WEB_ROOT_PATH._X_PUBLIC_PATH)>0) {
		$x_root_path .= str_repeat('../',substr_count(trim(_X_WEB_ROOT_PATH._X_PUBLIC_PATH,'/'),'/')+1);
	}//if(strlen(_X_WEB_ROOT_PATH._X_PUBLIC_PATH)>0)
	define('_X_ROOT_PATH',realpath($x_root_path));
?>