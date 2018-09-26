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
	 * @var    array|null An array of instance configuration options
	 * @access private
	 */
	private static $instanceConfig = NULL;
	/**
	 * Initialize application configuration class (structure and data)
	 *
	 * @param array $data
	 * @param array $structure
	 * @throws \Exception
	 */
	public static function LoadConfig(array $data,array $structure) {
		require_once(__DIR__.'/paf_cfg_structure.php');
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
			if(strlen($validation) && !validate_param($arguments[0],NULL,$validation,TRUE)) { throw new \InvalidArgumentException("Invalid value for property [{$name}]!"); }
			self::$data[$name] = $arguments[0];
		} else {
			if($access=='private') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
			// is getter
			$default = get_array_param($element,'default',NULL,'isset');
			$result = get_array_param(self::$data,$name,$default,$validation);
		}//if(is_array($arguments) && count($arguments))
		return $result;
	}//END public static function __callStatic
	/**
     * @return bool
     * @access public
     * @static
     */
    public static function IsInstanceConfigLoaded(): bool {
        return isset(static::$instanceConfig);
    }//END public static function IsInstanceConfigLoaded
    /**
     * @param array  $config
     * @param string $contextIdField
     * @param bool   $raw
     * @return array
     * @access public
     * @static
     */
    public static function SetInstanceConfigData(array $config,bool $raw = TRUE,?string $contextIdField = NULL): array {
        if($raw) {
            static::$instanceConfig = [];
            foreach($config as $item) {
                $section = strtolower(get_array_param($item,'section','','is_string'));
                $option = strtolower(get_array_param($item,'option','','is_string'));
                if(!strlen($option)) { continue; }
                $contextId = get_array_param($item,$contextIdField??'',NULL,'is_integer');
                if(!isset(static::$instanceConfig[$section])) {
                    static::$instanceConfig[$section] = [];
                } elseif(!isset(static::$instanceConfig[$section][$option])) {
                    static::$instanceConfig[$section][$option] = [];
                }//if(!isset($result[$section]))
                static::$instanceConfig[$section][$option][(string)$contextId] = get_array_param($item,'value','','is_string');
            }//END foreach
        } else {
            static::$instanceConfig = $config;
        }//if($raw)
        return static::$instanceConfig;
	}//END public static function SetInstanceConfigData
	/**
     * @param string   $option
     * @param string   $section
     * @param int|null $contextId
     * @return string|null
     * @access public
     * @throws \Exception
     * @static
     */
    public static function GetInstanceOption(string $option,string $section = '',?int $contextId = NULL): ?string {
        $options = get_array_value(static::$instanceConfig,[strtolower($section),strtolower($option)],[],'is_array');
        $defValue = get_array_param($options,'',NULL,'is_string');
        if(is_null($contextId)) { return $defValue; }
        return get_array_value($options,$contextId,$defValue,'is_string');
	}//END public static function GetInstanceOption
}//END class AppConfig
?>