<?php
/**
 * PAF (PHP AJAX Framework) ajax requests class file.
 *
 * The PAF class used for working with ajax requests.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0
 * @filesource
 */
namespace PAF;
/**
 * PAF ajax requests class.
 *
 * Class instance can be used for initiating ajax requests.
 *
 * @package  AdeoTEK\PAF
 * @access   public
 */
class AjaxRequest extends AppConfig {
	/**
	 * @var    PAF Reference to the PAF object (for interacting with session data)
	 * @access protected
	 */
	protected $app_object = NULL;
	/**
	 * @var    string Session sub-array key for storing ARequest data
	 * @access protected
	 */
	protected $subsession = NULL;
	/**
	 * @var    bool Flag indicating if the class implementing this class is other that the one set in the config
	 * @access protected
	 */
	protected $custom_class = FALSE;
	/**
	 * @var    array Custom post params to be sent with the ajax request
	 * @access protected
	 */
	protected $aapp_post_params = [];
	/**
	 * @var    array List of actions to be executed on the ajax request
	 * @access protected
	 */
	protected $arequest_actions = [];
	/**
	 * @var    string PAF ajax request session data ID
	 * @access protected
	 */
	protected $aapp_req_id = NULL;
	/**
	 * @var    string Control key for securing the request session data
	 * @access protected
	 */
	protected $aapp_req_key = '';
	/**
	 * @var    string Separator for ajax request arguments
	 * @access protected
	 */
	public static $aapp_req_sep = ']!r![';
	/**
	 * @var    string Separator for function arguments
	 * @access protected
	 */
	public static $aapp_arg_sep = ']!r!a![';
	/**
	 * @var    string Separator for ajax actions
	 * @access protected
	 */
	public static $aapp_act_sep = ']!r!s![';
	/**
	 * @var    int Session keys case
	 * @access protected
	 */
	public static $aapp_session_keys_case = CASE_UPPER;
	/**
	 * @var    string Parsing arguments separator
	 * @access protected
	 */
	protected $aapp_params_sep = ',';
	/**
	 * @var    string Array elements separator
	 * @access protected
	 */
	protected $aapp_arr_params_sep = '~';
	/**
	 * @var    string Array key-value separator
	 * @access protected
	 */
	protected $aapp_arr_key_sep = '|';
	/**
	 * ARequest constructor function
	 *
	 * @param  App $app_obj Reference to the PAF application instance
	 * @param  string $subsession Sub-session key/path
	 * @return void
	 * @access public
	 */
	public function __construct(&$app_obj,$subsession = NULL) {
		$this->app_object = &$app_obj;
		if(is_string($subsession) && strlen($subsession)) {
			$this->subsession = array($subsession,self::$aapp_session_key);
		} elseif(is_array($subsession) && count($subsession)) {
			$subsession[] = self::$aapp_session_key;
			$this->subsession = $subsession;
		} else {
			$this->subsession = self::$aapp_session_key;
		}//if(is_string($subsession) && strlen($subsession))
		$this->Init();
	}//END public function __construct
	/**
	 * Initiate ARequest session data (generate session data id) if is not initialized
	 *
	 * @return void
	 * @access protected
	 */
	protected function Init() {
		$laapp_req_id = $this->app_object->GetGlobalParam(App::ConvertToSessionCase('AAPP_RID',self::$aapp_session_keys_case),FALSE,$this->subsession,FALSE);
		if(strlen($laapp_req_id)) {
			$this->aapp_req_id = $laapp_req_id;
		} else {
			$this->aapp_req_id = self::GetNewUID();
			$this->app_object->SetGlobalParam(App::ConvertToSessionCase('AAPP_RID',self::$aapp_session_keys_case),$this->aapp_req_id,FALSE,$this->subsession,FALSE);
		}//if(strlen($laapp_req_id))
		$this->StartSecureHttp();
	}//END protected function Init
	/**
	 * Clear ARequest session data and re-initialize it
	 *
	 * @return void
	 * @access protected
	 */
	protected function ClearState() {
		$this->app_object->UnsetGlobalParam(App::ConvertToSessionCase('AAPP_RID',self::$aapp_session_keys_case),FALSE,$this->subsession,FALSE);
		$this->app_object->UnsetGlobalParam(App::ConvertToSessionCase('AAPP_UID',self::$aapp_session_keys_case),FALSE,$this->subsession,FALSE);
		$this->aapp_req_id = $this->aapp_req_key = NULL;
		$this->Init();
	}//END protected function ClearState

