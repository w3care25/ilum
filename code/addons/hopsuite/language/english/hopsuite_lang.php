<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(

	//Required for MODULES page

	'hopsuite_module_name'			=> 'Hopsuite',
	'hopsuite_module_description'	=> 'Display and merge into one timeline your posts from Twitter and Facebook',
	
	'nav_settings'							=> 'Settings',

	'label_cache_ttl'						=> 'Cache Life',
	'label_sub_cache_ttl'					=> 'Time in minutes before refreshing data. Be careful, loading data from social networks can be long.',
	'label_cache_ttl_unit'					=> 'Minutes',
	'label_fcbk_app_token'					=> 'Facebook App Token',
	'label_sub_fcbk_app_token'				=> 'The Facebook app token in order to access Facebook posts.',
	'label_fcbk_app_id'						=> 'Facebook App Id',
	'label_sub_fcbk_app_id'					=> 'The Facebook App ID you\'ll find in your app settings.',
	'label_fcbk_app_secret'					=> 'Facebook App Secret',
	'label_sub_fcbk_app_secret'				=> 'The Facebook App Secret you\'ll find in your app settings',
	'label_twitter_token'					=> 'Twitter Token',
	'label_sub_twitter_token'				=> 'Twitter token found on your developer app page.',
	'label_twitter_token_secret'			=> 'Twitter Token Secret',
	'label_sub_twitter_token_secret'		=> 'Twitter token secret found on your developer app page.',
	'label_twitter_cons_key'				=> 'Twitter Consumer Key',
	'label_sub_twitter_cons_key'			=> 'Twitter consumer key found on your developer app page',
	'label_twitter_cons_key_secret'			=> 'Twitter Consumer Key Secret',
	'label_sub_twitter_cons_key_secret'		=> 'Twitter consumer key secret found on your developer app page',
	'label_instagram_access_token'			=> 'Instagram Client Token',
	'label_sub_instagram_access_token'		=> 'See further down the page for more instructions',

	'settings_save'							=> 'Save',
	'settings_save_working'					=> 'Saving...',
	'settings_saved_success'				=> 'Settings have been saved successfully',
	'settings_form_error_cache'				=> 'Cache must be an integer greater than 0',
	//END
);
