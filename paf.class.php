<?php
	abstract class PAF {
		/*** configurable default vars ***/
		//default path (with tailing /) - this can be set through set_path()
		var $mypath = '';
		//default implementing class file
		var $class_file = '';
		//path to implementing class file (relative or absolute)
		var $class_file_path = '';
		//default implementing class name
		var $class_name = 'myPAF';
		//set utf8 support
		var $with_utf8 = TRUE;
		var $actions = array();
		var $pafi;
		var $http_key;
		var $with_history;
		//separator for function arguments
		var $argument_separator = '^^pafa^';
		//default parsing arguments separators
		var $parameters_separator = ',';
		var $array_elements_separator = '~';
		var $key_value_separator = '|';
		
		function PAF() {
			$this->pafi();
		}//function PAF()
		
		function generate_key() {
			return md5(uniqid(rand()));
		}//function generate_key()
		
		function pafi() {
			$xsession = xSession::GetInstance();
			$lpafi = $xsession->GetParam('PAF_PAFI');
			$this->pafi = isset($lpafi) ? $lpafi : $this->generate_key();
			$xsession->SetParam('PAF_PAFI',$this->pafi);
		}//function pafi()

		function clear_state() {
			$xsession = xSession::GetInstance();
			$xsession->UnsetParam('PAF_PAFI');
			$xsession->UnsetParam('PAF_HTTPK');
			$this->pafi = $this->http_key = NULL;
			$this->pafi();
		}//function clear_state()
		
		function secure_http() {
			$xsession = xSession::GetInstance();
			$this->http_key = $xsession->GetParam('PAF_HTTPK') ? $xsession->GetParam('PAF_HTTPK') : $this->generate_key();
			$xsession->SetParam('PAF_HTTPK',$this->http_key);
		}//function secure_http()

		function clear_secure_http() {
			$xsession = xSession::GetInstance();
			$this->http_key = NULL;
			$xsession->UnsetParam('PAF_HTTPK');
		}//function clear_secure_http()

		function set_utf8($value = TRUE) {
			$this->with_utf8 = $value;
		}//function set_utf8($bool = TRUE)

		function set_path($path) {
			$this->mypath = $path;
		}//function set_path($path)

		function set_class_file_path($path) {
			$this->class_file_path = $path;
		}//function set_class_file_path($path)

		function set_class_name($name) {
			$this->class_name = $name;
		}//function set_class_name($name)

		function get_class_name() {
			return $this->class_name;
		}//function get_class_name()

		function set_class_file($filename) {
			$this->class_file = $filename;
		}//function set_class_file($filename)

		function get_class_file() {
			return $this->class_file_path.$this->class_file;
		}//function get_class_file()
		
		function hasActions() {
			return (count($this->actions)>0);
		}//function hasActions()

		function js_init() {
			$js = '<script type="text/javascript">var PAF_PATH="'.$this->mypath.'"; var PAF_HTTPK="'.$this->http_key.'"</script>'."\n";
			$js .= '<script type="text/javascript" src="'.$this->mypath.'paf.js"></script>'."\n";
			return $js;
		}//function js_init()
		
		function paf_history($initializer) {
			$this->with_history = true;
			$result = '<iframe style="display:none;" id="pafHistory" name="pafHistory" src="'.$this->mypath.'paf.php/1/"></iframe>'."\n";
			$result .= '<script type="text/javascript">'."\n";
			$result .= "\t".'PAF.onHistoryChange = function(i) {'."\n";
			$result .= "\t\t".'PAF.history[i]();'."\n";
			$result .= "\t".'}//PAF.onHistoryChange = function(i)'."\n";
			$result .= "\t".'PAF.historyIndex = -1;'."\n";
			$result .= "\t".'PAF.history = [];'."\n";
			$result .= "\t".'PAF.pafHistory = window.frames["pafHistory"];'."\n";
			$result .= "\t".'PAF.lastHash = PAF.pafHistory.location.hash;'."\n";
			$result .= "\t".'PAF.monitorHistory();'."\n";
			$result .= "\t".$this->runWithHistory($initializer)."\n";
			$result .= '</script>'."\n";
			return $result;
		}//function paf_history($initializer)

		function paf_status($style = '',$string = 'Working...') {
			return '<span id="pafStatus" style="display: none; '.$style.'">'.htmlentities($string).'</span>';
		}//function paf_status($style = '',$string = 'Working...')

		//$js_script = script js sau numele unui fisier js care sa se ruleze inainte si/sau dupa call-ul de ajax
		function run($commands,$with_status = 1,$js_script = '',$class_file = NULL,$use_history = 'false') {
			if(!$this->http_key) {
				$this->clear_secure_http();
			}//if(!$this->http_key)
			if(!$class_file) {
				$class_file = $this->get_class_file();
			}//if(!$class_file)
			return $this->ParseCommands($commands,$class_file,$use_history,$with_status,$js_script);
		}//function run($commands,$with_status = 1,$js_script = '',$class_file = NULL,$use_history = 'false') {

		function ParseCommands($commands,$class_file,$use_history = 'false',$with_status = 1,$js_script = '') {
			$xsession = xSession::GetInstance();
			$commands = $this->texplode(';',$commands);
			$all_commands = '';
			$request_id = '';
			foreach($commands as $command) {
				$inputType = '';
				$targets = '';
				$tmp = $this->texplode('->',$command);
				if(isset($tmp[0])) {
					$functions = $tmp[0];
				}//if(isset($tmp[0]))
				if(isset($tmp[1])) {
					$targets = $tmp[1];
				}//if(isset($tmp[1]))
				if(strstr($functions,'(')) {
					$action = '';
					$target = '';
					$targetProperty = '';
					$inputArray = explode('(',$functions,2);
					list($function,$args) = $inputArray;
					$args = substr($args,0,-1);
					$tmp = $this->texplode(',',$targets);
					if(isset($tmp[0])) {
						$target = $tmp[0];
					}//if(isset($tmp[0]))
					if(isset($tmp[1])) {
						$action = $tmp[1];
					}//if(isset($tmp[1]))
					$tmp = $this->texplode(':',$target);
					if(isset($tmp[0])) {
						$targetId = $tmp[0];
					}//if(isset($tmp[0]))
					if(isset($tmp[1])) {
						$targetProperty = $tmp[1];
					}//if(isset($tmp[1]))
					if(!$action) {
						$action = 'r';
					}//if(!$action)
					if(!$targetProperty) {
						$targetProperty = 'innerHTML';
					}//if(!$targetProperty)
					if(!$targets) {
						$action = $targetProperty = $targetId = '';
					}//if(!$targets)
					if($function) {
						$request_id = md5($function.$this->pafi);
						$xsession->data['PAF_REQUEST']['REQUESTS'][$request_id] = array('UTF8'=>$this->with_utf8,'FUNCTION'=>$function,'CLASS_FILE'=>$class_file ? $class_file : $this->get_class_file(),'CLASS'=>$this->get_class_name());
						$session_id = session_id();
						$all_commands .= "PAF.run('".$this->parseArgs($args,'PHP')."','$targetId','$action','$targetProperty','$session_id','$request_id',$use_history,'$with_status','$js_script');";
					}//if($function)
				}//if(strstr($functions,'('))
			}//foreach($commands as $command)
			return $all_commands;
		}//function ParseCommands($commands,$class_file,$use_history = 'false',$with_status = 1,$js_script = '')

		function parseArgs($arg,$getType) {
			$concatSeparators = array($key_value_separator,$array_elements_separator,$parameters_separator);
			$inner = '';
			if(strlen($arg)==0)
				return $inner;
			if(is_array($concatSeparators) && !empty($concatSeparators)) {
				$mySeparator = $concatSeparators[sizeof($concatSeparators)-1];
				unset($concatSeparators[sizeof($concatSeparators)-1]);
			}else{
				$mySeparator = $concatSeparators;
				$concatSeparators = array();
			}//if(is_array($concatSeparators) && !empty($concatSeparators))
			if(sizeof($concatSeparators)>0) {
				$sufix = $mySeparator==',' ? $this->argument_separator : "'+'$mySeparator";
				$i = 0;
				foreach ($this->texplode($mySeparator,$arg) as $a) {
					if($getType=='PHP') {
						if($i)
							$inner .= $sufix;
						$inner .= $this->parseConcatArgs($a,$getType,$concatSeparators);
						$i++;
					}//if($getType=='PHP')
				}//foreach ($this->texplode($mySeparator,$arg) as $a)
				return $inner;
			}else{
				if(strlen($mySeparator)>0) {
					$sufix = $mySeparator==',' ? $this->argument_separator : "'+'$mySeparator";
					$i = 0;
					foreach ($this->texplode($mySeparator,$arg) as $a) {
						$id = $property = '';
						//shortcut for element:property syntax
						if(strstr($a,':')) {
							$tmp = $this->texplode(':',$a);
							if(isset($tmp[0]))
								$id = $tmp[0];
							if(isset($tmp[1]))
								$property = $tmp[1];
							$a = '';
						}
						if($getType=='PHP') {
							if($i)
								$inner .= $sufix;
							if($property)
								$inner .= "'+PAF.Get('$id','$property')+'";
							else if($a || is_numeric($a))
								$inner .= "'+PAF.Get($a)+'";
							else
								$inner .= "'+PAF.Get($id)+'";
							$i++;
						}//if($getType=='PHP')
					}//foreach ($this->texplode($mySeparator,$arg) as $a)
					return $inner;
				}else{
					//shortcut for element:property syntax
					if(strstr($arg,':')) {
						$tmp = $this->texplode(':',$arg);
						if(isset($tmp[0]))
							$id = $tmp[0];
						if(isset($tmp[1]))
							$property = $tmp[1];
						$arg = '';
					}//if(strstr($arg,':'))
					if($getType=='PHP') {
						if($property)
							$inner = "'+PAF.Get('$id','$property')+'";
						else if($arg || is_numeric($arg))
							$inner = "'+PAF.Get($arg)+'";
						else
							$inner = "'+PAF.Get($id)+'";
					}//if($getType=='PHP')
					return $inner;
				}//if(strlen($mySeparator)>0)
			}//if(sizeof($concatSeparators)>0)
		}//function parseArgs($arg,$getType)

		function texplode($seperator,$str) {
			$vals = array();
			foreach(explode($seperator,$str) as $val) {
				if(is_numeric($val)) {
					$vals[] = $val;
				} else if(strlen($val)>0) {
					$vals[] = trim($val);
				}//if(is_numeric($val))
			}//foreach(explode($seperator,$str) as $val)
			return $vals;
		}//function texplode($seperator,$str)

		/*** PAF js response functions ***/
		//execute javascript code
		function js($js) {
			$this->add_action($js);
		}//function js($js)

		//redirect the browser to a URL
		function redirect($url) {
			$this->add_action("window.location = '$url'");
		}//function redirect($url)

		//return a javascript alert
		function alert($txt) {
			$this->add_action("alert('".str_replace('\'','\\\'',$txt)."')");
		}//function alert($txt)

		//submit a form on the page
		function submit($name) {
			$this->add_action("document.forms['$name'].submit()");
		}//function submit($name)

		//adds a new paf action to the queue
		function exec($action) {
			$this->add_action($this->run($action));
		}//function exec($action)

		//used for placing complex / long text into an element
		function text($content,$target) {
			$action = '';
			$targetProperty = '';
			$target_arr = $this->texplode(',',$target);
			$target = $target_arr[0];
			if(count($target_arr)>1) {
				$action = $target_arr[1];
			}//if(count($target_arr)>1)
			$target_arr2 = $this->texplode(':',$target);
			$targetId = $target_arr2[0];
			if(count($target_arr2)>1) {
				$targetProperty = $target_arr2[1];
			}//if(count($target_arr2)>1)
			if(!$action) {
				$action = 'r';
			}//if(!$action)
			if(!$targetProperty) {
				$targetProperty = 'innerHTML';
			}//if(!$targetProperty)
			$action = "PAF.Put(".($this->with_utf8 ? 'decodeURIComponent' : 'unescape')."('".rawurlencode($content)."'),'$targetId','$action','$targetProperty')";
			$this->add_action($action);
		}//function text($content,$target)

		//hide an element
		function hide($element) {
			$this->add_action("PAF.Put('none','$element','r','style.display')");
		}//function hide($element)

		//show an element
		function show($element) {
			$this->add_action("PAF.Put('','$element','r','style.display')");
		}//function show($element)

		//set style for an element
		function style($element,$styleString) {
			$this->add_action("PAF.SetStyle('$element', '$styleString')");
		}//function style($element,$styleString)

		//return response actions to javascript for execution
		function send() {
			$ret = $this->get_actions();
			$this->actions = array();
			return $ret;
		}//function send()

		function add_action($js) {
			$this->actions[] = $js;
		}//function add_action($js)

		function get_actions() {
			return ($this->hasActions() ? '^paf!split^' : '').implode(';',$this->actions);
		}//function get_actions()

		/*** Request execution ***/
		function runFunc($function,$args) {
			$xsession = xSession::GetInstance();
			//kill magic quotes
			if(get_magic_quotes_gpc()) {
				$args = stripslashes($args);
			}//if(get_magic_quotes_gpc())
			//decode encrypted HTTP data if needed
			$lhttpkey = $xsession->GetParam('PAF_HTTPK');
			if(isset($lhttpkey)) {
				$this->secure_http();
				$args = $this->rc4($this->http_key,utf8_decode(rawurldecode($args)));
			}//if(isset($lhttpkey))
			$args = explode($this->argument_separator,$args,100);
			//limited to 100 arguments for DNOS attack protection
			for($i = 0;$i<count($args);$i++) {
				if($this->with_utf8) {
					$args[$i] = $this->utf8_unserialize(rawurldecode($args[$i]));
				}else{
					$args[$i] = unserialize(utf8_decode(rawurldecode($args[$i])));
				}//if($this->with_utf8)
			}//for($i = 0;$i<count($args);$i++)
			if(method_exists($this,$function)) {
				echo call_user_func_array(array(&$this,$function),$args);
			} else {
				echo "PAF ERROR: [$function] Not validated.";
			}//if(method_exists($this,$function))
		}//function runFunc($function,$args)

		function utf8_unserialize($str) {
			if(preg_match('/^a:[0-9]+:{s:[0-9]+:"/',$str)) {
				$ret = array();
				$args = preg_split('/"?;?s:[0-9]+:"/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
				array_shift($args);
				$last = array_pop($args);
				$last = preg_replace('/";}$/','',$last);
				$args[] = $last;
				for($i = 0;$i<count($args);$i += 2) {
					$ret[$args[$i]] = $args[$i + 1];
				}
				return $ret;
			} else if(preg_match('/^a:[0-9]+:{i:[0-9]+;s:[0-9]+:"/',$str)) {
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
		}//function utf8_unserialize($str)

		//RC4 Encryption from http://sourceforge.net/projects/rc4crypt
		function rc4($pwd,$data) {
			$cipher = '';
			$pwd_length = strlen($pwd);
			$data_length = strlen($data);
			for($i = 0;$i<256;$i++) {
				$key[$i] = ord($pwd[$i % $pwd_length]);
				$box[$i] = $i;
			}//for($i = 0;$i<256;$i++)
			for($j = $i = 0;$i<256;$i++) {
				$j = ($j + $box[$i] + $key[$i]) % 256;
				$tmp = $box[$i];
				$box[$i] = $box[$j];
				$box[$j] = $tmp;
			}//for($j = $i = 0;$i<256;$i++)
			for($a = $j = $i = 0;$i<$data_length;$i++) {
				$a = ($a + 1) % 256;
				$j = ($j + $box[$a]) % 256;
				$tmp = $box[$a];
				$box[$a] = $box[$j];
				$box[$j] = $tmp;
				$k = $box[(($box[$a] + $box[$j]) % 256)];
				$cipher .= chr(ord($data[$i]) ^ $k);
			}//for($a = $j = $i = 0;$i<$data_length;$i++)
			return $cipher;
		}//function rc4($pwd,$data)

	}//abstract class PAF
?>