	protected function StartSecureHttp() {
		if(!self::$aapp_secure_http) { return; }
		$this->aapp_req_key = $this->app_object->GetGlobalParam(App::ConvertToSessionCase('AAPP_UID',self::$aapp_session_keys_case),FALSE,$this->subsession,FALSE);
		if(!strlen($this->aapp_req_key)) {
			$this->aapp_req_key = self::GetNewUID(self::$session_key,'sha256');
			$this->app_object->SetGlobalParam(App::ConvertToSessionCase('AAPP_UID',self::$aapp_session_keys_case),$this->aapp_req_key,FALSE,$this->subsession,FALSE);
		}//if(!strlen($this->aapp_req_key))
	}//END protected function StartSecureHttp

	protected function ClearSecureHttp() {
		$this->app_object->UnsetGlobalParam(App::ConvertToSessionCase('AAPP_UID',self::$aapp_session_keys_case),FALSE,$this->subsession,FALSE);
		$this->aapp_req_key = NULL;
	}//END protected function ClearSecureHttp

	public function GetClassName() {
		return self::$aapp_class_name;
	}//END public function GetClassName

	public function SetClassName($value) {
		$this->custom_class = self::$aapp_class_name!=$value;
		self::$aapp_class_name = $value;
	}//END public function SetClassName

	public function GetClassFile() {
		if(strlen(self::$aapp_class_file)) { return self::$aapp_class_file; }
		return $this->app_object->GetAppAbsolutePath().self::$aapp_class_file_path.self::$aapp_class_file_name;
	}//END public function GetClassFile

	public function SetClassFile($value) {
		$this->custom_class = self::$aapp_class_file!=$value;
		self::$aapp_class_file = $value;
	}//END public function SetClassFile

	public function GetClassFilePath() {
		return self::$aapp_class_file_path;
	}//END public function GetClassFilePath

	public function SetClassFilePath($value) {
		$this->custom_class = self::$aapp_class_file_path!=$value;
		self::$aapp_class_file_path = $value;
	}//END public function SetClassFilePath

	public function GetClassFileName() {
		return self::$aapp_class_file_name;
	}//END public function GetClassFileName

	public function SetClassFileName($value) {
		$this->custom_class = self::$aapp_class_file_name!=$value;
		self::$aapp_class_file_name = $value;
	}//END public function SetClassFileName
	/**
	 * Sets params to be send via post on the ajax request
	 *
	 * @param  array $params Key-value array of parameters to be send via post
	 * @return void
	 * @access public
	 */
	public function SetPostParams($params) {
		if(is_array($params) && count($params)>0) { $this->aapp_post_params = $params; }
	}//END public function SetPostParams

	public function GetSecureHttp() {
		return self::$aapp_secure_http;
	}//END public function GetSecureHttp

	public function SetSecureHttp($value = TRUE) {
		self::$aapp_secure_http = $value;
	}//END public function SetSecureHttp

	public function HasActions() {
		return (count($this->arequest_actions)>0);
	}//END public function HasActions

