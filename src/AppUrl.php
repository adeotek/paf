<?php
/**
 * Class AppUrl file.
 *
 * Application URL interaction class.
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.2
 * @filesource
 */
namespace PAF;
/**
 * Class AppUrl
 *
 * Application URL interaction class.
 *
 * @package AdeoTEK\PAF
 */
class AppUrl {
	/**
	 * @var        string The path included in the application URL
	 * @access     protected
	 * @static
	 */
	protected static $url_path = NULL;
	/**
	 * @var    string Application web protocol (http/https)
	 * @access protected
	 */
	protected $app_web_protocol = NULL;
	/**
	 * @var    string Application domain (auto-set on constructor)
	 * @access protected
	 */
	protected $app_domain = NULL;
	/**
	 * @var    string Application folder inside www root (auto-set on constructor)
	 * @access protected
	 */
	protected $url_folder = NULL;
	/**
	 * @var    string Application base url: domain + path + url id (auto-set on constructor)
	 * @access protected
	 */
	protected $url_base = NULL;
	/**
	 * @var    array GET (URL) data
	 * @access public
	 */
	public $data = [];
	/**
	 * @var    array GET (URL) special parameters list
	 * @access public
	 */
	public $special_params = array('language','urlid');
	/**
	 * @var    string URL virtual path
	 * @access public
	 */
	public $url_virtual_path = NULL;
	/**
	 * Extracts the URL path of the application.
	 *
	 * @param      string $startup_path Entry point file absolute path
	 * @return     string Returns the URL path of the application.
	 * @access     public
	 * @static
	 */
	public static function ExtractUrlPath($startup_path = NULL) {
		if(strlen($startup_path)) {
			self::$url_path = str_replace('\\','/',(str_replace(_AAPP_ROOT_PATH._AAPP_PUBLIC_ROOT_PATH,'',$startup_path)));
			self::$url_path = trim(str_replace(trim(self::$url_path,'/'),'',trim(dirname($_SERVER['SCRIPT_NAME']),'/')),'/');
			self::$url_path = trim(self::$url_path.'/'.trim(_AAPP_PUBLIC_PATH,'\/'),'\/');
		} else {
			self::$url_path = trim(dirname($_SERVER['SCRIPT_NAME']),'\/');
		}//if(strlen($startup_path))
		return (strlen(self::$url_path) ? '/'.self::$url_path : '');
	}//END public static function ExtractUrlPath
	/**
	 * Gets the base URL of the application.
	 *
	 * @param	   string $startup_path Startup absolute path
	 * @return     string Returns the base URL of the application.
	 * @access     public
	 * @static
	 */
	public static function GetRootUrl($startup_path = NULL) {
		$app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
		$app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
		$url_folder = self::ExtractUrlPath($startup_path);
		return $app_web_protocol.$app_domain.$url_folder;
	}//END public static function GetRootUrl
	/**
	 * AppUrl constructor.
	 *
	 * @param string $app_domain
	 * @param string $app_web_protocol
	 * @param string $url_folder
	 */
	public function __construct(string $app_domain,string $app_web_protocol,string $url_folder) {
		$this->app_domain = $app_domain;
		$this->app_web_protocol = $app_web_protocol;
		$this->url_folder = strlen(trim($url_folder,'\/ ')) ? '/'.trim($url_folder,'\/ ') : '';
		if(isset($_SERVER['REQUEST_URI'])) {
			$uri_len = strpos($_SERVER['REQUEST_URI'],'?')!==FALSE ? strpos($_SERVER['REQUEST_URI'],'?') : (strpos($_SERVER['REQUEST_URI'],'#')!==FALSE ? strpos($_SERVER['REQUEST_URI'],'#') : strlen($_SERVER['REQUEST_URI']));
			$this->url_base = $this->app_web_protocol.$this->app_domain.substr($_SERVER['REQUEST_URI'],0,$uri_len);
		} else {
			$this->url_base = $this->app_web_protocol.$this->app_domain;
		}//if(isset($_SERVER['REQUEST_URI']))
		$this->data = is_array($_GET) ? $this->SetParams($_GET) : [];
	}//END public function __construct
	/**
	 * @return string
	 */
	public function GetCurrentUrl(): string {
		return $this->app_web_protocol.$this->app_domain.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
	}//END public function GetCurrentUrl
	/**
	 * @return string
	 */
	public function GetWebLink(): string {
		return $this->app_web_protocol.$this->app_domain.$this->url_folder;
	}//END public function GetWebLink
	/**
	 * @return string
	 */
	public function GetAppWebProtocol(): string {
		return $this->app_web_protocol;
	}//END public function GetAppWebProtocol
	/**
	 * @return string
	 */
	public function GetAppDomain(): string {
		return $this->app_domain;
	}//END public function GetAppDomain
	/**
	 * @return string
	 */
	public function GetUrlFolder(): string {
		return $this->url_folder;
	}//END public function GetUrlFolder
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @param bool        $keysonly
	 * @return string
	 * @access public
	 */
	public function ParamToString($params,$keysonly = FALSE) {
    	if(is_array($params)) {
    		$keys = '';
    		$texts = '';
    		foreach($params as $k=>$v) {
				$keys .= (strlen($keys) ? ',' : '').$k;
				if($keysonly!==TRUE) { $texts .= (strlen($texts) ? ',' : '').str_to_url($v); }
			}//foreach ($params as $k=>$v)
			if($keysonly===TRUE) { return $keys; }
			return $keys.(strlen($texts) ? '~'.$texts : '');
    	} else {
    		return (isset($params) ? $params : '');
    	}//if(is_array($params))
	}//END public function ParamToString
	/**
	 * description
	 *
	 * @param $param
	 * @return array|null
	 * @access public
	 */
	public function GetParamElements($param) {
		$result = NULL;
		if(strlen($param)) {
			$param_keys = strpos($param,'~')===FALSE ? $param : substr($param,0,(strpos($param,'~')));
			$param_texts = strpos($param,'~')===FALSE ? '' : substr($param,(strpos($param,'~')+1));
			$keys = explode(',',$param_keys);
			$texts = strlen($param_texts)>0 ? explode(',',$param_texts) : NULL;
			for($i=0; $i<count($keys); $i++) {
				if(strlen($keys[$i])>0) {
					if(!is_array($result)) {
						$result = array();
					}//if(!is_array($result))
					$result[$keys[$i]] = (is_array($texts) && array_key_exists($i,$texts)) ? $texts[$i] : '';
				}//if(strlen($keys[$i])>0)
			}//for($i=0; $i<count($keys); $i++)
		}//if(strlen($param))
		return $result;
	}//END public function GetParamElements
	/**
	 * Get elements for a parameter from the url data array
	 *
	 * @param      $key
	 * @param bool $string
	 * @param bool $keysonly
	 * @return string|null
	 * @access public
	 */
	public function GetComplexParam($key,$string = FALSE,$keysonly = FALSE) {
		$result = array_key_exists($key,$this->data) ? $this->data[$key] : NULL;
		if($string===TRUE && isset($result)) { return $this->ParamToString($result,$keysonly); }
		return $result;
	}//END public function GetComplexParam
	/**
	 * Set a simple parameter into the url data array
	 *
	 * @param $key
	 * @param $val
	 * @return bool
	 * @access public
	 */
	public function SetComplexParam($key,$val) {
		if(!is_array($key) || !count($val)) { return FALSE; }
		$this->data[$key] = $val;
		return TRUE;
	}//END public function SetComplexParam
	/**
	 * Unset a parameter from the url data array
	 *
	 * @param $key
	 * @return void
	 * @access public
	 */
	public function UnsetComplexParam($key) {
		unset($this->data[$key]);
	}//END public function UnsetComplexParam
	/**
	 * Get a simple parameter from the url data array
	 *
	 * @param      $key
	 * @param bool $full
	 * @return string|null
	 * @access public
	 */
	public function GetParam($key,$full = FALSE) {
		return $this->GetComplexParam($key,$full!==TRUE,TRUE);
	}//END public function GetParam
	/**
	 * Set a simple parameter into the url data array
	 *
	 * @param $key
	 * @param $val
	 * @return bool
	 * @access public
	 */
	public function SetParam($key,$val) {
		return $this->SetComplexParam($key,array($val=>''));
	}//END public function SetParam
	/**
	 * Unset a parameter from the url data array
	 *
	 * @param $key
	 * @return void
	 * @access public
	 */
	public function UnsetParam($key) {
		$this->UnsetComplexParam($key);
	}//END public function UnsetParam
	/**
	 * Gets n-th element from a parameter in the url data array
	 *
	 * @param     $key
	 * @param int $position
	 * @return string|null
	 * @access public
	 */
	public function GetParamElement($key,$position = 0) {
		if(strlen($key)>0 && array_key_exists($key,$this->data)) {
			if(is_array($this->data[$key])) {
				$i = 0;
				foreach ($this->data[$key] as $k=>$v) {
					if($i==$position) {
						return $k;
					} else {
						$i++;
					}//if($i==$position)
				}//foreach ($this->data[$key] as $k=>$v)
			} else {
				return $this->data[$key];
			}//if(is_array($this->data[$key]))
		}//if(strlen($key)>0 && array_key_exists($key,$this->data))
		return NULL;
	}//END public function GetParamElement
	/**
	 * Sets an element from a parameter in the url data array
	 *
	 * @param        $key
	 * @param        $element
	 * @param string $text
	 * @return bool
	 * @access public
	 */
	public function SetParamElement($key,$element,$text = '') {
		if(is_null($key) || is_null($element)) { return FALSE; }
		$this->data[$key] = is_array($this->data[$key]) ? $this->data[$key] : array();
		if(is_array($element)) {
			foreach ($element as $k=>$v) {
				$this->data[$key][$k] = str_to_url($v);
			}//foreach ($element as $k=>$v)
		} else {
			$this->data[$key][$element] = str_to_url($text);
		}//if(is_array($element))
	}//END public function SetParamElement
	/**
	 * Removes an element from a parameter in the url data array
	 *
	 * @param $key
	 * @param $element
	 * @return bool
	 * @access public
	 */
	public function UnsetParamElement($key,$element) {
		if(is_null($key) || is_null($element)) { return FALSE; }
		unset($this->data[$key][$element]);
	}//END public function UnsetParamElement
	/**
	 * description
	 *
	 * @param $url
	 * @return array
	 * @access public
	 */
	public function SetParams($url) {
		$result = array();
		if(is_array($url)) {
			foreach ($url as $k=>$v) { $result[$k] = $this->GetParamElements($v); }
		} else {
			$param_str = explode('?',$url);
			$param_str = count($param_str)>1 ? $param_str[1] : '';
			if(strlen($param_str)>0) {
				$params = explode('&',$param_str);
				foreach ($params as $param) {
					$element = explode('=',$param);
					if(count($element)>1) { $result[$element[0]] = $this->GetParamElements($element[1]); }
				}//foreach ($params as $k=>$v)
			}//if(strlen($param_str)>0)
		}//if(is_array($url))
		return $result;
	}//END public function SetParams
	/**
	 * description
	 *
	 * @param int  $url_format
	 * @param null $params
	 * @return string
	 * @access public
	 */
	public function GetBase($url_format = URL_FORMAT_FRIENDLY,$params = NULL) {
		$lurl_format = AppConfig::app_mod_rewrite() ? $url_format : URL_FORMAT_SHORT;
		switch($lurl_format) {
			case URL_FORMAT_FRIENDLY:
				$lang = NULL;
				$urlid = NULL;
				$urlpath = NULL;
				if(is_array($params) && count($params)) {
					$lang = array_key_exists('language',$params) ? $this->ParamToString($params['language']) : NULL;
					$urlid = array_key_exists('urlid',$params) ? $this->ParamToString($params['urlid']) : NULL;
				}//if(is_array($params) && count($params))
				if(is_null($lang)) {
					$lang = array_key_exists('language',$this->data) ? $this->ParamToString($this->data['language']) : NULL;
				}//if(is_null($lang))
				if(is_null($urlid)) {
					$urlid = array_key_exists('urlid',$this->data) ? $this->ParamToString($this->data['urlid']) : NULL;
				}//if(is_null($urlid))
				return $this->GetWebLink().'/'.(strlen($this->url_virtual_path) ? $this->url_virtual_path.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen(trim($urlid,'/')) ? trim($urlid,'/').'/' : '');
			case URL_FORMAT_FRIENDLY_ORIGINAL:
				return $this->url_base;
			case URL_FORMAT_FULL:
				return $this->GetWebLink().'/index.php';
			case URL_FORMAT_SHORT:
				return $this->GetWebLink().'/';
			case URL_FORMAT_URI_ONLY:
			default:
				return '';
		}//END switch
	}//END public function GetBase
	/**
	 * Create new application URL
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @param int         $url_format
	 * @return string
	 * @access public
	 */
	public function GetNewUrl($params = NULL,$url_format = URL_FORMAT_FRIENDLY) {
		$result = '';
		$anchor = '';
		$lurl_format = AppConfig::app_mod_rewrite() ? $url_format : URL_FORMAT_SHORT;
		if(is_array($params) && count($params)) {
			$first = TRUE;
			foreach($params as $k=>$v) {
				if($k=='anchor') {
					$anchor = $this->ParamToString($v);
					continue;
				}//if($k=='anchor')
				if(($lurl_format==URL_FORMAT_FRIENDLY || $lurl_format==URL_FORMAT_FRIENDLY_ORIGINAL) && in_array($k,$this->special_params)) { continue; }
				$val = $this->ParamToString($v);
				if(in_array($k,$this->special_params) && !$val) { continue; }
				$prefix = '&';
				if($first) {
					$first = FALSE;
					$prefix = '?';
				}//if($first)
				$result .= $prefix.$k.'='.$val;
			}//END foreach
		}//if(is_array($params) && count($params))
		return $this->GetBase($lurl_format,$params).$result.(strlen($anchor) ? '#'.$anchor : '');
	}//END public function GetNewUrl
	/**
	 * description
	 *
	 * @param null $params
	 * @param null $rparams
	 * @param int  $url_format
	 * @return string
	 * @access public
	 */
	public function GetUrl($params = NULL,$rparams = NULL,$url_format = URL_FORMAT_FRIENDLY) {
		$data = $this->data;
		if(is_array($rparams) && count($rparams)) {
			foreach($rparams as $key=>$value) {
				if(is_array($value)) {
					foreach($value as $rv) { unset($data[$key][$rv]); }
					if(count($data[$key])==0) { unset($data[$key]); }
				} else {
					unset($data[$value]);
				}//if(is_array($value))
			}//END foreach
		}//if(is_array($rparams) && count($rparams))
		if(is_array($params) && count($params)) { $data = custom_array_merge($data,$params,TRUE); }
		return $this->GetNewUrl($data,$url_format);
	}//END public function GetUrl
	/**
	 * description
	 *
	 * @param      $key
	 * @param null $element
	 * @return bool
	 * @access public
	 */
	public function ElementExists($key,$element = NULL) {
		if(is_null($element)) {
			if(array_key_exists($key,$this->data) && isset($this->data[$key])) {
				return TRUE;
			}//if(array_key_exists($key,$this->data) && isset($this->data[$key]))
		} else {
			if(array_key_exists($key,$this->data) && array_key_exists($element,$this->data[$key])) {
				return TRUE;
			}//if(array_key_exists($key,$this->data) && array_key_exists($element,$this->data[$key]))
		}//if(is_null($element))
		return FALSE;
	}//END public function ElementExists
}//END class AppUrl
?>