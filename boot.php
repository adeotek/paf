<?php
	if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }
	require_once(__DIR__.'/helpers.php');
	try {
		PAF\AppConfig::LoadConfig((isset($_APP_CONFIG) && is_array($_APP_CONFIG) ? $_APP_CONFIG : []),(isset($_CUSTOM_CONFIG_STRUCTURE) && is_array($_CUSTOM_CONFIG_STRUCTURE) ? $_CUSTOM_CONFIG_STRUCTURE : []));
	} catch(Exception $e) {
		die($e->getMessage());
	}//END try
?>