	public function JsInit($with_output = TRUE) {
		$js = '<script type="text/javascript">'."\n";
		$js .= "\t".'var AAPP_PHASH="'.$this->app_object->phash.'";'."\n";
		$js .= "\t".'var AAPP_TARGET="'.$this->app_object->app_web_link.'/'.self::$aapp_target.'";'."\n";
		$js .= "\t".'var AAPP_UID="'.$this->aapp_req_key.'";'."\n";
		$js .= "\t".'var AAPP_JS_PATH="'.$this->app_object->app_web_link.self::$aapp_js_path.'";'."\n";
		$js .= '</script>'."\n";
		$js .= '<script type="text/javascript" src="'.$this->app_object->app_web_link.self::$aapp_js_path.'/gibberish-aes.min.js?v=1411031"></script>'."\n";
		$js .= '<script type="text/javascript" src="'.$this->app_object->app_web_link.self::$aapp_js_path.'/arequest.min.js?v=1804291"></script>'."\n";
		if(is_object($this->app_object->debugger)) {
			$dbg_scripts = $this->app_object->debugger->GetScripts();
			if(is_array($dbg_scripts) && count($dbg_scripts)) {
				foreach($dbg_scripts as $dsk=>$ds) {
					$js .= '<script type="text/javascript" src="'.$this->app_object->app_web_link.self::$aapp_js_path.'/debug'.$ds.'?v=1712011"></script>'."\n";
				}//END foreach
			}//if(is_array($dbg_scripts) && count($dbg_scripts))
		}//if(is_object($this->app_object->debugger))
		if($with_output===TRUE) { echo $js; }
		return $js;
	}//END public function JsInit
	/**
	 * Description
	 *
	 * @param string $content
	 * @param string $class
	 * @param string $style
	 * @return string
	 * @access public
	 */
	public function ShowStatus($content = 'Working...',$class = '',$style = '') {
		$lclass = strlen($class)>0 ? ' class="'.$class.'"' : '';
		return '<span id="ARequestStatus"'.$lclass.' style="display: none; '.$style.'">'.htmlentities($content).'</span>';
	}//END public function ShowStatus
	/**
	 * Generate command parameters string for AjaxRequest request
	 *
	 * @param      $val
	 * @param null $key
	 * @return string
	 * @access public
	 */
	public function GetCommandParameters($val,$key = NULL) {
		$result = '';
		if(is_array($val)) {
			foreach($val as $k=>$v) {
				if(strlen($key)) {
					$lk = $key.'['.(is_numeric($k) ? '' : $k).']';
				} else {
					$lk = (is_numeric($k) ? '' : $k);
				}//if(strlen($key))
				$result .= (strlen($result) ? '~' : '');
				$result .= $this->GetCommandParameters($v,$lk);
			}//END foreach
		} elseif(strlen($key) || strlen($val)) {
			if(is_numeric($key) && is_string($val) && strpos($val,':')!==FALSE) {
				$result = $val;
			} else {
				$result = (strlen($key) ? "'{$key}'|" : '').(strpos($val,':')!==FALSE ? $val : "'{$val}'");
			}//if(is_numeric($key) && is_string($val) && strpos($val,':')!==FALSE)
		}//if(is_array($value) && count($value))
		return $result;
	}//END public function GetCommandParmeters
	/**
	 * Generate commands string for AjaxRequest request
	 *
	 * @param array $params
	 * @return string
	 * @access public
	 */
	public function GetCommands($params = NULL) {
		if(!is_array($params) || !count($params)) { return NULL; }
		$module = get_array_param($params,'module',NULL,'is_notempty_string');
		$method = get_array_param($params,'method',NULL,'is_notempty_string');
		if(!$module || !$method) { return NULL; }
		$call = get_array_param($params,'call','AjaxRequest','is_notempty_string');
		$target = get_array_param($params,'target','','is_string');
		$lparams = get_array_param($params,'params',array(),'is_array');
		$commands = "AjaxRequest('{$module}','{$method}'";
		if(array_key_exists('target',$lparams)) {
			$ptarget = $lparams['target'];
			unset($lparams['target']);
		} else {
			$ptarget = NULL;
		}//if(array_key_exists('target',$lparams))
		$parameters = $this->GetCommandParameters($lparams);
		// $this->app_object->Dlog($parameters,'$parameters');
		if(strlen($parameters)) { $commands .= ','.$parameters; }
		if(strlen($ptarget)) { $commands .= (strlen($parameters) ? '' : ",''").",'{$ptarget}'"; }
		$commands .= ")".(strlen($target) ? '->'.$target : '');
		return $commands;
	}//END public function GetCommands
	/**
	 * Generate javascript for ajax request
	 * $js_script -> js script or js file name (with full link) to be executed before or after the ajax request
	 *
	 * @param      $commands
	 * @param int  $loader
	 * @param null $confirm
	 * @param null $js_script
	 * @param int  $async
	 * @param int  $run_oninit_event
	 * @param null $post_params
	 * @param null $class_file
	 * @param null $class_name
	 * @param null $interval
	 * @param null $callback
	 * @return string
	 * @access public
	 */
	public function Prepare($commands,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL) {
		// if(self::$x_cache) { return (self::$x_cache_separator.$commands.self::$x_cache_arg_separator.$loader.self::$x_cache_arg_separator.$js_script.self::$x_cache_arg_separator.$class_file.self::$x_cache_arg_separator.$class_name.strrev(self::$x_cache_separator)); }
		$commands = texplode(';',$commands);
		$all_commands = '';
		$request_id = '';
		foreach($commands as $command) {
			$command = str_replace('\\','\\\\',$command);
			$inputType = '';
			$targets = '';
			$eparams = '';
			$jparams = '';
			if(strpos($command,'-<')!==FALSE) {
				$jparams = '{ ';
				foreach(texplode('-<',$command) as $k=>$v) {
					switch($k) {
						case 0:
							$command = trim($v);
							break;
						case 1:
						default:
							$jparams .= ($k>1 ? ',' : '').trim($v).':'.trim($v);
							break;
					}//END switch
				}//END foreach
				$jparams .= ' }';
			}//if(strpos($command,'-<')!==FALSE)
			$tmp = texplode('->',$command);
			if(isset($tmp[0])) { $functions = trim($tmp[0]); }
			if(isset($tmp[1])) { $targets = trim($tmp[1]); }
			if(isset($tmp[2])) { $eparams = trim($tmp[2]); }
			if(strstr($functions,'(')) {
				$action = '';
				$target = '';
				$targetProperty = '';
				$inputArray = explode('(',$functions,2);
				list($function,$args) = $inputArray;
				$args = substr($args,0,-1);
				$tmp = texplode(',',$targets);
				if(isset($tmp[0])) { $target = $tmp[0]; }
				if(isset($tmp[1])) { $action = $tmp[1]; }
				$tmp = texplode(':',$target);
				if(isset($tmp[0])) { $targetId = $tmp[0]; }
				if(isset($tmp[1])) { $targetProperty = $tmp[1]; }
				if(!$action) { $action = 'r'; }
				if(!$targetProperty) { $targetProperty = 'innerHTML'; }
				if(!$targets) { $action = $targetProperty = $targetId = ''; }
				if($function) {
					$request_id = self::GetNewUID($function.$this->aapp_req_id,'sha256',TRUE);
					if($class_file || $class_name || $this->custom_class) {
						$class_file = $class_file ? $class_file : $this->GetClassFile();
						$class_name = $class_name ? $class_name : $this->GetClassName();
						$req_sess_params = array(
							App::ConvertToSessionCase('METHOD',self::$aapp_session_keys_case)=>$function,
							App::ConvertToSessionCase('CLASS_FILE',self::$aapp_session_keys_case)=>$class_file,
							App::ConvertToSessionCase('CLASS',self::$aapp_session_keys_case)=>$class_name,
						);
					} else {
						$req_sess_params = array(
							App::ConvertToSessionCase('METHOD',self::$aapp_session_keys_case)=>$function,
						);
					}//if($class_file || $class_name || $this->custom_class)
					$subsession = is_array($this->subsession) ? $this->subsession : array($this->subsession);
					$subsession[] = App::ConvertToSessionCase('PAF_AREQUEST',self::$aapp_session_keys_case);
					$subsession[] = App::ConvertToSessionCase('AREQUESTS',self::$aapp_session_keys_case);
					$this->app_object->SetGlobalParam(App::ConvertToSessionCase($request_id,self::$aapp_session_keys_case),$req_sess_params,FALSE,$subsession,FALSE);
					$session_id = rawurlencode(\GibberishAES::enc(session_id(),self::$session_key));
					$postparams = $this->PreparePostParams($post_params);
					$args_separators = array($this->aapp_params_sep,$this->aapp_arr_params_sep,$this->aapp_arr_key_sep);
					$phash = self::$aapp_use_window_name ? "'+ARequest.get(window.name)+'".self::$aapp_arg_sep : '';
					$jsarguments = self::$aapp_params_encrypt ? \GibberishAES::enc($phash.$this->ParseArguments($args,$args_separators),$request_id) : $phash.$this->ParseArguments($args,$args_separators);
					$pconfirm = $this->PrepareConfirm($confirm,$request_id);
					$jcallback = strlen($callback) ? $callback : '';
					if(strlen($jcallback) && self::$aapp_params_encrypt) {
						$jcallback = \GibberishAES::enc($jcallback,$request_id);
					}//if(strlen($callback) && self::$aapp_params_encrypt)
					if(is_numeric($interval) && $interval>0) {
						$all_commands .= "ARequest.runRepeated({$interval},'".str_replace("'","\\'",$jsarguments)."',".((int)self::$aapp_params_encrypt).",'{$targetId}','{$action}','{$targetProperty}','{$session_id}','{$request_id}','{$postparams}',{$loader},'{$async}','{$js_script}',{$pconfirm},".(strlen($jparams) ? $jparams : 'undefined').",".(strlen($jcallback) ? $jcallback : 'false').",".($run_oninit_event==1 ? 1 : 0).','.(strlen($eparams) ? $eparams : 'undefined').");";
					} else {
						$all_commands .= 'ARequest.run('."'{$jsarguments}',".((int)self::$aapp_params_encrypt).",'{$targetId}','{$action}','{$targetProperty}','{$session_id}','{$request_id}','{$postparams}',{$loader},'{$async}','{$js_script}',{$pconfirm},".(strlen($jparams) ? $jparams : 'undefined').",".(strlen($jcallback) ? $jcallback : 'false').",".($run_oninit_event==1 ? 1 : 0).','.(strlen($eparams) ? $eparams : 'undefined').");";
					}//if(is_numeric($interval) && $interval>0)
				}//if($function)
			}//if(strstr($functions,'('))
		}//foreach($commands as $command)
		return $all_commands;
	}//END public function Prepare
	/**
	 * Generate javascript call for ajax request (with callback)
	 * $js_script -> js script or js file name (with full link) to be executed before or after the ajax request
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return string
	 * @access public
	 */
	public function PrepareWithCallback($commands,$callback,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
		return $this->Prepare($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,NULL,$callback);
	}//END public function PrepareWithCallback
	/**
	 * Generate javascript call for repeated ajax request
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access public
	 */
	public function PrepareRepeated($interval,$commands,$loader = 1,$js_script = '',$async = 1,$run_oninit_event = 1,$confirm = NULL,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
		return $this->Prepare($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,NULL);
	}//END public function PrepareRepeated
	/**
	 * Adds a new paf run action to the queue
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access public
	 */
	public function Execute($commands,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
		$this->AddAction($this->Prepare($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,NULL,NULL));
	}//END public function Execute
	/**
	 * Adds a new paf run action to the queue (with callback)
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access public
	 */
	public function ExecuteWithCallback($commands,$callback,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
		$this->AddAction($this->Prepare($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,NULL,$callback));
	}//END public function ExecuteWithCallback
	/**
	 * Generate and execute javascript for AjaxRequest request
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return string
	 * @access public
	 */
	public function ExecuteAjaxRequest($params = array(),$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL) {
		$this->AddAction($this->PrepareAjaxRequest($params,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name));
	}//END public function ExecuteAjaxRequest
	/**
	 * Generate javascript for AjaxRequest request
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return string
	 * @access public
	 */
	public function PrepareAjaxRequestWithCallback($params = array(),$callback = NULL,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL) {
		return $this->PrepareAjaxRequest($params,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,$callback);
	}//END public function PrepareAjaxRequestWithCallback
	/**
	 * Generate javascript for AjaxRequest request
	 *
	 * @param array $params Parameters object (instance of [Params])
	 * @param int   $loader
	 * @param null  $confirm
	 * @param null  $js_script
	 * @param int   $async
	 * @param int   $run_oninit_event
	 * @param null  $post_params
	 * @param null  $class_file
	 * @param null  $class_name
	 * @param null  $interval
	 * @param null  $callback
	 * @return string
	 * @access public
	 */
	public function PrepareAjaxRequest($params = array(),$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL) {
		if(!is_array($params) || !count($params)) { return NULL; }
		$commands = $this->GetCommands($params);
		if(!strlen($commands)) { return NULL; }
		return $this->Prepare($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,$callback);
	}//END public function PrepareAjaxRequest

