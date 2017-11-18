<?php
/**
 * ADebugger class file
 *
 * Class used for application debugging
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2010 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
 * @filesource
 */
    /**
     * PAF_DBG_DEBUG constant definition (used as parameter in PAF class debug methods)
     */
    define('PAF_DBG_DEBUG','log');
    /**
     * PAF_DBG_WARNING constant definition (used as parameter in PAF class debug methods)
     */
    define('PAF_DBG_WARNING','warning');
    /**
     * PAF_DBG_ERROR constant definition (used as parameter in PAF class debug methods)
     */
    define('PAF_DBG_ERROR','error');
    /**
     * PAF_DBG_INFO constant definition (used as parameter in PAF class debug methods)
     */
    define('PAF_DBG_INFO','info');
	/**
	 * ADebugger description
	 *
	 * PHP AJAX Framework application debugger
	 *
	 * @package  AdeoTEK\PAF
	 * @access   public
	 */
	class ADebugger {
		/**
		 * @var    array List of debugging plug-ins. To activate/inactivate an plug-in, change the value for "active" key corresponding to that plug-in.
		 * @access protected
		 */
		protected $debug_extensions = array(
			'Firefox'=>array('FirePHP'=>array('active'=>TRUE)),
			'Chrome'=>array('PhpConsole'=>array('active'=>TRUE)),
		);
		/**
		 * @var        boolean Debug mode on/off
		 * @access     protected
		 */
		protected $enabled = FALSE;
		/**
		 * @var    array Array containing debugging plug-ins objects.
		 * @access protected
		 */
		protected $debug_objects = FALSE;
		/**
		 * @var    array Array containing started debug timers.
		 * @access protected
		 * @static
		 */
		protected static $debug_timers = array();
		/**
		 * @var        boolean php console Chrome extension password
		 * @access     public
		 */
		public $phpconsole_password = 'pafD!';
		/**
		 * @var        string Relative path to the logs folder
		 * @access     public
		 */
		public $logs_path = 'logs';
		/**
		 * @var        string Name of the main log file
		 * @access     public
		 */
		public $log_file = 'application.log';
		/**
		 * @var        string Name of the errors log file
		 * @access     public
		 */
		public $errors_log_file = 'errors.log';
		/**
		 * @var        string Name of the debugging log file
		 * @access     public
		 */
		public $debug_log_file = 'debug.log';
		/**
		 * Debugger class constructor
		 *
		 * @param  boolean $debug Debug mode TRUE/FALSE
		 * @param  string $path Application absolute path
		 * @param  string $logs_path Logs directory relative path
		 * @param  string $tmp_path Temp directory absolute path
		 * (must be outside document root)
		 * @return void
		 * @access public
		 */
	    public function __construct($debug,$path,$logs_path = NULL,$tmp_path = NULL) {
			if($debug!==TRUE || !strlen($path)) { return; }
			if(strlen($logs_path)) { $this->logs_path = $logs_path; }
			else { $this->logs_path = rtrim($path,'/').'/'.$this->logs_path; }
			if(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT'])===1) {
				if(is_array($this->debug_extensions) && count($this->debug_extensions) && array_key_exists('Chrome',$this->debug_extensions) && is_array($this->debug_extensions['Chrome']) && count($this->debug_extensions['Chrome'])>0) {
					foreach($this->debug_extensions['Chrome'] as $dk=>$dv) {
						if($dv['active']!==TRUE) { continue; }
						if($dk=='PhpConsole') {
							require_once(rtrim($path,'/').'/'.$dk.'/__autoload.php');
							// $tmp_path = _X_ROOT_PATH.'/../';
							PhpConsole\Connector::setPostponeStorage(new PhpConsole\Storage\File((strlen($tmp_path) ? rtrim($tmp_path,'/') : rtrim($path,'/')).'/phpcons.data'));
							$this->debug_objects[$dk] = PhpConsole\Connector::getInstance();
							if(PhpConsole\Connector::getInstance()->isActiveClient()) {
								$this->debug_objects[$dk]->setServerEncoding('UTF-8');
								if(isset($this->phpconsole_password) && strlen($this->phpconsole_password)) { $this->debug_objects[$dk]->setPassword($this->phpconsole_password); }
							} else {
								$this->debug_objects[$dk] = NULL;
							}//if(PhpConsole\Connector::getInstance()->isActiveClient())
						} else {
							require_once(rtrim($path,'/').'/'.$dk.'.php');
							$this->debug_objects[$dk] = $dk::getInstance();
						}//if($dk=='PhpConsole')
					}//foreach($this->debug_extensions['Chrome'] as $dk=>$dv)
				}//if(is_array($this->debug_extensions) && count($this->debug_extensions)>0 && array_key_exists('Chrome',$this->debug_extensions) && is_array($this->debug_extensions['Chrome']) && count($this->debug_extensions['Chrome'])>0)
			} elseif(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Firefox/',$_SERVER['HTTP_USER_AGENT'])===1) {
				if(is_array($this->debug_extensions) && count($this->debug_extensions) && array_key_exists('Firefox',$this->debug_extensions) && is_array($this->debug_extensions['Firefox']) && count($this->debug_extensions['Firefox'])>0) {
					foreach($this->debug_extensions['Firefox'] as $dk=>$dv) {
						if($dv['active']!==TRUE) { continue; }
						require_once(rtrim($path,'/').'/'.$dk.'.php');
						$this->debug_objects[$dk] = $dk::getInstance(TRUE);
					}//foreach($this->debug_extensions['Firefox'] as $dk=>$dv)
				}//if(is_array($this->debug_extensions) && count($this->debug_extensions)>0 && array_key_exists('Firefox',$this->debug_extensions) && is_array($this->debug_extensions['Firefox']) && count($this->debug_extensions['Firefox'])>0)
			}//if(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT'])===1)
			if(!is_array($this->debug_objects) || !count($this->debug_objects)) { return; }
			$this->enabled = TRUE;
		}//END protected function __construct
		/**
		 * Get enabled state
		 *
		 * @return bool Returns TRUE if enabled or FALSE otherwise
		 * @access public
		 */
		public function IsEnabled() {
			return $this->enabled;
		}//END public function IsEnabled
		/**
		 * Displays a value in the debugger plugin
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned tot the value to be displayed
		 * @param  string $type Debug type defined bay PAF_DBG_... constants
		 * (PAF_DBG_DEBUG, PAF_DBG_WARNING, PAF_DBG_ERROR or PAF_DBG_INFO)
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Debug($value,$label = '',$type = PAF_DBG_DEBUG,$file = FALSE,$path = FALSE) {
			if(!$this->enabled || !is_array($this->debug_objects)) { return; }
			if($file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
			}//if($file===TRUE)
			foreach($this->debug_objects as $dn=>$do) {
				if(is_object($do)) {
					switch($dn) {
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
					}//END switch
				}//if(is_object($do))
			}//END foreach
		}//END public function Debug
		/**
		 * Displays a value in the debugger plugin as a debug message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned tot the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Dlog($value,$label = '',$file = FALSE,$path = FALSE) {
			if($file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
			}//if($file===TRUE)
			$this->Debug($value,$label,PAF_DBG_DEBUG);
		}//END public function Dlog
		/**
		 * Displays a value in the debugger plugin as a warning message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned tot the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Wlog($value,$label = '',$file = FALSE,$path = FALSE) {
			if($file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
			}//if($file===TRUE)
			$this->Debug($value,$label,PAF_DBG_WARNING);
		}//END public function Wlog
		/**
		 * Displays a value in the debugger plugin as an error message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned tot the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Elog($value,$label = '',$file = FALSE,$path = FALSE) {
			if($file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
			}//if($file===TRUE)
			$this->Debug($value,$label,PAF_DBG_ERROR);
		}//END public function Elog
		/**
		 * Displays a value in the debugger plugin as an info message
		 *
		 * @param  mixed $value Value to be displayed by the debug objects
		 * @param  string $label Label assigned tot the value to be displayed
		 * @param  boolean $file Output file name
		 * @param  boolean $path Output file path
		 * @return void
		 * @access public
		 */
		public function Ilog($value,$label = '',$file = FALSE,$path = FALSE) {
			if($file===TRUE) {
				$dbg = debug_backtrace();
				$caller = array_shift($dbg);
				$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
			}//if($file===TRUE)
			$this->Debug($value,$label,PAF_DBG_INFO);
		}//END public function Ilog
		/**
		 * Add entry to log file
		 *
		 * @param  string $msg Text to be written to log
		 * @param  string $file Custom log file complete name (path + name)
		 * @param  string $script_name Name of the file that sent the message to log (optional)
		 * @return bool|string Returns TRUE for success or error message on failure
		 * @access public
		 * @static
		 */
		public static function AddToLog($msg,$file = '',$script_name = '') {
			$lf = strlen($file) ? $file : 'unknown.log';
			try {
				$lfile = fopen($lf,'a');
				if(!$lfile) { throw new XException("Unable to open log file [{$file}]!",E_WARNING,1); }
				if(is_array($msg) && count($msg)>0) {
					$script_name = (array_key_exists('file',$msg) && strlen($msg['file'])) ? $msg['file'] : (strlen($script_name) ? $script_name : __FILE__);
					$script_name .= (array_key_exists('line',$msg) && strlen($msg['line'])) ? ' (ln: '.$msg['line'].')' : '';
					$type = (array_key_exists('type',$msg) && strlen($msg['type'])) ? ' #'.strtoupper($msg['type']).((array_key_exists('no',$msg) && strlen($msg['no'])) ? ':'.strtoupper($msg['no']) : '').'#' : '';
					$message = (array_key_exists('message',$msg) && strlen($msg['message'])) ? $msg['message'] : '';
				} else {
					$script_name = strlen($script_name) ? $script_name : __FILE__;
					$type = ' #LOG#';
					$message = $msg;
				}//if(is_array($msg) && count($msg)>0)
				fwrite($lfile,'#'.date('Y-m-d H:i:s')."# <{$script_name}>{$type} {$message}\n");
				fclose($lfile);
				return TRUE;
			} catch(XException $e) {
				return $e->getMessage();
			}//END try
		}//END public static function AddToLog
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
			$lpath = (is_string($path) && strlen($path) ? rtrim($path,'/') : $this->logs_path).'/';
			switch(strtolower($type)) {
				case 'error':
					return self::AddToLog($msg,$lpath.(strlen($file) ? $file : $this->errors_log_file));
				case 'debug':
					return self::AddToLog($msg,$lpath.(strlen($file) ? $file : $this->debugging_log_file));
				case 'log':
				default:
					return self::AddToLog($msg,$lpath.(strlen($file) ? $file : $this->log_file));
			}//switch(strtolower($type))
		}//END public function WriteToLog
		/**
		 * Starts a debug timer
		 *
		 * @param  string $name Name of the timer (required)
		 * @return void
		 * @access public
		 * @static
		 */
		public static function TimerStart($name) {
			if(!$name) { return FALSE; }
			self::$debug_timers[$name] = microtime(TRUE);
			return TRUE;
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
			if(!$name || !array_key_exists($name,self::$debug_timers)) { return NULL; }
			$time = self::$debug_timers[$name];
			if($stop) { unset(self::$debug_timers[$name]); }
			return (microtime(TRUE)-$time);
		}//END public static function TimerStart
	}//END class ADebugger
?>