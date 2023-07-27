<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hopsuite/helper.php';

class hopsuite_mcp
{
  
	function __construct()
	{
		ee()->load->library('logger');
	}

	function index()
	{
		// No index, redirect to settings directly
		ee()->functions->redirect(ee('CP/URL', 'addons/settings/'.HOPSUITE_NAME.'/settings')->compile());
	}

	function settings()
	{
		$settings = Hopsuite_helper::get_settings();
		
		$vars = array(
			'cp_page_title' => lang('nav_settings'),
			'base_url' => ee('CP/URL', 'addons/settings/'.HOPSUITE_NAME.'/settings')->compile(),
			'save_btn_text' => lang('settings_save'),
			'save_btn_text_working' => lang('settings_save_working'),
		);
		
		// Generate Instagram URL to get an access token
		// https://api.instagram.com/oauth/authorize/?client_id=xxxxxxxxxxxxxxxxxxxxxx&redirect_uri=http%3A%2F%2Fmysite.com&response_type=token&scope=public_content
		$site_url = ee()->config->item('site_url');
		$instagram_url = 'https://api.instagram.com/oauth/authorize/?client_id=CLIENTID&redirect_uri='.urlencode($site_url).'&response_type=token';
		$vars['site_url'] = $site_url;
		$vars['instagram_token_url'] = $instagram_url;
		
		
		// Using EE3 API to create config form
		$vars['sections'] = array(
			array(
				array(
					'title' => 'label_cache_ttl',
					'desc' => 'label_sub_cache_ttl',
					'fields' => array(
						'cache_ttl' => array('type' => 'short-text', 'required' => 'true', 'value' => $settings['cache_ttl'], 'label' => lang('label_cache_ttl_unit'))
					)
				),
				array(
					'title' => 'label_fcbk_app_id',
					'desc' => 'label_sub_fcbk_app_id',
					'fields' => array(
						'facebook_app_id' => array('type' => 'text', 'value' => $settings['facebook_app_id'])
					)
				),
				array(
					'title' => 'label_fcbk_app_secret',
					'desc' => 'label_sub_fcbk_app_secret',
					'fields' => array(
						'facebook_app_secret' => array('type' => 'text', 'value' => $settings['facebook_app_secret'])
					)
				),
				array(
					'title' => 'label_twitter_token',
					'desc' => 'label_sub_twitter_token',
					'fields' => array(
						'twitter_token' => array('type' => 'text', 'value' => $settings['twitter_token'])
					)
				),
				array(
					'title' => 'label_twitter_token_secret',
					'desc' => 'label_sub_twitter_token_secret',
					'fields' => array(
						'twitter_token_secret' => array('type' => 'text', 'value' => $settings['twitter_token_secret'])
					)
				),
				array(
					'title' => 'label_twitter_cons_key',
					'desc' => 'label_sub_twitter_cons_key',
					'fields' => array(
						'twitter_consumer_key' => array('type' => 'text', 'value' => $settings['twitter_consumer_key'])
					)
				),
				array(
					'title' => 'label_twitter_cons_key_secret',
					'desc' => 'label_sub_twitter_cons_key_secret',
					'fields' => array(
						'twitter_consumer_secret' => array('type' => 'text', 'value' => $settings['twitter_consumer_secret'])
					)
				),
				array(
					'title' => 'label_instagram_access_token',
					'desc' => 'label_sub_instagram_access_token',
					'fields' => array(
						'instagram_access_token' => array('type' => 'text', 'value' => $settings['instagram_access_token'])
					)
				),
				array(
					'title' => '',
					'fields' => array(
						'action' => array('type' => 'hidden', 'value' => 'save_settings')
					)
				),
			)
		);
		
		if (ee()->input->post('action') == "save_settings")
		{
			$settings = array();
			$form_is_valid = TRUE;
			
			// Validation
			$validator = ee('Validation')->make();
			
			$validator->setRules(array(
				'cache_ttl' => 'required|isNaturalNoZero',
			));
			$result = $validator->validate($_POST);
			
			if ($result->isValid())
			{
				// Get back all values, store them in array and save them
				$fields = array();
				foreach ($vars['sections'] as $settings)
				{
					foreach ($settings as $setting)
					{
						foreach ($setting['fields'] as $field_name => $field)
						{
							$fields[$field_name] = ee()->input->post($field_name);
						}
					}
				}
				// We don't want to save that field, it's not a setting
				unset($fields['action']);
				
				Hopsuite_helper::save_settings($fields);
				
				ee('CP/Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('preferences_updated'))
						->addToBody(lang('preferences_updated_desc'))
						->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/'.HOPSUITE_NAME.'/settings'));
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_save_error'))
					->addToBody(lang('settings_save_error_desc'))
					->now();
				$vars["settings"] = $settings;
			}

		}

		// return ee()->load->view('settings', $vars, TRUE);
		return array(
			'heading'			=> lang('nav_settings'),
			'body'				=> ee('View')->make(HOPSUITE_NAME.':settings')->render($vars),
			'breadcrumb'	=> array(
			  ee('CP/URL', 'addons/settings/'.HOPSUITE_NAME)->compile() => lang('hopsuite_module_name')
			),
		);
	}
}