	private function ParseArguments($args,$separators) {
		if(strlen($args)==0) { return ''; }
		$inner = '';
		$separator = NULL;
		$separators = is_string($separators) ? array($separators) : $separators;
		if(is_array($separators)) {
			$separator = array_shift($separators);
			$prefix = $separator==$this->aapp_params_sep ? self::$aapp_arg_sep : "'+'".$separator;
			foreach(texplode($separator,$args) as $v) {
				$inner .= $inner ? $prefix : '';
				if(str_contains($v,$separators)) {
					$inner .= $this->ParseArguments($v,$separators);
				} else {
					$inner .= $this->PrepareArgument($v);
				}//if(str_contains($v,$separators))
			}//foreach(explode($separator,$args) as $v)
		}//if(is_array($separators))
		return $inner;
	}//END private function ParseArguments

	private function PrepareArgument($arg) {
		$id = $property = $attribute = '';
		/* If arg contains ':', arg is element:property syntax */
		if(str_contains($arg,':')) {
			$tmp = texplode(':',$arg);
			if(isset($tmp[0])) { $id = $tmp[0]; }
			if(isset($tmp[1])) { $property = $tmp[1]; }
			if(isset($tmp[2])) { $attribute = $tmp[2]; }
			$arg = '';
		}//if(str_contains($arg,':'))
		if($property) {
			if($attribute) { return "'+ARequest.get('{$id}','{$property}','{$attribute}')+'"; }
			return "'+ARequest.get('{$id}','{$property}')+'";
		}//if($property)
		if($id) { return "'+ARequest.get({$id})+'"; }
		else { return "'+ARequest.get({$arg})+'"; }
	}//END private function PrepareArgument

