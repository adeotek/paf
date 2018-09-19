<?php
/**
 * PAF (PHP AJAX Framework) application interface file.
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
 * PAF (PHP AJAX Framework) application interface.
 *
 * @package  AdeoTEK\PAF
 */
interface IApp {
	/**
	 * Classic singleton method for retrieving the PAF object
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $session_init Flag indicating if session should be started or not
	 * @param  bool  $do_not_keep_alive Flag indicating if session should be kept alive by the current request
	 * @param  bool  $shell Shell mode on/off
	 * @return \PAF\IApp Returns the PAF application instance
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function GetInstance($ajax = FALSE,$params = [],$session_init = TRUE,$do_not_keep_alive = NULL,$shell = FALSE);
	/**
	 * Static setter for phash property
	 *
	 * @param  string $value The new value for phash property
	 * @return void
	 * @access public
	 * @static
	 */
	public static function SetPhash($value);
	/**
	 * Gets application absolute path
	 *
	 * @return string Returns the application absolute path
	 * @access public
	 */
	public function GetAppAbsolutePath();
	/**
	 * Initialize ARequest object
	 *
	 * @param  array $post_params Default parameters to be send via post on ajax requests
	 * @param  string $subsession Sub-session key/path
	 * @param  bool $js_init Do javascript initialization
	 * @param  bool $with_output If TRUE javascript is outputted, otherwise is returned
	 * @return bool
	 * @access public
	 */
	public function ARequestInit($post_params = array(),$subsession = NULL,$js_init = TRUE,$with_output = TRUE);
	/**
	 * Execute a method of the ARequest implementing class in an ajax request
	 *
	 * @param  array $post_params Parameters to be send via post on ajax requests
	 * @param  string $subsession Sub-session key/path
	 * @return void
	 * @access public
	 */
	public function ExecuteARequest($post_params = array(),$subsession = NULL);
}//END interface IApp
?>