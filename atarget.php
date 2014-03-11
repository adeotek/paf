<?php
/**
 * Ajax requests entry point file
 *
 * All ajax request have this file as target.
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.0
 * @filesource
 */
	/* Let browser know that response is utf-8 encoded */
	header('Content-Type: text/html; charset=UTF-8');
	header('Content-Language: en');
	error_reporting(0);
	if(in_array('globals',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	if(in_array('_post',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	require_once(realpath(dirname(__FILE__)).'/configuration.php');
	$paf = PAF::GetInstance(TRUE);
	require_once($paf->app_absolute_path.'/PafDispatcher.php');
	$paf->ExecutePAFReq();
?>