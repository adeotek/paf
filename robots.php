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
	require_once(realpath(dirname(__FILE__)).'/configuration.php');
	PAFApp::SessionStart();
	$_SESSION['robot'] = 1;
	echo file_get_contents('robots.txt');
	exit;
?>