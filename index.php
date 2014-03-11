<?php
/**
 * Main entry point file
 *
 * All requests begins here (except ajax, uploads, downloads, crons, api and newsletter).
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
	define('_IS_VALID_PAGE',1);
	if(in_array('globals',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	if(in_array('_post',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
	if(array_key_exists('phpnfo',$_GET) && $_GET['phpnfo']==1) { phpinfo(); die(); }
	require_once(realpath(dirname(__FILE__)).'/configs/configuration.php');
	$paf = XSession::GetInstance();
	//Require html file
	$paf->CommitSession();
?>