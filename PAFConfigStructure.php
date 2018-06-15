<?php
/**
 * PAF (PHP AJAX Framework) application configuration structure file
 *
 * Here are all the configuration elements definition for PAF
 *
 * @package    AdeoTEK\PAF
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.2
 * @filesource
 */
if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }

	$_PAF_CONFIG_STRUCTURE = [
	//START Session configuration
		// PHP Session name
		'session_name'=>['type'=>'readonly','default'=>'PHPSESSID','validation'=>'is_notempty_string'],
		// Use session splitting by window.name or not
		'split_session_by_page'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
		// Use asynchronous session read/write
		'async_session'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
		// Session timeout in seconds
		'session_timeout'=>['type'=>'readonly','default'=>3600,'validation'=>'is_not0_integer'],
		// Use redis for session storage
		'session_redis'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
		// Redis server connection string (host_name:port?params)
		'session_redis_server'=>['type'=>'readonly','default'=>'tcp://127.0.0.1:6379?timeout=1&weight=1&database=0','validation'=>'is_notempty_string'],
		// Use redis for session storage
		'session_memcached'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// Memcache server connection string (host_name:port)
		'session_memcached_server'=>['type'=>'readonly','default'=>'localhost:11211','validation'=>'is_notempty_string'],
		// PHP Session file path. If left blank default php setting will be used (absolute or relative path).
		'session_file_path'=>['type'=>'readonly','default'=>'tmp','validation'=>'is_notempty_string'],
		// Verification key for session data
		'session_key'=>['type'=>'readonly','default'=>'1234567890','validation'=>'is_string'],
		// Session array keys case: CASE_LOWER/CASE_UPPER or NULL for no case modification
		'session_keys_case'=>['type'=>'readonly','default'=>CASE_LOWER,'validation'=>'is_integer'],
	//END Session configuration
	//START PAF configuration
		// Relative path to PAF javascript file (linux style)
		'app_js_path'=>['type'=>'readonly','default'=>'/lib/paf','validation'=>'is_string'],
		// Target file for PAF AJAX post (relative path from public folder + name)
		'app_ajax_target'=>['type'=>'readonly','default'=>'aindex.php','validation'=>'is_notempty_string'],
		// PAF session key
		'app_session_key'=>['type'=>'readonly','default'=>'AAPP_DATA','validation'=>'is_notempty_string'],
		// PAF implementing class name
		'ajax_class_name'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		// PAF implementing class file full name (including path)
		'ajax_class_file'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		// Javascript on request completed callback
		'app_areq_js_callbak'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		// Secure http support on/off
		'app_secure_http'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
		// Parameters sent as value encryption on/off
		'app_params_encrypt'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// Mod rewrite support on/off
		'app_mod_rewrite'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
		// Window name auto usage on/off
		'app_use_window_name'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
	//END PAF configuration
	//START Logs & errors reporting
		// Debug mode on/off
		'debug'=>['type'=>'public','default'=>TRUE,'validation'=>'bool'],
		// Database debug mode on/off
		'db_debug'=>['type'=>'public','default'=>FALSE,'validation'=>'bool'],
		// Database debug to file on/off
		'db_debug2file'=>['type'=>'public','default'=>FALSE,'validation'=>'bool'],
		// Show debug invocation source file name and path in browser console on/off
		'console_show_file'=>['type'=>'public','default'=>TRUE,'validation'=>'bool'],
		// Javascript php console password
		'js_console_password'=>['type'=>'readonly','default'=>'12345','validation'=>'is_string'],
		// Relative path to the logs folder
		'logs_path'=>['type'=>'readonly','default'=>'/.logs','validation'=>'is_notempty_string'],
		// Name of the main log file
		'log_file'=>['type'=>'readonly','default'=>'app.log','validation'=>'is_notempty_string'],
		// Name of the errors log file
		'errors_log_file'=>['type'=>'readonly','default'=>'errors.log','validation'=>'is_notempty_string'],
		// Name of the debugging log file
		'debug_log_file'=>['type'=>'readonly','default'=>'debugging.log','validation'=>'is_notempty_string'],
	//END START Logs & errors reporting
	];
?>