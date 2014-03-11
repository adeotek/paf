<?php
/**
 * short description
 *
 * long description
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.0
 * @filesource
 */
	/**
	 * AppLogger description
	 *
	 * long_description
	 *
	 * @package  Hinter\PAF
	 * @access   public
	 */
	class AppLogger {
		/**
		 * @var    string Database absolute path
		 * @access private
		 */
		private $f_path = '';
		/**
		 * @var    string Database name
		 * @access private
		 */
		private $f_name = 'applog.db';
		/**
		 * @var    string Database user
		 * @access private
		 */
		private $f_user = '';
		/**
		 * @var    string Database password
		 * @access private
		 */
		private $f_password = '';
		/**
		 * @var    bool Use PDO connection true/false
		 * @access private
		 */
		private $use_pdo = FALSE;
		/**
		 * @var    object Database connection instance
		 * @access private
		 */
		private $db = NULL;
		/**
		 * @var    array An array of custom fields to log
		 * @access private
		 */
		private $custom_fields = array();
		/**
		 * @var    string Custom fields separator
		 * @access private
		 */
		private $custom_fields_separator = '[|]';
		/**
		 * @var    string Log name
		 * @access public
		 */
		public $name = 'app';
		/**
		 * @var    string Remote address (IP)
		 * @access public
		 */
		public $address = '';
		/**
		 * @var    string User agent
		 * @access public
		 */
		public $agent = '';
		/**
		 * @var    string Logged in user hash
		 * @access public
		 */
		public $user_hash = NULL;
		/**
		 * @var    string Logged in user full name/username
		 * @access public
		 */
		public $user_name = NULL;
		/**
		 * AppLogger class constructor
		 *
		 * @param  array $params An array of params to be used for initialization
		 * @return void
		 * @access public
		 */
		public function __construct($params = array()) {
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
			if(!strlen($this->f_path.$this->f_name)) { return; }
			$this->address = $this->address ? $this->address : (array_key_exists('REMOTE_ADDR',$_SERVER) ? $_SERVER['REMOTE_ADDR'] : '');
			$this->agent = $this->agent ? $this->agent : (array_key_exists('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '');
			if($this->use_pdo && extension_loaded('pdo_sqlite3')) {
				try {
					$conn_str = 'sqlite:dbname=localhost:'.$this->f_path.$this->f_name.';charset=UTF8';
					$this->db = new PDO($conn_str,$this->f_user,$this->f_password);
					$this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
				} catch(Exception $e) {
					throw new XException("PDO(AppLog) failed to open file: ".$this->f_path.$this->f_name.' ('.$e->getMessage().")",E_USER_ERROR,1,__FILE__,__LINE__,'pdo',0);
				}//END try
			} else {
				$this->use_pdo = FALSE;
				try {
					$this->db = new SQLite3($this->f_path.$this->f_name,SQLITE3_OPEN_READWRITE);
				} catch(Exception $e) {
					throw new XException("SQLITE(AppLog) failed to open file: ".$this->f_path.$this->f_name.' ('.$e->getMessage().")",E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',0);
				}//END try
			}//if($this->use_pdo && extension_loaded('pdo_sqlite3'))
		}//END public function __construct
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function AddEvent($params = array()) {
			global $xsession;
			$time = microtime(TRUE);
			$name = get_array_param($params,'name',$this->name,'is_notempty_string');
			$tstamp = get_array_param($params,'timestamp',NULL,'is_notempty_string');
			$action = get_array_param($params,'action','','is_string');
			$duration = get_array_param($params,'duration',0,'is_numeric');
			$address = get_array_param($params,'address',$this->address,'is_notempty_string');
			$agent = get_array_param($params,'agent',$this->agent,'is_notempty_string');
			$uhash = get_array_param($params,'user_hash',$this->user_hash,'is_notempty_string');
			$user = get_array_param($params,'user_name',$this->user_name,'is_notempty_string');
			$data = get_array_param($params,'data',NULL,'is_notempty_string');
			$customfields = get_array_param($params,'custom_fields',array(),'is_array');
			$cf_value = '';
			if(is_array($this->custom_fields) && count($this->custom_fields)) {
				foreach($this->custom_fields as $v) { $cf_value .= $this->custom_fields_separator.get_array_param($customfields,$v,'','is_string'); }
				$customfields = trim($customfields,$this->custom_fields_separator);
			}//if(is_array($this->custom_fields) && count($this->custom_fields))
			$qry = "insert into ".$name."_log (";
			$qry .= 	"[name]".($tstamp ? ',[timestamp]' : '').",[action],[duration],";
			$qry .= 	"[address],";
			$qry .= 	"[agent],";
			$qry .= 	"[user_hash],";
			$qry .= 	"[user_name],";
			$qry .= 	"[data],";
			$qry .= 	"[custom_fields]) ";
			$qry .= " values (";
			$qry .= 	"'$name'".($tstamp ? ",'$tstamp'" : '').",'$action','$duration',";
			$qry .= 	$address ? "'$address'," : "null,";
			$qry .= 	$agent ? "'$agent'," : "null,";
			$qry .= 	$uhash ? "'$uhash'," : "null,";
			$qry .= 	$user ? "'".$this->db->escapeString($user)."'," : "null,";
			$qry .= 	$data ? "'".$this->db->escapeString($data)."'," : "null,";
			$qry .= 	$cf_value ? "'$cf_value');" : "null);";
			//$xsession->Dlog($qry,'$qry');
			if($this->use_pdo) {
				try {
					$result = $conn->query($qry);
				} catch(Exception $e) {
					throw new XException("PDO(AppLog) execute query failed: ".$e->getMessage()." at statement: $qry",E_USER_ERROR,1,__FILE__,__LINE__,'pdo',$e->getCode());
				}//END try
			} else {
				try {
					$result = $this->db->exec($qry);
				} catch(Exception $e) {
					throw new XException("SQLITE(AppLog) execute query failed: ".$e->getMessage()." at statement: $qry",E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',$e->getCode());
				}//END try
			}//if($this->use_pdo)
			//$xsession->Dlog($qry.'   =>   Duration: '.number_format((microtime(TRUE)-$time),3,'.','').' sec','AppLog'.($this->use_pdo ? '(PDO)' : ''));
			return TRUE;
		}//END public function AddEvent
		/**
		 * description
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function GetData($params = array()) {
			global $xsession;
			$time = microtime(TRUE);
			$final_result = NULL;
			$filters = '';
			$orderby = '';
			$name = get_array_param($params,'name',NULL,'is_notempty_string');
			$from = get_array_param($params,'from_date',NULL,'is_notempty_string');
			$to = get_array_param($params,'to_date',NULL,'is_notempty_string');
			$action = get_array_param($params,'address',NULL,'is_string');
			$address = get_array_param($params,'address',NULL,'is_notempty_string');
			$agent = get_array_param($params,'agent',NULL,'is_notempty_string');
			$uhash = get_array_param($params,'user_hash',NULL,'is_notempty_string');
			$user = get_array_param($params,'user_name',NULL,'is_notempty_string');
			$text = get_array_param($params,'text',NULL,'is_notempty_string');
			$order = get_array_param($params,'orderby',array(),'is_array');
			if($from) { $filters .= ($filters ? ' and ' : '')."[timestamp] >= '$from'"; }
			if($to) { $filters .= ($filters ? ' and ' : '')."[timestamp] <= '$to'"; }
			if($action) { $filters .= ($filters ? ' and ' : '')."[action] = '$action'"; }
			if($address) { $filters .= ($filters ? ' and ' : '')."[address] = '$address'"; }
			if($agent) { $filters .= ($filters ? ' and ' : '')."[agent] = '$agent'"; }
			if($uhash) { $filters .= ($filters ? ' and ' : '')."[user_hash] = '$uhash'"; }
			if($user) { $filters .= ($filters ? ' and ' : '')."[user_name] like '".$this->db->escapeString($user)."'"; }
			if($text) { $filters .= ($filters ? ' and ' : '')."[data] like '".$this->db->escapeString($text)."'"; }
			foreach($order as $k=>$v) { $orderby .= ($orderby ? ', ' : '').$k.' '.$v; }
			$qey = "select *";
			$qey .= " from ".($name ? $name.'_log l ' : 'logs l ');
			$qey .= ($filters ? " where $filters " : '');
			$qey .= ($orderby ? " order by $orderby;" : ';');
			if($this->use_pdo) {
				try {
					$result = $conn->query($query);
					if(is_object($result)) {
						$final_result = $result->fetchAll(PDO::FETCH_ASSOC);
					} else {
						$final_result = $result;
					}//if(is_object($result))
				} catch(Exception $e) {
					throw new XException("PDO(AppLog) execute query failed: ".$e->getMessage()." at statement: $qry",E_USER_ERROR,1,__FILE__,__LINE__,'pdo',$e->getCode());
				}//END try
			} else {
				try {
					$result = $this->connection->query($query);
					if(is_object($result)) {
						while($data = $result->fetchArray(SQLITE3_ASSOC)) { $final_result[] = $data; }
						$result->finalize();
					} else {
						$final_result = $result;
					}//if(is_object($result))
				} catch(Exception $e) {
					throw new XException("SQLITE(AppLog) execute query failed: ".$e->getMessage()." at statement: $qry",E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',$e->getCode());
				}//END try
			}//if($this->use_pdo)
			$xsession->Dlog($qry.'   =>   Duration: '.number_format((microtime(TRUE)-$time),3,'.','').' sec','AppLog'.($this->use_pdo ? '(PDO)' : ''));
			return $final_result;
		}//END public function GetData
		/**
		 * description
		 *
		 * @param  type $params = array() param description
		 * @return void return description
		 * @access public
		 */
		public function ClearLog($params = array()) {
			$name = get_array_param($params,'name',NULL,'is_notempty_string');
			if(!$name) { return FALSE; }
			$filters = '';
			$from = get_array_param($params,'from_date',NULL,'is_notempty_string');
			$to = get_array_param($params,'to_date',NULL,'is_notempty_string');
			$action = get_array_param($params,'address',NULL,'is_string');
			$address = get_array_param($params,'address',NULL,'is_notempty_string');
			$agent = get_array_param($params,'agent',NULL,'is_notempty_string');
			$uhash = get_array_param($params,'user_hash',NULL,'is_notempty_string');
			$user = get_array_param($params,'user_name',NULL,'is_notempty_string');
			if($from) { $filters .= ($filters ? ' and ' : '')."[timestamp] >= '$from'"; }
			if($to) { $filters .= ($filters ? ' and ' : '')."[timestamp] <= '$to'"; }
			if($action) { $filters .= ($filters ? ' and ' : '')."[action] = '$action'"; }
			if($address) { $filters .= ($filters ? ' and ' : '')."[address] = '$address'"; }
			if($agent) { $filters .= ($filters ? ' and ' : '')."[agent] = '$agent'"; }
			if($uhash) { $filters .= ($filters ? ' and ' : '')."[user_hash] = '$uhash'"; }
			if($user) { $filters .= ($filters ? ' and ' : '')."[user_name] like '".$this->db->escapeString($user)."'"; }
			$qey = "delete from ".$name.'_log l ';
			$qey .= ($filters ? " where $filters " : '');
			if($this->use_pdo) {
				try {
					$result = $conn->query($qry);
				} catch(Exception $e) {
					throw new XException("PDO(AppLog) execute query failed: ".$e->getMessage()." at statement: $qry",E_USER_ERROR,1,__FILE__,__LINE__,'pdo',$e->getCode());
				}//END try
			} else {
				try {
					$result = $this->db->exec($qry);
				} catch(Exception $e) {
					throw new XException("SQLITE(AppLog) execute query failed: ".$e->getMessage()." at statement: $qry",E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',$e->getCode());
				}//END try
			}//if($this->use_pdo)
			return TRUE;
		}//END public function ClearLog
	}//END class AppLogger
?>