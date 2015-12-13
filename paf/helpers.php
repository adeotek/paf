<?php
/**
 * PAF (PHP AJAX Framework) Helpers file
 *
 * This contains a collection of php functions that extends standard php functions.
 * This functions are used by PAF (PHP AJAX Framework) and can olso be used in your project.
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.1
 * @filesource
 */
	/**
	 * PAFReq_INIT_ALL constant definition (used as parameter in PAF class AReqInit method)
	 */
	define('PAFReq_INIT_ALL',1);
	/**
	 * PAFReq_INIT_NO_JS constant definition (used as parameter in PAF class AReqInit method)
	 */
	define('PAFReq_INIT_NO_JS',0);
	/**
	 * PAF_DBG_DEBUG constant definition (used as parameter in PAF class debug methods)
	 */
	define('PAF_DBG_DEBUG','log');
	/**
	 * PAF_DBG_WARNING constant definition (used as parameter in PAF class debug methods)
	 */
	define('PAF_DBG_WARNING','warning');
	/**
	 * PAF_DBG_ERROR constant definition (used as parameter in PAF class debug methods)
	 */
	define('PAF_DBG_ERROR','error');
	/**
	 * PAF_DBG_INFO constant definition (used as parameter in PAF class debug methods)
	 */
	define('PAF_DBG_INFO','info');
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
	 * @param   bool $overwrite Overwrite sitch: TRUE with overwrite (default), FALSE without overwrite
	 * @return  array|bool Returns the merged array or FALSE if one of the arr arguments is not an array
	 */
	function custom_array_merge($arr1,$arr2,$overwrite = TRUE) {
		if(!is_array($arr1) || !is_array($arr2)) { return FALSE; }
		$result = $arr1;
		foreach ($arr2 as $k=>$v) {
			if(array_key_exists($k,$result)) {
				if(is_array($result[$k]) && is_array($v)) {
					$result[$k] = custom_array_merge($result[$k],$v,$overwrite);
				} else {
					if($overwrite===TRUE) { $result[$k] = $v; }
				}//if(is_array($result[$k]) && is_array($v))
			} else {
				$result[$k] = $v;
			}//if(array_key_exists($k,$result))
		}//END foreach
		return $result;
	}//END function custom_array_merge
	/**
	 * This returns the element from certain level of the backtrace stack.
	 *
	 * @param   string $param Type of the return.
	 * Values can be: "function" and "class" for returning full array of the specified step
	 * or "array" and empty string for returning an array containing only the name of the function/method
	 * and the  class name (if there is one) of the specified step.
	 * @param   integer $step The backtrace step index to be returned, starting from 0 (default 1)
	 * @return  array The full array or an array containing function/method and class names from the specified stop.
	 */
	function call_back_trace($param = 'function',$step = 1) {
		$result = array();
		$trdata = debug_backtrace();
		if(!is_numeric($step) || $step<0 || !array_key_exists($step,$trdata)) { return $result; }
		$lstep = $step + 1;
		switch($param) {
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
			default:
				break;
		}//switch($param)
		return $result;
	}//END function call_back_trace
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
	function custom_ucfirst($str,$all = TRUE,$settolower = TRUE) {
		if($all) {
			$str_arr = explode(' ',(is_string($str) ? $str : ''));
			for($i=0; $i<count($str_arr); $i++) {
				$str_arr[$i] = ucfirst(($settolower ? strtolower($str_arr[$i]) : $str_arr[$i]));
			}//for($i=0; $i<count($str_arr); $i++)
			return implode(' ',$str_arr);
		} else {
			return ucfirst(($settolower ? strtolower($str) : $str));
		}//if($all)
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
	 * Checks if a key exists in an array and validates its value
	 * (if validation is set)
	 *
	 * @param   mixed $key Key to be checked
	 * @param   array $array Array to be searched (passed by reference)
	 * @param   string $validation Validation type
	 * - NULL or empty string = no validation
	 * - 'true' = checks if value is not FALSE (generic)
	 * - 'isset' = checks if value is set (not NULL)
	 * - 'is_numeric' = checks if value is a number
	 * - 'is_array' = checks if value is an array
	 * @return  bool Returns TRUE if $key exists in the $array or FALSE otherwise.
	 * If $validation is not NULL, result is TRUE only if $array[$key] is validated
	 */
	function check_array_key($key,&$array,$validation = NULL) {
		if(!is_array($array) || is_null($key) || !array_key_exists($key,$array)){ return FALSE; }
		if(!is_string($validation)){ return TRUE; }
		switch(strtolower($validation)){
			case 'true': return ($array[$key] ? TRUE : FALSE);
			case 'isset': return isset($array[$key]);
			case 'is_numeric': return is_numeric($array[$key]);
			case 'is_not0_numeric': return (is_numeric($array[$key]) && $array[$key]<>0);
			case 'is_array': return is_array($array[$key]);
			case 'is_notempty_array': return (is_array($array[$key]) && count($array[$key])>0);
			case 'is_string': return (is_string($array[$key]) || is_numeric($array[$key]));
			case 'is_notempty_string': return ((is_string($array[$key]) || is_numeric($array[$key])) && strlen($array[$key])>0);
			case 'trim_is_notempty_string': return ((is_string($array[$key]) || is_numeric($array[$key])) && strlen(trim($array[$key]))>0);
		  	default: return isset($array[$key]);
		}//END switch
	}//END function check_array_key
	/**
	 * Extracts a param value from a params array
	 *
	 * @param   array $params Params array
	 * (parsed as reference)
	 * @param   string $key Key of the param to be returned
	 * @param   mixed $def_value Default value to be returned if param is not validated
	 * @param   string $validation Validation type
	 * (as implemented in check_array_key function)
	 * @return  mixed Returns param value or default value if not validated
	 */
	function get_array_param(&$params,$key,$def_value = NULL,$validation = NULL,$sub_key = NULL) {
		if(strlen($sub_key)) {
			if(!is_array($params) || !array_key_exists($key,$params)) { return $def_value; }
			return check_array_key($sub_key,$params[$key],$validation) ? ($validation=='bool' ? (strtolower($params[$key][$sub_key])=='true' ? TRUE : (strtolower($params[$key][$sub_key])=='false' ? FALSE : ($params[$key][$sub_key] ? TRUE : FALSE))) : $params[$key][$sub_key]) : $def_value;
		}//if(strlen($sub_key))
		return check_array_key($key,$params,$validation) ? ($validation=='bool' ? (strtolower($params[$key])=='true' ? TRUE : (strtolower($params[$key])=='false' ? FALSE : ($params[$key] ? TRUE : FALSE))) : $params[$key]) : $def_value;
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
		global $xsession;
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
	 * @param  mixd $date The date to be converted in unix timestamp format
	 * or in string fromat (if string the $ts_input param must be set to FALSE)
	 * @param  bool $ts_input Param indicating the format of the input date
	 * (TRUE for unix timestamp or FALSE for string)
	 * @param  string $timestamp The timestamp for the string data to be converted
	 * @return int Returns the date in excel serial format
	 */
	function unixts2excel($date,$ts_input = TRUE,$timezone = NULL) {
		if($ts_input) { return (is_numeric($date) ? (25569 + $date / 86400) : 1); }
		try {
			if(strlen($timezone)) {
				$dt = new DateTime($date,new DateTimeZone($timezone));
			} else {
				$dt = new DateTime($date);
			}//if(strlen($timezone))
			return (25569 + $dt->getTimestamp() / 86400);
		} catch(Exception $ne) {
			return 1;
		}//END try
	}//END function unixts2excel
	/**
	 * Converts a date from excel serial to unix timestamp
	 *
	 * @param  mixd $date The date to be converted in excel serial format
	 * @param  bool $ts_output Param indicating the format of the output date
	 * (TRUE for unix timestamp or FALSE for string)
	 * @param  string $format The format in which the string data will be outputed
	 * @return int Returns the date in unix timestamp format
	 * or in string fromat (if $ts_output param is set to FALSE)
	 */
	function excel2unixts($date,$ts_output = TRUE,$format = 'Y-m-d H:i:s') {
		if($ts_output) { return (($date - 25569) * 86400); }
		return date($format,(($date - 25569) * 86400));
	}//END function excel2unixts
?>