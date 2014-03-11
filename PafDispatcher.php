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
	class XPAF extends PAFReq {
		/**
		 * Generic ajax call
		 *
		 * @param  type $param_name param description
		 * @return void return description
		 * @access public
		 */
		public function AjaxCall($window_name,$param) {
			global $paf;
			if(!strlen($window_name)) {
				$window_name = XSession::GenerateUID();
				$this->js("window.name = '$window_name'");
			}//if(!strlen($window_name))
			try {
				/*
				 * Code
				 */
			} catch(XException $e) {
				ErrorHandler::AddError($e);
			}//END try
			ErrorHandler::ShowErrors();
			$xsession->CommitSession(FALSE,TRUE);
		}//END public function AjaxCall
	}//END class XPAF extends PAFReq
?>