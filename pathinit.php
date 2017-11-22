<?php
/**
 * PAF (PHP AJAX Framework) paths configuration file.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.0.0
 * @filesource
 */
	if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }
// Define offline mode on/off
	if(file_exists('.offline') && trim(file_get_contents('.offline'))=='1') {
		define('_AAPP_OFFLINE',TRUE);
	} else {
	define('_AAPP_OFFLINE',FALSE);
	}//if(file_exists('.offline') && trim(file_get_contents('.offline'))=='1')
// Paths constants definition (Editable zone)
	define('_AAPP_APPLICATION_PATH','');
	define('_AAPP_CONFIG_PATH','');
	define('_AAP_PUBLIC_ROOT_PATH','');
	define('_AAP_PUBLIC_PATH','');
// END Paths constants definition (Editable zone)
	$aapp_root_path = dirname(__FILE__).'/';
	if(strlen(_AAP_PUBLIC_ROOT_PATH._AAP_PUBLIC_PATH)>0) {
		$aapp_root_path .= str_repeat('../',substr_count(trim(_AAP_PUBLIC_ROOT_PATH._AAP_PUBLIC_PATH,'/'),'/')+1);
	}//if(strlen(_AAP_PUBLIC_ROOT_PATH._AAP_PUBLIC_PATH)>0)
	define('_AAPP_ROOT_PATH',realpath($aapp_root_path));
?>