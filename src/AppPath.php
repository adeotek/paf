<?php
/**
 * PAF (PHP AJAX Framework) application path class file
 *
 * The PAF path class contains helper methods for application paths.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.2
 * @filesource
 */
namespace PAF;
/**
 * PAF path class
 *
 * The PAF path class contains helper methods for application paths.
 *
 * @package  AdeoTEK\PAF
 * @access   public
 */
class AppPath {
	/**
	 * Get PAF path
	 *
	 * @return string
	 */
	public static function GetPath(): string {
		return __DIR__;
	}//END public static function GetPath
	/**
	 * Get PAF boot file
	 *
	 * @return string
	 */
	public static function GetBootFile(): string {
		return __DIR__.'/boot.php';
	}//END public static function GetBootFile
}//END class AppPath
?>