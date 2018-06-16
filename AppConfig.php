<?php
/**
 * PAF (PHP AJAX Framework) global configuration class file
 *
 * Here are all the configuration parameters for PAF
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.2
 * @filesource
 */
namespace PAF;
if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }
// require_once(__DIR__.'/helpers.php');
/**
 * AppConfig is the application global configuration class
 *
 * AppConfig contains all the configuration parameters for PAF (PHP AJAX Framework)
 *
 * @package    AdeoTEK\PAF
 * @access     public
 * @abstract
 */
class AppConfig {
	/**
	 * @var    array Configuration structure
	 * @access protected
	 * @static
	 */
	private static $structure = NULL;
	/**
	 * @var    array Configuration data
	 * @access protected
	 * @static
	 */
	private static $data = NULL;
	/**
	 * Initialize application configuration class (structure and data)
	 *
	 * @param array $data
	 * @param array $structure
	 * @throws \Exception
	 */
	public static function LoadConfig(array $data,array $structure) {
		require_once(__DIR__.'/PAFConfigStructure.php');
		if(!isset($_PAF_CONFIG_STRUCTURE) || !is_array($_PAF_CONFIG_STRUCTURE)) { die('Invalid PAF configuration structure!'); }
		self::$structure = array_merge($_PAF_CONFIG_STRUCTURE,$structure);
		self::$data = $data;
	}//END public static function LoadConfig
	/**
	 * Magic method for accessing configuration elements
	 *
	 * @param  string $name Name of the configuration element
	 * @param  array  $arguments Value to be set to the configuration element
	 * @return mixed Returns the configuration element value
	 * @throws \Exception
	 * @access public
	 * @static
	 */
	public static function __callStatic(string $name,$arguments) {
		if(!is_array(self::$structure)) { throw new \Exception('Invalid configuration structure!'); }
		$element = get_array_param(self::$structure,$name,NULL,'is_array');
		if(!is_array($element)) { throw new \InvalidArgumentException("Undefined or invalid property [{$name}]!"); }
		$access = get_array_param($element,'access','readonly','is_notempty_string');
		$validation = get_array_param($element,'validation','','is_string');
		$result = NULL;
		if(is_array($arguments) && count($arguments)) {
			// is setter
			if($access!='public') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
			if(strlen($validation) && !validate_param($arguments[1],NULL,$validation,TRUE)) { throw new \InvalidArgumentException("Invalid value for property [{$name}]!"); }
			self::$data[$name] = $arguments[1];
		} else {
			if($access=='private') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
			// is getter
			$default = get_array_param($element,'default',NULL,'isset');
			$result = get_array_param(self::$data,$name,$default,$validation);
		}//if(is_array($arguments) && count($arguments))
		return $result;
	}//END public static function __callStatic
}//END class AppConfig
?>