<?php
/**
 * PAF (PHP AJAX Framework) main class file.
 *
 * The PAF main class can be used for interacting with the session data, get (link) data and for debugging or application logging.
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
	 * URL format: friendly (original)
	 */
	define('URL_FORMAT_FRIENDLY_ORIGINAL',-1);
	/**
	 * URL format: URI only
	 */
	define('URL_FORMAT_URI_ONLY',0);
	/**
	 * URL format: friendly
	 */
	define('URL_FORMAT_FRIENDLY',1);
	/**
	 * URL format: full
	 */
	define('URL_FORMAT_FULL',2);
	/**
	 * URL format: short
	 */
	define('URL_FORMAT_SHORT',3);
/**
 * PAF main class.
 *
 * PHP AJAX Framework main class, has to be instantiated at the entry point of the application with the static method GetInstance().
 *
 * @package  AdeoTEK\PAF
 * @access   public
 */
class App implements IApp {
	/**
	 * @var    Object Singleton unique instance
	 * @access protected
	 * @static
	 */
	protected static $_app_instance = NULL;
	/**
	 * @var    bool Flag for output buffering (started or not)
	 * @access protected
	 * @static
	 */
	protected static $app_ob_started = FALSE;
	/**
	 * @var    bool State of session before current request (TRUE for existing session or FALSE for newly initialized)
	 * @access protected
	 */
	protected $_app_state = FALSE;
	/**
	 * @var    string Application absolute path (auto-set on constructor)
	 * @access public
	 */
	protected $app_absolute_path = NULL;
	/**
	 * @var    \PAF\Debugger Object for debugging
	 * @access public
	 */
	public $debugger = NULL;
	/**
	 * @var    \PAF\AjaxRequest Object for ajax requests processing
	 * @access public
	 */
	public $arequest = NULL;
	/**
	 * @var    \PAF\AppUrl Object for application URL processing
	 * @access public
	 */
	public $url = NULL;
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
	 * Magic method for accessing non-static public members with static call
	 *
	 * If no arguments are provided, first tries to return the property with the given name
	 * and if such property doesn't exists or inaccessible, tries to call the public method with the given name
	 *
	 * @param  string $name Name of the member to be accessed
	 * @param  array  $arguments The arguments for accessing methods
	 * @return mixed Returns the property or method result
	 * @throws \PAF\AppException|\ReflectionException
	 * @access public
	 * @static
	 */
	public static function __callStatic($name,$arguments) {
		$class = get_called_class();
		if(!is_object(self::$_app_instance)) { throw new AppException("Invalid class [{$class}] instance",E_ERROR); }
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
					if(preg_match('/^[DWEI]log$/i',$member) && (AppConfig::console_show_file()===TRUE || (isset($arguments[2]) && $arguments[2]===TRUE))) {
						$dbg = debug_backtrace();
						$caller = array_shift($dbg);
						$cfile = isset($caller['file']) ? $caller['file'] : '';
						$label = '['.(isset($arguments[3]) && $arguments[3]===TRUE ? $cfile : basename($cfile)).(isset($caller['line']) ? ':'.$caller['line'] : '').']'.(isset($arguments[1]) ? $arguments[1] : '');
						$larguments = array((isset($arguments[0]) ? $arguments[0] : NULL),$label);
					} else {
						$larguments = $arguments;
					}//if(preg_match('/^[DWEI]log$/i',$member) && (AppConfig::console_show_file()===TRUE || (isset($arguments[2]) && $arguments[2]===TRUE)))
					// self::$_app_instance->Dlog($larguments,'__callStatic:$larguments');
					return call_user_func_array(array(self::$_app_instance,$member),$larguments);
				}//if(!$method->isStatic() && $method->isPublic())
			}//if($reflector->hasMethod($member))
		}//if(strpos($name,'_')!==0)
		throw new \InvalidArgumentException("Undefined or inaccessible property/method [{$member}]!");
	}//END public static function __callStatic
	/**
	 * Classic singleton method for retrieving the PAF object
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $session_init Flag indicating if session should be started or not
	 * @param  bool  $do_not_keep_alive Flag indicating if session should be kept alive by the current request
	 * @param  bool  $shell Shell mode on/off
	 * @return Object
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function GetInstance($ajax = FALSE,$params = [],$session_init = TRUE,$do_not_keep_alive = NULL,$shell = FALSE) {
		if($session_init) {
			AppSession::SetWithSession(TRUE);
			$cdir = AppUrl::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
			AppSession::SessionStart($cdir,$do_not_keep_alive,$ajax);
		} else {
			AppSession::SetWithSession(FALSE);
		}//if($session_init)
		if(is_null(self::$_app_instance)) {
			$class_name = get_called_class();
			self::$_app_instance = new $class_name($ajax,$params,$do_not_keep_alive,$shell);
		}//if(is_null(self::$_app_instance))
		return self::$_app_instance;
	}//END public static function GetInstance
	/**
	 * Method for returning the static instance property
	 *
	 * @return object Returns the value of $_aapp_instance property
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
	 * PAF constructor function
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $do_not_keep_alive Do not keep alive user session
	 * @param  bool  $shell Shell mode on/off
	 * @throws \Exception|\ReflectionException
	 * @return void
	 * @access protected
	 */
	protected function __construct($ajax = FALSE,$params = [],$do_not_keep_alive = NULL,$shell = FALSE) {
		$this->app_absolute_path = _AAPP_ROOT_PATH;
		$this->app_path = _AAPP_ROOT_PATH._AAPP_APPLICATION_PATH;
		$this->app_public_path = _AAPP_ROOT_PATH._AAP_PUBLIC_ROOT_PATH._AAP_PUBLIC_PATH;
		$this->ajax = $ajax;
		$this->keep_alive = $do_not_keep_alive ? FALSE : TRUE;
		if($shell) {
			$this->_app_state = TRUE;
			$app_domain = trim(get_array_param($_GET,'domain','','is_string'),' /\\');
			if(strlen($app_domain)) {
				$app_web_protocol = trim(get_array_param($_GET,'protocol','http','is_notempty_string'),' /:\\').'://';
				$url_folder = trim(get_array_param($_GET,'uri_path','','is_string'),' /\\');
			} else {
				$app_web_protocol = '';
				$url_folder = '';
			}//if(strlen($app_domain))
			$this->url = new AppUrl($app_domain,$app_web_protocol,$url_folder);
			$this->app_web_link = $this->url->GetWebLink();
		} else {
			$this->_app_state = AppSession::WithSession() ? AppSession::GetState() : TRUE;
			$app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
			$app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			$url_folder = AppUrl::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
			$this->url = new AppUrl($app_domain,$app_web_protocol,$url_folder);
			$this->app_web_link = $this->url->GetWebLink();
			if(AppConfig::split_session_by_page()) {
				$this->phash = get_array_param($_GET,'phash',get_array_param($_POST,'phash',NULL,'is_notempty_string'),'is_notempty_string');
				if(!$this->phash) {
					$this->phash = is_array($_COOKIE) && array_key_exists('__aapp_pHash_',$_COOKIE) && strlen($_COOKIE['__aapp_pHash_']) && strlen($_COOKIE['__aapp_pHash_'])>15 ? substr($_COOKIE['__aapp_pHash_'],0,-15) : NULL;
				}//if(!$this->phash)
				if(!$this->phash ) { $this->phash = AppSession::GetNewUID(); }
			}//if(AppConfig::split_session_by_page())
		}//if($shell)
		if(is_array($params) && count($params)>0) {
			foreach($params as $key=>$value) {
				if(property_exists($this,$key)) {
					$prop = new \ReflectionProperty($this,$key);
					if(!$prop->isStatic()) {
						$this->$key = $value;
					} else {
						$this::$$key = $value;
					}//if(!$prop->isStatic())
				}//if(property_exists($this,$key))
			}//foreach($params as $key=>$value)
		}//if(is_array($params) && count($params)>0)
		if($shell) { return; }
		$this->InitDebugger();
		$this->StartOutputBuffer();
	}//END protected function __construct
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
	 * @return bool
	 */
	public function OutputBufferStarted() {
		return self::$app_ob_started;
	}//END public function OutputBufferStarted
	/**
	 * @return bool
	 */
	public function StartOutputBuffer() {
		if(!$this->ajax && !AppConfig::bufferd_output() && !$this->debugger) { return FALSE; }
		ob_start();
		return (self::$app_ob_started = TRUE);
	}//END public function StartOutputBuffer
	/**
	 * @param bool $end
	 * @return bool
	 */
	public function FlushOutputBuffer($end = FALSE) {
		if(!self::$app_ob_started) { return FALSE; }
		if(is_object($this->debugger)) { $this->debugger->SendData(); }
		if($end===TRUE) {
			ob_end_flush();
			self::$app_ob_started = FALSE;
		} else {
			ob_flush();
		}//if($end===TRUE)
		return TRUE;
	}//END public function FlushOutputBuffer
	/**
	 * @param bool $clear
	 * @return bool|string
	 */
	public function GetOutputBufferContent($clear = TRUE) {
		if(!self::$app_ob_started) { return FALSE; }
		if($clear===TRUE) {
			$content = ob_get_clean();
		} else {
			$content = ob_get_contents();
		}//if($clear===TRUE)
		return $content;
	}//END public function GetOutputBufferContent
	/**
	 * @param bool $end
	 * @return bool
	 */
	public function ClearOutputBuffer($end = FALSE) {
		if(!self::$app_ob_started) { return FALSE; }
		if($end===TRUE) {
			ob_end_clean();
			self::$app_ob_started = FALSE;
		} else {
			ob_clean();
		}//if($end===TRUE)
		return TRUE;
	}//END public function ClearOutputBuffer
	/**
	 * Commit the temporary session into the session
	 *
	 * @param  bool   $clear If TRUE is passed the session will be cleared
	 * @param  bool  $preserve_output_buffer If true output buffer is preserved
	 * @param  bool   $show_errors Display errors TRUE/FALSE
	 * @param  string $key Session key to commit (do partial commit)
	 * @param  string $phash Page (tab) hash
	 * @param  bool   $reload Reload session data after commit
	 * @return void
	 * @poaram bool $reload Reload session after commit (default TRUE)
	 * @access public
	 */
	public function SessionCommit($clear = FALSE,$preserve_output_buffer = FALSE,$show_errors = TRUE,$key = NULL,$phash = NULL,$reload = TRUE) {
		if(!AppSession::WithSession()) {
			if($show_errors && method_exists('\ErrorHandler','ShowErrors')) { \ErrorHandler::ShowErrors(); }
			if($preserve_output_buffer!==TRUE) { $this->FlushOutputBuffer(); }
			return;
		}//if(!AppSession::WithSession())
		AppSession::SessionCommit($clear,$show_errors,$key,$phash,$reload);
		if($preserve_output_buffer!==TRUE) { $this->FlushOutputBuffer(); }
	}//END public function SessionCommit
	/**
	 * Get a global parameter (a parameter from first level of the array) from the session data array
	 *
	 * @param  string $key The key of the searched parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (App property) page hash will not be used
	 * @param  string $path An array containing the succession of keys for the searched parameter
	 * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
	 * FALSE - do not change case, NULL - use the configuration value
	 * @return mixed Returns the parameter value or NULL
	 * @access public
	 */
	public function GetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
		$lphash = isset($phash) ? $phash : $this->phash;
		return AppSession::GetGlobalParam($key,$lphash,$path,$keys_case);
	}//END public function GetGlobalParam
	/**
	 * Set a global parameter (a parameter from first level of the array) from the session data array
	 *
	 * @param  string $key The key of the searched parameter
	 * @param  mixed  $val The value to be set
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (App property) page hash will not be used
	 * @param  string $path An array containing the succession of keys for the searched parameter
	 * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
	 * FALSE - do not change case, NULL - use the configuration value
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function SetGlobalParam($key,$val,$phash = NULL,$path = NULL,$keys_case = NULL) {
		$lphash = isset($phash) ? $phash : $this->phash;
		return AppSession::SetGlobalParam($key,$val,$lphash,$path,$keys_case);
	}//END public function SetGlobalParam
	/**
	 * Delete a global parameter (a parameter from first level of the array) from the session data array
	 *
	 * @param  string $key The key of the searched parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (App property) page hash will not be used
	 * @param  null   $path
	 * @param  null   $keys_case
	 * @return bool
	 * @access public
	 */
	public function UnsetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
		$lphash = isset($phash) ? $phash : $this->phash;
		return AppSession::UnsetGlobalParam($key,$lphash,$path,$keys_case);
	}//END public function UnsetGlobalParam
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
	public function ARequestInit($post_params = [],$subsession = NULL,$js_init = TRUE,$with_output = TRUE) {
		if(!is_object($this->arequest)) {
			$this->arequest = new AjaxRequest($this,$subsession);
			$this->arequest->SetPostParams($post_params);
		}//if(!is_object($this->arequest))
		if($js_init!==TRUE) { return TRUE; }
		return $this->arequest->JsInit($with_output);
	}//END public function ARequestInit
	/**
	 * Execute a method of the ARequest implementing class in an ajax request
	 *
	 * @param  array $post_params Parameters to be send via post on ajax requests
	 * @param  string $subsession Sub-session key/path
	 * @return void
	 * @access public
	 */
	public function ExecuteARequest($post_params = [],$subsession = NULL) {
		$errors = '';
		$request = array_key_exists('req',$_POST) ? $_POST['req'] : NULL;
		if(!$request) { $errors .= 'Empty Request!'; }
		$php = NULL;
		$session_id = NULL;
		$request_id = NULL;
		$class_file = NULL;
		$class = NULL;
		$function = NULL;
		$requests = NULL;
		if(!$errors) {
			/* Start session and set ID to the expected paf session */
			list($php,$session_id,$request_id) = explode(AjaxRequest::$app_req_sep,$request);
			/* Validate this request */
			$spath = array(
				AppSession::ConvertToSessionCase(AppConfig::app_session_key(),AjaxRequest::$session_keys_case),
				AppSession::ConvertToSessionCase('PAF_AREQUEST',AjaxRequest::$session_keys_case),
			);
			$requests = AppSession::GetGlobalParam(AppSession::ConvertToSessionCase('AREQUESTS',AjaxRequest::$session_keys_case),FALSE,$spath,FALSE);
			if(\GibberishAES::dec(rawurldecode($session_id),AppConfig::app_session_key())!=session_id() || !is_array($requests)) {
				$errors .= 'Invalid Request!';
			} elseif(!in_array(AppSession::ConvertToSessionCase($request_id,AjaxRequest::$session_keys_case),array_keys($requests))) {
				$errors .= 'Invalid Request Data!';
			}//if(\GibberishAES::dec(rawurldecode($session_id),AppConfig::app_session_key())!=session_id() || !is_array($requests))
		}//if(!$errors)
		if(!$errors) {
			/* Get function name and process file */
			$REQ = $requests[AppSession::ConvertToSessionCase($request_id,AjaxRequest::$session_keys_case)];
			$method = $REQ[AppSession::ConvertToSessionCase('METHOD',AjaxRequest::$session_keys_case)];
			$lkey = AppSession::ConvertToSessionCase('CLASS',AjaxRequest::$session_keys_case);
			$class = (array_key_exists($lkey,$REQ) && $REQ[$lkey]) ? $REQ[$lkey] : AppConfig::ajax_class_name();
			/* Load the class extension containing the user functions */
			$lkey = AppSession::ConvertToSessionCase('CLASS_FILE',AjaxRequest::$session_keys_case);
			if(array_key_exists($lkey,$REQ) && isset($REQ[$lkey])) {
				$class_file = $REQ[$lkey];
			} else {
				$app_class_file = AppConfig::ajax_class_file();
				$class_file = $app_class_file ? $this->app_path.$app_class_file : '';
			}//if(array_key_exists($lkey,$REQ) && isset($REQ[$lkey]))
			if(strlen($class_file)) {
				if(file_exists($class_file)) {
					require_once($class_file);
				} else {
					$errors = 'Class file ['.$class_file.'] not found!';
				}//if(file_exists($class_file))
			}//if(strlen($class_file))
			if(!$errors) {
				/* Execute the requested function */
				$this->arequest = new $class($this,$subsession);
				$this->arequest->SetPostParams($post_params);
				$this->arequest->ExecuteRequest($method,$php);
				$this->SessionCommit(NULL,TRUE);
				if($this->arequest->HasActions()) { echo $this->arequest->Send(); }
				$content = $this->GetOutputBufferContent();
			} else {
				$content = $errors;
			}//if(!$errors)
			echo $content;
			//$this->ClearOutputBuffer(TRUE);
		} else {
			self::Log2File(['type'=>'error','message'=>$errors,'no'=>-1,'file'=>__FILE__,'line'=>__LINE__],$this->app_path.AppConfig::logs_path().'/'.AppConfig::errors_log_file());
			$this->RedirectOnError();
		}//if(!$errors)
	}//END public function ExecuteARequest
	/**
	 * Redirect to home page/login page if an error occurs in ARequest execution
	 *
	 * @return void
	 * @access protected
	 */
	protected function RedirectOnError() {
		if($this->ajax) {
			echo AjaxRequest::$app_act_sep.'window.location.href = "'.$this->app_web_link.'";';
		} else {
			header('Location:'.$this->app_web_link);
		}//if($this->ajax)
		exit();
	}//END protected function RedirectOnError
	/**
	 * Initialize debug environment
	 *
	 * @return bool
	 * @throws \Exception
	 * @access public
	 */
    public function InitDebugger() {
		if(AppConfig::debug()!==TRUE || !class_exists('PAF\Debugger')) { return FALSE; }
		if(is_object($this->debugger)) { return $this->debugger->IsEnabled(); }
		$tmp_path = isset($_SERVER['DOCUMENT_ROOT']) && strpos(_AAPP_ROOT_PATH, $_SERVER['DOCUMENT_ROOT'])!==FALSE ? _AAPP_ROOT_PATH.'/../tmp' : _AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.'/tmp';
		$this->debugger = new Debugger(AppConfig::debug(),_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.AppConfig::logs_path(),$tmp_path,AppConfig::debug_console_password());
		$this->debugger->log_file = AppConfig::log_file();
		$this->debugger->errors_log_file = AppConfig::errors_log_file();
		$this->debugger->debug_log_file = AppConfig::debug_log_file();
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
	 * @param  mixed   $value Value to be displayed by the debug objects
	 * @param  string  $label Label assigned to the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Dlog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,DBG_DEBUG);
	}//END public function Dlog
	/**
	 * Displays a value in the debugger plug-in as a warning message
	 *
	 * @param  mixed   $value Value to be displayed by the debug objects
	 * @param  string  $label Label assigned to the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Wlog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,DBG_WARNING);
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
	 * @throws \Exception
	 */
	public function Elog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,DBG_ERROR);
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
	 * @throws \Exception
	 */
	public function Ilog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,DBG_INFO);
	}//END public function Ilog
	/**
	 * Writes a message in one of the application log files
	 *
	 * @param  string $msg Text to be written to log
	 * @param  string $type Log type (log, error or debug) (optional)
	 * @param  string $file Custom log file complete name (path + name) (optional)
	 * @param string  $path
	 * @return bool|string
	 * @access public
	 */
	public function Write2LogFile($msg,$type = 'log',$file = '',$path = '') {
		if(is_object($this->debugger)) { return $this->debugger->Write2LogFile($msg,$type,$file,$path); }
		$lpath = (is_string($path) && strlen($path) ? rtrim($path,'/') : _AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.AppConfig::logs_path()).'/';
		switch(strtolower($type)) {
			case 'error':
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::errors_log_file()));
			case 'debug':
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::debugging_log_file()));
			case 'log':
			default:
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::log_file()));
		}//switch(strtolower($type))
	}//END public function WriteToLog
	/**
	 * description
	 *
	 * @param  string|array $msg Text to be written to log
	 * @param  string $file Custom log file complete name (path + name) (optional)
	 * @param  string $script_name Name of the file that sent the message to log (optional)
	 * @return bool|string Returns TRUE for success or error message on failure
	 * @access public
	 * @static
	 */
	public static function Log2File($msg,$file = '',$script_name = '') {
		return Debugger::Log2File($msg,$file,$script_name);
	}//END public static function AddToLog
	/**
	 * Starts a debug timer
	 *
	 * @param  string $name Name of the timer (required)
	 * @return bool
	 * @access public
	 * @static
	 */
	public static function StartTimeTrack($name) {
		return Debugger::StartTimeTrack($name);
	}//END public static function TimerStart
	/**
	 * Displays a debug timer elapsed time
	 *
	 * @param  string $name Name of the timer (required)
	 * @param  bool $stop Flag for stopping and destroying the timer (default TRUE)
	 * @return double
	 * @access public
	 * @static
	 */
	public static function ShowTimeTrack($name,$stop = TRUE) {
		return Debugger::ShowTimeTrack($name,$stop);
	}//END public static function TimerStart
}//END class App extends AppConfig
?>