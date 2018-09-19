<?php
/**
 * PAF AppException class file
 *
 * Definition of the custom exception class
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
 * PAF AppException application exception class
 *
 * Extends ErrorException and must be the only exception class
 * used in the application
 *
 * @package  AdeoTEK\PAF
 * @access   public
 * @final
 */
final class AppException extends \ErrorException {
	/**
	 * @param  string Exception type (default: app)
	 * posible values: app, firebird, mysql, pdo, mssql
	 * @access protected
	 */
	protected $type = NULL;
	/**
	 * @param  string Stores the original exception message
	 * @access protected
	 */
	protected $original_message = NULL;
	/**
	 * @param  mixed External error code
	 * (used generally for database exceptions)
	 * @access protected
	 */
	protected $ext_code = NULL;
	/**
	 * @param  array More exception information
	 * (inherited from PDOException)
	 * @access public
	 */
	public $error_info = [];
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
	 * @param  mixed $ext_code Extra error code
	 * @param  array $error_info PDO specific error information
	 * @return void
	 * @access public
	 */
	public function __construct($message,$code = -1,$severity = 1,$file = NULL,$line = NULL,$type = 'app',$ext_code = NULL,$error_info = []) {
		$this->type = strtolower($type);
		$this->message = $message;
		$this->code = $code;
		$this->severity = $severity;
		if($file) { $this->file = $file; }
		if($line) { $this->line = $line; }
		$this->ext_code = $ext_code;
		$this->error_info = $error_info;
		$this->original_message = $message;
		switch($this->type) {
			case 'firebird':
				$this->ext_code = is_numeric($this->ext_code) ?  $this->ext_code*(-1) : $this->ext_code;
				break;
		  	case 'mysql':
		  	case 'mongodb':
			case 'sqlite':
			case 'sqlsrv':
				break;
			case 'pdo':
				switch($this->ext_code) {
					case 'HY000':
						if(is_array($this->error_info) && count($this->error_info)>2) {
							$this->ext_code = is_numeric($this->error_info[1]) ?  $this->error_info[1]*(-1) : $this->error_info[1];
							$this->message = 'SQL ERROR: '.$this->error_info[2];
						}//if(is_array($this->error_info) && count($this->error_info)>2)
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
		return $this->ext_code;
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
	/**
	 * Gets the full exception message
	 *
	 * @return string Returns full exception message
	 * @access public
	 * @final
	 */
	final public function getFullMessage() {
		$result = $this->message;
		if($this->file) { $result .= ' in file ['.$this->file.']'; }
		if($this->line) { $result .= ' at line ['.$this->line.']'; }
		return $result;
	}//END public function getFullMessage
}//END class AppException extends ErrorException
?>