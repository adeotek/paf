<?php
/**
 * PAF (PHP AJAX Framework) ajax requests class file.
 *
 * The PAF class used for working with ajax requests.
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.0
 * @filesource
 */
	/**
	 * PAF ajax requests class.
	 *
	 * Class instance can be used for initiating ajax requests.
	 *
	 * @package  Hinter\PAF
	 * @access   public
	 */
	class PAFReq extends PAFConfig {
		/**
		 * @var    PAF Reference to the PAF object (for interacting with session data)
		 * @access protected
		 */
		protected $paf_object = NULL;
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
		 * @var    string Control key for securizing the request session data
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
		 * @param  PAF $paf_obj Reference to the PAF instance
		 * @return void
		 * @access public
		 */
		public function __construct(&$paf_obj) {
			$this->paf_object = $paf_obj;
			$this->Init();
		}//public function __construct(&$paf_obj)
		/**
		 * Initiate PAFReq session data (generate session data id) if is not initialized
		 *
		 * @return void
		 * @access private
		 */
		private function Init() {
			$lpafi = $this->paf_object->GetGlobalParam('PAF_PAFI',self::$paf_session_key);
			if(strlen($lpafi)>0) {
				$this->pafi = $lpafi;
			} else {
				$this->pafi = self::GenerateUID();
				$this->paf_object->data[self::$paf_session_key]['PAF_PAFI'] = $this->pafi;
			}//if(strlen($lpafi)>0)
			$this->StartSecureHttp();
		}//private function Init()
		/**
		 * Clear PAFReq session data and re-initialize it
		 *
		 * @return void
		 * @access private
		 */
		private function ClearState() {
			unset($this->paf_object->data[self::$paf_session_key]['PAF_PAFI']);
			unset($this->paf_object->data[self::$paf_session_key]['PAF_HTTPK']);
			$this->pafi = $this->paf_http_key = NULL;
			$this->Init();
		}//private function ClearState()

		public function StartSecureHttp() {
			if(self::$paf_secure_http) {
				$this->paf_http_key = (strlen($this->paf_object->GetGlobalParam('PAF_PAFI',self::$paf_session_key))>0 && strlen($this->paf_object->GetGlobalParam('PAF_HTTPK',self::$paf_session_key))>0) ? $this->paf_object->GetGlobalParam('PAF_HTTPK',self::$paf_session_key) : self::GenerateUID(self::$session_key,'sha256');
				$this->paf_object->data[self::$paf_session_key]['PAF_HTTPK'] = $this->paf_http_key;
			}//if(self::$paf_secure_http)
		}//public function StartSecureHttp()

		public function ClearSecureHttp() {
			unset($this->paf_object->data[self::$paf_session_key]['PAF_HTTPK']);
			$this->paf_http_key = NULL;
		}//public function ClearSecureHttp()

		public function GetClassName() {
			return self::$paf_class_name;
		}//public function GetClassName()

		public function SetClassName($value) {
			$this->custom_class = self::$paf_class_name!=$value;
			self::$paf_class_name = $value;
		}//public function SetClassName($value)

		public function GetClassFile() {
			if(strlen(self::$paf_class_file)>0) {
				return self::$paf_class_file;
			}//if(strlen(self::$paf_class_file)>0)
			return $this->paf_object->app_absolute_path.self::$paf_class_file_path.self::$paf_class_file_name;
		}//public function GetClassFile()

		public function SetClassFile($value) {
			$this->custom_class = self::$paf_class_file!=$value;
			self::$paf_class_file = $value;
		}//public function SetClassFile($value)

		public function GetClassFilePath() {
			return self::$paf_class_file_path;
		}//public function GetClassFilePath()

		public function SetClassFilePath($value) {
			$this->custom_class = self::$paf_class_file_path!=$value;
			self::$paf_class_file_path = $value;
		}//public function SetClassFilePath($value)

		public function GetClassFileName() {
			return self::$paf_class_file_name;
		}//public function GetClassFileName()

		public function SetClassFileName($value) {
			$this->custom_class = self::$paf_class_file_name!=$value;
			self::$paf_class_file_name = $value;
		}//public function SetClassFileName($value)

		public function GetUtf8() {
			return self::$paf_utf8;
		}//public function GetUtf8()

		public function SetUtf8($value = TRUE) {
			self::$paf_utf8 = $value;
		}//public function SetUtf8($value = TRUE)
		/**
		 * Sets params to be send via post on the ajax request
		 *
		 * @param  array $params Key-value array of parameters to be send via post
		 * @return void
		 * @access public
		 */
		public function SetPostParams($params) {
			if(is_array($params) && count($params)>0) {
				$this->paf_post_params = $params;
			}//if(is_array($params) && count($params)>0)
		}//END public function SetPostParams

		public function GetSecureHttp() {
			return self::$paf_secure_http;
		}//public function GetSecureHttp()

		public function SetSecureHttp($value = TRUE) {
			self::$paf_secure_http = $value;
		}//public function SetSecureHttp($value = TRUE)

		public function HasActions() {
			return (count($this->req_actions)>0);
		}//public function HasActions()

		public function jsInit($with_output = TRUE) {
			$wpath =
			$js = '<script type="text/javascript">'."\n";
			$js .= '<!--'."\n";
			$js .= "\t".'var PAF_TARGET="'.$this->paf_object->app_web_link.self::$paf_target.'";'."\n";
			$js .= "\t".'var PAF_HTTPK="'.$this->paf_http_key.'";'."\n";
			$js .= '//-->'."\n";
			$js .= '</script>'."\n";
			$js .= '<script type="text/javascript" src="'.$this->paf_object->app_web_link.self::$paf_path.'gibberish-aes.min.js?v=140127"></script>'."\n";
			$js .= '<script type="text/javascript" src="'.$this->paf_object->app_web_link.self::$paf_path.'paf.min.js?v=1403121"></script>'."\n";
			if($with_output===TRUE) { echo $js; }
			return $js;
		}//public function jsInit($with_output = TRUE)

		public function ShowStatus($content = 'Working...',$class = '',$style = '') {
			$lclass = strlen($class)>0 ? ' class="'.$class.'"' : '';
			return '<span id="PAFReqStatus"'.$lclass.' style="display: none; '.$style.'">'.htmlentities($content).'</span>';
		}//public function ShowStatus($content = 'Working...',$class = '',$style = '')

		/* Generate javascript call for ajax request
		 * $js_script -> js script or js file name (with full link) to be executed before or after the ajax request */
		public function run($commands,$with_status = 1,$js_script = '',$run_oninit_func = 1,$confirm = NULL,$class_file = NULL,$class_name = NULL) {
			if(self::$cache) { return (self::$cache_separator.$commands.self::$cache_arg_separator.$with_status.$cache_arg_separator.$js_script.$cache_arg_separator.$class_file.$cache_arg_separator.$class_name.strrev(self::$cache_separator)); }
			$commands = texplode(';',$commands);
			$all_commands = '';
			$request_id = '';
			foreach($commands as $command) {
				$inputType = '';
				$targets = '';
				$eparams = '';
				$tmp = texplode('->',$command);
				if(isset($tmp[0])) { $functions = $tmp[0]; }
				if(isset($tmp[1])) { $targets = $tmp[1]; }
				if(isset($tmp[2])) { $eparams = $tmp[2]; }
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
							$this->paf_object->data[self::$paf_session_key]['PAF_REQUEST']['REQUESTS'][$request_id] = array('UTF8'=>self::$paf_utf8,'FUNCTION'=>$function,'CLASS_FILE'=>$class_file,'CLASS'=>$class_name);
						} else {
							$this->paf_object->data[self::$paf_session_key]['PAF_REQUEST']['REQUESTS'][$request_id] = array('UTF8'=>self::$paf_utf8,'FUNCTION'=>$function);
						}//if($class_file || $class_name || $this->custom_class)
						$session_id = rawurlencode(GibberishAES::enc(session_id(),self::$session_key));
						$postparams = $this->PreparePostParams();
						$args_separators = array($this->paf_params_separator,$this->paf_arr_e_separator,$this->paf_arr_kv_separator);
						$phash = self::$paf_use_window_name ? "'+PAFReq.get(window.name)+'".self::$paf_arg_separator : '';
						$jsarguments = self::$paf_params_encrypt ? GibberishAES::enc($phash.$this->ParseArguments($args,$args_separators),$request_id) : $phash.$this->ParseArguments($args,$args_separators);
						$pconfirm = $this->PrepareConfirm($confirm,$request_id);
						$all_commands .= "PAFReq.run('".$jsarguments."',".((int)self::$paf_params_encrypt).",'$targetId','$action','$targetProperty','$session_id','$request_id','$postparams','$with_status','$js_script',".$pconfirm.','.($run_oninit_func==1 ? 1 : 0).($eparams ? ','.$eparams : '').");";
					}//if($function)
				}//if(strstr($functions,'('))
			}//foreach($commands as $command)
			return $all_commands;
		}//END public function run

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
			$id = $property = '';
			/* If arg contains ':', arg is element:property syntax */
			if(str_contains($arg,':')) {
				$tmp = texplode(':',$arg);
				if(isset($tmp[0])) { $id = $tmp[0]; }
				if(isset($tmp[1])) { $property = $tmp[1]; }
				$arg = '';
			}//if(str_contains($arg,':'))
			if($property) { return "'+PAFReq.get('$id','$property')+'"; }
			if($id) { return "'+PAFReq.get($id)+'"; }
			else { return "'+PAFReq.get($arg)+'"; }
		}//END private function PrepareArgument

		private function PrepareConfirm($confirm,$request_id) {
			if(!is_array($confirm) || !count($confirm)) { return 'undefined'; }
			switch(get_array_param($confirm,'type','js','is_notempty_string')) {
				case 'jqui':
					// ToDo: de implementat jQueryUI dialog box
					//$confirm_str = '{ "type": "jqui", "txt": "'.get_array_param($confirm,'type',NULL,'is_notempty_string').'" }';
					return 'undefined';
					break;
				default:
					$confirm_str = self::$paf_params_encrypt ? "'".GibberishAES::enc('{"type":"js","txt":"'.str_replace('"','\"',get_array_param($confirm,'txt','','is_string')).'"}',$request_id)."'" : '{ type: \'js\', txt: \''.get_array_param($confirm,'txt','','is_string').'\' }';
					break;
			}//END switch
			return $confirm_str;
		}//END private function PrepareConfirm
		/**
		 * Transforms the post params array into a string to be posted by the javascript method
		 *
		 * @return string The post params as a string
		 * @access private
		 */
		private function PreparePostParams() {
			$result = '';
			if(is_array($this->paf_post_params) && count($this->paf_post_params)>0) {
				foreach($this->paf_post_params as $k=>$v) {
					$result .= '&'.$k.'='.$v;
				}//foreach($this->paf_post_params as $k=>$v)
			}//END if(is_array($this->paf_post_params) && count($this->paf_post_params)>0)
			return $result;
		}//END private function PreparePostParams

		private function AddAction($action) {
			$this->req_actions[] = $action;
		}//private function AddAction(string $action)

		public function GetActions() {
			return ($this->HasActions() ? self::$paf_act_separator : '').implode(';',$this->req_actions);
		}//END public function GetActions

		/*** PAF js response functions ***/
		/* Execute javascript code */
		public function js($jscript) {
			$this->AddAction($jscript);
		}//END public function js(string $jscript)

		/* Redirect the browser to a URL */
		public function redirect($url) {
			$this->AddAction("window.location.href = '$url'");
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
		 * Adds a new paf run action to the queue
		 *
		 * @param  type $param_name param description
		 * @return void
		 * @access public
		 */
		public function exec_run($commands,$with_status = 1,$js_script = '',$class_file = NULL,$class_name = NULL) {
			$this->AddAction($this->run($commands,$with_status,$js_script,$class_file,$class_name));
		}//END public function exec_run
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
		//START Request execution
		public function RunFunc($function,$args) {
			//Kill magic quotes if they are on
			if(get_magic_quotes_gpc()) {
				$args = stripslashes($args);
			}//if(get_magic_quotes_gpc())
			//decode encrypted HTTP data if needed
			if(self::$paf_secure_http) {
				if(!$this->paf_http_key) { return "PAFReq ERROR: [$function] Not validated."; }
				$args = GibberishAES::dec(utf8_decode(rawurldecode($args)),$this->paf_http_key);
			}//if(self::$paf_secure_http)
			//limited to 100 arguments for DNOS attack protection
			$args = explode(self::$paf_arg_separator,$args,100);
			for($i=0; $i<count($args); $i++) {
				if(self::$paf_utf8) {
					$args[$i] = $this->Utf8Unserialize(rawurldecode($args[$i]));
				}else{
					$args[$i] = unserialize(utf8_decode(rawurldecode($args[$i])));
				}//if(self::$paf_utf8)
			}//for($i=0; $i<count($args); $i++)
			if(method_exists($this,$function)) {
				echo call_user_func_array(array(&$this,$function),$args);
			} else {
				echo "PAFReq ERROR: [$function] Not validated.";
			}//if(method_exists($this,$function))
		}//END public function RunFunc($function,$args)
		//END Request execution
		private function Utf8Unserialize($str) {
			if(preg_match('/^a:[0-9]+:{s:[0-9]+:"/',$str)) {
				$ret = array();
				$args = preg_split('/"?;?s:[0-9]+:"/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
				array_shift($args);
				$last = array_pop($args);
				$last = preg_replace('/";}$/','',$last);
				$args[] = $last;
				for($i = 0;$i<count($args);$i += 2) {
					$ret[$args[$i]] = $args[$i + 1];
				}//for($i = 0;$i<count($args);$i += 2)
				return $ret;
			} elseif(preg_match('/^a:[0-9]+:{i:[0-9]+;s:[0-9]+:"/',$str)) {
				$args = preg_split('/"?;?i:[0-9]+;s:[0-9]+:"/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
				array_shift($args);
				$last = array_pop($args);
				$last = preg_replace('/";}$/','',$last);
				$args[] = $last;
				return $args;
			} else {
				$args = preg_split('/^s:[0-9]+:"([\w\W]*?)";$/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
				$ret = preg_replace('/\";(.)s:[0-9]*:\"/','$1',$args[1]);
				return $ret;
			}//if(preg_match('/^a:[0-9]+:{s:[0-9]+:"/',$str))
		}//END private function Utf8Unserialize($str)

	}//END class PAFReq extends PAFConfig
?>