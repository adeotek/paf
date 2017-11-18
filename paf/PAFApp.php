<?php
/**
 * PAF (PHP AJAX Framework) main class file.
 *
 * The PAF main class can be used for interacting with the session data, get (link) data and for debugging or application logging.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2010 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
 * @filesource
 */
 	define('PAF_URL_FORMAT_FRIENDLY_ORIGINAL',-1);
 	define('PAF_URL_FORMAT_URI_ONLY',0);
	define('PAF_URL_FORMAT_FRIENDLY',1);
	define('PAF_URL_FORMAT_FULL',2);
	define('PAF_URL_FORMAT_SHORT',3);
	/**
	 * PAF main class.
	 *
	 * PHP AJAX Framework main class, has to be instantiated at the entry point of the application with the static method GetInstance().
	 *
	 * @package  AdeoTEK\PAF
	 * @access   public
	 */
	class PAFApp extends PAFAppConfig {
		/**
		 * @var    Object Singleton unique instance
		 * @access protected
		 * @static
		 */
		protected static $_app_instance = NULL;
		/**
		 * @var        string The path included in the application URL
		 * @access     protected
		 * @static
		 */
		protected static $url_path = NULL;
		/**
		 * @var    array Session initial data
		 * @access public
		 */
		protected static $initial_data = NULL;
		/**
		 * @var    bool Flag for state of the session (started or not)
		 * @access protected
		 * @static
		 */
		protected static $session_started = FALSE;
		/**
		 * @var    bool Flag for output buffering (started or not)
		 * @access protected
		 * @static
		 */
		protected static $output_buffer_started = FALSE;
		/**
		 * @var    array Session data
		 * @access public
		 */
		public static $data = NULL;
		/**
		 * @var    bool State of session before current request (TRUE for existing session or FALSE for newly initialized)
		 * @access protected
		 */
		protected $_app_state = FALSE;
		/**
		 * @var    bool Flag for cleaning session data (if is set to TRUE, the session data will be erased on commit)
		 * @access protected
		 */
		protected $clear_session = FALSE;
		/**
		 * @var    string Application absolute path (auto-set on constructor)
		 * @access public
		 */
		protected $app_absolute_path = NULL;
		/**
		 * @var    PAFDebugger Object for debugging
		 * @access public
		 */
		public $debugger = NULL;
		/**
		 * @var    PAFReq Object for ajax requests processing
		 * @access public
		 */
		public $areq = NULL;
		/**
		 * @var    string Application non-public path (auto-set on constructor)
		 * @access public
		 */
		public $app_path = NULL;
		/**
		 * @var    string Application public path (auto-set on constructor)
		 * @access public
		 */
		public $app_public_path = NULL;
		/**
		 * @var    string Application base link (auto-set on constructor)
		 * @access public
		 */
		public $app_web_link = NULL;
		/**
		 * @var    string Application web protocol (http/https)
		 * @access public
		 */
		public $app_web_protocol = NULL;
		/**
		 * @var    string Application domain (auto-set on constructor)
		 * @access public
		 */
		public $app_domain = NULL;
		/**
		 * @var    string Application folder inside www root (auto-set on constructor)
		 * @access public
		 */
		public $url_folder = NULL;
		/**
		 * @var    bool Flag indicating if the session was started for the current request
		 * @access public
		 */
		public $with_session = NULL;
		/**
		 * @var    bool Flag to indicate if the request is ajax or not
		 * @access public
		 */
		public $ajax = FALSE;
		/**
		 * @var    bool Flag to indicate if the request should keep the session alive
		 * @access public
		 */
		public $keep_alive = TRUE;
		/**
		 * @var    string Sub-session key (page hash)
		 * @access public
		 */
		public $phash = NULL;
		/**
		 * @var    string Application base url: domain + path + url id (auto-set on constructor)
		 * @access public
		 */
		public $url_base = NULL;
		/**
		 * @var    array GET (URL) data
		 * @access public
		 */
		public $url_data = array();
		/**
		 * @var    array GET (URL) special parameters list
		 * @access public
		 */
		public $special_url_params = array('language','urlid');
		/**
		 * @var    string URL virtual path
		 * @access public
		 */
		public $url_virtual_path = NULL;

		/**
		 * Magic method for accessing non-static public members with static call
		 *
		 * If no arguments are provided, first tries to return the property with the given name
		 * and if such property doesn't exists or inaccessible, tries to call the public method with the given name
		 *
		 * @param  string $name Name of the member to be accessed
		 * @param  array  $arguments The arguments for accessing methods
		 * @return mixed Returns the property or method result
		 * @throws AException|InvalidArgumentException
		 * @access public
		 * @static
		 */
		public static function __callStatic($name,$arguments) {
			$class = get_called_class();
			if(!is_object(self::$_app_instance)) { throw new AException("Invalid class [{$class}] instance",E_ERROR); }
			// self::$_app_instance->Dlog($name,'__callStatic:name');
			// self::$_app_instance->Dlog($class,'__callStatic:class');
			$reflector = new \ReflectionClass($class);
			if(strpos($name,'_')!==0) {
				$member = $name;
				// self::$_app_instance->Dlog($member,'__callStatic:property');
				if($reflector->hasProperty($member)) {
					$property = $reflector->getProperty($member);
					if(!$property->isStatic() && $property->isPublic()) { return self::$_app_instance->{$member}; }
				}//if($reflector->hasProperty($member))
			} else {
				$member = substr($name,1);
				// self::$_app_instance->Dlog($member,'__callStatic:method');
				if($reflector->hasMethod($member)) {
					$method = $reflector->getMethod($member);
					if(!$method->isStatic() && $method->isPublic()) {
						// self::$_app_instance->Dlog($arguments,'__callStatic:arguments');
						if(preg_match('/^[DWEI]log$/i',$member) && (self::$console_show_file===TRUE || (isset($arguments[2]) && $arguments[2]===TRUE))) {
							$dbg = debug_backtrace();
							$caller = array_shift($dbg);
							$cfile = isset($caller['file']) ? $caller['file'] : '';
							$label = '['.(isset($arguments[3]) && $arguments[3]===TRUE ? $cfile : basename($cfile)).(isset($caller['line']) ? ':'.$caller['line'] : '').']'.(isset($arguments[1]) ? $arguments[1] : '');
							$larguments = array((isset($arguments[0]) ? $arguments[0] : NULL),$label);
						} else {
							$larguments = $arguments;
						}//if(in_array($method,['Dlog','Wlog','Elog','Ilog']) && (self::$console_show_file===TRUE || (isset($arguments[2]) && $arguments[2]===TRUE)))
						// self::$_app_instance->Dlog($larguments,'__callStatic:$larguments');
						return call_user_func_array(array(self::$_app_instance,$member),$larguments);
					}//if(!$method->isStatic() && $method->isPublic())
				}//if($reflector->hasMethod($member))
			}//if(strpos($name,'_')!==0)
			throw new InvalidArgumentException("Undefined or inaccessible property or method [{$member}]!");
		}//END public static function __callStatic
		/**
		 * Extracts the URL path of the application.
		 *
		 * @return     string Returns the URL path of the application.
		 * @access     protected
		 * @static
		 */
		protected static function ExtractUrlPath($startup_path = NULL) {
			if(strlen($startup_path)) {
				self::$url_path = str_replace('\\','/',(str_replace(_X_ROOT_PATH._X_WEB_ROOT_PATH,'',$startup_path)));
				self::$url_path = trim(str_replace(trim(self::$url_path,'/'),'',trim(dirname($_SERVER['SCRIPT_NAME']),'/')),'/');
				self::$url_path = (strlen(self::$url_path) ? '/'.self::$url_path : '')._X_PUBLIC_PATH;
			} else {
				self::$url_path = '/'.trim(dirname($_SERVER['SCRIPT_NAME']),'/');
			}//if(strlen($startup_path))
			return rtrim(self::$url_path,'/');
		}//END protected static function ExtractUrlPath
		/**
		 * Gets the base URL of the application.
		 *
		 * @param	   string $startup_path Startup absolute path
		 * @return     string Returns the base URL of the application.
		 * @access     public
		 * @static
		 */
		public static function GetRootUrl($startup_path = NULL) {
			$app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
			$app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			$url_folder = self::ExtractUrlPath($startup_path);
			return $app_web_protocol.$app_domain.$url_folder;
		}//END public static function GetRootUrl
		/**
		 * Classic singleton method for retrieving the PAF object
		 *
		 * @param  bool $ajax Optional flag indicating whether is an ajax request or not
		 * @param  array $params An optional key-value array containing to be assigned to non-static properties
		 * (key represents name of the property and value the value to be assigned)
		 * @param  bool $session_init Flag indicating if session should be started or not
		 * @param  bool $do_not_keep_alive Flag indicating if session should be kept alive by the current request
		 * @param  bool $shell Shell mode on/off
		 * @return PAF Returns the PAF instance
		 * @access public
		 * @static
		 */
		public static function GetInstance($ajax = FALSE,$params = array(),$session_init = TRUE,$do_not_keep_alive = NULL,$shell = FALSE) {
			if($session_init && !self::$session_started) {
				$cdir = self::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
				self::SessionStart($cdir,$do_not_keep_alive);
				self::$data = $_SESSION;
				self::$initial_data = self::$data;
				if(self::$async_session && $ajax) { self::SessionClose(); }
			}//if($session_init && !self::$session_started)
			if(is_null(self::$_app_instance)) {
				$class_name = get_called_class();
				self::$_app_instance = new $class_name($ajax,$params,$session_init,$do_not_keep_alive,$shell);
			}//if(is_null(self::$_app_instance))
			return self::$_app_instance;
		}//END public static function GetInstance
		/**
		 * Method for returning the static instance property
		 *
		 * @return object Returns the value of $_app_instance property
		 * @access public
		 * @static
		 */
		public static function GetCurrentInstance() {
			return self::$_app_instance;
		}//END public static function GetCurrentInstance
		/**
		 * Static setter for phash property
		 *
		 * @param  string $value The new value for phash property
		 * @return void
		 * @access public
		 * @static
		 */
		public static function SetPhash($value) {
			self::$_app_instance->phash = $value;
		}//END public static function SetPhash
		/**
		 * Convert a string to the session keys case (set in configuration)
		 *
		 * @param  string $input The string to be converted to the session case
		 * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
		 * FALSE - do not change case, NULL - use the configuration value
		 * @return string The value converted to the session case
		 * @access public
		 * @static
		 */
		public static function ConvertToSessionCase($input,$keys_case = NULL) {
			if($keys_case===FALSE) { return $input; }
			if(is_array($input)) {
				$linput = array();
				foreach($input as $k=>$v) { $linput[$k] = self::ConvertToSessionCase($v,$keys_case); }
				return $linput;
			}//if(is_array($input))
			if(!is_string($input)) { return $input; }
			switch(is_numeric($keys_case) ? $keys_case : self::$session_keys_case) {
				case CASE_LOWER:
					return strtolower($input);
				case CASE_UPPER:
					return strtoupper($input);
				default:
					return $input;
			}//END switch
		}//END public static function ConvertToSessionCase
		/**
		 * Set session configuration
		 *
		 * @return void
		 * @access public
		 * @static
		 */
		public static function ConfigAndStartSession($absolute_path,$domain,$session_id = NULL) {
			self::$session_started = FALSE;
			ErrorHandler::$silent_mode = TRUE;
			$errors = [];
			$dbg_data = '';
			ini_set('session.use_cookies',1);
			ini_set('session.cookie_lifetime',0);
			ini_set('session.cookie_domain',$domain);
			ini_set('session.gc_maxlifetime',self::$session_timeout);
			ini_set('session.cache_expire',self::$session_timeout/60);
			if(self::$session_redis===TRUE) {
				if(class_exists('Redis',FALSE)) {
					try {
						ini_set('session.save_handler','redis');
						ini_set('session.save_path',self::$session_redis_server);
						ini_set('session.cache_expire',intval(self::$session_timeout/60));
						if(is_string(self::$x_session_name) && strlen(self::$x_session_name)) { session_name(self::$x_session_name); }
						if(is_string($session_id) && strlen($session_id)) {
							session_id($session_id);
							$dbg_data .= 'Set new session id: '.$session_id."\n";
						}//if(is_string($session_id) && strlen($session_id))
						session_start();
					} catch(Exception $e) {
						$errors[] = ['errstr'=>$e->getMessage(),'errno'=>$e->getCode(),'errfile'=>$e->getFile(),'errline'=>$e->getLine()];
					} finally {
						if(ErrorHandler::HasErrors()) {
							$eh_errors = ErrorHandler::GetErrors(TRUE);
							$errors = array_merge($errors,$eh_errors);
						}//if(ErrorHandler::HasErrors())
						if(count($errors)>0) {
							self::$session_started = FALSE;
							self::AddToLog(print_r($errors,1),$absolute_path.self::$logs_path.'/'.self::$errors_log_file);
							$dbg_data .= 'Session start [handler: Redis] errors: '.print_r($errors,1)."\n";
						} else {
							self::$session_started = TRUE;
							$dbg_data .= 'Session start done [handler: Redis]'."\n";
						}//if(count($errors)>0)
					}//try
				}//if(class_exists('Redis',FALSE))
			}//if(self::$session_redis===TRUE)
			if(!self::$session_started && self::$session_memcached===TRUE) {
				$errors = [];
				if(class_exists('Memcached',FALSE)) {
					try {
						ini_set('session.save_handler','memcached');
						ini_set('session.save_path',self::$session_memcached_server);
						ini_set('session.cache_expire',intval(self::$session_timeout/60));
						if(is_string(self::$x_session_name) && strlen(self::$x_session_name)) { session_name(self::$x_session_name); }
						if(is_string($session_id) && strlen($session_id)) {
							session_id($session_id);
							$dbg_data .= 'Set new session id: '.$session_id."\n";
						}//if(is_string($session_id) && strlen($session_id))
						session_start();
					} catch(Exception $e) {
						$errors[] = ['errstr'=>$e->getMessage(),'errno'=>$e->getCode(),'errfile'=>$e->getFile(),'errline'=>$e->getLine()];
					} finally {
						if(ErrorHandler::HasErrors()) {
							$eh_errors = ErrorHandler::GetErrors(TRUE);
							$errors = array_merge($errors,$eh_errors);
						}//if(ErrorHandler::HasErrors())
						if(count($errors)>0) {
							self::$session_started = FALSE;
							self::AddToLog(print_r($errors,1),$absolute_path.self::$logs_path.'/'.self::$errors_log_file);
							$dbg_data .= 'Session start [handler: Memcached] errors: '.print_r($errors,1)."\n";
						} else {
							self::$session_started = TRUE;
							$dbg_data .= 'Session start done [Memcached: Redis]'."\n";
						}//if(count($errors)>0)
					}//try
				} elseif(class_exists('Memcache',FALSE)) {
					try {
						ini_set('session.save_handler','memcache');
						ini_set('session.save_path',self::$session_memcached_server);
						ini_set('session.cache_expire',intval(self::$session_timeout/60));
						if(is_string(self::$x_session_name) && strlen(self::$x_session_name)) { session_name(self::$x_session_name); }
						if(is_string($session_id) && strlen($session_id)) {
							session_id($session_id);
							$dbg_data .= 'Set new session id: '.$session_id."\n";
						}//if(is_string($session_id) && strlen($session_id))
						session_start();
					} catch(Exception $e) {
						$errors[] = ['errstr'=>$e->getMessage(),'errno'=>$e->getCode(),'errfile'=>$e->getFile(),'errline'=>$e->getLine()];
					} finally {
						if(ErrorHandler::HasErrors()) {
							$eh_errors = ErrorHandler::GetErrors(TRUE);
							$errors = array_merge($errors,$eh_errors);
						}//if(ErrorHandler::HasErrors())
						if(count($errors)>0) {
							self::$session_started = FALSE;
							self::AddToLog(print_r($errors,1),$absolute_path.self::$logs_path.'/'.self::$errors_log_file);
							$dbg_data .= 'Session start [handler: Memcache] errors: '.print_r($errors,1)."\n";
						} else {
							self::$session_started = TRUE;
							$dbg_data .= 'Session start done [Memcache: Redis]'."\n";
						}//if(count($errors)>0)
					}//try
				}//if(class_exists('Memcached',FALSE))
			}//if(!$initialized && self::$session_memcached===TRUE)
			ErrorHandler::$silent_mode = FALSE;
			if(!self::$session_started) {
				ini_set('session.save_handler','files');
				if(strlen(self::$session_file_path)>0) {
					if((substr(self::$session_file_path,0,1)=='/' || substr(self::$session_file_path,1,2)==':\\') && file_exists(self::$session_file_path)) {
						session_save_path(self::$session_file_path);
					} elseif(file_exists($absolute_path.'/'.self::$session_file_path)) {
						session_save_path($absolute_path.'/'.self::$session_file_path);
					}//if((substr(self::$session_file_path,0,1)=='/' || substr(self::$session_file_path,1,2)==':\\') && file_exists(self::$session_file_path))
				}//if(strlen(self::$session_file_path)>0)
				if(is_string(self::$x_session_name) && strlen(self::$x_session_name)) { session_name(self::$x_session_name); }
				if(is_string($session_id) && strlen($session_id)) {
					session_id($session_id);
					$dbg_data .= 'Set new session id: '.$session_id."\n";
				}//if(is_string($session_id) && strlen($session_id))
				session_start();
				self::$session_started = TRUE;
				$dbg_data .= 'Session started [handler: Files]'."\n";
			}//if(!$initialized)
			return $dbg_data;
		}//END public static function ConfigAndStartSession
		/**
		 * Initiate/reinitiate session and read session data
		 *
		 * @return void
		 * @access public
		 * @static
		 */
		public static function SessionStart($path = '',$do_not_keep_alive = NULL) {
			$dbg_data = '>> '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'console')."\n";
			$dbg_data .= 'Session started: '.(self::$session_started ? 'TRUE' : 'FALSE')."\n";
			$absolute_path = _X_ROOT_PATH._X_APP_PATH;
			$cremoteaddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
			$cdomain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			$cfulldomain = $cdomain.$path;
			$cuseragent = array_key_exists('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN USER AGENT';
			if(!self::$session_started) { $dbg_data .= self::ConfigAndStartSession($absolute_path,$cdomain); }
			$dbg_data .= 'Session ID: '.session_id()."\n";
			$dbg_data .= 'Session age: '.(isset($_SESSION['X_SCAT']) ? (time()-$_SESSION['X_SCAT']) : 'N/A')."\n";
			$dbg_data .= 'Last request: '.(isset($_SESSION['X_SEXT']) ? (time()-$_SESSION['X_SEXT']) : 'N/A')."\n";
			$dbg_data .= 'X_SKEY: '.(isset($_SESSION['X_SKEY']) ? $_SESSION['X_SKEY'] : 'N/A')."\n";
	        if(!isset($_SESSION['X_SEXT']) || !isset($_SESSION['X_SKEY']) || ($_SESSION['X_SEXT']+self::$session_timeout)<time() || $_SESSION['X_SKEY']!=self::GenerateUID(self::$session_key.session_id(),'sha256',TRUE)) {
	            $dbg_data .= 'Do: SESSION RESET'."\n";
	        	$_SESSION = array();
			    setcookie(session_name(),'',time()-4200,'/',$cdomain);
				session_destroy();
				ini_set('session.use_cookies',1);
				ini_set('session.cookie_lifetime',0);
				ini_set('cookie_domain',$cdomain);
				ini_set('session.gc_maxlifetime',self::$session_timeout);
				ini_set('session.cache_expire',self::$session_timeout/60);
				$new_session_id = self::GenerateUID($cfulldomain.$cuseragent.$cremoteaddress,'sha256');
				$dbg_data .= self::ConfigAndStartSession($absolute_path,$cdomain,$new_session_id);
				$_SESSION['X_SCAT'] = time();
				$_SESSION['SESSION_ID'] = session_id();
				$dbg_data .= 'Session ID (new): '.session_id()."\n";
			}//if(!isset($_SESSION['X_SEXT']) || !isset($_SESSION['X_SKEY']) || ($_SESSION['X_SEXT']+self::$session_timeout)<time() || $_SESSION['X_SKEY']!=self::GenerateUID(self::$session_key.session_id(),'sha256',TRUE))
			set_time_limit(self::$request_time_limit);
			$_SESSION['X_SKEY'] = self::GenerateUID(self::$session_key.session_id(),'sha256',TRUE);
			$dbg_data .= 'Do not keep alive: '.($do_not_keep_alive!==TRUE && $do_not_keep_alive!==1 ? 'FALSE' : 'TRUE')."\n";
			if($do_not_keep_alive!==TRUE && $do_not_keep_alive!==1) { $_SESSION['X_SEXT'] = time(); }
			// vprint($dbg_data);
			// self::AddToLog($dbg_data,$absolute_path.self::$logs_path.'/'.self::$debugging_log_file);
	    }//END public static function SessionStart
	    /**
		 * Close session for write
		 *
		 * @return void
		 * @access public
		 * @static
		 */
		public static function SessionClose($write = TRUE) {
			if(!self::$session_started) { return; }
			if($write) {
				session_write_close();
			} else {
				session_abort();
			}//if($write)
			self::$session_started = FALSE;
		}//END public static function SessionClose
		/**
		 * PAF constructor function
		 *
		 * @param  bool $ajax Optional flag indicating whether is an ajax request or not
		 * @param  array $params An optional key-value array containing to be assigned to non-static properties
		 * (key represents name of the property and value the value to be assigned)
		 * @param  bool $with_session Start PHP session (default FALSE)
		 * @param  bool $do_not_keep_alive Do not keep alive user session
		 * @param  bool $shell Shell mode on/off
		 * @return void
		 * @access protected
		 */
		protected function __construct($ajax = FALSE,$params = array(),$with_session = FALSE,$do_not_keep_alive = NULL,$shell = FALSE) {
			$this->app_absolute_path = _X_ROOT_PATH;
			$this->app_path = _X_ROOT_PATH._X_APP_PATH;
			$this->app_public_path = _X_ROOT_PATH._X_WEB_ROOT_PATH._X_PUBLIC_PATH;
			$this->ajax = $ajax;
			$this->with_session = $with_session;
			$this->keep_alive = $do_not_keep_alive ? FALSE : TRUE;
			if($shell) {
				self::$data = array();
				$this->_paf_state = TRUE;
				$this->app_domain = trim(get_array_param($_GET,'domain','','is_string'),' /\\');
				if(strlen($this->app_domain)) {
					$this->app_web_protocol = trim(get_array_param($_GET,'protocol','http','is_notempty_string'),' /:\\').'://';
					$this->url_folder = trim(get_array_param($_GET,'uri_path','','is_string'),' /\\');
					$this->app_web_link = $this->app_web_protocol.$this->app_domain.(strlen($this->url_folder) ? '/' : '').$this->url_folder;
				}//if(strlen($this->app_domain))
			} else {
				$this->_paf_state = $with_session ? isset(self::$data) : TRUE;
				$this->app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
				$this->app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
				$this->url_folder = self::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
				$this->app_web_link = $this->app_web_protocol.$this->app_domain.$this->url_folder;
				if(self::$split_session_by_page) {
					$this->phash = get_array_param($_GET,'phash',get_array_param($_POST,'phash',NULL,'is_notempty_string'),'is_notempty_string');
					if(!$this->phash) {
						$this->phash = is_array($_COOKIE) && array_key_exists('__x_pHash_',$_COOKIE) && strlen($_COOKIE['__x_pHash_']) && strlen($_COOKIE['__x_pHash_'])>12 ? substr($_COOKIE['__x_pHash_'],0,-12) : NULL;
					}//if(!$this->phash)
					if(!$this->phash ) { $this->phash = XSession::GenerateUID(); }
				}//if(self::$split_session_by_page)
				$uri_len = strpos($_SERVER['REQUEST_URI'],'?')!==FALSE ? strpos($_SERVER['REQUEST_URI'],'?') : (strpos($_SERVER['REQUEST_URI'],'#')!==FALSE ? strpos($_SERVER['REQUEST_URI'],'#') : strlen($_SERVER['REQUEST_URI']));
				$this->url_base = $this->app_web_protocol.$this->app_domain.substr($_SERVER['REQUEST_URI'],0,$uri_len);
				$this->url_data = is_array($_GET) ? $this->SetUrlParams($_GET) : array();
			}//if($shell)
			if(is_array($params) && count($params)>0) {
				foreach($params as $key=>$value) {
					if(property_exists($this,$key)) {
						$prop = new ReflectionProperty($this,$key);
						if(!$prop->isStatic()) {
							$this->$key = $value;
						} else {
							$this::$$key = $value;
						}//if(!$prop->isStatic())
					}//if(property_exists($this,$key))
				}//foreach($params as $key=>$value)
			}//if(is_array($params) && count($params)>0)
			if($shell) { return; }
			$this->DebuggerStart();
			$this->StartOutputBuffer();
		}//END protected function __construct
		/**
		 * Commit the temporary session into the session
		 *
		 * @param  bool  $clear If TRUE is passed the session will be cleared
		 * @param  bool  $preserve_output_buffer If true output buffer is preserved
		 * @param  bool $show_errors Display errors TRUE/FALSE
		 * @param  string $key Session key to commit (do partial commit)
		 * @param  string $phash Page (tab) hash
		 * @poaram bool $reload Reload session after commit (default TRUE)
		 * @return void
		 * @access public
		 */
		public function SessionCommit($clear = FALSE,$preserve_output_buffer = FALSE,$show_errors = TRUE,$key = NULL,$phash = NULL,$reload = TRUE) {
			if(!$this->with_session) {
				if($show_errors && method_exists('ErrorHandler','ShowErrors')) { ErrorHandler::ShowErrors(); }
				if($preserve_output_buffer!==TRUE) { $this->FlushOutputBuffer(); }
				return;
			}//if(!$this->with_session)
			if(!is_array(self::$data)) { self::$data = array(); }
			$lphash = isset($phash) ? $phash : $this->phash;
			if(!self::$session_started) { session_start(); }
			if($clear===TRUE || $this->clear_session===TRUE) {
				if(strlen($key)) {
					if(strlen($phash)) {
						unset(self::$initial_data[$key][$phash]);
						unset(self::$data[$key][$phash]);
						unset($_SESSION[$key][$phash]);
					} else {
						unset(self::$initial_data[$key]);
						unset(self::$data[$key]);
						unset($_SESSION[$key]);
					}//if(strlen($phash))
				} else {
					if(strlen($phash)) {
						unset(self::$initial_data[$phash]);
						unset(self::$data[$phash]);
						unset($_SESSION[$phash]);
					} else {
						self::$initial_data = NULL;
						self::$data = NULL;
						unset($_SESSION);
					}//if(strlen($phash))
				}//if(strlen($key))
			} else {
				if(strlen($key)) {
					if(strlen($phash)) {
						$lvalue = (array_key_exists($key,self::$data) && is_array(self::$data[$key]) && array_key_exists($phash,self::$data[$key])) ? self::$data[$key][$phash] : NULL;
						$li_arr = (array_key_exists($key,self::$initial_data) && is_array(self::$initial_data[$key]) && array_key_exists($phash,self::$initial_data[$key])) ? self::$initial_data[$key][$phash] : NULL;
						if(array_key_exists($key,$_SESSION) && is_array($_SESSION[$key]) && array_key_exists($phash,$_SESSION[$key])) {
							$_SESSION[$key][$phash] = custom_array_merge($_SESSION[$key][$phash],$lvalue,TRUE,$li_arr);
						} else {
							$_SESSION[$key][$phash] = $lvalue;
						}//if(array_key_exists($key,$_SESSION) && is_array($_SESSION[$key]) && array_key_exists($phash,$_SESSION[$key]))
					} else {
						$lvalue = array_key_exists($key,self::$data) ? self::$data[$key] : NULL;
						$li_arr = array_key_exists($key,self::$initial_data) ? self::$initial_data[$key] : NULL;
						if(array_key_exists($key,$_SESSION)) {
							$_SESSION[$key] = custom_array_merge($_SESSION[$key],$lvalue,TRUE,$li_arr);
						} else {
							$_SESSION[$key] = $lvalue;
						}//if(array_key_exists($key,$_SESSION))
					}//if(strlen($phash))
				} else {
					if(strlen($phash)) {
						$lvalue = array_key_exists($phash,self::$data) ? self::$data[$phash] : NULL;
						$li_arr = is_array(self::$initial_data) && array_key_exists($phash,self::$initial_data) ? self::$initial_data[$phash] : NULL;
						if(array_key_exists($phash,$_SESSION)) {
							$_SESSION[$phash] = custom_array_merge($_SESSION[$phash],$lvalue,TRUE,$li_arr);
						} else {
							$_SESSION[$phash] = $lvalue;
						}//if(array_key_exists($phash,$_SESSION))
					} else {
						$_SESSION = custom_array_merge($_SESSION,self::$data,TRUE,self::$initial_data);
					}//if(strlen($phash))
				}//if(strlen($key))
				if($reload) {
					self::$data = $_SESSION;
					self::$initial_data = self::$data;
				}//if($reload)
			}//($clear===TRUE || $this->clear_session===TRUE)
			if(!self::$session_started) { session_write_close(); }
			if($show_errors && method_exists('ErrorHandler','ShowErrors')) { ErrorHandler::ShowErrors(); }
			if($preserve_output_buffer!==TRUE) { $this->FlushOutputBuffer(); }
		}//END public function SessionCommit
		/**
		 * Gets the session state befor current request (TRUE for existing session or FALSE for newly initialized)
		 *
		 * @return bool Session state (TRUE for existing session or FALSE for newly initialized)
		 * @access public
		 */
		public function GetSessionState() {
			return $this->_paf_state;
		}//END public function GetSessionState
		/**
		 * Set clear session flag (on commit session will be cleared)
		 *
		 * @return void
		 * @access public
		 */
		public function ClearSession() {
			$this->clear_session = TRUE;
		}//END public function ClearSession
		/**
		 * Gets application absolute path
		 *
		 * @return string Returns the application absolute path
		 * @access public
		 */
		public function GetAppAbsolutePath() {
			return $this->app_absolute_path;
		}//END public function GetAppAbsolutePath
		/**
		 * Gets a session parameter at a certain path (path = a succession of keys of the session data array)
		 *
		 * @param  string $key The key of the searched parameter
		 * @param  string $path An array containing the succession of keys for the searched parameter
		 * @param  array $data The session data array to be searched
		 * @return mixed Value of the parameter if it exists or NULL
		 * @access protected
		 */
		protected function GetCustomParam($key,$path,$data) {
			if(!is_array(self::$data)) { return NULL; }
			if(is_array($path) && count($path)) {
				$lpath = array_shift($path);
				if(!strlen($lpath) || !array_key_exists($lpath,$data)) { return NULL; }
				return $this->GetCustomParam($key,$path,$data[$lpath]);
			}//if(is_array($path) && count($path))
			if(is_string($path) && strlen($path)) {
				if(!array_key_exists($path,$data) || !is_array($data[$path])) { return NULL; }
				return array_key_exists($key,$data[$path]) ? $data[$path][$key] : NULL;
			}//if(is_string($path) && strlen($path))
			return array_key_exists($key,$data) ? $data[$key] : NULL;
		}//END protected function GetCustomParam
		/**
		 * Get a global parameter (a parameter from first level of the array) from the session data array
		 *
		 * @param  string $key The key of the searched parameter
		 * @param  string $phash The page hash (default NULL)
		 * If FALSE is passed, the main (XSession property) page hash will not be used
		 * @param  string $path An array containing the succession of keys for the searched parameter
		 * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
		 * FALSE - do not change case, NULL - use the configuration value
		 * @return mixed Returns the parameter value or NULL
		 * @access public
		 */
		public function GetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
			if(!is_array(self::$data)) { return NULL; }
			$lphash = isset($phash) ? $phash : $this->phash;
			$lkey = self::ConvertToSessionCase($key,$keys_case);
			$lpath = self::ConvertToSessionCase($path,$keys_case);
			if($lphash) {
				if(!array_key_exists($lphash,self::$data)) { return NULL; }
				if(isset($lpath)) { return $this->GetCustomParam($lkey,$lpath,self::$data[$lphash]); }
				return (array_key_exists($lkey,self::$data[$lphash]) ? self::$data[$lphash][$lkey] : NULL);
			}//if($lphash)
			if($lpath) { return $this->GetCustomParam($key,$lpath,self::$data); }
			return (array_key_exists($lkey,self::$data) ? self::$data[$lkey] : NULL);
		}//END public function GetGlobalParam
		/**
		 * Set a global parameter (a parameter from first level of the array) from the session data array
		 *
		 * @param  string $key The key of the searched parameter
		 * @param  mixed  $val The value to be set
		 * @param  string $phash The page hash (default NULL)
		 * If FALSE is passed, the main (XSession property) page hash will not be used
		 * @param  string $path An array containing the succession of keys for the searched parameter
		 * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
		 * FALSE - do not change case, NULL - use the configuration value
		 * @return bool Returns TRUE on success or FALSE otherwise
		 * @access public
		 */
		public function SetGlobalParam($key,$val,$phash = NULL,$path = NULL,$keys_case = NULL) {
			if(!is_array(self::$data)) { self::$data = array(); }
			$lphash = isset($phash) ? $phash : $this->phash;
			$lkey = self::ConvertToSessionCase($key,$keys_case);
			$lpath = self::ConvertToSessionCase($path,$keys_case);
			if(isset($lpath)) {
				if(is_array($lpath) && count($lpath)) {
					$part_arr = array($lkey=>$val);
					foreach(array_reverse($lpath) as $k) { $part_arr = array($k=>$part_arr); }
					if($lphash) {
						self::$data[$lphash] = custom_array_merge(self::$data[$lphash],$part_arr,TRUE);
					} else {
						self::$data = custom_array_merge(self::$data,$part_arr,TRUE);
					}//if($lphash)
					return TRUE;
				}//if(is_array($path) && count($path))1
				if(is_string($lpath) && strlen($lpath)) {
					if($lphash) {
						self::$data[$lphash][$lpath][$lkey] = $val;
					} else {
						self::$data[$lpath][$lkey] = $val;
					}//if($lphash)
					return TRUE;
				}//if(is_string($path) && strlen($path))
				return FALSE;
			}//if(isset($path))
			if($lphash) {
				self::$data[$lphash][$lkey] = $val;
			} else {
				self::$data[$lkey] = $val;
			}//if($lphash)
			return TRUE;
		}//END public function SetGlobalParam
		/**
		 * Delete a global parameter (a parameter from first level of the array) from the session data array
		 *
		 * @param  string $key The key of the searched parameter
		 * @param  string $phash The page hash (default NULL)
		 * If FALSE is passed, the main (XSession property) page hash will not be used
		 * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
		 * FALSE - do not change case, NULL - use the configuration value
		 * @return void
		 * @access public
		 */
		public function UnsetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
			if(!is_array(self::$data)) { return TRUE; }
			$lphash = isset($phash) ? $phash : $this->phash;
			$lkey = self::ConvertToSessionCase($key,$keys_case);
			$lpath = self::ConvertToSessionCase($path,$keys_case);
			if(isset($lpath)) {
				if(is_array($lpath) && count($lpath)) {
					$part_arr = array($lkey=>NULL);
					foreach(array_reverse($lpath) as $k) { $part_arr = array($k=>$part_arr); }
					if($lphash) {
						self::$data[$lphash] = custom_array_merge(self::$data[$lphash],$part_arr,TRUE);
					} else {
						self::$data = custom_array_merge(self::$data,$part_arr,TRUE);
					}//if($lphash)
					return TRUE;
				}//if(is_array($path) && count($path))1
				if(is_string($lpath) && strlen($lpath)) {
					if($lphash) {
						unset(self::$data[$lphash][$lpath][$lkey]);
					} else {
						unset(self::$data[$lpath][$lkey]);
					}//if($lphash)
					return TRUE;
				}//if(is_string($path) && strlen($path))
				return FALSE;
			}//if(isset($path))
			if($lphash) {
				unset(self::$data[$lphash][$lkey]);
			} else {
				unset(self::$data[$lkey]);
			}//if($lphash)
			return TRUE;
		}//END public function UnsetGlobalParam

		public function OutputBufferStarted() {
			return self::$output_buffer_started;
		}//END public function OutputBufferStarted

		public function StartOutputBuffer() {
			if(!$this->ajax && !self::$bufferd_output && !$this->debugger) { return FALSE; }
			ob_start();
			return (self::$output_buffer_started = TRUE);
		}//END public function StartOutputBuffer

		public function FlushOutputBuffer($end = FALSE) {
			if(!self::$output_buffer_started) { return FALSE; }
			if($end===TRUE) {
				ob_end_flush();
				self::$output_buffer_started = FALSE;
			} else {
				ob_flush();
			}//if($end===TRUE)
			return TRUE;
		}//END public function FlushOutputBuffer

		public function GetOutputBufferContent($clear = TRUE) {
			if(!self::$output_buffer_started) { return FALSE; }
			if($clear===TRUE) {
				$content = ob_get_clean();
			} else {
				$content = ob_get_contents();
			}//if($clear===TRUE)
			return $content;
		}//END public function GetOutputBufferContent

		public function ClearOutputBuffer($end = FALSE) {
			if(!self::$output_buffer_started) { return FALSE; }
			if($end===TRUE) {
				ob_end_clean();
				self::$output_buffer_started = FALSE;
			} else {
				ob_clean();
			}//if($end===TRUE)
			return TRUE;
		}//END public function ClearOutputBuffer
		/**
		 * Initialize ARequest object
		 *
		 * @param  array $post_params Default parameters to be send via post on ajax requests
		 * @param  string $subsession Sub-session key/path
		 * @param  bool $js_init Do javascript initialization
		 * @param  bool $with_output If TRUE javascript is outputted, otherwise is returned
		 * @return void
		 * @access public
		 */
		public function ARequestInit($post_params = array(),$subsession = NULL,$js_init = TRUE,$with_output = TRUE) {
			if(!is_object($this->areq)) {
				$this->areq = new PAFReq($this,$subsession);
				$this->areq->SetPostParams($post_params);
			}//if(!is_object($this->areq))
			if($js_init!==TRUE) { return TRUE; }
			return $this->areq->jsInit($with_output);
		}//END public function ARequestInit
		/**
		 * Execute a method of the ARequest implementing class in an ajax request
		 *
		 * @param  array $post_params Parameters to be send via post on ajax requests
		 * @param  string $subsession Sub-session key/path
		 * @return void
		 * @access public
		 */
		public function ExecuteARequest($post_params = array(),$subsession = NULL) {
			$errors = '';
			$request = array_key_exists('req',$_POST) ? $_POST['req'] : NULL;
			if(!$request) { $errors .= 'Empty Request!'; }
			$php = NULL;
			$session_id = NULL;
			$request_id = NULL;
			$with_utf8 = TRUE;
			$class_file = NULL;
			$class = NULL;
			$function = NULL;
			$requests = NULL;
			if(!$errors) {
				/* Start session and set ID to the expected paf session */
				list($php,$session_id,$request_id) = explode(PAFReq::$paf_req_separator,$request);
				/* Validate this request */
				$spath = array(
					$this->current_namespace,
					self::ConvertToSessionCase(self::$paf_session_key,PAFReq::$paf_session_keys_case),
					self::ConvertToSessionCase('PAF_REQUEST',PAFReq::$paf_session_keys_case),
				);
				$requests = $this->GetGlobalParam(PAF::ConvertToSessionCase('REQUESTS',PAFReq::$paf_session_keys_case),FALSE,$spath,FALSE);
				if(GibberishAES::dec(rawurldecode($session_id),self::$session_key)!=session_id() || !is_array($requests)) {
					$errors .= 'Invalid Request!';
				} elseif(!in_array(self::ConvertToSessionCase($request_id,PAFReq::$paf_session_keys_case),array_keys($requests))) {
					$errors .= 'Invalid Request Data!';
				}//if(GibberishAES::dec(rawurldecode($session_id),self::$session_key)!=session_id() || !is_array($requests))
			}//if(!$errors)
			if(!$errors) {
				/* Get function name and process file */
				$REQ = $requests[self::ConvertToSessionCase($request_id,PAFReq::$paf_session_keys_case)];
				$with_utf8 = $REQ[self::ConvertToSessionCase('UTF8',PAFReq::$paf_session_keys_case)];
				$function = $REQ[self::ConvertToSessionCase('FUNCTION',PAFReq::$paf_session_keys_case)];
				$lkey = self::ConvertToSessionCase('CLASS_FILE',PAFReq::$paf_session_keys_case);
				$class_file = (array_key_exists($lkey,$REQ) && $REQ[$lkey]) ? $REQ[$lkey] : (self::$paf_class_file ? self::$paf_class_file : $this->app_path.self::$paf_class_file_path.'/'.self::$paf_class_file_name);
				$lkey = self::ConvertToSessionCase('CLASS',PAFReq::$paf_session_keys_case);
				$class = (array_key_exists($lkey,$REQ) && $REQ[$lkey]) ? $REQ[$lkey] : self::$paf_class_name;
				/* Load the class extension containing the user functions */
				try {
					require_once($class_file);
				} catch(Exception $e) {
					$errors = 'Class file: '.$class_file.' not found ('.$e->getMessage().') !';
				}//try
				if(!$errors) {
					/* Execute the requested function */
					$this->areq = new $class($this,$subsession);
					$this->areq->SetPostParams($post_params);
					$this->areq->SetUtf8($with_utf8);
					$errors = $this->areq->RunFunc($function,$php);
					$this->SessionCommit(NULL,TRUE);
					if($this->areq->HasActions()) { echo $this->areq->Send(); }
					$content = $this->GetOutputBufferContent();
				} else {
					$content = $errors;
				}//if(!$errors)
				echo $this->areq->GetUtf8() ? $content : utf8_encode($content);
				//$this->ClearOutputBuffer(TRUE);
			} else {
				XSession::AddToLog(array('type'=>'error','message'=>$errors,'no'=>-1,'file'=>__FILE__,'line'=>__LINE__),$this->app_path.self::$logs_path.'/'.self::$errors_log_file);
				$this->RedirectOnError();
			}//if(!$errors)
		}//END public function ExecuteARequest
		/**
		 * Redirect to home page/login page if an error occurs in PAFReq execution
		 *
		 * @return void
		 * @access protected
		 */
		protected function RedirectOnError() {
			if($this->ajax) {
				echo PAFReq::$paf_act_separator.'window.location.href = "'.$this->app_web_link.'";';
			} else {
				header('Location:'.$this->app_web_link);
			}//if($this->ajax)
			exit();
		}//END protected function RedirectOnError
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function UrlParamToString($params,$keysonly = FALSE) {
	    	if(is_array($params)) {
	    		$keys = '';
	    		$texts = '';
	    		foreach($params as $k=>$v) {
					$keys .= (strlen($keys) ? ',' : '').$k;
					if($keysonly!==TRUE) { $texts .= (strlen($texts) ? ',' : '').str_to_url($v); }
				}//foreach ($params as $k=>$v)
				if($keysonly===TRUE) { return $keys; }
				return $keys.(strlen($texts) ? '~'.$texts : '');
	    	} else {
	    		return (isset($params) ? $params : '');
	    	}//if(is_array($params))
		}//END public function UrlParamToString
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetUrlParamElements($param) {
			$result = NULL;
			if(strlen($param)) {
				$param_keys = strpos($param,'~')===FALSE ? $param : substr($param,0,(strpos($param,'~')));
				$param_texts = strpos($param,'~')===FALSE ? '' : substr($param,(strpos($param,'~')+1));
				$keys = explode(',',$param_keys);
				$texts = strlen($param_texts)>0 ? explode(',',$param_texts) : NULL;
				for($i=0; $i<count($keys); $i++) {
					if(strlen($keys[$i])>0) {
						if(!is_array($result)) {
							$result = array();
						}//if(!is_array($result))
						$result[$keys[$i]] = (is_array($texts) && array_key_exists($i,$texts)) ? $texts[$i] : '';
					}//if(strlen($keys[$i])>0)
				}//for($i=0; $i<count($keys); $i++)
			}//if(strlen($param))
			return $result;
		}//END public function GetUrlParamElements
		/**
		 * Get elements for a parameter from the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetUrlComplexParam($key,$string = FALSE,$keysonly = FALSE) {
			$result = array_key_exists($key,$this->url_data) ? $this->url_data[$key] : NULL;
			if($string===TRUE && isset($result)) { return $this->UrlParamToString($result,$keysonly); }
			return $result;
		}//END public function GetUrlComplexParam
		/**
		 * Set a simple parameter into the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function SetUrlComplexParam($key,$val) {
			if(!is_array($key) || !count($val)) { return FALSE; }
			$this->url_data[$key] = $val;
			return TRUE;
		}//END public function SetUrlComplexParam
		/**
		 * Unset a parameter from the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function UnsetUrlComplexParam($key) {
			unset($this->url_data[$key]);
		}//END public function UnsetUrlComplexParam
		/**
		 * Get a simple parameter from the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetUrlParam($key,$full = FALSE) {
			return $this->GetUrlComplexParam($key,$full!==TRUE,TRUE);
		}//END public function GetUrlParam
		/**
		 * Set a simple parameter into the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function SetUrlParam($key,$val) {
			return $this->SetUrlComplexParam($key,array($val=>''));
		}//END public function SetUrlParam
		/**
		 * Unset a parameter from the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function UnsetUrlParam($key) {
			return $this->UnsetUrlComplexParam($key);
		}//END public function UnsetUrlParam
		/**
		 * Gets n-th element from a parameter in the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetUrlParamElement($key,$position = 0) {
			if(strlen($key)>0 && array_key_exists($key,$this->url_data)) {
				if(is_array($this->url_data[$key])) {
					$i = 0;
					foreach ($this->url_data[$key] as $k=>$v) {
						if($i==$position) {
							return $k;
						} else {
							$i++;
						}//if($i==$position)
					}//foreach ($this->url_data[$key] as $k=>$v)
				} else {
					return $this->url_data[$key];
				}//if(is_array($this->url_data[$key]))
			}//if(strlen($key)>0 && array_key_exists($key,$this->url_data))
			return NULL;
		}//END public function GetUrlParamElement
		/**
		 * Sets an element from a parameter in the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function SetUrlParamElement($key,$element,$text = '') {
			if(is_null($key) || is_null($element)) { return FALSE; }
			$this->url_data[$key] = is_array($this->url_data[$key]) ? $this->url_data[$key] : array();
			if(is_array($element)) {
				foreach ($element as $k=>$v) {
					$this->url_data[$key][$k] = str_to_url($v);
				}//foreach ($element as $k=>$v)
			} else {
				$this->url_data[$key][$element] = str_to_url($text);
			}//if(is_array($element))
		}//END public function SetUrlParamElement
		/**
		 * Removes an element from a parameter in the url data array
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function UnsetUrlParamElement($key,$element) {
			if(is_null($key) || is_null($element)) { return FALSE; }
			unset($this->url_data[$key][$element]);
		}//END public function UnsetUrlParamElement
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function SetUrlParams($url) {
			$result = array();
			if(is_array($url)) {
				foreach ($url as $k=>$v) { $result[$k] = $this->GetUrlParamElements($v); }
			} else {
				$param_str = explode('?',$url);
				$param_str = count($param_str)>1 ? $param_str[1] : '';
				if(strlen($param_str)>0) {
					$params = explode('&',$param_str);
					foreach ($params as $param) {
						$element = explode('=',$param);
						if(count($element)>1) { $result[$element[0]] = $this->GetUrlParamElements($element[1]); }
					}//foreach ($params as $k=>$v)
				}//if(strlen($param_str)>0)
			}//if(is_array($url))
			return $result;
		}//END public function SetUrlParams
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetBaseUrl($url_format = PAF_URL_FORMAT_FRIENDLY,$params = NULL) {
			$lurl_format = self::$x_mod_rewrite ? $url_format : PAF_URL_FORMAT_SHORT;
			switch($lurl_format) {
				case PAF_URL_FORMAT_FRIENDLY:
					$lang = NULL;
					$urlid = NULL;
					$urlpath = NULL;
					if(is_array($params) && count($params)) {
						$lang = array_key_exists('language',$params) ? $this->UrlParamToString($params['language']) : NULL;
						$urlid = array_key_exists('urlid',$params) ? $this->UrlParamToString($params['urlid']) : NULL;
					}//if(is_array($params) && count($params))
					if(is_null($lang)) {
						$lang = array_key_exists('language',$this->url_data) ? $this->UrlParamToString($this->url_data['language']) : NULL;
					}//if(is_null($lang))
					if(is_null($urlid)) {
						$urlid = array_key_exists('urlid',$this->url_data) ? $this->UrlParamToString($this->url_data['urlid']) : NULL;
					}//if(is_null($urlid))
					$ns_link_alias = get_array_param($this->globals,'domain_registry','','is_string','link_alias');
					return $this->app_web_link.'/'.(strlen($this->url_virtual_path) ? $this->url_virtual_path.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen(trim($urlid,'/')) ? trim($urlid,'/').'/' : '');
				case PAF_URL_FORMAT_FRIENDLY_ORIGINAL:
					return $this->url_base;
				case PAF_URL_FORMAT_FULL:
					return $this->app_web_link.'/index.php';
				case PAF_URL_FORMAT_SHORT:
					return $this->app_web_link.'/';
				case PAF_URL_FORMAT_URI_ONLY:
				default:
					return '';
			}//END switch
		}//END public function GetBaseUrl
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetUrl($params = NULL,$rparams = NULL,$url_format = PAF_URL_FORMAT_FRIENDLY) {
			$data = $this->url_data;
			if(is_array($rparams) && count($rparams)) {
				foreach($rparams as $key=>$value) {
					if(is_array($value)) {
						foreach($value as $rv) { unset($data[$key][$rv]); }
						if(count($data[$key])==0) { unset($data[$key]); }
					} else {
						unset($data[$value]);
					}//if(is_array($value))
				}//END foreach
			}//if(is_array($rparams) && count($rparams))
			if(is_array($params) && count($params)) { $data = custom_array_merge($data,$params,TRUE); }
			return $this->GetNewUrl($data,$url_format);
		}//END public function GetUrl
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetNewUrl($params = NULL,$url_format = PAF_URL_FORMAT_FRIENDLY) {
			$result = '';
			$anchor = '';
			$lurl_format = self::$x_mod_rewrite ? $url_format : PAF_URL_FORMAT_SHORT;
			if(is_array($params) && count($params)) {
				$first = TRUE;
				foreach($params as $k=>$v) {
					if($k=='anchor') {
						$anchor = $this->UrlParamToString($v);
						continue;
					}//if($k=='anchor')
					if(($lurl_format==PAF_URL_FORMAT_FRIENDLY || $lurl_format==PAF_URL_FORMAT_FRIENDLY_ORIGINAL) && in_array($k,$this->special_url_params)) { continue; }
					$val = $this->UrlParamToString($v);
					if(in_array($k,$this->special_url_params) && !$val) { continue; }
					$prefix = '&';
					if($first) {
						$first = FALSE;
						$prefix = '?';
					}//if($first)
					$result .= $prefix.$k.'='.$val;
				}//END foreach
			}//if(is_array($params) && count($params))
			return $this->GetBaseUrl($lurl_format,$params).$result.(strlen($anchor) ? '#'.$anchor : '');
		}//END public function GetNewUrl
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function UrlElementExists($key,$element = NULL) {
			if(is_null($element)) {
				if(array_key_exists($key,$this->url_data) && isset($this->url_data[$key])) {
					return TRUE;
				}//if(array_key_exists($key,$this->url_data) && isset($this->url_data[$key]))
			} else {
				if(array_key_exists($key,$this->url_data) && array_key_exists($element,$this->url_data[$key])) {
					return TRUE;
				}//if(array_key_exists($key,$this->url_data) && array_key_exists($element,$this->url_data[$key]))
			}//if(is_null($element))
			return FALSE;
		}//END public function UrlElementExists
		/**
		 * Initialize debug environment
		 *
		 * @return void
		 * @access public
		 */
	    public function DebuggerStart() {
			if(self::$debug!==TRUE || !class_exists('PAFDebugger')) { return FALSE; }
			if(is_object($this->debugger)) { return $this->debugger->IsEnabled(); }
			$this->debugger = new PAFDebugger(self::$debug,_X_ROOT_PATH.self::GetPafPath().'/debug',_X_ROOT_PATH._X_APP_PATH.self::$logs_path,_X_ROOT_PATH._X_APP_PATH.'/tmp');
			$this->debugger->phpconsole_password = self::$phpconsole_password;
			$this->debugger->log_file = self::$log_file;
			$this->debugger->errors_log_file = self::$errors_log_file;
			$this->debugger->debugging_log_file = self::$debugging_log_file;
			return $this->debugger->IsEnabled();
		}//END public function DebuggerStart
		/**
		 * Get debugger state
		 *
		 * @return bool Returns TRUE if debugger is started, FALSE otherwise
		 * @access public
		 * @static
		 */
	    public static function GetDebuggerState() {
			if(!is_object(self::$_app_instance)) { return FALSE; }
			return is_object(self::$_app_instance->debugger);
	    }//END public static function GetDebuggerState
		/**
		 * Displays a value in the debugger plug-in as a debug message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
	 	 * @param  string $label Label assigned to the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Dlog($value,$label = '',$file = FALSE,$path = FALSE) {
			if(!is_object($this->debugger)) { return; }
			if(self::$console_show_file===TRUE || $file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
			}//if(self::$console_show_file===TRUE || $file===TRUE)
			$this->debugger->Debug($value,$label,PAF_DBG_DEBUG);
		}//END public function Dlog
		/**
		 * Displays a value in the debugger plug-in as a warning message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned to the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Wlog($value,$label = '',$file = FALSE,$path = FALSE) {
			if(!is_object($this->debugger)) { return; }
			if(self::$console_show_file===TRUE || $file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
			}//if(self::$console_show_file===TRUE || $file===TRUE)
			$this->debugger->Debug($value,$label,PAF_DBG_WARNING);
		}//END public function Wlog
		/**
		 * Displays a value in the debugger plug-in as an error message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned to the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Elog($value,$label = '',$file = FALSE,$path = FALSE) {
			if(!is_object($this->debugger)) { return; }
			if(self::$console_show_file===TRUE || $file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
			}//if(self::$console_show_file===TRUE || $file===TRUE)
			$this->debugger->Debug($value,$label,PAF_DBG_ERROR);
		}//END public function Elog
		/**
		 * Displays a value in the debugger plug-in as an info message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned to the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Ilog($value,$label = '',$file = FALSE,$path = FALSE) {
			if(!is_object($this->debugger)) { return; }
			if(self::$console_show_file===TRUE || $file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
			}//if(self::$console_show_file===TRUE || $file===TRUE)
			$this->debugger->Debug($value,$label,PAF_DBG_INFO);
		}//END public function Ilog
		/**
		 * Writes a message in one of the application log files
		 *
		 * @param  string $msg Text to be written to log
		 * @param  string $type Log type (log, error or debug) (optional)
		 * @param  string $file Custom log file complete name (path + name) (optional)
		 * @return void
		 * @access public
		 */
		public function WriteToLog($msg,$type = 'log',$file = '',$path = '') {
			if(is_object($this->debugger)) { return $this->debugger->WriteToLog($msg,$type,$file,$path); }
			$lpath = (is_string($path) && strlen($path) ? rtrim($path,'/') : _X_ROOT_PATH._X_APP_PATH.self::$logs_path).'/';
			switch(strtolower($type)) {
				case 'error':
					return PAFDebugger::AddToLog($msg,$lpath.(strlen($file) ? $file : self::$errors_log_file));
				case 'debug':
					return PAFDebugger::AddToLog($msg,$lpath.(strlen($file) ? $file : self::$debugging_log_file));
				case 'log':
				default:
					return PAFDebugger::AddToLog($msg,$lpath.(strlen($file) ? $file : self::$log_file));
			}//switch(strtolower($type))
		}//END public function WriteToLog
		/**
		 * description
		 *
		 * @param  string $msg Text to be written to log
		 * @param  string $file Custom log file complete name (path + name) (optional)
		 * @param  string $script_name Name of the file that sent the message to log (optional)
		 * @return bool|string Returns TRUE for success or error message on failure
		 * @access public
		 * @static
		 */
		public static function AddToLog($msg,$file = '',$script_name = '') {
			return PAFDebugger::AddToLog($msg,$file,$script_name);
		}//END public static function AddToLog
		/**
		 * Starts a debug timer
		 *
		 * @param  string $name Name of the timer (required)
		 * @return void
		 * @access public
		 * @static
		 */
		public static function TimerStart($name) {
			return PAFDebugger::TimerStart($name);
		}//END public static function TimerStart
		/**
		 * Displays a debug timer elapsed time
		 *
		 * @param  string $name Name of the timer (required)
		 * @param  bool $stop Flag for stopping and destroying the timer (default TRUE)
		 * @return void
		 * @access public
		 * @static
		 */
		public static function TimerShow($name,$stop = TRUE) {
			return PAFDebugger::TimerShow($name,$stop);
		}//END public static function TimerStart
	}//END class PAFApp extends PAFAppConfig
?>