<?php
/**
 * PAF (PHP AJAX Framework) Configuration file
 *
 * Here are all the configuration params for PAF
 * Edit only values for the PAFConfig class properties
 * and the User global required files section
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.0
 * @filesource
 */
	/** Require files for application startup */
	$app_absolute_path = PAFConfig::ExtractAbsolutePath();
	require_once($app_absolute_path.PAFConfig::$paf_path.'helpers.php');
	require_once($app_absolute_path.PAFConfig::$paf_path.'GibberishAES.php');
	require_once($app_absolute_path.PAFConfig::$paf_path.'class.applogger.php');
	require_once($app_absolute_path.PAFConfig::$paf_path.'class.paf.php');
	require_once($app_absolute_path.PAFConfig::$paf_path.'class.pafreq.php');
	/**
	 * User global required files load
	 *
	 * Enter here all global requires needed for your applications and they will be loaded automaticly at startup
	 */

	 
	 
	 //END User global required files load
	//END Require files for application startup
	/**
	 * PAFConfig is the configuration holder for PAF
	 *
	 * PAFConfig contains all the configuration parameters for PAF (PHP AJAX Framework)
	 * Most of the properties are public. Those that are not, either have Getter/Setter function
	 * or can not be changed during run.
	 *
 	 * @package    Hinter\PAF
	 * @access     public
	 * @abstract
	 */
	abstract class PAFConfig {
//START Custom configuration params



//END Custom configuration params
//START Basic configuration
		/**
		 * @var        string Relative path to this file (Linux style)
		 * @access     public
		 * @static
		 */
		public static $paf_config_path = '/';
		/**
		 * @var        integer Request max duration in seconds
		 * @access     public
		 * @static
		 */
		public static $request_time_limit = 1200;
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
		public static $bufferd_output = FALSE;
		/**
		 * @var        boolean Use internal cache system
		 * @access     public
		 * @static
		 */
		public static $cache = FALSE;
		/**
		 * @var        boolean PAF cached calls separator
		 * @access     public
		 * @static
		 */
		public static $cache_separator = '![PAFC[';
		/**
		 * @var        boolean PAF cached arguments separator
		 * @access     public
		 * @static
		 */
		public static $cache_arg_separator = ']!PAFC!A![';
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
		 * @var        integer Session timeout in seconds
		 * @access     public
		 * @static
		 */
		public static $session_timeout = 1800;
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
		public static $session_file_path = '/tmp';
		/**
		 * @var    string Verification key for session data
		 * @access protected
		 * @static
		 */
		protected static $session_key = 'a1b2c3d4';
//END Session configuration
//START PAF configuration
		/**
		 * @var        string Relative path to PAF class (linux style)
		 * @access     public
		 * @static
		 */
		public static $paf_path = '/paf/';
		/**
		 * @var        string Target file for PAF post (relative path + name)
		 * @access     public
		 * @static
		 */
		public static $paf_target = '/atarget.php';
		/**
		 * @var        string PAF session key
		 * @access     protected
		 * @static
		 */
		protected static $paf_session_key = 'PAF_DATA';
		/**
		 * @var        string PAF implementig class name
		 * @access     protected
		 * @static
		 */
		protected static $paf_class_name = 'XPAF';
		/**
		 * @var        string PAF implementing class file (relative path + name)
		 * @access     protected
		 * @static
		 */
		protected static $paf_class_file = '';
		/**
		 * @var        string PAF implementing class file path (relative)
		 * @access     protected
		 * @static
		 */
		protected static $paf_class_file_path = '/';
		/**
		 * @var        string PAF implementing class file name
		 * @access     protected
		 * @static
		 */
		protected static $paf_class_file_name = 'PafDispatcher.php';
		/**
		 * @var        boolean utf8 support on/off
		 * @access     public
		 * @static
		 */
		public static $paf_utf8 = TRUE;
		/**
		 * @var        boolean Secure http support on/off
		 * @access     protected
		 * @static
		 */
		protected static $paf_secure_http = TRUE;
		/**
		 * @var        boolean Parameters sent as value encryption on/off
		 * @access     protected
		 * @static
		 */
		//TODO: de modificat in TRUE la trecere in productie
		protected static $paf_params_encrypt = FALSE;
		/**
		 * @var        boolean Window name auto usage on/off
		 * @access     protected
		 * @static
		 */
		protected static $paf_use_window_name = TRUE;
//END PAF configuration
//START Logs & errors reporting
		/**
		 * @var        boolean Debug mode on/off
		 * @access     public
		 * @static
		 */
		public static $debug = TRUE;
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
		public static $logs_path = '/logs/';
		/**
		 * @var        bool Flag for enabling/disabling aplication logging
		 * @access     public
		 * @static
		 */
		public static $app_logging = TRUE;
		/**
		 * @var        string Name of the main log file
		 * @access     public
		 * @static
		 */
		public static $log_file = 'paf.log';
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
		public static $debugging_log_file = 'debugging.log';
//END Logs & errors reporting
//////////DO NOT MODIFY BELOW THIS LINE !
		/**
		 * ExtractAbsolutePath function returns the absolute path to the root of the application.
		 * For this to work properly, paf_config_path property must be set.
		 *
		 * @return     string Returns the absolute path for the root of the application.
		 * @access     public
		 * @static
		 */
		public static function ExtractAbsolutePath() {
			if(strlen(self::$paf_config_path)>0) {
				return realpath(dirname(__FILE__).str_repeat('/..',substr_count(ltrim(self::$paf_config_path,'/'),'/')));
			}//if(strlen(self::$paf_path)>0)
			return realpath(dirname(__FILE__));
		}//END public static function ExtractAbsolutePath
		/**
		 * GenerateUID function generates a GUID (unique id) as 40-character hexadecimal number.
		 *
		 * @param      string $salt A string to be added as salt to the generated unique id (NULL and empty string means no salt will be used)
		 * @param      string $algorithm The name of the algorithm used for GUID generation (posible values are those in hash_algos() array - see: http://www.php.net/manual/en/function.hash-algos.php)
		 * @param      bool $raw Sets return type: hexits for FALSE (default) or raw binary for TRUE
		 * @return     string Returns an unique id (GUID) as lowercase hexits or raw binary representation if $raw is set to TRUE.
		 * @access     public
		 * @static
		 */
		public static function GenerateUID($salt = NULL,$algorithm = 'sha1',$notime = FALSE,$raw = FALSE) {
			if($notime) { return hash($algorithm,$salt,$raw); }
			return hash($algorithm,((is_string($salt) && strlen($salt)>0) ? $salt : '').uniqid(microtime().rand(),TRUE),$raw);
		}//END public static function GenerateUID
	}//END abstract class PAFConfig
?>