	private function PrepareConfirm($confirm,$request_id) {
		if(is_string($confirm)) {
			$ctxt = $confirm;
			$ctype = 'js';
		} else {
			$ctxt = get_array_param($confirm,'text','','is_string');
			$ctype = get_array_param($confirm,'type','js','is_notempty_string');
		}//if(is_string($confirm))
		if(!strlen($ctxt)) { return 'undefined'; }
		switch($ctype) {
			case 'jqui':
				$confirm_str = str_replace('"',"'",json_encode(array(
					'type'=>'jqui',
					'message'=>rawurlencode($ctxt),
					'title'=>get_array_param($confirm,'title','','is_string'),
					'ok'=>get_array_param($confirm,'ok','','is_string'),
					'cancel'=>get_array_param($confirm,'cancel','','is_string'),
				)));
				if(self::$aapp_params_encrypt) { $confirm_str = "'".\GibberishAES::enc($confirm_str,$request_id)."'"; }
				// return 'undefined';
				break;
			case 'js':
			default:
				if(self::$aapp_params_encrypt) {
					$confirm_str = str_replace('"',"'",json_encode(array('type'=>'std','message'=>rawurlencode($ctxt))));
					$confirm_str = "'".\GibberishAES::enc($confirm_str,$request_id)."'";
				} else {
					$confirm_str = "'".rawurlencode($ctxt)."'";
				}//if(self::$aapp_params_encrypt)
				break;
		}//END switch
		return $confirm_str;
	}//END private function PrepareConfirm
	/**
	 * Transforms the post params array into a string to be posted by the javascript method
	 *
	 * @param  array  $params An array of parameters to be sent with the request
	 * @return string The post params as a string
	 * @access private
	 */
	private function PreparePostParams($params = NULL) {
		$result = '';
		if(is_array($this->aapp_post_params) && count($this->aapp_post_params)) {
			foreach($this->aapp_post_params as $k=>$v) { $result .= '&'.$k.'='.$v; }
		}//if(is_array($this->aapp_post_params) && count($this->aapp_post_params))
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) { $result .= '&'.$k.'='.$v; }
		}//if(is_array($params) && count($params))
		return $result;
	}//END private function PreparePostParams

	private function AddAction($action) {
		$this->arequest_actions[] = $action;
	}//private function AddAction(string $action)

	public function GetActions() {
		if(!$this->HasActions()) { return NULL; }
		return self::$aapp_act_sep.implode(';',$this->arequest_actions).self::$aapp_act_sep;
	}//END public function GetActions

	/*** PAF js response functions ***/
	/**
	 * Execute javascript code
	 * @param $jscript
	 */
	public function ExecuteJs($jscript) {
		if(is_string($jscript) && strlen($jscript)) { $this->AddAction($jscript); }
	}//END public function ExecuteJs

	/* Redirect the browser to a URL */
	public function Redirect($url) {
		$this->AddAction("window.location.href = '{$url}'");
	}//END public function Redirect

	/* Reloads current page */
	public function Refresh() {
		$this->AddAction("window.location.reload()");
	}//END public function Refresh

	/* Display a javascript alert */
	public function Alert($text) {
		$this->AddAction("alert(\"".addslashes($text)."\")");
	}//END public function Alert

	/* Submit a form on the page */
	public function Submit($form) {
		$this->AddAction("document.forms['$form'].submit()");
	}//END public function Submit
	/**
	 * Used for placing complex/long text into an element (text or html)
	 *
	 * @param  sting $content The content to be inserted in the element
	 * @param  string $target The id of the element
	 * @return void
	 * @access public
	 */
	public function InnerHtml($content,$target) {
		$action = '';
		$targetProperty = '';
		$target_arr = texplode(',',$target);
		$target = $target_arr[0];
		if(count($target_arr)>1) { $action = $target_arr[1]; }
		$target_arr2 = texplode(':',$target);
		$targetId = $target_arr2[0];
		if(count($target_arr2)>1) { $targetProperty = $target_arr2[1]; }
		if(!$action) { $action = 'r'; }
		if(!$targetProperty) { $targetProperty = 'innerHTML'; }
		$action = "ARequest.put(decodeURIComponent('".rawurlencode($content)."'),'$targetId','$action','$targetProperty')";
		$this->AddAction($action);
	}//END public function InnerHtml
	/**
	 * Hides an element (sets css display property to none)
	 *
	 * @param  string $element Id of element to be hidden
	 * @return void
	 * @access public
	 */
	public function Hide($element) {
		$this->AddAction("ARequest.put('none','$element','r','style.display')");
	}//END public function Hide
	/**
	 * Shows an element (sets css display property to '')
	 *
	 * @param  string $element Id of element to be shown
	 * @return void
	 * @access public
	 */
	public function Show($element) {
		$this->AddAction("ARequest.put('','$element','r','style.display')");
	}//END public function Show
	/**
	 * Set style for an element
	 *
	 * @param  string $element Id of element to be set
	 * @param  string $styleString Style to be set
	 * @return void
	 * @access public
	 */
	public function Style($element,$styleString) {
		$this->AddAction("ARequest.setStyle('$element', '$styleString')");
	}//END public function Style
	/**
	 * Return response actions to javascript for execution and clears actions property
	 *
	 * @return array The array containing all actions to be executed
	 * @access public
	 */
	public function Send() {
		$actions = $this->GetActions();
		$this->actions = array();
		return $actions;
	}//END public function Send
	//END PAF js response functions

	public function ExecuteRequest($function,$args) {
		//Kill magic quotes if they are on
		if(get_magic_quotes_gpc()) { $args = stripslashes($args); }
		//decode encrypted HTTP data if needed
		$args = utf8_decode(rawurldecode($args));
		if(self::$aapp_secure_http) {
			if(!$this->aapp_req_key) { return "ARequest ERROR: [$function] Not validated."; }
			$args = \GibberishAES::dec($args,$this->aapp_req_key);
		}//if(self::$aapp_secure_http)
		//limited to 100 arguments for DNOS attack protection
		$args = explode(self::$aapp_arg_sep,$args,100);
		for($i=0; $i<count($args); $i++) {
			$args[$i] = $this->Utf8Unserialize(rawurldecode($args[$i]));
			$args[$i] = str_replace(self::$aapp_act_sep,'',$args[$i]);
		}//END for
		if(method_exists($this,$function)) {
			echo call_user_func_array(array($this,$function),$args);
		} else {
			echo "ARequest ERROR: [$function] Not validated.";
		}//if(method_exists($this,$function))
		return NULL;
	}//END public function ExecuteRequest

	private function Utf8Unserialize($str) {
		$rsearch = array('^[!]^','^[^]^');
		$rreplace = array('|','~');
		if(strpos(trim($str,'|'),'|')===FALSE && strpos(trim($str,'~'),'~')===FALSE) { return $this->ArrayNormalize(str_replace($rsearch,$rreplace,unserialize($str))); }
		$ret = array();
		foreach(explode('~',$str) as $arg) {
			$sarg = explode('|',$arg);
			if(count($sarg)>1) {
				$rval = $this->ArrayNormalize(str_replace($rsearch,$rreplace,unserialize($sarg[1])));
				$rkey = $this->ArrayNormalize(str_replace($rsearch,$rreplace,unserialize($sarg[0])),$rval);
				if(is_array($rkey)) {
					$ret = array_merge_recursive($ret,$rkey);
				} else {
					$ret[$rkey] = $rval;
				}//if(is_array($rkey))
			} else {
				$tmpval = $this->ArrayNormalize(str_replace($rsearch,$rreplace,unserialize($sarg[0])));
				if(is_array($tmpval) && count($tmpval)) {
					foreach($tmpval as $k=>$v) { $ret[$k] = $v; }
				} else {
					$ret[] = $tmpval;
				}//if(is_array($tmpval) && count($tmpval))
			}//if(count($sarg)>1)
		}//END foreach
		return $ret;
	}//END private function Utf8Unserialize

	private function ArrayNormalize($arr,$val = NULL) {
		if(is_string($arr)) {
			$res = preg_replace('/\A#k#_/','',$arr);
			if(is_null($val) || $res!=$arr || strpos($arr,'[')===FALSE || strpos($arr,']')===FALSE) { return $res; }
			$tres = explode('][',trim(preg_replace('/^\w+/','${0}]',$arr),'['));
			$res = $val;
			foreach(array_reverse($tres) as $v) {
				$rk = trim($v,']');
				$res = strlen($rk) ? array($rk=>$res) : array($res);
			}//END foreach
			return $res;
		}//if(is_string($arr))
		if(!is_array($arr) || !count($arr)) { return $arr; }
		$result = array();
		foreach($arr as $k=>$v) { $result[preg_replace('/\A#k#_/','',$k)] = is_array($v) ? $this->ArrayNormalize($v) : $v; }
		return $result;
	}//END private function ArrayNormalize
}//END class AjaxRequest extends AppConfig
?>