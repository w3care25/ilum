<?php

$lang = array(

	'site_key' =>
	'Site Key<div style="font-weight:normal;"><a href="http://www.google.com/recaptcha/admin" target="_blank">receive Google API key pair</a></div>',
	
	'secret' =>
	'Secret key',

	'lang' =>
	'Language',
	
	'debug' =>
	'Show response code on validation failure (debug)',

	'recaptcha_error' =>
	'Captcha error',

	// Google error code reference
	'missing-input-secret'   => 'The secret parameter is missing',
	'invalid-input-secret'   => 'The secret parameter is invalid or malformed',
	'missing-input-response' => 'The response parameter is missing',
	'invalid-input-response' => 'The response parameter is invalid or malformed',
	
	// Unknown response or valid to connect to Google service
	"unknown-response"       => "We could not validate your response, please try again",

	"inc_type"    => "Auto append reCAPTCHAv2 script",
	"end_of_head" => "Add before closing tag </head>",
	"end_of_body" => "Add before closing tag </body>",
	"no_inc" => "Do not add script (manual variant)",

	''=>''
);
