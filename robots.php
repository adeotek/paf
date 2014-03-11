<?php
/**
 * PHP file that serves the robots.txt after seting 'robot' flag in session data.
 *
 * To detect crowlers, the robots.txt is served by this php file
 * (after setting the 'robot' session flag to 1), with the help of a .htaccess rewrite rule.
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.0
 * @filesource
 */
	require_once(realpath(dirname(__FILE__)).'/configuration.php');
	PAF::SessionStart();
	$_SESSION['robot'] = 1;
	echo file_get_contents('robots.txt');
	exit;
?>