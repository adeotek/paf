<?php
	class xSession {
		//xSession singleton unique instance
		private static $XSession = null;
		//emptying flag default false
		private static $destroy = false;
		//effective temporary session array
		public $data = array();
		public $log_file = 'paf.log';
		public $debug = 1;
		public $FirePHP = NULL;
		private function __construct() {
			self::$destroy = false;
			$this->data = $_SESSION;
			/*** For debugging (use only with FireBug & FirePHP)***/
			if($this->debug==1) {
				require_once(realpath(dirname(__FILE__)).'FirePHP.php');
				$this->FirePHP = FirePHP::getInstance(true);
			}//if($this->debug==1)
			/* END For debugging */
		}//private function __construct()

		//classic singleton method for retrieving the object
		public static function GetInstance() {
			if(is_null(self::$XSession)) {
				self::$XSession = new xSession();
			}//if(is_null(self::$xSession))
			return self::$XSession;
		}//public static function GetInstance()

		//get a global parameter from the temporarry session
		public function GetParam($key) {
			return array_key_exists($key,$this->data) ? $this->data[$key] : NULL;
		}//public function GetParam($key)
		
		//set a global parameter into the temporarry session
		public function SetParam($key,$val) {
			$this->data[$key] = $val;
		}//public function SetParam($key,$val)

		//unset a global parameter from the temporarry session
		public function UnsetParam($key) {
			unset($this->data[$key]);
		}//public function UnsetParam($key)
		
		//signal emptying of the session
		public static function Dump() {
			self::$destroy = TRUE;
		}//public static function Dump()

		//Commit the temporary session into the root session
		public function Commit() {
			$_SESSION = array();
			if(self::$destroy===TRUE) {
				$this->data = array();
			} else {
				foreach($this->data as $k=>$v) {
					$_SESSION[$k] = $v;
				}//foreach($this->data as $k=>$v)
			}//if(self::$destroy===true)
		}//public function Commit()

		function Echo2File($msg,$file = "") {
			$lf = strlen($file)>0 ? $file : realpath(dirname(__FILE__)).'/'.$this->log_file;
			$lfile = fopen($lf,'a') or exit("Can't open $lf!");
			if($lfile) {
				$script_name = pathinfo($_SERVER['PHP_SELF'],PATHINFO_FILENAME);
				$time = date('Y-m-d H:i:s');
				fwrite($lfile,"#$time# <$script_name> $msg\n");
				fclose($lf) or exit("Can't close $lf!");
				return TRUE;
			}//if($lfile)
			return FALSE;
		}//function Echo2File($msg,$file = "")
		
	}//class xSession
?>