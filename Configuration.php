<?php
/**
 * PAF (PHP AJAX Framework) Configuration file
 *
 * Here are all the configuration parameters for PAF
 * Edit only values for the AppConfig class properties
 * and the User global required files section
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0
 * @filesource
 */
namespace PAF;
	if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }
	/** Require files for application startup */
	require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/helpers.php');
	if(version_compare(PHP_VERSION,'7.0.0')<0) {
		// require_once(_AAPP_ROOT_PATH.PAFConfig::GetPafAppRelativePath().'/random_compat.phar');
		require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/GibberishAES_5x.php');
	} else {
	require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/GibberishAES.php');
	}//if(version_compare(PHP_VERSION,'7.0.0')<0)
	require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/AppException.php');
	require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/Debugger.php');
	require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/App.php');
	require_once(_AAPP_ROOT_PATH.AppConfig::GetPafAppRelativePath().'/AjaxRequest.php');
	/**
	 * User global constants and required files load
	 *
	 * Enter here all global requires needed for your applications and they will be loaded automatically at startup
	 */

/**
 * AppConfig is the configuration holder for PAF
 *
 * AppConfig contains all the configuration parameters for PAF (PHP AJAX Framework)
 * Most of the properties are public. Those that are not, either have Getter/Setter function
 * or can not be changed during run.
 *
 * @package    AdeoTEK\PAF
 * @access     public
 * @abstract
 */
