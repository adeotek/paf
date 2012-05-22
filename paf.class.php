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
		//pad functions names having less than this many characters in their name
		var $function_padding = 15;
		var $actions = array();
		var $pafi;
		var $http_key;
		var $with_history;
		//separator for function arguments
		var $argument_separator = '^^paf!arg^';
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

		
		
		
		
		function saja_history($initializer) {
			$this->historyAvailable = true;
			return '
			<iframe style="display:none;" id="sajaHistory" name="sajaHistory" src="'.$this->mypath.'saja.php/1/"></iframe>
			<script type="text/javascript">
			saja.onHistoryChange = function(i){
				saja.history[i]();
			}
			saja.historyIndex = -1;
			saja.history = [];
			saja.sajaHistory = window.frames["sajaHistory"];
			saja.lastHash = saja.sajaHistory.location.hash;
			saja.monitorHistory();
			'.$this->runWithHistory($initializer).'
			</script>
			';
		}

		function saja_status($style = '',$string = 'Working...') {
			return "<span id=\"sajaStatus\" style=\"visibility:hidden;$style\">".htmlentities($string)."</span>";
		}


		/*** Call de saja cu rulare de script js la inceput si/sau sfarsit ***/
		//$js_script = script js sau numele unui fisier js care sa se ruleze inainte si/sau dupa call-ul de ajax
		function run($commands,$with_status = 1,$js_script = '',$process_file = null,$use_history = 'false') {
			if(!$this->http_key)
				$this->clear_secure_http();
			if(!$process_file)
				$process_file = $this->get_process_file();
			return $this->ParseCommands($commands,$process_file,$use_history,$with_status,$js_script);
		}

		function ParseCommands($commands,$process_file,$use_history = 'false',$with_status = 1,$js_script = '') {
			$xsession = XSession::GetInstance();
			//echo2file("WithStatus=$with_status",$xsession->GetGlobalParam('app_absolute_path').'/debugging.log');
			$commands = $this->texplode(';',$commands);
			$all_commands = '';
			$request_id = '';
			foreach($commands as $command) {
				$inputType = '';
				$targets = '';
				$tmp = $this->texplode('->',$command);
				if(isset($tmp[0]))
					$functions = $tmp[0];
				if(isset($tmp[1]))
					$targets = $tmp[1];
				if(strstr($functions,'(')) {
					$action = '';
					$target = '';
					$targetProperty = '';
					$inputArray = explode('(',$functions,2);
					list($function,$args) = $inputArray;
					$args = substr($args,0,-1);
					$tmp = $this->texplode(',',$targets);
					if(isset($tmp[0]))
						$target = $tmp[0];
					if(isset($tmp[1]))
						$action = $tmp[1];
					$tmp = $this->texplode(':',$target);
					if(isset($tmp[0]))
						$targetId = $tmp[0];
					if(isset($tmp[1]))
						$targetProperty = $tmp[1];
					if(!$action)
						$action = 'r';
					if(!$targetProperty)
						$targetProperty = 'innerHTML';
					if(!$targets)
						$action = $targetProperty = $targetId = '';
					if($function) {
						$request_id = md5($function.$this->salt);
						$xsession->data['PAF_PROCESS']['REQUESTS'][$request_id] = array('UTF8'=>$this->true_utf8,'FUNCTION'=>$function,'PROCESS_FILE'=>$process_file ? $process_file : $this->get_process_file(),'CLASS'=>$this->get_process_class());
						$session_id = session_id();
						//adaugat de Horia pentru a transmite sectiunea curenta
						//global $xsession;
						$cursection = $xsession->current_section;
						//
						$all_commands .= "saja.run('".$this->parseArgs($args,'PHP')."','$targetId','$action','$targetProperty','$session_id','$request_id',$use_history,'$cursection','$with_status','$js_script');";
					}
				}
			}
			return $all_commands;
		}

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
			}
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
								$inner .= "'+saja.Get('$id','$property')+'";
							else if($a || is_numeric($a))
								$inner .= "'+saja.Get($a)+'";
							else
								$inner .= "'+saja.Get($id)+'";
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
							$inner = "'+saja.Get('$id','$property')+'";
						else if($arg || is_numeric($arg))
							$inner = "'+saja.Get($arg)+'";
						else
							$inner = "'+saja.Get($id)+'";
					}//if($getType=='PHP')
					return $inner;
				}
			}//if(sizeof($concatSeparators)>0)
		}//function parseConcatArgs($arg,$getType,$concatSeparators=array())

		function texplode($seperator,$str) {
			$vals = array();
			foreach(explode($seperator, $str) as $val) {
				if(is_numeric($val)) {
					$vals[] = $val;
				} else if(strlen($val)>0) {
					$vals[] = trim($val);
				}
			}
			return $vals;
		}


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
			if(!$action){
				$action = 'r';
			}
			if(!$targetProperty) {
				$targetProperty = 'innerHTML';
			}
			$action = "saja.Put(".($this->true_utf8 ? 'decodeURIComponent' : 'unescape')."('".rawurlencode($content)."'),'$targetId','$action','$targetProperty')";
			$this->add_action($action);
		}//function text($content,$target)

		//hide an element
		function hide($element) {
			$this->add_action("saja.Put('none','$element','r','style.display')");
		}//function hide($element)

		//show an element
		function show($element) {
			$this->add_action("saja.Put('','$element','r','style.display')");
		}//function show($element)

		//set style for an element
		function style($element,$styleString) {
			$this->add_action("saja.SetStyle('$element', '$styleString')");
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
			return ($this->hasActions() ? '<saja_split>' : '').implode(';',$this->actions);
		}//function get_actions()

		/*** Request execution ***/
		function runFunc($function,$args) {
			$xsession = XSession::GetInstance();
			//kill magic quotes
			if(get_magic_quotes_gpc()) {
				$args = stripslashes($args);
			}
			//decode encrypted HTTP data if needed
			$lhttpkey = $xsession->GetGlobalParam('PAF_HTTPK');
			if(isset($lhttpkey)) {
				$this->secure_http();
				$args = $this->rc4($this->http_key,utf8_decode(rawurldecode($args)));
			}
			//$this->echo2file($args,'F:/xampp/htdocs/gestcart/debugging.log');
			$args = explode($this->argument_separator,$args,100);
			//limited to 100 arguments for DNOS attack protection
			for($i = 0;$i<count($args);$i++) {
				if($this->true_utf8){
					$args[$i] = $this->utf8_unserialize(rawurldecode($args[$i]));
				}else{
					$args[$i] = unserialize(utf8_decode(rawurldecode($args[$i])));
				}
			}
			if(method_exists($this,$function)) {
				echo call_user_func_array(array(&$this,$function),$args);
			} else {
				echo "ERROR: [$function] Not validated.";
			}
		}

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
			}
		}

		//RC4 Encryption from http://sourceforge.net/projects/rc4crypt
		function rc4($pwd,$data) {
			$cipher = '';
			$pwd_length = strlen($pwd);
			$data_length = strlen($data);
			for($i = 0;$i<256;$i++) {
				$key[$i] = ord($pwd[$i % $pwd_length]);
				$box[$i] = $i;
			}
			for($j = $i = 0;$i<256;$i++) {
				$j = ($j + $box[$i] + $key[$i]) % 256;
				$tmp = $box[$i];
				$box[$i] = $box[$j];
				$box[$j] = $tmp;
			}
			for($a = $j = $i = 0;$i<$data_length;$i++) {
				$a = ($a + 1) % 256;
				$j = ($j + $box[$a]) % 256;
				$tmp = $box[$a];
				$box[$a] = $box[$j];
				$box[$j] = $tmp;
				$k = $box[(($box[$a] + $box[$j]) % 256)];
				$cipher .= chr(ord($data[$i]) ^ $k);
			}
			return $cipher;
		}

	}//abstract class PAF
?>