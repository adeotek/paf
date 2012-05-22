<?php
	//error_reporting(E_ALL);
	if(!session_id()) {
		session_start();
	}//if(!session_id())
	//set_time_limit(30);
	//let browser know that response is utf-8 encoded
	header('Content-Type: text/html; charset=utf-8');
	$errors = '';
	if(!is_object($xsession)) {
		require_once ('xsession.php');
		$xsession = xSession::GetInstance();
	}//if(is_null($xsession))
	//initialize vars
	$php = NULL;
	$class_file = NULL;
	$function = NULL;
	$session_id = NULL;
	$request_id = NULL;
	$with_utf8 = TRUE;
	$request = $_POST['req'];
	if(!$request) {
		$errors .= 'Empty Request!';
	}//if(!$request)
	//load the PAF base class
	require_once ('paf.class.php');
	if(!$errors) {
		//start session and set ID to the expected paf session
		list($php,$session_id,$request_id) = explode('^!PAF!^',$request);
		//validate this request
		if(!array_key_exists('PAF_REQUEST',$xsession->data) || !is_array($xsession->data['PAF_REQUEST']['REQUESTS'])) {
			$errors .= 'Invalid Request!';
		} else if(!in_array($request_id,array_keys($xsession->data['PAF_REQUEST']['REQUESTS']))) {
			$errors .= 'Invalid Request!';
		}//if(!array_key_exists('PAF_REQUEST',$xsession->data)||!is_array($xsession->data['PAF_REQUEST']['REQUESTS']))
	}//if(!$errors)
	//start capturing the response
	ob_start();
	if(!$errors) {
		//get function name and process file
		$REQ = $xsession->data['PAF_REQUEST']['REQUESTS'][$request_id];
		$class_file = $REQ['CLASS_FILE'];
		$function = $REQ['FUNCTION'];
		$with_history = array_key_exists('HISTORY',$REQ) ? $REQ['HISTORY'] : NULL;
		$with_utf8 = $REQ['UTF8'];
		//add this request to the history
		if($with_history) {
			$xsession->data['PAF_REQUEST']['HISTORY'][] = $xsession->data['PAF_REQUEST']['REQUESTS'][$request_id]['HISTORY'];
		}//if($with_history)
		//load the class extension containing the user functions
		if(file_exists($class_file)) {
			require_once ($class_file);
		} else {
			echo 'Class file: '.$class_file.' not found!';
		}//if(file_exists($class_file))
		//execute the requested function
		$s = new myPAF();
		$s->set_utf8($with_utf8);
		$s->runFunc($function,$php);
		if($s->hasActions()) {
			echo $s->send();
		}//if($s->hasActions())
		//capture the response and output as utf-8 encoded
		$content = ob_get_contents();
		ob_end_clean();
		if($s->with_utf8) {
			echo $content;
		} else {
			echo utf8_encode($content);
		}//if($s->with_utf8)
	} else {
		echo $errors;
	}//if(!$errors)
?>