abstract class AppConfig {
//START Custom configuration params

//END Custom configuration params
//START Basic configuration
	/**
	 * @var        integer Request max duration in seconds
	 * @access     public
	 * @static
	 */
	public static $request_time_limit = 1800;
	/**
	 * @var        string Server timezone (php timezone accepted value)
	 * @access     public
	 * @static
	 */
	public static $server_timezone = 'Europe/Bucharest';
	/**
	 * @var        boolean Use output buffering via ob_start/ob_flush
	 * @access     public
	 * @static
	 */
	public static $bufferd_output = TRUE;
	/**
	 * @var        boolean Use internal cache system
	 * @access     public
	 * @static
	 */
	public static $x_cache = FALSE;
	/**
	 * @var    boolean Use database internal cache system
	 * @access public
	 * @static
	 */
	public static $x_db_cache = FALSE;
	/**
	 * @var    boolean Use Redis storage for internal cache system
	 * @access public
	 * @static
	 */
	public static $x_cache_redis = FALSE;
	/**
	 * @var    string Cache files path (absolute)
	 * @access public
	 * @static
	 */
	public static $x_cache_path = NULL;
	/**
	 * @var        boolean PAF cached calls separator
	 * @access     public
	 * @static
	 */
	public static $x_cache_separator = '![PAFC[';
	/**
	 * @var        boolean PAF cached arguments separator
	 * @access     public
	 * @static
	 */
	public static $x_cache_arg_separator = ']!PAFC!A![';
	/**
	 * @var        boolean Cookie login on/off
	 * @access     public
	 * @static
	 */
	public static $cookie_login = TRUE;
	/**
	 * @var        integer Valability of login cookie from last action (in days)
	 * @access     public
	 * @static
	 */
	public static $cookie_login_lifetime = 15;
//END Basic configuration
//START Session configuration
	/**
	 * @var        string Session name (NULL for default)
	 * @access     public
	 * @static
	 */
	public static $x_session_name = 'PHPSESSID';
	/**
	 * @var        boolean Use session splitting by window.name or not
	 * @access     public
	 * @static
	 */
	public static $split_session_by_page = TRUE;
	/**
	 * @var        boolean Use asynchronous session read/write
	 * @access     public
	 * @static
	 */
	public static $async_session = TRUE;
	/**
	 * @var        integer Session timeout in seconds
	 * @access     public
	 * @static
	 */
	public static $session_timeout = 3600;
	/**
	 * @var        boolean Use redis for session storage
	 * @access     public
	 * @static
	 */
	public static $session_redis = FALSE;
	/**
	 * @var        string Redis server connection string (host_name:port)
	 * @access     public
	 * @static
	 */
	public static $session_redis_server = 'tcp://127.0.0.1:6379?timeout=1&weight=1&database=0';
	/**
	 * @var        boolean Use memcached for session storage
	 * @access     public
	 * @static
	 */
	public static $session_memcached = FALSE;
	/**
	 * @var        string Memcache server connection string (host_name:port)
	 * @access     public
	 * @static
	 */
	public static $session_memcached_server = 'localhost:11211';
	/**
	 * @var        string Session file path. If left blank default php setting will be used (absolute or relative path).
	 * @access     public
	 * @static
	 */
	public static $session_file_path = 'tmp';
	/**
	 * @var    string Verification key for session data
	 * @access protected
	 * @static
	 */
	protected static $session_key = '14159265';
	/**
	 * @var    int Session array keys case: CASE_LOWER/CASE_UPPER or NULL for no case modification
	 * @access protected
	 * @static
	 */
	protected static $session_keys_case = CASE_LOWER;
//END Session configuration
//START PAF configuration
	/**
	 * @var        string PAF folder location ("public" or "application")
	 * @access     public
	 * @static
	 */
	public static $aapp_location = 'public';
	/**
	 * @var        string Relative path to PAF class (linux style)
	 * @access     public
	 * @static
	 */
	public static $aapp_path = '/src';
	/**
	 * @var        string Relative path to PAF javascript file (linux style)
	 * @access     public
	 * @static
	 */
	public static $aapp_js_path = '/src';
	/**
	 * @var        string Target file for PAF post (relative path from public folder + name)
	 * @access     public
	 * @static
	 */
	public static $aapp_target = 'aindex.php';
	/**
	 * @var        string PAF session key
	 * @access     protected
	 * @static
	 */
	protected static $aapp_session_key = 'AAPP_DATA';
	/**
	 * @var        string PAF implementig class name
	 * @access     protected
	 * @static
	 */
	protected static $aapp_class_name = 'MyAjaxRequest';
	/**
	 * @var        string PAF implementing class file (relative path + name)
	 * @access     protected
	 * @static
	 */
	protected static $aapp_class_file = '';
	/**
	 * @var        string PAF implementing class file path (relative)
	 * @access     protected
	 * @static
	 */
	protected static $aapp_class_file_path = '';
	/**
	 * @var        string PAF implementing class file name
	 * @access     protected
	 * @static
	 */
	protected static $aapp_class_file_name = 'MyAjaxRequest.php';
	/**
	 * @var        string Javascript on request completed callback
	 * @access     public
	 * @static
	 */
	public static $aapp_areq_js_callbak = NULL;
	/**
	 * @var        boolean Secure http support on/off
	 * @access     protected
	 * @static
	 */
	protected static $aapp_secure_http = TRUE;
	/**
	 * @var        boolean Parameters sent as value encryption on/off
	 * @access     protected
	 * @static
	 */
	protected static $aapp_params_encrypt = FALSE;
	/**
	 * @var        boolean Window name auto usage on/off
	 * @access     protected
	 * @static
	 */
	protected static $aapp_use_window_name = TRUE;
//END PAF configuration
//START Logs & errors reporting
	/**
	 * @var        boolean Debug mode on/off
	 * @access     public
	 * @static
	 */
	public static $debug = TRUE;
	/**
	 * @var        boolean Database debug mode on/off
	 * @access     public
	 * @static
	 */
	public static $db_debug = TRUE;
	/**
	 * @var        boolean Database debug to file on/off
	 * @access     public
	 * @static
	 */
	public static $db_debug2file = FALSE;
	/**
	 * @var        boolean Show debug invocation source file name and path in browser console on/off
	 * @access     public
	 * @static
	 */
	public static $console_show_file = TRUE;
	/**
	 * @var        boolean php console Chrome extension password
	 * @access     public
	 * @static
	 */
	protected static $phpconsole_password = '1234';
	/**
	 * @var        string Relative path to the logs folder
	 * @access     public
	 * @static
	 */
	public static $logs_path = '/.logs';
	/**
	 * @var        string Name of the main log file
	 * @access     public
	 * @static
	 */
	public static $log_file = 'app.log';
	/**
	 * @var        string Name of the errors log file
	 * @access     public
	 * @static
	 */
	public static $errors_log_file = 'errors.log';
	/**
	 * @var        string Name of the debugging log file
	 * @access     public
	 * @static
	 */
	public static $debug_log_file = 'debugging.log';
//END Logs & errors reporting
//////////DO NOT MODIFY BELOW THIS LINE !
	/**
	 * Gets the relative path to the PAF classes folder
	 *
	 * @return     string Returns the relative PAF path.
	 * @access     public
	 * @static
	 */
	public static function GetPafAppRelativePath() {
		return ((self::$aapp_location=='public' ? _AAP_PUBLIC_ROOT_PATH._AAP_PUBLIC_PATH : _AAPP_APPLICATION_PATH).self::$aapp_path);
	}//END public static function GetPafAppRelativePath
	/**
	 * GetNewUID method generates a new unique ID
	 *
	 * @param      string $salt A string to be added as salt to the generated unique ID (NULL and empty string means no salt will be used)
	 * @param      string $algorithm The name of the algorithm used for unique ID generation (possible values are those in hash_algos() array - see: http://www.php.net/manual/en/function.hash-algos.php)
	 * @param      bool $raw Sets return type: hexits for FALSE (default) or raw binary for TRUE
	 * @return     string Returns an unique ID as lowercase hex or raw binary representation if $raw is set to TRUE.
	 * @access     public
	 * @static
	 */
	public static function GetNewUID($salt = NULL,$algorithm = 'sha1',$notime = FALSE,$raw = FALSE) {
		if($notime) { return hash($algorithm,$salt,$raw); }
		return hash($algorithm,((is_string($salt) && strlen($salt)>0) ? $salt : '').uniqid(microtime().rand(),TRUE),$raw);
	}//END public static function GetNewUID
}//END abstract class AppConfig
?>
