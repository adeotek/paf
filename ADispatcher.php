<?php
/**
 * Short desc
 *
 * description
 *
 * @package    Hinter\PAF
 * @author     Hinter Software
 * @copyright  Copyright (c) 2004 - 2013 Hinter Software
 * @license    LICENSE.txt
 * @version    1.2.0
 * @filesource
 */
	/**
	 * ClassName description
	 *
	 * long_description
	 *
	 * @package  Hinter\PAF
	 * @access   public
	 */
	class PAFRequest extends ARequest {
		/**
		 * Generic ajax call
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function AjaxCall($window_name,$param) {
			if(!strlen($window_name)) { $this->js("window.name = '{$this->paf_object->phash}'"); }
			try {
				/*
				 * Code
				 */
			} catch(XException $e) {
				ErrorHandler::AddError($e);
			}//END try
		}//END public function AjaxCall
	}//END class PAFRequest extends ARequest
?>