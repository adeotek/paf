<?php
/**
 * XException class file
 *
 * Definition of the custom exception class
 *
 * @package    Hinter\NETopes\Base
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.0.0.85
 * @filesource
 */
	/**
	 * XException application exception class
	 *
	 * Extends ErrorException and must be the only exception class
	 * used in the application
	 *
	 * @package  Hinter\NETopes\Base
	 * @access   public
	 * @final
	 */
	final class XException extends ErrorException {
		/**
		 * @var    string Exception type (default: app)
		 * posible values: app, firebird, mysql, pdo, mssql
		 * @access protected
		 */
		protected $type = NULL;
		/**
		 * @var    string Stores the original exception message
		 * @access protected
		 */
		protected $original_message = NULL;
		/**
		 * @var    mixed External error code
		 * (used generally for database exceptions)
		 * @access protected
		 */
		protected $extcode = NULL;
		/**
		 * @var    array More exception informations
		 * (inherited from PDOException)
		 * @access public
		 */
		public $errorInfo = array();
		/**
		 * Class constructor method
		 *
		 * @param  string $message Exception message
		 * @param  int $code Exception message
		 * @param  int $severity Exception severity
		 * <= 0 - stops the execution
		 * > 0 continues execution
		 * @param  string $file Exception location (file)
		 * @param  int $line Exception location (line)
		 * @param  string $type Exception type
		 * @return void
		 * @access public
		 */
		public function __construct($message,$code = -1,$severity = 1,$file = NULL,$line = NULL,$type = 'app',$extcode = NULL,$errorinfo = array()) {
			$this->type = strtolower($type);
			$this->message = $message;
			$this->code = $code;
			$this->severity = $severity;
			$this->file = $file;
			$this->line = $line;
			$this->extcode = $extcode;
			$this->errorInfo = $errorinfo;
			$this->original_message = $message;
			switch($this->type) {
				case 'firebird':
					$this->extcode = is_numeric($this->extcode) ?  $this->extcode*(-1) : $this->extcode;
					break;
			  	case 'mysql':
			  	case 'mongodb':
				case 'sqlite':
				case 'mssql':
					break;
				case 'pdo':
					switch($this->extcode) {
						case 'HY000':
							if(is_array($this->errorInfo) && count($this->errorInfo)>2) {
								$this->extcode = is_numeric($this->errorInfo[1]) ?  $this->errorInfo[1]*(-1) : $this->errorInfo[1];
								$this->message = 'SQL ERROR: '.$this->errorInfo[2];
							}//if(is_array($this->errorInfo) && count($this->errorInfo)>2)
							break;
						default:
							break;
					}//END switch
					break;
				default:
					$this->type = 'app';
					break;
			}//END switch
		}//END public function __construct
		/**
		 * Gets the external error code
		 *
		 * @return int Returns external error code
		 * @access public
		 * @final
		 */
		final public function getExtCode() {
			return $this->extcode;
		}//END public function getExtCode
		/**
		 * Gets the original exception message
		 *
		 * @return string Returns original exception message
		 * @access public
		 * @final
		 */
		final public function getOriginalMessage() {
			return $this->original_message;
		}//END public function getOriginalMessage
		/**
		 * Sets the original exception message
		 *
		 * @param  string $message New message to be stored in the
		 * original message property
		 * @return void
		 * @access public
		 * @final
		 */
		final public function setOriginalMessage($message) {
			$this->original_message = $message;
		}//END public function setOriginalMessage
	}//END class XException extends ErrorException
?>