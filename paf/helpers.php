<?php
/**
 * PAF (PHP AJAX Framework) Helpers file
 *
 * This contains a collection of php functions that extends standard php functions.
 * This functions are used by PAF (PHP AJAX Framework) and can olso be used in your project.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    1.5.0
 * @filesource
 */
	/**
	 * SQL-like coalesce function
	 *
	 * @param   mixed $arg Any number of arguments to be coalesced
	 * @return  bool Returns first non-null argument
	 */
    function coalesce() {
        $params = func_get_args();
		foreach($params as $p) { if(isset($p)) { return $p; } }
		return NULL;
    }//END function coalesce
	/**
	 * SQL-like coalesce function for strings
	 * (empty string is considered null)
	 *
	 * @param   mixed [ $arg ] Any number of arguments to be coalesced
	 * Obs. Each argument will be checked after trim
	 * @return  bool Returns first non-null, non-empty argument
	 */
	function str_coalesce() {
		$params = func_get_args();
		foreach($params as $p) {
			if(isset($p) && !is_string($p)) { continue; }
			$val = isset($p) ? trim($p) : '';
			if(strlen($val)) { return $p; }
		}//END foreach
		return NULL;
	}//END function str_coalesce
	/**
	 * Echo string after applying htmlentities to it
	 *
	 * @param   string $string String to echo
	 * @return  bool Returns TRUE on success or FALSE if $string is of an unsupporten type
	 */
	function secho($string) {
		if(is_array($string) || is_object($string)) { return FALSE; }
		echo htmlentities($string);
		return TRUE;
	}//END function secho
	/**
	 * File unlink with check if file exists
	 *
	 * @param   string $file File to unlink
	 * @return  bool Returns TRUE on success or FALSE on error or if the file doesn't exist
	 */
	function sunlink($file) {
		if(!is_string($file) || !strlen($file) || !file_exists($file)) { return FALSE; }
		try { unlink($file); return TRUE; } catch(Exception $e) { return FALSE; }
	}//END function sunlink
	/**
	 * Check if a string contains one or more strings.
	 *
	 * @param   string $haystack The string to be searched.
	 * @param   mixed $needle The string to be searched for.
	 * To search for multiple strings, needle can be an array containing this strings.
	 * @param   integer $offset The offset from which the search to begin (default 0, the begining of the string).
	 * @param   bool $all_array Used only if the needle param is an array, sets the search type:
	 * * if is set TRUE the function will return TRUE only if all the strings contained in needle are found in haystack,
	 * * if is set FALSE (default) the function will return TRUE if any (one, several or all)
	 * of the strings in the needle are found in haystack.
	 * @return  bool Returns TRUE if needle is found in haystack or FALSE otherwise.
	 */
	function str_contains($haystack,$needle,$offset = 0,$all_array = FALSE) {
		if(is_array($needle)) {
			if(!$haystack || count($needle)==0) { return FALSE; }
			foreach($needle as $n) {
				$tr = strpos($haystack,$n,$offset);
				if(!$all_array && $tr!==FALSE) { return TRUE; }
				if($all_array && $tr===FALSE) { return FALSE; }
			}//foreach($needle as $n)
			return $all_array;
		}//if(is_array($needle))
		return strpos($haystack,$needle,$offset)!==FALSE;
	}//END function str_contains
	/**
	 * Array merge with overwrite option (the 2 input arrays remains untouched).
	 * The second array will overwrite the first.
	 *
	 * @param   array $arr1 First array to merge
	 * @param   array $arr2 Second array to merge
	 * @param   bool  $overwrite Overwrite sitch: TRUE with overwrite (default), FALSE without overwrite
	 * @param   array $initial_arr2
	 * @return  array|bool Returns the merged array or FALSE if one of the arr arguments is not an array
	 */
	function custom_array_merge($arr1,$arr2,$overwrite = TRUE,$initial_arr2 = NULL) {
		if(!is_array($arr1) || !is_array($arr2)) { return NULL; }
		if(!is_array($arr1)) { return $arr2; }
		if(!is_array($arr2)) { return $arr1; }
		$result = $arr1;
		foreach($arr2 as $k=>$v) {
			$i_arr = is_array($initial_arr2) && array_key_exists($k,$initial_arr2) ? $initial_arr2[$k] : NULL;
			if($i_arr && $v===$i_arr) { continue; }
			if(array_key_exists($k,$result)) {
				if(is_array($result[$k]) && is_array($v)) {
					$result[$k] = custom_array_merge($result[$k],$v,$overwrite,$i_arr);
				} else {
					if($overwrite===TRUE) { $result[$k] = $v; }
				}//if(is_array($result[$k]) && is_array($v))
			} else {
				$result[$k] = $v;
			}//if(array_key_exists($k,$result))
		}//END foreach
		if(is_array($initial_arr2) && count($initial_arr2)) {
			foreach(array_diff_key($initial_arr2,$arr2) as $k=>$v) { unset($result[$k]); }
		}//if(is_array($initial_arr2) && count($initial_arr2))
		return $result;
	}//END function custom_array_merge
	/**
	 * This returns the element from certain level of the backtrace stack.
	 *
	 * @param   integer $step The backtrace step index to be returned, starting from 0 (default 1)
	 * @param   string $param Type of the return.
	 * Values can be: "function" and "class" for returning full array of the specified step
	 * or "array" and empty string for returning an array containing only the name of the function/method
	 * and the  class name (if there is one) of the specified step.
	 * @return  array The full array or an array containing function/method and class names from the specified stop.
	 */
	function call_back_trace($step = 1,$param = 'function') {
		$result = array();
		$trdata = debug_backtrace();
		if(!is_numeric($step) || $step<0 || !array_key_exists($step,$trdata)) { return $result; }
		$lstep = $step + 1;
		switch(strtolower($param)) {
			case 'function':
			case 'class':
				$result = array_key_exists($param,$trdata[$lstep]) ? $trdata[$lstep][$param] : '';
				break;
			case 'array':
			case '':
				$result = array(
						'function'=>(array_key_exists('function',$trdata[$lstep]) ? $trdata[$lstep]['function'] : ''),
						'class'=>(array_key_exists('class',$trdata[$lstep]) ? $trdata[$lstep]['class'] : ''),
					);
				break;
			case 'full':
				$result = $trdata[$lstep];
				break;
			default:
				break;
		}//END switch
		return $result;
	}//END function call_back_trace
	/**
	 * Convert string from unknown character set to UTF-8
	 *
	 * @param      string $value The string to be converted
	 * @return     string Returns the converted string
	 * @access     public
	 */
	function custom_utf8_encode($value) {
		$enc = mb_detect_encoding($value,mb_detect_order(),TRUE);
		if(strtoupper($enc)=='UTF-8') { return $value; }
		return iconv($enc,'UTF-8',$value);
	}//END function custom_utf8_encode
	/**
	 * String explode function based on standard php explode function.
	 * Explode on two levels to generate a table-like array.
	 *
	 * @param   string $str The string to be exploded.
	 * @param   string $rsep The string used as row separator.
	 * @param   string $csep The string used as column separator.
	 * @param   string $ksep The string used as column key-value separator.
	 * @return  array The exploded multi-level array.
	 */
	function explode_to_table($str,$rsep = '|:|',$csep = ']#[',$ksep = NULL,$keys_case = CASE_LOWER) {
		$result = [];
		if(!is_string($str) || !strlen($str) || !is_string($rsep) || !strlen($rsep)
				|| (isset($csep) && (!is_string($csep) || !strlen($csep)))
			) { return $result; }
		foreach(explode($rsep,$str) as $row) {
			if(!strlen($row)) { continue; }
			if(!$csep) {
				$result[] = $row;
				continue;
			}//if(!$csep)
			$r_arr = [];
			foreach(explode($csep,$row) as $col) {
				if(!strlen($col)) { continue; }
				if(!is_string($ksep) || !strlen($ksep)) {
					$r_arr[] = $col;
					continue;
				}//if(!is_string($ksep) || !strlen($ksep))
				$c_kv = explode($ksep,$col);
				if(count($c_kv)!=2) {
					$r_arr[] = $col;
				} else {
					if(is_numeric($keys_case)) {
						$r_arr[($keys_case==CASE_UPPER ? strtoupper($c_kv[0]) : strtolower($c_kv[0]))] = $c_kv[1];
					} else {
						$r_arr[$c_kv[0]] = $c_kv[1];
					}//if(is_numeric($keys_case))
				}//if(count($c_kv)!=2)
			}//END foreach
			$result[] = $r_arr;
		}//END foreach
		return $result;
	}//END function explode_to_table
	/**
	 * String explode function based on standard php explode function.
	 * After exploding the string, for each non-numeric element, all leading and trailing spaces will be trimmed.
	 *
	 * @param   string $separator The string used as separator.
	 * @param   string $str The string to be exploded.
	 * @return  array The exploded and trimed string as array.
	 */
	function texplode($separator,$str) {
		$vals = array();
		foreach(explode($separator,$str) as $val) {
			if(is_numeric($val)) {
				$vals[] = $val;
			} elseif(strlen($val)>0) {
				$vals[] = trim($val);
			}//if(is_numeric($val))
		}//foreach(explode($separator,$str) as $val)
		return $vals;
	}//END function texplode
	/**
	 * Eliminate last N folders from a path.
	 *
	 * @param   string $path The path to be processed.
	 * @param   integer $no The number of folders to be removed from the end of the path (default 1).
	 * @return  string The processed path.
	 */
	function up_in_path($path,$no = 1) {
		$result = $path;
		for($i=0; $i<$no; $i++) {
			$result = str_replace('/'.basename($result),'',$result);
		}//for($i=0; $i<$no; $i++)
		return $result;
	}//END function up_in_path
	/**
	 * Changes the case of the first letter of the string or for the first letter of each word in string.
	 *
	 * @param   string $str String to be processed.
	 * @param   bool $all If all param is set TRUE, all words in the string will be processed with ucfirst()
	 * standard php function, otherwise just the first letter in string will be changed to upper.
	 * @return  string The processed string.
	 */
	function custom_ucfirst($str,$all = TRUE,$settolower = TRUE,$delimiter = ' ',$remove_delimiter = FALSE) {
		if(!is_string($str) || !strlen($str)) { return NULL; }
		if($all) {
			$delimiter = is_string($delimiter) && strlen($delimiter) ? $delimiter : ' ';
			$str_arr = explode($delimiter,trim(($settolower ? strtolower($str) : $str)));
			$result = '';
			foreach($str_arr as $stri) { $result .= (strlen($result) && !$remove_delimiter ? $delimiter : '').ucfirst($stri); }
		} else {
			$result = ucfirst(trim(($settolower ? strtolower($str) : $str)));
		}//if($all)
		return $result;
	}//END function custom_ucfirst
	/**
	 * Replaces all url not accepted characters with minus character (-)
	 *
	 * @param   string $string String to be processed.
	 * @return  string The processed string.
	 */
	function str_to_url($string){
		return trim(str_replace(array('--','~~','~',','),'-',preg_replace('/(\W)/','-',trim($string))),'-');
	}//END function str_to_url
	/**
	 * Converts a string to float
	 * (if variable is not a string, is null or is empty, 0 is returned)
	 *
	 * @param   string $var String to be converted to float
	 * @return  string Returns float value or 0.
	 */
	function custom_floatval($var) {
		if(is_null($var) || !is_string($var)) { return 0; }
		if(is_numeric($var)) { return floatval($var); }
		$lvar = preg_replace("/[^-0-9\.\,]/",'',$var);
		if(strlen($lvar)==0) { return 0; }
		if(substr_count($lvar,',')>0 && substr_count($lvar,'.')==0) {
			$lvar = str_replace(',',(substr_count($lvar,',')==1 ? '.' : ''),$lvar);
		} elseif(substr_count($lvar,',')==0 && substr_count($lvar,'.')>0) {
			$lvar = str_replace('.',(substr_count($lvar,'.')==1 ? '.' : ''),$lvar);
		} else {
			if(strrpos($lvar,'.')<strrpos($lvar,',')) {
				$lvar = str_replace(',','',substr($lvar,0,(-1)*(strrpos($lvar,'.')+1)));
			} else {
				$lvar = str_replace('.','',substr($lvar,0,(-1)*(strrpos($lvar,',')+1)));
			}//if(strrpos($lvar,'.')<strrpos($lvar,','))
		}//if(substr_count($lvar,',')>0 && substr_count($lvar,'.')==0)
		return floatval($lvar);
	}//END function custom_floatval
	/**
	 * Validate variable value
	 *
	 * @param   mixed $value Variable to be validated
	 * @param   mixed $def_value Default value to be returned if param is not validated
	 * @param   string $validation Validation type
	 * @param   bool $checkonly Flag for setting validation as check only
	 * @return  mixed Returns param value or default value if not validated
	 * or TRUE/FALSE if $checkonly is TRUE
	 */
	function validate_param($value,$def_value = NULL,$validation = NULL,$checkonly = FALSE) {
		if(!is_string($validation) || !strlen($validation)) {
			if($checkonly) { return isset($value); }
			return (isset($value) ? $value : $def_value);
		}//if(!is_string($validation) || !strlen($validation))
		if($checkonly) {
			switch(strtolower($validation)){
				case 'true':
					return ($value ? TRUE : FALSE);
				case 'is_object':
					return is_object($value);
				case 'is_numeric':
					return is_numeric($value);
				case 'is_integer':
					return (is_numeric($value) && is_integer($value*1));
				case 'is_float':
					return (is_numeric($value) && is_float($value*1));
				case 'is_not0_numeric':
					return (is_numeric($value) && $value<>0);
				case 'is_not0_integer':
					return (is_numeric($value) && is_integer($value*1) && $value<>0);
				case 'is_not0_float':
					return (is_numeric($value) && is_float($value*1) && $value<>0);
				case 'is_array':
					return is_array($value);
				case 'is_notempty_array':
					return (is_array($value) && count($value));
				case 'is_string':
					return (is_string($value) || is_numeric($value));
				case 'is_notempty_string':
					return ((is_string($value) || is_numeric($value)) && strlen($value));
				case 'trim_is_notempty_string':
					return ((is_string($value) || is_numeric($value)) && strlen(trim($value)));
				case 'isset':
				case 'bool':
			    default: return isset($value);
			}//END switch
		}//if($checkonly)
		switch(strtolower($validation)){
			case 'true':
				return ($value ? $value : $def_value);
			case 'is_object':
				return (is_object($value) ? $value : $def_value);
			case 'is_numeric':
				return (is_numeric($value) ? ($value+0) : $def_value);
			case 'is_integer':
				return (is_numeric($value) ? intval($value) : $def_value);
			case 'is_float':
				return (is_numeric($value) ? floatval($value) : $def_value);
			case 'is_not0_numeric':
				return (is_numeric($value) && $value<>0 ? ($value+0) : $def_value);
			case 'is_not0_integer':
				return (is_numeric($value) && intval($value)<>0 ? intval($value) : $def_value);
			case 'is_not0_float':
				return (is_numeric($value) && $value<>0 ? floatval($value) : $def_value);
			case 'is_array':
				return is_array($value) ? $value : $def_value;
			case 'is_notempty_array':
				return (is_array($value) && count($value) ? $value : $def_value);
			case 'is_string':
				return (is_string($value) || is_numeric($value) ? strval($value) : $def_value);
			case 'is_notempty_string':
				return ((is_string($value) || is_numeric($value)) && strlen($value) ? strval($value) : $def_value);
			case 'trim_is_notempty_string':
				return ((is_string($value) || is_numeric($value)) && strlen(trim($value)) ? strval($value) : $def_value);
			case 'bool':
				return (isset($value) ? (strtolower($value)=='true' ? TRUE : (strtolower($value)=='false' ? FALSE : ($value ? TRUE : FALSE))) : $def_value);
			case 'isset':
		  	default:
		  	    return (isset($value) ? $value : $def_value);
		}//END switch
	}//END function validate_param
	/**
	 * Checks if a key exists in an array and validates its value
	 * (if validation is set)
	 *
	 * @param   mixed $key Key to be checked
	 * @param   array $array Array to be searched (passed by reference)
	 * @param   string $validation Validation type
	 * (as implemented in validate_param function)
	 * @return  bool Returns TRUE if $key exists in the $array or FALSE otherwise.
	 * If $validation is not NULL, result is TRUE only if $array[$key] is validated
	 */
	function check_array_key($key,&$array,$validation = NULL) {
		if(!is_array($array) || is_null($key) || !array_key_exists($key,$array)){ return FALSE; }
		if(!is_string($validation)){ return TRUE; }
		return validate_param($array[$key],NULL,$validation,TRUE);
	}//END function check_array_key
	/**
	 * Extracts a param value from a params array
	 *
	 * @param   array $params Params array
	 * (parsed as reference)
	 * @param   string $key Key of the param to be returned
	 * @param   mixed $def_value Default value to be returned if param is not validated
	 * @param   string $validation Validation type
	 * (as implemented in validate_param function)
	 * @return  mixed Returns param value or default value if not validated
	 */
	function get_array_param(&$params,$key,$def_value = NULL,$validation = NULL,$sub_key = NULL) {
		if(!is_array($params) || is_null($key) || !array_key_exists($key,$params)){ return $def_value; }
		if(!isset($sub_key) || (!is_string($sub_key) && !is_numeric($sub_key))) {
			return validate_param($params[$key],$def_value,$validation);
		}//if(!isset($sub_key) || (!is_string($sub_key) && !is_numeric($sub_key)))
		if(!is_array($params[$key]) || !array_key_exists($sub_key,$params[$key])){ return $def_value; }
		return validate_param($params[$key][$sub_key],$def_value,$validation);
	}//END function get_array_param
	/**
	 * Converts a hex color to RGB
	 *
	 * @param  string $hex Color hex code
	 * @param  number $r R code by reference (for output)
	 * @param  number $r G code by reference (for output)
	 * @param  number $r B code by reference (for output)
	 * @return array Returns an array containing the RGB values - array(R,G,B)
	 */
	function hex2rgb($hex,&$r = NULL,&$g = NULL,&$b = NULL) {
	   $hex = str_replace('#','',$hex);
	   if(strlen($hex)==3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }//if(strlen($hex)==3)
	   return array($r,$g,$b);
	}//END function hex2rgb
	/**
	 * Custom round numeric values
	 *
	 * @param  numeric $value The numeric value to be rounded
	 * @param  numeric $scale The rounding precision (number of decimals to keep)
	 * @param  numeric $step The step value for rounding (integers from 1 to 9)
	 * @param  numeric $mode Rounding mode: 1 = round up, 0 = matematical round (default) and -1 = round down
	 * @return numeric Returns the rounded number or FALSE on wrong params
	 */
	function custom_round($value,$scale,$step = 1,$mode = 0) {
		if(!is_numeric($value) || !is_numeric($scale)) { return FALSE; }
		$lstep = (!is_numeric($step) || $step<=0 || $step>9) ? 1 : intval($step);
		$lscale = pow(10,$scale-1);
		$val = intval($value*$lscale)/$lscale;
		$rem = round(($value)-$val,6)*$lscale*10;
		if($rem==0) { return $value; }
		$rval = intval($rem/$lstep)*$lstep;
		switch($mode) {
			case -1:
				$rem = $rval;
				break;
			case 1:
				$rem = $rval+$lstep;
				break;
			case 0;
			default:
				$rem = $rval+(($rem-$rval*$lstep)>=($lstep/2) ? $lstep : 0);
				break;
		}//END switch
		return ($val + $rem/($lscale*10));
	}//END function custom_round
	/**
	 * Converts a date from unix timestamp to excel serial
	 *
	 * @param  mixed   $date The date to be converted in unix time stamp format
	 * or in string format (if string the $ts_input param must be set to FALSE)
	 * @param  string $timezone The time zone for the string data to be converted
	 * @param  string $new_timezone User's time zone
	 * @return int Returns the date in excel serial format
	 */
	function unixts2excel($date,$timezone = NULL,$new_timezone = NULL) {
		if(!$date) { return NULL; }
		try {
			if(is_numeric($date)) {
				$dt = strlen($timezone) ? new DateTime(date('Y-m-d H:i:s',$date),new DateTimeZone($timezone)) : new DateTime(date('Y-m-d H:i:s',$date));
			} elseif(is_object($date)) {
				$dt = $date;
			} else {
				$dt = strlen($timezone) ? new DateTime($date,new DateTimeZone($timezone)) : new DateTime($date);
			}//if(strlen($timezone))
			if(strlen($new_timezone) && $new_timezone!==$timezone) {
				$dt->setTimezone(new DateTimeZone($new_timezone));
			}//if(strlen($new_timezone) && $new_timezone!==$timezone)
			$result = (25569.083333333 + ($dt->getTimestamp() + 3600) / 86400);
			return $result;
		} catch(Exception $ne) {
			return NULL;
		}//END try
	}//END function unixts2excel
	/**
	 * Converts a date from excel serial to unix time stamp
	 *
	 * @param  numeric $date The date to be converted from excel serial format
	 * @param  string $timezone User's time zone
	 * @param  string $new_timezone The time zone for the string data to be converted
	 * @param  string $format The format in which the string data will be outputed
	 * If NULL or empty, numeric time stamp is returned
	 * @return int Returns the date as string or or unix time stamp
	 */
	function excel2unixts($date,$timezone = NULL,$new_timezone = NULL,$format = 'Y-m-d H:i:s') {
		if(!is_numeric($date)) { return NULL; }
		try {
			$ldate = date('Y-m-d H:i:s',(round(($date - 25569.083333333) * 86400) - 3600));
			$dt = strlen($timezone) ? new DateTime($ldate,new DateTimeZone($timezone)) : new DateTime($ldate);
			if(strlen($new_timezone) && $new_timezone!==$timezone) {
				$dt->setTimezone(new DateTimeZone($new_timezone));
			}//if(strlen($new_timezone) && $new_timezone!==$timezone)
			if(!$format || !strlen($format)) {
				return $dt->getTimestamp();
			}//if(!$format || !strlen($format))
			return $dt->format($format);
		} catch(Exception $e) {
			return NULL;
		}//END try
	}//END function excel2unixts
	/**
	 * Gets the Unix timestamp for a date/time with an optional timezone
	 *
	 * @param  string $date The string representing a date/time in a PHP accepted format
	 * (if NULL or 'now' is passed, the function will return the current Unix timestamp)
	 * @param  string $timezone Optional parameter representing the timezone string
	 * @param  string $new_timezone User's time zone (optional)
	 * @return int The Unix timestamp
	 */
	function get_timestamp($date,$timezone = NULL,$new_timezone = NULL) {
		try {
			$dt = new DateTime($date,new DateTimeZone(strlen($timezone) ? $timezone : XSession::$server_timezone));
			if(strlen($new_timezone)) {
				$dt->setTimezone(new DateTimeZone($new_timezone));
			}//if(strlen($new_timezone))
			return $dt->getTimestamp();
		} catch(Exception $e) {
			return NULL;
		}//END try
	}//END function get_timestamp
	/**
	 * Returns a string containing a formated number
	 *
	 * @param  number $value The number to be formated
	 * @param  string $format The format string in NETopes style
	 * (NETopes format: "[number of decimals]|[decimal separator|[group separator]|[sufix]"
	 * @return string Returns the formated number or NULL in case of errors
	 */
	function custom_number_format($value,$format = '0|||') {
		if(!is_numeric($value) || !is_string($format) || !strlen($format)) { return NULL; }
		$f_arr = explode('|',$format);
		if(!is_array($f_arr) || count($f_arr)!=4) { return NULL; }
		return number_format($value,$f_arr[0],$f_arr[1],$f_arr[2]).$f_arr[3];
	}//END function custom_number_format
	/**
	 * Returns an array of files from the provided path and all its sub folders.
	 * For each file the value is an array with the following structure:
	 * array(
	 * 		'name'=>(string) File name (with extension),
	 * 		'path'=>(string) Full path of the file (without file name),
	 * 		'ext'=>(string) File extension (without "." character)
	 * )
	 *
	 * @param  string $path The starting path for the search
	 * @param  array  $extensions An array of accepted file extensions (without the "." character)
	 * or NULL for all
	 * @param  string $exclude A regex string for filtering files and folders names with preg_match function
	 * or NULL for all
	 * @param  int    $sort Sort type in php scandir() format (default SCANDIR_SORT_ASCENDING)
	 * @param  array  $dir_exclude An array of folders to be excluded (at any level of the tree)
	 * @return array  Returns an array of found files
	 */
	function get_files_recursive($path,$extensions = NULL,$exclude = NULL,$sort = SCANDIR_SORT_ASCENDING,$dir_exclude = NULL) {
		if(!$path || !file_exists($path)) { return FALSE; }
		$result = array();
		foreach(scandir($path,$sort) as $v) {
			if($v=='.' || $v=='..' || (strlen($exclude) && preg_match($exclude,$v))) { continue; }
			if(is_dir($path.'/'.$v)) {
				if(is_array($dir_exclude) && count($dir_exclude) && in_array($v,$dir_exclude)) { continue; }
				$tmp_result = get_files_recursive($path.'/'.$v,$extensions,$exclude,$sort,$dir_exclude);
				if(is_array($tmp_result)) { $result = array_merge($result,$tmp_result); }
			} else {
				$ext = strrpos($v,'.')===FALSE || strrpos($v,'.')==0 ? '' : substr($v,strrpos($v,'.')+1);
				if(is_array($extensions) && !in_array($ext,$extensions)) { continue; }
				$result[] = array('name'=>$v,'path'=>$path,'ext'=>$ext);
			}//if(is_dir($path.'/'.$v))
		}//END foreach
		return $result;
	}//END function get_files_recursive
	/**
	 * Converts a string of form [abcd_efgh_ijk] into a camel case form [AbcdEfghIjk]
	 *
	 * @param  string $string String to be converted
	 * @param  bool   $lower_first Flag to indicate if the first char should be lower case
	 * @return string Returns the string in camel case format or NULL on error
	 */
	function convert_to_camel_case($string,$lower_first = FALSE) {
		$result = custom_ucfirst($string,TRUE,TRUE,'_',TRUE);
		return ($lower_first ? lcfirst($result) : $result);
	}//END function convert_to_camel_case
	/**
	 * Get file mime type by extension
	 *
	 * @param  string $filename Target file name (with or without path)
	 * @return string Returns the mime type identified by file extension
	 */
	function get_file_mime_type_by_extension($filename) {
		$standard_mime_types = array(
			    'pdf'=>'application/pdf',
			    'txt'=>'text/plain',
			    'html'=>'text/html',
			    'htm'=>'text/html',
			    'exe'=>'application/octet-stream',
			    'zip'=>'application/zip',
			    'doc'=>'application/msword',
			    'xls'=>'application/vnd.ms-excel',
			    'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			    'ppt'=>'application/vnd.ms-powerpoint',
			    'gif'=>'image/gif',
			    'png'=>'image/png',
			    'jpeg'=>'image/jpg',
			    'jpg'=> 'image/jpg',
			    'php'=>'text/plain',
			    'apk'=>'application/octet-stream',
			    'log'=>'text/plain',
			);
		$fileext = substr($filename,strrpos($filename,'.')+1);
		return (array_key_exists($fileext,$standard_mime_types) ? $standard_mime_types[$fileext] : 'application/force-download');
	}//END function get_file_mime_type_by_extension

	function custom_nl2br($string) {
		if(!is_string($string)) { return NULL; }
		return nl2br(str_replace("\t",'&nbsp;&nbsp;&nbsp;',$string));
	}//END function custom_nl2br

	function custom_br2nl($string) {
		if(!is_string($string)) { return NULL; }
		return str_replace('&nbsp;&nbsp;&nbsp;',"\t",str_replace(array('<br/>','<br />','<br>'),"\n",$string));
	}//END function custom_br2nl

	function safe_json_encode($data,$for_html = TRUE) {
		$result = json_encode($data);
		if($for_html) {
			$result = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$result);
			$result = nl2br($result);
		} else {
			$result = str_replace(array("\r\n","\r","\n","\t"),' ',$result);
		}//if($for_html)
		return $result;
	}//END function safe_json_encode

	function vprint($var,$html_entities = FALSE,$return = FALSE,$utf8encode = TRUE) {
		if(is_string($var)) { $result = $var; }
		else { $result = print_r($var,TRUE); }
		if($html_entities) {
			$result = htmlentities($result,NULL,($utf8encode ? 'utf-8' : NULL));
		} else {
			if($utf8encode) { $result = utf8_encode($result); }
			$result = '<pre>'.$result.'</pre>';
		}//if($html_entities)
		if($return===TRUE) { return $result; }
		echo $result;
	}//END function vprint

	function array2string($array,$separator = '|',$parent = NULL) {
		if(!is_array($array) || !count($array)) { return FALSE; }
		$result = '';
		foreach($array as $k=>$v) {
		    if(is_array($v)) {
		    	$result .= array2string($v,$separator,$parent.(strlen($parent) ? '/' : '').$k);
		    } else {
		    	$result .= $k.$separator."'{$v}'".$separator.$parent.$separator.$separator."\n";
		    }//if(is_array($v))
		}//END foreach
		return $result;
	}//END function array2string
?>