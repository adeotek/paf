<?php
/**
 * PAF (PHP AJAX Framework) main class file.
 *
 * The PAF main class can be used for interacting with the session data, get (link) data and for debugging or application logging.
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.1
 * @filesource
 */
	/**
	 * PAF main class.
	 *
	 * PHP AJAX Framework main class, has to be instantiated at the entry point of the application with the static method GetInstance().
	 *
	 * @package  Hinter\PAF
	 * @access   public
	 */
	class PAF extends PAFConfig {
		/**
		 * @var    Object Singleton unique instance
		 * @access protected
		 * @static
		 */
		protected static $_paf_instance = NULL;
		/**
		 * @var    bool State of session before current request (TRUE for existing session or FALSE for newly initialized)
		 * @access protected
		 */
		protected $_paf_state = FALSE;
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
		 * @var    array List of debugging plugins. To activate/inactivate an plugin, change te value for "active" key coresponding to that plugin.
		 * @access protected
		 * @static
		 */
		protected static $debug_extensions = array(
			'Firefox'=>array(
				'FirePHP'=>array('active'=>TRUE),
				'FireLogger'=>array('active'=>FALSE)),
			'Chrome'=>array(
				'ChromePhp'=>array('active'=>FALSE),
				'PhpConsole'=>array('active'=>TRUE)),
		);
		/**
		 * @var    array Array containing debugging plugins objects.
		 * @access public
		 */
		public $debug_objects = array();
		/**
		 * @var    array Array containing started debug timers.
		 * @access public
		 */
		public $debug_timers = array();
		/**
		 * @var    bool Flag for cleaning session data (if is set to TRUE, the session data will be erased on commit)
		 * @access protected
		 */
		protected $clear_session = FALSE;
		/**
		 * @var    string Application absolute path (auto-set on constructor)
		 * @access public
		 */
		public $app_absolute_path = '';
		/**
		 * @var    string Application base link (auto-set on constructor)
		 * @access public
		 */
		public $app_web_link = '';
		/**
		 * @var    string Application domain (auto-set on constructor)
		 * @access public
		 */
		public $app_domain = '';
		/**
		 * @var    string Application web protocol (http/https)
		 * @access public
		 */
		public $app_web_protocol = '';
		/**
		 * @var    string Application folder inside www root (auto-set on constructor)
		 * @access public
		 */
		public $app_folder = '';
		/**
		 * @var    bool Flag to indicate if the request is ajax or not
		 * @access public
		 */
		public $ajax = FALSE;
		/**
		 * @var    array Session data
		 * @access public
		 */
		public $data = array();
		/**
		 * @var    array Get (link) data
		 * @access public
		 */
		public $url_data = array();
		/**
		 * @var    PAFReq Object for ajax requests processing
		 * @access public
		 */
		public $areq = NULL;
		/**
		 * @var    AppLogging Object for application logging
		 * @access public
		 */
		public $logger = NULL;
		/**
		 * PAF constructor function
		 *
		 * @param  bool $ajax Optional flag indicating whether is an ajax request or not
		 * @param  array $params An optional key-value array containing to be assigned to non-static properties
		 * (key represents name of the property and vaue the value to be asigned)
		 * @return void
		 * @access protected
		 */
		protected function __construct($ajax = FALSE,$params = array(),$with_session = FALSE) {
			$this->app_absolute_path = self::ExtractAbsolutePath();
			$this->app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			if(is_array($params) && array_key_exists('startup_path',$params) && strlen($params['startup_path'])>0) {
				$cdir = str_replace('\\','/',(str_replace($this->app_absolute_path,'',$params['startup_path'])));
				$this->app_folder = str_replace($cdir,'',rtrim(dirname($_SERVER['SCRIPT_NAME']),'/'));
			} else {
				$this->app_folder = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/');
			}//if(is_array($params) && array_key_exists('startup_path',$params) && strlen($params['startup_path'])>0)
			$this->app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').':';
			$this->app_web_link = $this->app_web_protocol.'//'.$this->app_domain.$this->app_folder;
			$this->ajax = $ajax;
			if($with_session) {
				$this->_paf_state = isset($_SESSION);
				$this->data = $_SESSION;
			} else {
				$this->_paf_state = TRUE;
			}//if($with_session)
			$this->url_data = is_array($_GET) ? $this->SetUrlParams($_GET) : array();
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
			$this->DebugStart();
			$this->StartOutputBuffer();
			if(self::$app_logging) { $this->logger = new AppLogger(array_merge(array('f_path'=>$this->app_absolute_path.self::$logs_path),get_array_param($params,'applogger-config',array(),'is_array'))); }
		}//END protected function __construct
		/**
		 * Classic singleton method for retrieving the PAF object
		 *
		 * @param  bool $ajax Optional flag indicating whether is an ajax request or not
		 * @param  array $params An optional key-value array containing to be assigned to non-static properties
		 * (key represents name of the property and vaue the value to be asigned)
		 * @return PAF Returns the PAF instance
		 * @access public
		 * @static
		 */
		public static function GetInstance($ajax = FALSE,$params = array(),$session_init = TRUE) {
			if($session_init && !self::$session_started) {
				$cdir = (is_array($params) && array_key_exists('startup_path',$params) && strlen($params['startup_path'])>0) ? $cdir = str_replace('\\','/',(str_replace(self::ExtractAbsolutePath(),'',$params['startup_path']))) : '';
				self::SessionStart($cdir);
			}//if($session_init && !self::$session_started)
			if(is_null(self::$_paf_instance)) {
				self::$_paf_instance = new PAF($ajax,$params,$session_init);
			}//if(is_null(self::$_paf_instance))
			return self::$_paf_instance;
		}//public static function GetInstance($ajax = FALSE,$params = array())
		/**
		 * Method for returning the static instance property
		 *
		 * @return void Returns the value of $_paf_instance property
		 * @access public
		 * @static
		 */
		public static function GetCurrentInstance() {
			return self::$_paf_instance;
		}//public static function GetCurrentInstance()
		/**
		 * Initiate/reinitiate session and read session data
		 *
		 * @return void
		 * @access public
		 * @static
		 */
		public static function SessionStart($path = '') {
			$absolute_path = self::ExtractAbsolutePath();
			$cremoteaddress = array_key_exists('REMOTE_ADDR',$_SERVER) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
			$cdomain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			$cfulldomain = $cdomain.($path ? str_replace($path,'',rtrim(dirname($_SERVER['SCRIPT_NAME']),'/')) : rtrim(dirname($_SERVER['SCRIPT_NAME']),'/'));
			$cuseragent = array_key_exists('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN USER AGENT';
			if(!self::$session_started) {
				ini_set('session.use_cookies',1);
				ini_set('cookie_domain',$cdomain);
				ini_set('session.cookie_lifetime',self::$session_timeout);
				ini_set('session.gc_maxlifetime',self::$session_timeout);
				ini_set('session.cache_expire',self::$session_timeout/60);
				$store_to_file = TRUE;
				if(self::$session_memcached===TRUE && class_exists('Memcached',FALSE)) {
					$store_to_file = FALSE;
					try {
						ini_set('session.save_handler','memcached');
						ini_set('session.save_path',self::$session_memcached_server);
						ini_set('session.cache_expire',intval(self::$session_timeout/60));
						session_start();
					} catch(Exception $e) {
						self::AddToLog($e->getMessage(),$absolute_path.self::$logs_path.self::$errors_log_file);
						$store_to_file = TRUE;
					}//try
				}//if(self::$session_memcached===TRUE && class_exists('Memcache',FALSE))
				if($store_to_file) {
					ini_set('session.save_handler','files');
					if(strlen(self::$session_file_path)>0) {
						if((substr(self::$session_file_path,0,1)=='/' || substr(self::$session_file_path,1,2)==':\\') && file_exists(self::$session_file_path)) {
							session_save_path(self::$session_file_path);
						} elseif(file_exists($absolute_path.'/'.self::$session_file_path)) {
							session_save_path($absolute_path.'/'.self::$session_file_path);
						}//if((substr(self::$session_file_path,0,1)=='/' || substr(self::$session_file_path,1,2)==':\\') && file_exists(self::$session_file_path))
					}//if(strlen(self::$session_file_path)>0)
					session_start();
				}//if($store_to_file)
				self::$session_started = TRUE;
			}//if(!self::$session_started && strlen(session_id())>0)
	        if(!array_key_exists('X_SEXT',$_SESSION) || !isset($_SESSION['X_SEXT']) || $_SESSION['X_SEXT']<time() || !array_key_exists('X_SKEY',$_SESSION) || !isset($_SESSION['X_SKEY']) || $_SESSION['X_SKEY']!=self::GenerateUID(self::$session_key.session_id(),'sha256',TRUE)) {
	        	$_SESSION = array();
			    setcookie(session_name(),'',time()-4200,'/',$cdomain);
				session_destroy();
				ini_set('session.use_cookies',1);
				ini_set('cookie_domain',$cdomain);
				ini_set('session.cookie_lifetime',self::$session_timeout);
				ini_set('session.gc_maxlifetime',self::$session_timeout);
				ini_set('session.cache_expire',self::$session_timeout/60);
				$store_to_file = TRUE;
				if(self::$session_memcached===TRUE && class_exists('Memcached',FALSE)) {
					$store_to_file = FALSE;
					try {
						ini_set('session.save_handler','memcached');
						ini_set('session.save_path',self::$session_memcached_server);
						ini_set('session.cache_expire',intval(self::$session_timeout/60));
						session_id(self::GenerateUID($cfulldomain.$cuseragent.$cremoteaddress,'sha256'));
						session_start();
					} catch(Exception $e) {
						self::AddToLog($e->getMessage(),$absolute_path.self::$logs_path.self::$errors_log_file);
						$store_to_file = TRUE;
					}//try
				}//if(self::$session_memcached===TRUE && class_exists('Memcache',FALSE))
				if($store_to_file) {
					ini_set('session.save_handler','files');
					if(strlen(self::$session_file_path)>0) {
						if((substr(self::$session_file_path,0,1)=='/' || substr(self::$session_file_path,1,2)==':\\') && file_exists(self::$session_file_path)) {
							session_save_path(self::$session_file_path);
						} elseif(file_exists($absolute_path.'/'.self::$session_file_path)) {
							session_save_path($absolute_path.'/'.self::$session_file_path);
						}//if((substr(self::$session_file_path,0,1)=='/' || substr(self::$session_file_path,1,2)==':\\') && file_exists(self::$session_file_path))
					}//if(strlen(self::$session_file_path)>0)
					session_id(self::GenerateUID($cfulldomain.$cuseragent.$cremoteaddress,'sha256'));
					session_start();
				}//if($store_to_file)
			}//if(!array_key_exists('X_SEXT',$_SESSION) || !isset($_SESSION['X_SEXT']) || $_SESSION['X_SEXT']<time() || !array_key_exists('X_SKEY',$_SESSION) || !isset($_SESSION['X_SKEY']) || $_SESSION['X_SKEY']!=self::GenerateUID(self::$session_key.session_id(),'sha256',TRUE))
			$_SESSION['X_SKEY'] = self::GenerateUID(self::$session_key.session_id(),'sha256',TRUE);
	        $_SESSION['X_SEXT'] = time() + self::$session_timeout;
			set_time_limit(self::$request_time_limit);
	    }//END public static function SessionStart
		/**
		 * Initialise debug environment
		 *
		 * @return void
		 * @access protected
		 */
	    protected function DebugStart() {
			if(self::$debug!==TRUE) { return FALSE; }
			if(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT'])===1) {
				if(is_array(self::$debug_extensions) && count(self::$debug_extensions)>0 && array_key_exists('Chrome',self::$debug_extensions) && is_array(self::$debug_extensions['Chrome']) && count(self::$debug_extensions['Chrome'])>0) {
					foreach(self::$debug_extensions['Chrome'] as $dk=>$dv) {
						if($dv['active']!==TRUE) { continue; }
						if($dk=='PhpConsole') {
							require_once($this->app_absolute_path.self::$paf_path.'debug/'.$dk.'/__autoload.php');
							$pcdf_path = !strrpos($this->app_absolute_path,'/')
								? (!strrpos($this->app_absolute_path,'\\')
									? '/tmp'
									: substr($this->app_absolute_path,0,strrpos($this->app_absolute_path,'\\')))
								: substr($this->app_absolute_path,0,strrpos($this->app_absolute_path,'/'));
							PhpConsole\Connector::setPostponeStorage(new PhpConsole\Storage\File($pcdf_path.'/phpcons.data'));
							$this->debug_objects[$dk] = PhpConsole\Connector::getInstance();
							if(PhpConsole\Connector::getInstance()->isActiveClient()) {
								$this->debug_objects[$dk]->setServerEncoding('UTF-8');
								if(isset(self::$phpconsole_password) && strlen(self::$phpconsole_password)) { $this->debug_objects[$dk]->setPassword(self::$phpconsole_password); }
							} else {
								$this->debug_objects[$dk] = NULL;
							}//if(PhpConsole\Connector::getInstance()->isActiveClient())
						} else {
							require_once($this->app_absolute_path.self::$paf_path.'debug/'.$dk.'.php');
							$this->debug_objects[$dk] = $dk::getInstance();
						}//if($dk=='PhpConsole')
					}//foreach(self::$debug_extensions['Chrome'] as $dk=>$dv)
				}//if(is_array(self::$debug_extensions) && count(self::$debug_extensions)>0 && array_key_exists('Chrome',self::$debug_extensions) && is_array(self::$debug_extensions['Chrome']) && count(self::$debug_extensions['Chrome'])>0)
			} elseif(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Firefox/',$_SERVER['HTTP_USER_AGENT'])===1) {
				if(is_array(self::$debug_extensions) && count(self::$debug_extensions)>0 && array_key_exists('Firefox',self::$debug_extensions) && is_array(self::$debug_extensions['Firefox']) && count(self::$debug_extensions['Firefox'])>0) {
					foreach(self::$debug_extensions['Firefox'] as $dk=>$dv) {
						if($dv['active']!==TRUE) { continue; }
						require_once($this->app_absolute_path.self::$paf_path.'debug/'.$dk.'.php');
						if($dk=='FireLogger') {
							$this->debug_objects[$dk] = new FireLogger();
						} else {
							$this->debug_objects[$dk] = $dk::getInstance(TRUE);
						}//if($dk=='FireLogger')
					}//foreach(self::$debug_extensions['Firefox'] as $dk=>$dv)
				}//if(is_array(self::$debug_extensions) && count(self::$debug_extensions)>0 && array_key_exists('Firefox',self::$debug_extensions) && is_array(self::$debug_extensions['Firefox']) && count(self::$debug_extensions['Firefox'])>0)
			}//if(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT'])===1)
		}//protected function DebugStart()
		/**
		 * Gets the session state befor current request (TRUE for existing session or FALSE for newly initialized)
		 *
		 * @return bool Session state (TRUE for existing session or FALSE for newly initialized)
		 * @access public
		 */
		public function GetSessionState() {
			return $this->_paf_state;
		}//END public function GetSessionState

		public function StartOutputBuffer() {
			if($this->ajax || (!self::$cache && !self::$bufferd_output && !self::$debug)) { return; }
			ob_start();
			self::$output_buffer_started = TRUE;
		}//END public function StartOutputBuffer

		public function FlushOutputBuffer() {
			if(self::$output_buffer_started===TRUE) { ob_flush(); }
		}//END public function FlushOutputBuffer

		public function GetOutputBufferContent() {
			if(self::$output_buffer_started===TRUE) { return ob_get_contents(); }
		}//END public function GetOutputBufferContent

		public function ClearOutputBuffer($end = FALSE) {
			if(!self::$output_buffer_started) { return; }
			if($end===TRUE) {
				ob_end_clean();
			} else {
				ob_clean();
			}//if($end===TRUE)
		}//END public function ClearOutputBuffer

		public function GetCachedContent($output = TRUE,&$content = NULL) {
			if(!self::$cache) { return FALSE; }
			$requri = $this->GetUrl(NULL,array('pag'),TRUE);
			$uripag = ((is_numeric($this->GetUrlParam('pag',TRUE,TRUE)) && $this->GetUrlParam('pag',TRUE,TRUE)>0) ? $this->GetUrlParam('pag',TRUE,TRUE) : NULL);
			$dbfile = DataSourcesDispatcher::Call('CacheDataAdapter','GetCacheFile',array(
					'for_url_hash'=>(strlen($requri)>0 ? self::GenerateUID($requri,'sha1',TRUE) : ''),
					'for_language_code'=>$this->GetParam('lang_code'),
					'for_page'=>($uripag ? $uripag : 'null')
				));
			if(!is_array($dbfile) || !count($dbfile) || !array_key_exists('url_hash',$dbfile) || !strlen($dbfile['url_hash'])) { return FALSE; }
			$file = $this->app_absolute_path.'/cache/'.$dbfile['url_hash'].$this->GetParam('lang_code').($uripag ? $uripag : 0).'.cache';
			if(!file_exists($file)) { return FALSE; }
			$content = $this->areq->cached_run(file_get_contents($file));
			if($output) { echo $content; }
			return TRUE;
		}//END public function GetCachedContent

		public function CacheContent($content,$products_ids = '') {
			if(!self::$cache || !$content) { return FALSE; }
			$requri = $this->GetUrl(NULL,array('pag'),TRUE);
			$lang = $this->GetParam('lang_code');
			if(!trim($requri) || !$lang) { return FALSE; }
			$uripag = ((is_numeric($this->GetUrlParam('pag',TRUE,TRUE)) && $this->GetUrlParam('pag',TRUE,TRUE)>0) ? $this->GetUrlParam('pag',TRUE,TRUE) : NULL);
			$urlhash = self::GenerateUID($requri,'sha1',TRUE);
			$file = $this->app_absolute_path.'/cache/'.$urlhash.$lang.($uripag ? $uripag : 0).'.cache';
			if(file_put_contents($file,$content)===FALSE) { return FALSE; }
			return DataSourcesDispatcher::Call('CacheDataAdapter','SetNewCacheFile',array(
					'for_url_hash'=>$urlhash,
					'for_language_code'=>$lang,
					'for_page'=>($uripag ? $uripag : 'null'),
					'for_products_ids'=>$products_ids,
					'for_url'=>$this->GetUrl()
				));
		}//END public function CacheContent

		public function AReqInit($post_params = array(),$init_type = PAFReq_INIT_ALL,$with_output = TRUE) {
			if(!is_object($this->areq)) {
				$this->areq = new PAFReq($this);
				$this->areq->SetPostParams($post_params);
			}//if(!is_object($this->areq))
			if($init_type==PAFReq_INIT_NO_JS) { return TRUE; }
			return $this->areq->jsInit($with_output);
		}//public function AReqInit($init_type = PAFReq_INIT_ALL,$with_output = TRUE)
		/**
		 * Execute a method of the PAFReq implementing class in an ajax request
		 *
		 * @param  array $post_params Parameters to be send via post on ajax requests
		 * @return void
		 * @access public
		 */
		public function ExecutePAFReq($post_params = array()) {
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
			if(!$errors) {
				/* Start session and set ID to the expected paf session */
				list($php,$session_id,$request_id) = explode(PAFReq::$paf_req_separator,$request);
				/* Validate this request */
				if(GibberishAES::dec(rawurldecode($session_id),self::$session_key)!=session_id() || !array_key_exists(self::$paf_session_key,$this->data) || !array_key_exists('PAF_REQUEST',$this->data[self::$paf_session_key]) || !is_array($this->data[self::$paf_session_key]['PAF_REQUEST']['REQUESTS'])) {
					$errors .= 'Invalid Request!';
				} elseif(!in_array($request_id,array_keys($this->data[self::$paf_session_key]['PAF_REQUEST']['REQUESTS']))) {
					$errors .= 'Invalid Request Data!';
				}//if(GibberishAES::dec(rawurldecode($session_id),self::$session_key)!=session_id() || !array_key_exists(self::$paf_session_key,$this->data) || !array_key_exists('PAF_REQUEST',$this->data[self::$paf_session_key]) || !is_array($this->data[self::$paf_session_key]['PAF_REQUEST']['REQUESTS']))
			}//if(!$errors)
			if(!$errors) {
				/* Start output buffering */
				ob_start();
				/* Get function name and process file */
				$REQ = $this->data[self::$paf_session_key]['PAF_REQUEST']['REQUESTS'][$request_id];
				$with_utf8 = $REQ['UTF8'];
				$function = $REQ['FUNCTION'];
				$class_file = (array_key_exists('CLASS_FILE',$REQ) && $REQ['CLASS_FILE']) ? $REQ['CLASS_FILE'] : (self::$paf_class_file ? self::$paf_class_file : $this->app_absolute_path.self::$paf_class_file_path.self::$paf_class_file_name);
				$class = (array_key_exists('CLASS',$REQ) && $REQ['CLASS']) ? $REQ['CLASS'] : self::$paf_class_name;
				/* Load the class extension containing the user functions */
				try {
					require_once($class_file);
				} catch(Exception $e) {
					$errors = 'Class file: '.$class_file.' not found ('.$e->getMessage().') !';
				}//try
				if(!$errors) {
					/* Execute the requested function */
					$this->areq = new $class($this);
					$this->areq->SetPostParams($post_params);
					$this->areq->SetUtf8($with_utf8);
					$errors = $this->areq->RunFunc($function,$php);
					if($this->areq->HasActions()) { echo $this->areq->Send(); }
					$content = ob_get_contents();
				} else {
					$content = $errors;
				}//if(!$errors)
				ob_end_clean();
				echo $this->areq->GetUtf8() ? $content : utf8_encode($content);
			} else {
				XSession::AddToLog(array('type'=>'error','message'=>$errors,'no'=>-1,'file'=>__FILE__,'line'=>__LINE__),$this->app_absolute_path.self::$logs_path.self::$errors_log_file);
				$this->RedirectOnError();
			}//if(!$errors)
		}//END public function ExecutePAFReq
		/**
		 * Redirect to home page/login page if an error occurs in PAFReq execution
		 *
		 * @return void
		 * @access public
		 */
		public function RedirectOnError() {
			if($this->ajax) {
				echo PAFReq::$paf_act_separator.'window.location.href = "'.$this->app_web_link.'";';
			} else {
				header('Location:'.$this->app_web_link);
			}//if($this->ajax)
		}//END public function RedirectOnError
		/**
		 * Gets a session parameter at a certain path (path = a succesion of keys of the session data array)
		 *
		 * @param  string $key The key of the searched parameter
		 * @param  string $path An array containing the succesion of keys for the searched parameter
		 * @param  array $data The session data array to be searched
		 * @return mixed Value of the parameter if it exists or NULL
		 * @access protected
		 */
		protected function GetCustomParam($key,$path,$data) {
			if(is_array($path)) {
				if(count($path)==0) { return NULL; }
				$lpath = array_shift($path);
				if(strlen($lpath)>0 && array_key_exists($lpath,$data)) {
					if(count($path)>0) {
						return $this->GetCustomParam($key,$lpath,$data[$lpath]);
					} else {
						return array_key_exists($key,$this->data[$lpath]) ? $this->data[$lpath][$key] : NULL;
					}//if(count($path)>0)
				}//if(strlen($lpath)>0 && array_key_exists($lpath,$data))
				return NULL;
			}//if(is_array($path))
			if(strlen($path)>0) {
				if(array_key_exists($path,$this->data) && is_array($this->data[$path])) {
					return array_key_exists($key,$this->data[$path]) ? $this->data[$path][$key] : NULL;
				}//if(array_key_exists($path,$this->data) && is_array($this->data[$path]))
				return NULL;
			}//if(strlen($path)>0)
		}//protected function GetCustomParam($key,$path,$data)
		/**
		 * Get a global parameter (a parameter from first level of the array) from the session data array
		 *
		 * @param  string $key The key of the searched parameter
		 * @param  string $path An array containing the succesion of keys for the searched parameter
		 * @return mixed Returns the parameter value or NULL
		 * @access public
		 */
		public function GetGlobalParam($key,$path = NULL) {
			if(isset($path)) {
				return $this->GetCustomParam($key,$path,$this->data);
			}//if(isset($path))
			return array_key_exists($key,$this->data) ? $this->data[$key] : NULL;
		}//public function GetGlobalParam($key,$path = NULL)

		/* Set a global parameter into the temporarry session */
		public function SetGlobalParam($key,$val,$path = NULL) {
			if(isset($path)) {
				if(is_array($path) && count($path)>0) {
					$part_arr = array($key=>$value);
					foreach(array_reverse($path) as $k) {
						$part_arr = array($k=>$part_arr);
					}//foreach(array_reverse($path) as $k)
					$this->data = custom_array_merge($this->data,$part_arr,TRUE);
					return TRUE;
				}//if(is_array($path) && count($path)>0)1
				if(is_string($path) && strlen($path)>0) {
					$this->data[$path][$key] = $val;
					return TRUE;
				}//if(is_string($path) && strlen($path)>0)
				return FALSE;
			}//if(isset($path))
			$this->data[$key] = $val;
			return TRUE;
		}//public function SetGlobalParam($key,$val,$path = NULL)

		/* Unset a global parameter from the temporarry session */
		public function UnsetGlobalParam($key) {
			unset($this->data[$key]);
		}//public function UnsetParam($key)

		/* Set clear session flag (on commit session will be cleard) */
		public function ClearSession() {
			$this->clear_session = TRUE;
		}//public function ClearSession()

		/* Commit the temporary session into the root session */
		public function CommitSession($clear = FALSE,$preserve_output_buffer = FALSE) {
			if(self::$output_buffer_started===TRUE && $preserve_output_buffer!==TRUE && $this->ajax!==TRUE) { ob_flush(); }
			$_SESSION = array();
			if($clear===TRUE || $this->clear_session===TRUE) {
				$this->data = array();
			} else {
				foreach($this->data as $k=>$v) { $_SESSION[$k] = $v; }
			}//if($clear===TRUE)
		}//public function CommitSession($clear = FALSE)

		public function UrlParamToString($params,$keysonly = FALSE) {
	    	if(is_array($params)) {
	    		$keys = '';
	    		$texts = '';
	    		foreach($params as $k=>$v) {
					$keys .= (strlen($keys)>0 ? ',' : '').$k;
					if($keysonly!==TRUE) {
						$texts .= (strlen($texts)>0 ? ',' : '').str_to_url($v);
					}//if($keysonly===TRUE)
				}//foreach ($params as $k=>$v)
				if($keysonly===TRUE) {
					return $keys;
				} else {
					return $keys.(strlen($texts)>0 ? '~'.$texts : '');
				}//if($keysonly===TRUE)
	    	} else {
	    		return (isset($params) ? $params : '');
	    	}//if(is_array($params))
		}//public function UrlParamToString($params)

		public function GetUrlParamElements($param) {
			$result = NULL;
			if(strlen($param)>0) {
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
			}//if(strlen($param)>0)
			return $result;
		}//public function GetUrlParamElements($param)

	    /*get a parameter from the url data array*/
		public function GetUrlParam($key,$string = FALSE,$keysonly = FALSE) {
			$result = array_key_exists($key,$this->url_data) ? $this->url_data[$key] : NULL;
			if($string===TRUE && isset($result)) {
				return $this->UrlParamToString($result,$keysonly);
			}//if($string===TRUE)
			return $result;
		}//public function GetUrlParam($key,$string = FALSE)

	    /*set a parameter into the url data array*/
		public function SetUrlParam($key,$val) {
			if(is_null($key)) { return FALSE; }
			$this->url_data[$key] = $val;
			return TRUE;
		}//public function SetUrlParam($key,$val)

		/*unset a parameter from the url data array*/
		public function UnsetUrlParam($key) {
			unset($this->url_data[$key]);
		}//public function UnsetUrlParam($key)

		/*gets n-th element from a parameter in the url data array*/
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
		}//public function GetUrlParamElement($key,$position = 0)

		/*adds/modifies an element from a parameter in the url data array*/
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
		}//public function SetUrlParamElement($key,$element,$text = '')

		/*removes an element from a parameter in the url data array*/
		public function UnsetUrlParamElement($key,$element) {
			if(is_null($key) || is_null($element)) { return FALSE; }
			unset($this->url_data[$key][$element]);
		}//public function UnsetUrlParamElement($key,$element)

		public function SetUrlParams($url) {
			$result = array();
			if(is_array($url)) {
				foreach ($url as $k=>$v) { $result[$k] = $this->GetUrlParamElements($v); }
			} else {
				$param_str = explode('?',$urle);
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
		}//public function SetUrlParams($url)

		public function GetUrl($params = NULL,$rparams = NULL,$as_request_uri = FALSE) {
			$result = ($as_request_uri!==TRUE ? $this->app_web_link : '').'/index.php';
			$first = TRUE;
			$data = $this->url_data;
			if(is_array($rparams)) {
				foreach ($rparams as $key=>$value) {
					if(is_array($value)) {
						foreach ($value as $rv) {
							unset($data[$key][$rv]);
						}//foreach ($value as $rv)
						if(count($data[$key])==0) { unset($data[$key]); }
					} else {
						unset($data[$value]);
					}//if(is_array($value))
				}//foreach ($rparams as $key=>$value)
			}//if(is_array($rparams))
			if(is_array($params)) {
				$data = custom_array_merge($data,$params,TRUE);
			}//if(is_array($params))
			foreach ($data as $k=>$v) {
				$prefix = '&';
				if($first) {
					$first = FALSE;
					$prefix = '?';
				}//if($first)
				$result .= $prefix.$k.'='.$this->UrlParamToString($v);
			}//foreach ($data as $k=>$v)
			return $result;
		}//public function GetUrl($params = NULL,$rparams = NULL,$as_request_uri = FALSE)

		public function GetNewUrl($params = NULL,$as_request_uri = FALSE) {
			$result = ($as_request_uri!==TRUE ? $this->app_web_link : '').'/index.php';
			$first = TRUE;
			$data = array();
			if(is_array($params)) {
				$data = custom_array_merge($data,$params,TRUE);
			}//if(is_array($params))
			foreach ($data as $k=>$v) {
				$prefix = '&';
				if($first) {
					$first = FALSE;
					$prefix = '?';
				}//if($first)
				$result .= $prefix.$k.'='.$this->UrlParamToString($v);
			}//foreach ($data as $k=>$v)
			return $result;
		}//public function GetNewUrl($params = NULL,$as_request_uri = FALSE)

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
		}//public function UrlElementExists($key,$element)

		/**
		 * Displays a value in the debugger plugin
		 *
		 * @param  mixed $value Value to be displayd by the debug objects
		 * @param  string $label Label asigned tot the value to be displayed
		 * @param  string $type Debug type defined bay PAF_DBG_... constants
		 * (PAF_DBG_DEBUG, PAF_DBG_WARNING, PAF_DBG_ERROR or PAF_DBG_INFO)
		 * @return void
		 * @access public
		 */
		public function Debug($value,$label = '',$type = PAF_DBG_DEBUG) {
			if(!self::$debug) { return; }
			foreach($this->debug_objects as $dn=>$do) {
				if(is_object($do)) {
					switch($dn) {
					  	case 'ChromePhp':
							switch($type) {
								case PAF_DBG_WARNING:
									$do::warn($label,$value);
									break;
								case PAF_DBG_ERROR:
									$do::error($label,$value);
									break;
								case PAF_DBG_INFO:
									$do::info($label,$value);
									break;
							  	case PAF_DBG_DEBUG:
							  	default:
									$do::log($label,$value);
									break;
							}//END switch($type)
							break;
						case 'PhpConsole':
							switch($type) {
								case PAF_DBG_WARNING:
								case PAF_DBG_ERROR:
								case PAF_DBG_INFO:
							  	case PAF_DBG_DEBUG:
							  	default:
									$do->getDebugDispatcher()->dispatchDebug($value,$label);
									break;
							}//END switch($type)
							break;
					  	case 'FireLogger':
							switch($type) {
								case PAF_DBG_WARNING:
								case PAF_DBG_ERROR:
								case PAF_DBG_INFO:
									$dlevel = $type;
									break;
							  	case PAF_DBG_DEBUG:
							  	default:
									$dlevel = PAF_DBG_DEBUG;
									break;
							}//END switch($type)
							$do->log($dlevel,$value);
							break;
						case 'FirePHP':
							switch($type) {
								case PAF_DBG_WARNING:
									$do->warn($value,$label);
									break;
								case PAF_DBG_ERROR:
									$do->error($value,$label);
									break;
								case PAF_DBG_INFO:
									$do->info($value,$label);
									break;
							  	case PAF_DBG_DEBUG:
							  	default:
									$do->log($value,$label);
									break;
							}//END switch($type)
						break;
					  	default:
							break;
					}//END switch($dn)
				}//if(is_object($do))
			}//END foreach($this->debug_objects as $do)
		}//END public function Debug
		/**
		 * Displays a value in the debugger plugin as a debug message
		 *
		 * @param  mixed $value Value to be displayd by the debug objects
		 * @param  string $label Label asigned tot the value to be displayed
		 * @return void
		 * @access public
		 */
		public function Dlog($value,$label = '') {
			$this->Debug($value,$label,PAF_DBG_DEBUG);
		}//END public function Dlog
		/**
		 * Displays a value in the debugger plugin as a warning message
		 *
		 * @param  mixed $value Value to be displayd by the debug objects
		 * @param  string $label Label asigned tot the value to be displayed
		 * @return void
		 * @access public
		 */
		public function Wlog($value,$label = '') {
			$this->Debug($value,$label,PAF_DBG_WARNING);
		}//END public function Wlog
		/**
		 * Displays a value in the debugger plugin as an error message
		 *
		 * @param  mixed $value Value to be displayd by the debug objects
		 * @param  string $label Label asigned tot the value to be displayed
		 * @return void
		 * @access public
		 */
		public function Elog($value,$label = '') {
			$this->Debug($value,$label,PAF_DBG_ERROR);
		}//END public function Elog
		/**
		 * Displays a value in the debugger plugin as an info message
		 *
		 * @param  mixed $value Value to be displayd by the debug objects
		 * @param  string $label Label asigned tot the value to be displayed
		 * @return void
		 * @access public
		 */
		public function Ilog($value,$label = '') {
			$this->Debug($value,$label,PAF_DBG_INFO);
		}//END public function Ilog
		/**
		 * description
		 *
		 * @param  type $params = array() param description
		 * @return void return description
		 * @access public
		 */
		public function AppLoggerAddEvent($params = array(),$log = TRUE) {
			if(!$log || !self::$app_logging || !is_object($this->logger)) { return FALSE;}
			return $this->logger->AddEvent($params);
		}//END public function AppLoggerAddEvent
		/**
		 * Writes a message in one of the application log files
		 *
		 * @param  string $msg Text to be written to log
		 * @param  string $type Log type (log, error or debug) (optional)
		 * @param  string $file Custom log file complete name (path + name) (optional)
		 * @return void
		 * @access public
		 */
		public function WriteToLog($msg,$type = 'log',$file = '') {
			switch(strtolower($type)) {
				case 'error':
					return self::AddToLog($msg,strlen($file)>0 ? $file : $this->app_absolute_path.self::$logs_path.self::$errors_log_file);
				case 'debug':
					return self::AddToLog($msg,strlen($file)>0 ? $file : $this->app_absolute_path.self::$logs_path.self::$debugging_log_file);
				case 'log':
				default:
					return self::AddToLog($msg,strlen($file)>0 ? $file : $this->app_absolute_path.self::$logs_path.self::$log_file);
			}//switch(strtolower($type))
		}//public function WriteToLog($msg,$type = 'log',$file = '')
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
			$result = "Can't open $file!";
			$absolute_path = self::ExtractAbsolutePath();
			$lf = strlen($file)>0 ? $file : $absolute_path.self::$logs_path.'unknown.log';
			$lfile = fopen($lf,'a') or exit("Can't open $lf !");
			if($lfile) {
				if(is_array($msg) && count($msg)>0) {
					$script_name = (array_key_exists('file',$msg) && strlen($msg['file'])>0) ? $msg['file'] : (strlen($script_name)>0 ? $script_name : __FILE__);
					$script_name .= (array_key_exists('line',$msg) && strlen($msg['line'])>0) ? ' (ln: '.$msg['line'].')' : '';
					$type = (array_key_exists('type',$msg) && strlen($msg['type'])>0) ? ' #'.strtoupper($msg['type']).((array_key_exists('no',$msg) && strlen($msg['no'])>0) ? ':'.strtoupper($msg['no']) : '').'#' : '';
					$message = (array_key_exists('message',$msg) && strlen($msg['message'])>0) ? $msg['message'] : '';
				} else {
					$script_name = strlen($script_name)>0 ? $script_name : __FILE__;
					$type = ' #LOG#';
					$message = $msg;
				}//if(is_array($msg) && count($msg)>0)
				$time = date('Y-m-d H:i:s');
				fwrite($lfile,"#$time# <$script_name>$type $message\n");
				fclose($lfile) or exit("Can't close $lf !");
				$result = TRUE;
			}//if($lfile)
			return $result;
		}//public static function AddToLog($msg,$file = '',$script_name = '')
		/**
		 * Starts a debug timer
		 *
		 * @param  string $name Name of the timer (required)
		 * @return void
		 * @access public
		 */
		public function TimerStart($name) {
			$this->debug_timers[$name] = microtime(TRUE);
		}//END public function TimerStart
		/**
		 * Displays a debug timer elapsed time
		 *
		 * @param  string $name Name of the timer (required)
		 * @param  bool $stop Flag for stopping and destroying the timer (default TRUE)
		 * @return void
		 * @access public
		 */
		public function TimerShow($name,$stop = TRUE) {
			$time = $this->debug_timers[$name];
			if($stop) { unset($this->debug_timers[$name]); }
			return (microtime(TRUE)-$time);
		}//END public function TimerStart
	}//END class PAF extends PAFConfig
?>