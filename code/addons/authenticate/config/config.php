<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*-------------------------------------------    
	Remove the ugly redirect screen
-------------------------------------------*/

if(!defined('AUTHENTICATE_VERSION'))
{
	define('AUTHENTICATE_VERSION', '3.0.0');
}

$config['authenticate_version']   = AUTHENTICATE_VERSION;
$config['remove_redirect_screen'] = TRUE;