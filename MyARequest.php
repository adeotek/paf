<?php
/**
 * Sample ARequest implementation class file
 *
 * This class extends ARequest
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.0.0
 * @filesource
 */
	/**
	 * Sample ARequest implementation class
	 *
	 * This class extends ARequest
	 *
	 * @package  AdeoTEK\PAF
	 * @access   public
	 */
	class MyARequest extends PAF\ARequest {
		/**
		 * Generic ajax call
		 *
		 * @param  string $window_name Window/tab name (UID)
		 * @param  mixed $param Parameter to be used in code execution
		 * @return void return description
		 * @access public
		 */
		public function AjaxCall($window_name,$param) {
			if(!strlen($window_name)) { $this->ExecuteJs("window.name = '{$this->aapp_object->phash}'"); }
			try {
				/*
				 * Code
				 */
			} catch(AException $e) {
				echo $e->getFullMessage();
			}//END try
		}//END public function AjaxCall
	}//END class MyARequest extends PAF\ARequest
?>