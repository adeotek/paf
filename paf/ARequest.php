<?php
/**
 * PAF (PHP AJAX Framework) ajax requests class file.
 *
 * The PAF class used for working with ajax requests.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2010 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
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
	class ARequest extends PAFAppConfig {
		/**
		 * @var    PAF Reference to the PAF object (for interacting with session data)
		 * @access protected
		 */
		protected $aapp_object = NULL;
		/**
		 * @var    string Session sub-array key for storing PAFReq data
		 * @access protected
		 */
		protected $subsession = NULL;
		/**
		 * @var    bool [with_context] argument default value
		 * @access protected
		 */
		protected $with_context_default = FALSE;
		/**
		 * @var    bool Flag indicating if the class implementing this class is other that the one set in the config
		 * @access protected
		 */
		protected $custom_class = FALSE;
		/**
		 * @var    array Custom post params to be sent with the ajax request
		 * @access protected
		 */
		protected $paf_post_params = array();
		/**
		 * @var    array List of actions to be executed on the ajax request
		 * @access protected
		 */
		protected $req_actions = array();
		/**
		 * @var    string PAF ajax request session data id
		 * @access protected
		 */
		protected $pafi = NULL;
		/**
		 * @var    string Control key for securing the request session data
		 * @access protected
		 */
		protected $paf_http_key = '';
		/**
		 * @var    string Separator for ajax request arguments
		 * @access protected
		 */
		public static $paf_req_separator = ']!PAF![';
		/**
		 * @var    string Separator for function arguments
		 * @access protected
		 */
		public static $paf_arg_separator = ']!paf!a![';
		/**
		 * @var    string Separator for ajax actions
		 * @access protected
		 */
		public static $paf_act_separator = ']!paf!s![';
		/**
		 * @var    int Session keys case
		 * @access protected
		 */
		public static $paf_session_keys_case = CASE_UPPER;
		/**
		 * @var    string Parsing arguments separator
		 * @access protected
		 */
		protected $paf_params_separator = ',';
		/**
		 * @var    string Array elements separator
		 * @access protected
		 */
		protected $paf_arr_e_separator = '~';
		/**
		 * @var    string Array key-value separator
		 * @access protected
		 */
		protected $paf_arr_kv_separator = '|';
		/**
		 * PAFReq constructor function
		 *
		 * @param  AApp $app_obj Reference to the PAF application instance
		 * @param  string $subsession Sub-session key/path
		 * @return void
		 * @access public
		 */
		public function __construct(&$app_obj,$subsession = NULL) {
			$this->aapp_object = &$app_obj;
			if(is_string($subsession) && strlen($subsession)) {
				$this->subsession = array($subsession,self::$paf_session_key);
			} elseif(is_array($subsession) && count($subsession)) {
				$subsession[] = self::$paf_session_key;
				$this->subsession = $subsession;
			} else {
				$this->subsession = self::$paf_session_key;
			}//if(is_string($subsession) && strlen($subsession))
			$this->Init();
		}//END public function __construct
		/**
		 * Initiate PAFReq session data (generate session data id) if is not initialized
		 *
		 * @return void
		 * @access protected
		 */
		protected function Init() {
			$lpafi = $this->aapp_object->GetGlobalParam(AApp::ConvertToSessionCase('PAF_PAFI',self::$paf_session_keys_case),FALSE,$this->subsession,FALSE);
			if(strlen($lpafi)) {
				$this->pafi = $lpafi;
			} else {
				$this->pafi = self::GenerateUID();
				$this->aapp_object->SetGlobalParam(AApp::ConvertToSessionCase('PAF_PAFI',self::$paf_session_keys_case),$this->pafi,FALSE,$this->subsession,FALSE);
			}//if(strlen($lpafi))
			$this->StartSecureHttp();
		}//END protected function Init
		/**
		 * Clear PAFReq session data and re-initialize it
		 *
		 * @return void
		 * @access protected
		 */
		protected function ClearState() {
			$this->aapp_object->UnsetGlobalParam(AApp::ConvertToSessionCase('PAF_PAFI',self::$paf_session_keys_case),FALSE,$this->subsession,FALSE);
			$this->aapp_object->UnsetGlobalParam(AApp::ConvertToSessionCase('PAF_HTTPK',self::$paf_session_keys_case),FALSE,$this->subsession,FALSE);
			$this->pafi = $this->paf_http_key = NULL;
			$this->Init();
		}//END protected function ClearState

		protected function StartSecureHttp() {
			if(!self::$paf_secure_http) { return; }
			$this->paf_http_key = $this->aapp_object->GetGlobalParam(AApp::ConvertToSessionCase('PAF_HTTPK',self::$paf_session_keys_case),FALSE,$this->subsession,FALSE);
			if(!strlen($this->paf_http_key)) {
				$this->paf_http_key = self::GenerateUID(self::$session_key,'sha256');
				$this->aapp_object->SetGlobalParam(AApp::ConvertToSessionCase('PAF_HTTPK',self::$paf_session_keys_case),$this->paf_http_key,FALSE,$this->subsession,FALSE);
			}//if(!strlen($this->paf_http_key))
		}//END protected function StartSecureHttp

		protected function ClearSecureHttp() {
			$this->aapp_object->UnsetGlobalParam(AApp::ConvertToSessionCase('PAF_HTTPK',self::$paf_session_keys_case),FALSE,$this->subsession,FALSE);
			$this->paf_http_key = NULL;
		}//END protected function ClearSecureHttp

		public function GetClassName() {
			return self::$paf_class_name;
		}//END public function GetClassName

		public function SetClassName($value) {
			$this->custom_class = self::$paf_class_name!=$value;
			self::$paf_class_name = $value;
		}//END public function SetClassName

		public function GetClassFile() {
			if(strlen(self::$paf_class_file)) { return self::$paf_class_file; }
			return $this->aapp_object->GetAppAbsolutePath().self::$paf_class_file_path.self::$paf_class_file_name;
		}//END public function GetClassFile

		public function SetClassFile($value) {
			$this->custom_class = self::$paf_class_file!=$value;
			self::$paf_class_file = $value;
		}//END public function SetClassFile

		public function GetClassFilePath() {
			return self::$paf_class_file_path;
		}//END public function GetClassFilePath

		public function SetClassFilePath($value) {
			$this->custom_class = self::$paf_class_file_path!=$value;
			self::$paf_class_file_path = $value;
		}//END public function SetClassFilePath

		public function GetClassFileName() {
			return self::$paf_class_file_name;
		}//END public function GetClassFileName

		public function SetClassFileName($value) {
			$this->custom_class = self::$paf_class_file_name!=$value;
			self::$paf_class_file_name = $value;
		}//END public function SetClassFileName

		public function GetUtf8() {
			return self::$paf_utf8;
		}//END public function GetUtf8

		public function SetUtf8($value = TRUE) {
			self::$paf_utf8 = $value;
		}//END public function SetUtf8
		/**
		 * Sets params to be send via post on the ajax request
		 *
		 * @param  array $params Key-value array of parameters to be send via post
		 * @return void
		 * @access public
		 */
		public function SetPostParams($params) {
			if(is_array($params) && count($params)>0) { $this->paf_post_params = $params; }
		}//END public function SetPostParams

		public function GetSecureHttp() {
			return self::$paf_secure_http;
		}//END public function GetSecureHttp

		public function SetSecureHttp($value = TRUE) {
			self::$paf_secure_http = $value;
		}//END public function SetSecureHttp

		public function HasActions() {
			return (count($this->req_actions)>0);
		}//END public function HasActions

		public function jsInit($with_output = TRUE) {
			$js = '<script type="text/javascript">'."\n";
			$js .= "\t".'var PAF_PHASH="'.$this->aapp_object->phash.'";'."\n";
			$js .= "\t".'var PAF_TARGET="'.$this->aapp_object->app_web_link.'/'.self::$paf_target.'";'."\n";
			$js .= "\t".'var PAF_HTTPK="'.$this->paf_http_key.'";'."\n";
			$js .= "\t".'var PAF_JS_PATH="'.$this->aapp_object->app_web_link.self::$paf_js_path.'";'."\n";
			$js .= '</script>'."\n";
			$js .= '<script type="text/javascript" src="'.$this->aapp_object->app_web_link.self::$paf_js_path.'/gibberish-aes.min.js?v=1411031"></script>'."\n";
			$js .= '<script type="text/javascript" src="'.$this->aapp_object->app_web_link.self::$paf_js_path.'/arequest.min.js?v=1711181"></script>'."\n";
			if($with_output===TRUE) { echo $js; }
			return $js;
		}//END public function jsInit
		/**
		 * Description
		 *
		 * @param  type $param_name param description
		 * @return void
		 * @access public
		 */
		public function ShowStatus($content = 'Working...',$class = '',$style = '') {
			$lclass = strlen($class)>0 ? ' class="'.$class.'"' : '';
			return '<span id="PAFReqStatus"'.$lclass.' style="display: none; '.$style.'">'.htmlentities($content).'</span>';
		}//END public function ShowStatus
		/**
		 * Generate and execute javascript for AjaxCall request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function ExecuteAjaxCall($params = array(),$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL) {
			$this->AddAction($this->PrepareAjaxCall($params,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name));
		}//END public function ExecuteAjaxCall
		/**
		 * Generate javascript for AjaxCall request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function PrepareAjaxCallWithCallback($params = array(),$callback = NULL,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$with_context = NULL) {
			return $this->PrepareAjaxCall($params,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,$callback,$with_context);
		}//END public function PrepareAjaxCallWithCallback
		/**
		 * Generate javascript for AjaxCall request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function PrepareAjaxCall($params = array(),$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL,$with_context = NULL) {
			if(!is_array($params) || !count($params)) { return NULL; }
			$commands = $this->GetCommands($params);
			if(!strlen($commands)) { return NULL; }
			return $this->run($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,$callback,$with_context);
		}//END public function PrepareAjaxCall
		/**
		 * Generate command parameters string for AjaxCall request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function GetCommandParmeters($val,$key = NULL) {
			$result = '';
			if(is_array($val)) {
				foreach($val as $k=>$v) {
					$lk = strlen($key) ? "{$key}[{$k}]" : $k;
					$result .= (strlen($result) ? '~' : '');
					$result .= $this->GetCommandParmeters($v,$lk);
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
		 * Generate commands string for AjaxCall request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function GetCommands($params = array()) {
			if(!is_array($params) || !count($params)) { return NULL; }
			$module = get_array_param($params,'module',NULL,'is_notempty_string');
			$method = get_array_param($params,'method',NULL,'is_notempty_string');
			if(!$module || !$method) { return NULL; }
			$call = get_array_param($params,'call','AjaxCall','is_notempty_string');
			$target = get_array_param($params,'target','','is_string');
			$lparams = get_array_param($params,'params',array(),'is_array');
			$commands = "AjaxCall('{$module}','{$method}'";
			if(array_key_exists('target',$lparams)) {
				$ptarget = $lparams['target'];
				unset($lparams['target']);
			} else {
				$ptarget = NULL;
			}//if(array_key_exists('target',$lparams))
			$parameters = $this->GetCommandParmeters($lparams);
			// XSession::_Dlog($parameters,'$parameters');
			if(strlen($parameters)) { $commands .= ','.$parameters; }
			if(strlen($ptarget)) { $commands .= (strlen($parameters) ? '' : ",''").",'{$ptarget}'"; }
			$commands .= ")".(strlen($target) ? '->'.$target : '');
			return $commands;
		}//END public function GetCommands
		/**
		 * Generate javascript for ajax request
		 * $js_script -> js script or js file name (with full link) to be executed before or after the ajax request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function run($commands,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL,$with_context = NULL) {
			// if(self::$x_cache) { return (self::$x_cache_separator.$commands.self::$x_cache_arg_separator.$loader.self::$x_cache_arg_separator.$js_script.self::$x_cache_arg_separator.$class_file.self::$x_cache_arg_separator.$class_name.strrev(self::$x_cache_separator)); }
			$commands = texplode(';',$commands);
			$all_commands = '';
			$request_id = '';
			foreach($commands as $command) {
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
						$request_id = self::GenerateUID($function.$this->pafi,'sha256',TRUE);
						if($class_file || $class_name || $this->custom_class) {
							$class_file = $class_file ? $class_file : $this->GetClassFile();
							$class_name = $class_name ? $class_name : $this->GetClassName();
							$req_sess_params = array(
								AApp::ConvertToSessionCase('UTF8',self::$paf_session_keys_case)=>self::$paf_utf8,
								AApp::ConvertToSessionCase('FUNCTION',self::$paf_session_keys_case)=>$function,
								AApp::ConvertToSessionCase('CLASS_FILE',self::$paf_session_keys_case)=>$class_file,
								AApp::ConvertToSessionCase('CLASS',self::$paf_session_keys_case)=>$class_name,
							);
						} else {
							$req_sess_params = array(
								AApp::ConvertToSessionCase('UTF8',self::$paf_session_keys_case)=>self::$paf_utf8,
								AApp::ConvertToSessionCase('FUNCTION',self::$paf_session_keys_case)=>$function,
							);
						}//if($class_file || $class_name || $this->custom_class)
						$subsession = is_array($this->subsession) ? $this->subsession : array($this->subsession);
						$subsession[] = AApp::ConvertToSessionCase('PAF_REQUEST',self::$paf_session_keys_case);
						$subsession[] = AApp::ConvertToSessionCase('REQUESTS',self::$paf_session_keys_case);
						$this->aapp_object->SetGlobalParam(AApp::ConvertToSessionCase($request_id,self::$paf_session_keys_case),$req_sess_params,FALSE,$subsession,FALSE);
						$session_id = rawurlencode(GibberishAES::enc(session_id(),self::$session_key));
						$postparams = $this->PreparePostParams($post_params);
						$args_separators = array($this->paf_params_separator,$this->paf_arr_e_separator,$this->paf_arr_kv_separator);
						$phash = self::$paf_use_window_name ? "'+PAFReq.get(window.name)+'".self::$paf_arg_separator : '';
						$jsarguments = self::$paf_params_encrypt ? GibberishAES::enc($phash.$this->ParseArguments($args,$args_separators),$request_id) : $phash.$this->ParseArguments($args,$args_separators);
						$pconfirm = $this->PrepareConfirm($confirm,$request_id);
						$jcallback = strlen($callback) ? $callback : '';
						if(strlen($jcallback) && self::$paf_params_encrypt) {
							$jcallback = GibberishAES::enc($jcallback,$request_id);
						}//if(strlen($callback) && self::$paf_params_encrypt)
						if(is_numeric($interval) && $interval>0) {
							$all_commands .= "PAFReq.runRepeated({$interval},'".str_replace("'","\\'",$jsarguments)."',".((int)self::$paf_params_encrypt).",'{$targetId}','{$action}','{$targetProperty}','{$session_id}','{$request_id}','{$postparams}',{$loader},'{$async}','{$js_script}',{$pconfirm},".(strlen($jparams) ? $jparams : 'undefined').",".(strlen($jcallback) ? $jcallback : 'false').",".($run_oninit_event==1 ? 1 : 0).','.(strlen($eparams) ? $eparams : 'undefined').");";
						} else {
							$with_context = is_bool($with_context) ? $with_context : $this->with_context_default;
							$all_commands .= 'PAFReq.run('."'{$jsarguments}',".((int)self::$paf_params_encrypt).",'{$targetId}','{$action}','{$targetProperty}','{$session_id}','{$request_id}','{$postparams}',{$loader},'{$async}','{$js_script}',{$pconfirm},".(strlen($jparams) ? $jparams : 'undefined').",".(strlen($jcallback) ? $jcallback : 'false').",".($run_oninit_event==1 ? 1 : 0).','.(strlen($eparams) ? $eparams : 'undefined').",".($with_context ? 'event' : 'null').");";
						}//if(is_numeric($interval) && $interval>0)
					}//if($function)
				}//if(strstr($functions,'('))
			}//foreach($commands as $command)
			return $all_commands;
		}//END public function run
		/**
		 * Generate javascript call for ajax request (with callback)
		 * $js_script -> js script or js file name (with full link) to be executed before or after the ajax request
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function run_with_callback($commands,$callback,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$with_context = NULL) {
			return $this->run($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,NULL,$callback,$with_context);
		}//END public function run_with_callback
		/**
		 * Generate javascript call for repeated ajax request
		 *
		 * @param  type $param_name param description
		 * @return void
		 * @access public
		 */
		public function run_repeated($interval,$commands,$loader = 1,$js_script = '',$async = 1,$run_oninit_event = 1,$confirm = NULL,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
			return $this->run($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,NULL,FALSE);
		}//END public function run_repeated
		/**
		 * Adds a new paf run action to the queue
		 *
		 * @param  type $param_name param description
		 * @return void
		 * @access public
		 */
		public function exec_run($commands,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
			$this->AddAction($this->run($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,NULL,NULL,FALSE));
		}//END public function exec_run
		/**
		 * Adds a new paf run action to the queue (with callback)
		 *
		 * @param  type $param_name param description
		 * @return void
		 * @access public
		 */
		public function exec_run_with_callback($commands,$callback,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL) {
			$this->AddAction($this->run($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,NULL,$callback,FALSE));
		}//END public function exec_run_with_callback
		/**
		 * Generate javascript for ajax request with event context
		 *
		 * @param  type $param_name param description
		 * @return string
		 * @access public
		 */
		public function run_with_context($commands,$loader = 1,$confirm = NULL,$js_script = NULL,$async = 1,$run_oninit_event = 1,$post_params = NULL,$class_file = NULL,$class_name = NULL,$interval = NULL,$callback = NULL) {
			return $this->run($commands,$loader,$confirm,$js_script,$async,$run_oninit_event,$post_params,$class_file,$class_name,$interval,$callback,TRUE);
		}//END public function run_with_context

		private function ParseArguments($args,$separators) {
			if(strlen($args)==0) { return ''; }
			$inner = '';
			$separator = NULL;
			$separators = is_string($separators) ? array($separators) : $separators;
			if(is_array($separators)) {
				$separator = array_shift($separators);
				$prefix = $separator==$this->paf_params_separator ? self::$paf_arg_separator : "'+'".$separator;
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
				if($attribute) { return "'+PAFReq.get('{$id}','{$property}','{$attribute}')+'"; }
				return "'+PAFReq.get('{$id}','{$property}')+'";
			}//if($property)
			if($id) { return "'+PAFReq.get({$id})+'"; }
			else { return "'+PAFReq.get({$arg})+'"; }
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
					if(self::$paf_params_encrypt) { $confirm_str = "'".GibberishAES::enc($confirm_str,$request_id)."'"; }
					// return 'undefined';
					break;
				case 'js':
				default:
					if(self::$paf_params_encrypt) {
						$confirm_str = str_replace('"',"'",json_encode(array('type'=>'std','message'=>rawurlencode($ctxt))));
						$confirm_str = "'".GibberishAES::enc($confirm_str,$request_id)."'";
					} else {
						$confirm_str = "'".rawurlencode($ctxt)."'";
					}//if(self::$paf_params_encrypt)
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
		private function PreparePostParams($params = array()) {
			$result = '';
			if(is_array($this->paf_post_params) && count($this->paf_post_params)) {
				foreach($this->paf_post_params as $k=>$v) { $result .= '&'.$k.'='.$v; }
			}//if(is_array($this->paf_post_params) && count($this->paf_post_params))
			if(is_array($params) && count($params)) {
				foreach($params as $k=>$v) { $result .= '&'.$k.'='.$v; }
			}//if(is_array($params) && count($params))
			return $result;
		}//END private function PreparePostParams

		private function AddAction($action) {
			$this->req_actions[] = $action;
		}//private function AddAction(string $action)

		public function GetActions() {
			if(!$this->HasActions()) { return NULL; }
			return self::$paf_act_separator.implode(';',$this->req_actions).self::$paf_act_separator;
		}//END public function GetActions

		/*** PAF js response functions ***/
		/* Execute javascript code */
		public function js($jscript) {
			if(is_string($jscript) && strlen($jscript)) { $this->AddAction($jscript); }
		}//END public function js(string $jscript)

		/* Redirect the browser to a URL */
		public function redirect($url) {
			$this->AddAction("window.location.href = '{$url}'");
		}//END public function redirect($url)

		/* Reloads current page */
		public function refresh() {
			$this->AddAction("window.location.reload()");
		}//END public function refresh()

		/* Display a javascript alert */
		public function alert($txt) {
			$this->AddAction("alert(\"".addslashes($txt)."\")");
		}//END public function alert

		/* Submit a form on the page */
		public function submit($form) {
			$this->AddAction("document.forms['$form'].submit()");
		}//END public function submit
		/**
		 * Used for placing complex/long text into an element (text or html)
		 *
		 * @param  sting $content The content to be inserted in the element
		 * @param  string $target The id of the element
		 * @return void
		 * @access public
		 */
		public function text($content,$target) {
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
			$action = "PAFReq.put(".(self::$paf_utf8 ? 'decodeURIComponent' : 'unescape')."('".rawurlencode($content)."'),'$targetId','$action','$targetProperty')";
			$this->AddAction($action);
		}//END public function text
		/**
		 * Hides an element (sets css display property to none)
		 *
		 * @param  string $element Id of element to be hidden
		 * @return void
		 * @access public
		 */
		public function hide($element) {
			$this->AddAction("PAFReq.put('none','$element','r','style.display')");
		}//END public function hide($element)
		/**
		 * Shows an element (sets css display property to '')
		 *
		 * @param  string $element Id of element to be shown
		 * @return void
		 * @access public
		 */
		public function show($element) {
			$this->AddAction("PAFReq.put('','$element','r','style.display')");
		}//END public function show($element)
		/**
		 * Set style for an element
		 *
		 * @param  string $element Id of element to be set
		 * @param  string $styleString Style to be set
		 * @return void
		 * @access public
		 */
		public function style($element,$styleString) {
			$this->AddAction("PAFReq.setStyle('$element', '$styleString')");
		}//END public function style($element,$styleString)
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
		}//END public function Send()
		//END PAF js response functions

		public function RunFunc($function,$args) {
			//Kill magic quotes if they are on
			if(get_magic_quotes_gpc()) { $args = stripslashes($args); }
			//decode encrypted HTTP data if needed
			$args = utf8_decode(rawurldecode($args));
			if(self::$paf_secure_http) {
				if(!$this->paf_http_key) { return "PAFReq ERROR: [$function] Not validated."; }
				$args = GibberishAES::dec($args,$this->paf_http_key);
			}//if(self::$paf_secure_http)
			//limited to 100 arguments for DNOS attack protection
			$args = explode(self::$paf_arg_separator,$args,100);
			for($i=0; $i<count($args); $i++) {
				$args[$i] = $this->Utf8Unserialize(rawurldecode($args[$i]));
				$args[$i] = str_replace(self::$paf_act_separator,'',$args[$i]);
			}//END for
			if(method_exists($this,$function)) {
				echo call_user_func_array(array($this,$function),$args);
			} else {
				echo "PAFReq ERROR: [$function] Not validated.";
			}//if(method_exists($this,$function))
			return NULL;
		}//END public function RunFunc

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
	}//END class ARequest extends PAFAppConfig
?>