<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of Escort add-on for ExpressionEngine.

    Escort is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Escort is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2013-2019 Derek Hogue - http://amphibian.info
*/

include(PATH_THIRD.'/escort/config.php');

class Escort_ext {


	var $config;
	var $debug = false;
	var $email_crlf = '\n';
	var $email_in = array();
	var $email_out = array();
	var $model;
	var $protocol;
	var $settings = array();
	var $site_id;
	var $services = array();
	var $version = ESCORT_VERSION;
	

	function __construct($settings = '')
	{
        $this->config = ee()->config->item('escort_settings');
		if(ee()->config->item('email_crlf') != false)
		{
			$this->email_crlf = ee()->config->item('email_crlf');
		}
		$this->model = ee('Model')->get('Extension')
			->filter('class', ucfirst(get_class($this)))
			->first();
		$this->protocol = ee()->config->item('mail_protocol');
		$this->services = array(
			'mailgun' => array(
				'mailgun_api_key',
				'mailgun_domain',
				'mailgun_region'
			),
			'mandrill' => array(
				'mandrill_api_key',
				'mandrill_subaccount'
			),
			'postageapp' => array(
				'postageapp_api_key'
			),			
			'postmark' => array(
				'postmark_api_key'
			),
			'sendgrid' => array(
				'sendgrid_api_key',
			),
			'sparkpost' => array(
				'sparkpost_api_key',
			)
		);
		$this->settings = $settings;
		$this->site_id = ee()->config->item('site_id');
	}
	

	function settings_form($all_settings)
	{	    		
		$settings = $this->get_settings();
		$services_sorted = array();
		
		if(ee('Request')->isAjax() && $services = ee('Request')->post('service_order'))
		{
			$all_settings[$this->site_id]['service_order'] = explode(',', $services);
			$this->model->settings = $all_settings;
			$this->model->save();
			exit();
		}
		
		// Look at custom service order
		foreach($settings['service_order'] as $service)
		{
			$services_sorted[$service] = $this->services[$service];
		}
		
		// Add any services were not included in the custom order
		foreach($this->services as $service => $service_settings)
		{
			if(empty($services_sorted[$service]))
			{
				$services_sorted[$service] = $service_settings;
			}
		}
		
		$vars = array(
			'current_service' => false,
			'current_settings' => $settings,
			'services' => $services_sorted,
			'ee_version' => $this->ee_version()
		);
		
		if($current_service = ee()->uri->segment(5))
		{
			$vars['current_service'] = $current_service;
			
			$sections = array(
				array(
					'title' => lang('escort_description'),
					'fields' => array(
						'description' => array(
							'type' => 'html',
							'content' => '<div class="escort-service-description">'.sprintf(lang('escort_'.$current_service.'_description'), ee()->cp->masked_url(lang('escort_'.$current_service.'_link'))).'</div>'
						)
					)
				),
				array(
					'title' => lang('escort_status'),
					'fields' => array(
						$current_service.'_active' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => lang('escort_enabled'),
								'n' => lang('escort_disabled')
							),
							'value' => (!empty($settings[$current_service.'_active']) && $settings[$current_service.'_active'] == 'y') ? 'y' : 'n'
						)
					)
				)
			);
			
			foreach($vars['services'][$current_service] as $key => $field_name)
			{
				$sections[] = array(
					'title' => lang('escort_'.$field_name),
					'desc' => ($field_name == 'mandrill_subaccount') ? lang('escort_optional') : '',
					'fields' => array(
						$field_name => array(
							'type' => ($field_name == 'mailgun_region') ? 'radio' : 'text',
							'value' => (!empty($settings[$field_name])) ? $settings[$field_name] : '',
							'choices' => ($field_name == 'mailgun_region') ? array('US' => 'US','EU' => 'EU') : array(),
						)
					)
				);
			}
			
			$vars['form_vars'] = array(
				'base_url' => ee('CP/URL','addons/settings/escort/save'),
				'cp_page_title' => lang('escort_'.$current_service.'_name'),
				'save_btn_text' => 'btn_save_settings',
				'save_btn_text_working' => 'btn_saving',
				'sections' => array($sections)
			);
		}

		if(!empty($this->config))
		{
			$vars['form_vars']['extra_alerts'] = array('escort_config_warning');
			ee('CP/Alert')->makeInline('escort_config_warning')
				->asWarning()
				->withTitle(lang('escort_config_warning_heading'))
				->addToBody(lang('escort_config_warning_text'))
				->cannotClose()
				->now();
		}
				
		return ee('View')->make('escort:settings')->render($vars);
	}
	
	
	function save_settings()
	{
		$settings = $this->get_settings(true);
		$current_service = '';

		foreach($this->services as $service => $service_settings)
		{
			if($v = ee('Request')->post($service.'_active'))
			{
				$current_service = $service;
				$settings[$this->site_id][$service.'_active'] = $v;
			
				foreach($service_settings as $setting)
				{
					$settings[$this->site_id][$setting] = ee('Request')->post($setting);
				}
			}
		}

		$this->model->settings = $settings;
		$this->model->save();
		
		ee('CP/Alert')->makeInline('shared-form')
	      ->asSuccess()
		  ->withTitle(lang('settings_saved'))
		  ->addToBody(sprintf(lang('settings_saved_desc'), 'Escort'))
	      ->defer();
	      
	    ee()->functions->redirect(
	    	ee('CP/URL')->make('addons/settings/escort/'.$current_service)
	    );
	}

	
	function get_settings($all_sites = false)
	{
		$all_settings = $this->model->settings;
		$settings = ($all_sites == true || empty($all_settings)) ? $all_settings : $all_settings[$this->site_id];
                
        // Check for config settings - they will override database settings
		if($all_sites == false)
	    {
	        // Set a service order if none is set
	        if(empty($settings['service_order']) && empty($this->config[$this->site_id]['service_order']))
	        {
		        $settings['service_order'] = array();
		        foreach($this->services as $service => $service_settings)
		        {
			        $settings['service_order'][] = $service;
		        }
			}
	        
	        // Override each setting from config
	        if(!empty($this->config[$this->site_id]))
	        {
		        foreach($this->config[$this->site_id] as $k => $v)
		        {
			        $settings[$k] = $v;
		        }
			}
		}
        return $settings;
	}


	function email_send($data)
	{	
		$settings = $this->get_settings();
		if(empty($settings['service_order']))
		{
			return false;
		}
	
		ee()->lang->loadfile('escort');
		ee()->load->library('logger');
		
		$sent = false;
		$this->email_in = $data;
		unset($data);
		
		// Grats to Justin Kimbrell for the alert and code - remove {unwrap} tags
		$this->email_in['finalbody'] = str_replace(array(LD.'unwrap'.RD,LD.'/unwrap'.RD), '', $this->email_in['finalbody']);
		
		if($this->debug == true)
		{
			$this->_debug($this->email_in, false);
		}
		
		// Set X-Mailer
		$this->email_out['headers']['X-Mailer'] = $this->email_in['headers']['X-Mailer'].' (via Escort '.$this->version.')';
		
		// From (may include a name)
		$this->email_out['from'] = $this->_name_and_email($this->email_in['headers']['From']);	  
		
		// Reply-To (may include a name)
		if(!empty($this->email_in['headers']['Reply-To']))
		{
			$this->email_out['reply-to'] = $this->_name_and_email($this->email_in['headers']['Reply-To']);
		}
		
		// To (email-only)
		$this->email_out['to'] = (is_array($this->email_in['recipients'])) ? $this->email_in['recipients'] : $this->_recipient_array($this->email_in['recipients']);
		
		// Cc (email-only)
		if(!empty($this->email_in['cc_array']))
		{
			$this->email_out['cc'] = array();
			foreach($this->email_in['cc_array'] as $cc_email)
			{
				if(!empty($cc_email))
				{
					$this->email_out['cc'][] = $cc_email;
				}
			}
		}
		elseif(!empty($this->email_in['headers']['Cc']))
		{
			$this->email_out['cc'] = $this->_recipient_array($this->email_in['headers']['Cc']);
		}

		// Bcc (email-only)
		if(!empty($this->email_in['bcc_array']))
		{
			$this->email_out['bcc'] = array();
			foreach($this->email_in['bcc_array'] as $bcc_email)
			{
				if(!empty($bcc_email))
				{
					$this->email_out['bcc'][] = $bcc_email;
				}
			}
		}
		elseif(!empty($this->email_in['headers']['Bcc']))
		{
			$this->email_out['bcc'] = $this->_recipient_array($this->email_in['headers']['Bcc']);
		}
		
		// Subject	
		$subject = '';
		if(!empty($this->email_in['subject']))
		{
			$subject = $this->email_in['subject'];
		}
		elseif(!empty($this->email_in['headers']['Subject']))
		{
			$subject = $this->email_in['headers']['Subject'];
		}
		$this->email_out['subject'] = (strpos($subject, '?Q?') !== false) ? $this->_decode_q($subject) : $subject;
		
		
		// Set HTML/Text and attachments
		$this->_body_and_attachments();
		
		if($this->debug == true)
		{
			$this->_debug($this->email_out);
		}
			
		foreach($settings['service_order'] as $service)
		{
			if(!empty($settings[$service.'_active']) && $settings[$service.'_active'] == 'y')
			{
				$missing_credentials = true;
				switch($service)
				{
					case 'mailgun':
						if(!empty($settings['mailgun_api_key']) && !empty($settings['mailgun_domain']))
						{
							$sent = $this->_send_mailgun($settings['mailgun_api_key'], $settings['mailgun_domain'], @$settings['mailgun_region']);
							$missing_credentials = false;
						}
						break;				
					case 'mandrill':
						if(!empty($settings['mandrill_api_key']))
						{
							$subaccount = (!empty($settings['mandrill_subaccount']) ? $settings['mandrill_subaccount'] : '');
							$sent = $this->_send_mandrill($settings['mandrill_api_key'], $subaccount);
							$missing_credentials = false;
						}
						break;
					case 'postageapp':
						if(!empty($settings['postageapp_api_key']))
						{
							$sent = $this->_send_postageapp($settings['postageapp_api_key']);
							$missing_credentials = false;
						}						
						break;	
					case 'postmark':
						if(!empty($settings['postmark_api_key']))
						{
							$sent = $this->_send_postmark($settings['postmark_api_key']);
							$missing_credentials = false;
						}						
						break;				
					case 'sendgrid':
						if(!empty($settings['sendgrid_api_key']))
						{
							$sent = $this->_send_sendgrid($settings['sendgrid_api_key']);
							$missing_credentials = false;
						}
						break;
					case 'sparkpost':
						if(!empty($settings['sparkpost_api_key']))
						{
							$sent = $this->_send_sparkpost($settings['sparkpost_api_key']);
							$missing_credentials = false;
						}
						break;
				}
				
				if($missing_credentials == true)
				{
					ee()->logger->developer(sprintf(lang('escort_missing_service_credentials'), $service));
				}
				elseif($sent == false)
				{
					ee()->logger->developer(sprintf(lang('escort_could_not_deliver'), $service));
				}
			}
			
			if($sent == true)
			{
				ee()->extensions->end_script = true;
				return true;
			}		
		}
		
		return false;
				  
	}
	
	
	/**
		Sending methods for each of our services follow.
	**/

	function _send_mandrill($api_key, $subaccount)
	{
		$content = array(
			'key' => $api_key,
			'message' => $this->email_out
		);
		
		if(!empty($subaccount))
		{
			$content['message']['subaccount'] = $subaccount;
		}
		
		$content['message']['from_email'] = $content['message']['from']['email'];
		if(!empty($content['message']['from']['name']))
		{
			$content['message']['from_name'] = $content['message']['from']['name'];
		}
		unset($content['message']['from']);
		
		$mandrill_to = array();
		
		foreach($content['message']['to'] as $to)
		{
			$mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'to'));
		}
		
		if(!empty($content['message']['cc']))
		{
			foreach($content['message']['cc'] as $to)
			{
				$mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'cc'));
			}
			unset($content['message']['cc']);
		}
				
		if(!empty($content['message']['reply-to']))
		{
			$content['message']['headers']['Reply-To'] = $this->_recipient_str($content['message']['reply-to'], true);
		}
		unset($content['message']['reply-to']);

		
		if(!empty($content['message']['bcc']))
		{
			foreach($content['message']['bcc'] as $to)
			{
				$mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'bcc'));
			}
		}
		unset($content['message']['bcc']);
		
		$content['message']['to'] = $mandrill_to;
						
		$headers = array(
	    	'Accept: application/json',
			'Content-Type: application/json',
		);
		
		if(ee()->extensions->active_hook('escort_pre_send'))
		{
			$content = ee()->extensions->call('escort_pre_send', 'mandrill', $content);
		}
		
		// Did someone set a template? Then we need a different API method.
		$method = (!empty($content['template_name']) && !empty($content['template_content'])) ? 'send-template' : 'send';
		$content = json_encode($content);
				
		return $this->_curl_request('https://mandrillapp.com/api/1.0/messages/'.$method.'.json', $headers, $content);
	}
	
	
	function _send_mailgun($api_key, $domain, $region = 'US')
	{
		$headers = array();
		$email = $this->email_out;
		$email['from'] = $this->_recipient_str($email['from'], true);
	    
	    foreach($email['headers'] as $header => $value)
	    {
		    $email['h:'.$header] = $value;    
	    }
	    unset($email['headers']);
	    
	    if(!empty($email['reply-to']))
	    {
	    	$email['h:Reply-To'] = $this->_recipient_str($email['reply-to'], true);
	    	unset($email['reply-to']);
	    }
	    	    
	    if(!empty($email['attachments']))
	    {
	    	$attachments = $this->_write_attachments();
	    	unset($email['attachments']);
	    	$i = 1;	    	
	    	foreach($attachments as $name => $path)
	    	{
	    		$email['attachment'][$i] = '@'.$path;
	    		$i++;
	    	}
	    }
	    
	    if(ee()->extensions->active_hook('escort_pre_send'))
		{
			$email = ee()->extensions->call('escort_pre_send', 'mailgun', $email);
		}
	    
	    $post = array();
	    $this->_http_build_post($email, $post);
	    
	    $region = ($region == 'EU') ? 'eu.' : '';
	    
	    return $this->_curl_request("https://api.".$region."mailgun.net/v3/$domain/messages", $headers, $post, "api:$api_key");
	}


	function _send_postageapp($api_key)
	{
		$content = array(
			'api_key' => $api_key,
			'uid' => sha1(serialize($this->email_out['to']).$this->email_out['subject'].ee()->localize->now),
			'arguments' => array(
				'headers' => array(
					'from' => $this->_recipient_str($this->email_out['from'], true),
					'subject' => $this->email_out['subject'],
				)
			)
		);
		
		foreach($this->email_out['headers'] as $header => $value)
	    {
		    $content['arguments']['headers'][$header] = $value;    
	    }
		
		/*
			All recipients, including Cc and Bcc, must be in the recipients array, and will be Bcc by default.
			Any addresses which are *also* included in the Cc header will be visible as Cc
		*/
		$recipients = $this->email_out['to'];
		if(!empty($this->email_out['cc']))
	    {
	    	$recipients = array_merge($recipients,  $this->email_out['cc']);
	    	$content['arguments']['headers']['cc'] = $this->_recipient_str($this->email_out['cc']);
	    }
	    if(!empty($this->email_out['bcc']))
	    {
	    	$recipients = array_merge($recipients,  $this->email_out['bcc']);
	    }
	    $content['arguments']['recipients'] = $recipients;
		
	    if(!empty($this->email_out['reply-to']))
	    {
	    	$content['arguments']['headers']['reply-to'] = $this->_recipient_str($this->email_out['reply-to'], true);
	    }
	    if(!empty($this->email_out['html']))
	    {
	    	$content['arguments']['content']['text/html'] = $this->email_out['html'];
	    }
	    if(!empty($this->email_out['text']))
	    {
	    	$content['arguments']['content']['text/plain'] = $this->email_out['text'];
	    }
	    if(!empty($this->email_out['attachments']))
	    {
	    	foreach($this->email_out['attachments'] as $attachment)
	    	{
	    		$content['arguments']['attachments'][$attachment['name']] = array(
	    			'content_type' => $attachment['type'],
	    			'content' => $attachment['content']
	    		);
	    	}
	    }
	    
	    $headers = array(
	    	'Accept: application/json',
			'Content-Type: application/json'
		);
		
		if(ee()->extensions->active_hook('escort_pre_send'))
		{
			$content = ee()->extensions->call('escort_pre_send', 'postageapp', $content);
		}
		$content = json_encode($content);
		
		return $this->_curl_request('https://api.postageapp.com/v.1.0/send_message.json', $headers, $content);
	}
	
	
	function _send_postmark($api_key)
	{	
	   	$email = array(
	    	'From' => $this->_recipient_str($this->email_out['from'], true),
	    	'To' => $this->_recipient_str($this->email_out['to']),
	    	'Subject' => $this->email_out['subject'],
	    	'Headers' => array($this->email_out['headers'])
	    );
	    if(!empty($this->email_out['reply-to']))
	    {
	    	$email['ReplyTo'] = $this->_recipient_str($this->email_out['reply-to'], true);
	    }
	    if(!empty($this->email_out['cc']))
	    {
	    	$email['Cc'] = $this->_recipient_str($this->email_out['cc']);
	    }
	    if(!empty($this->email_out['bcc']))
	    {
	    	$email['Bcc'] = $this->_recipient_str($this->email_out['bcc']);
	    }
	    if(!empty($this->email_out['html']))
	    {
	    	$email['HtmlBody'] = $this->email_out['html'];
	    }
	    if(!empty($this->email_out['text']))
	    {
	    	$email['TextBody'] = $this->email_out['text'];
	    }
	    if(!empty($this->email_out['attachments']))
	    {
	    	foreach($this->email_out['attachments'] as $attachment)
	    	{
	    		$email['Attachments'][] = array(
	    			'Name' => $attachment['name'],
	    			'ContentType' => $attachment['type'],
	    			'Content' => $attachment['content']
	    		);
	    	}
	    }

		$headers = array(
	    	'Accept: application/json',
			'Content-Type: application/json',
			'X-Postmark-Server-Token: '.$api_key
		);
		
		if(ee()->extensions->active_hook('escort_pre_send'))
		{
			$email = ee()->extensions->call('escort_pre_send', 'postmark', $email);
		}	
		$email = json_encode($email);
		
		return $this->_curl_request('http://api.postmarkapp.com/email', $headers, $email);
	}	
	
	
	function _send_sendgrid($api_key)
	{
		$email = $this->email_out;
		$email['headers'] = json_encode($email['headers']);
		
		if(!empty($email['from']['name']))
		{
			$email['fromname'] = $email['from']['name'];		
		}
		$email['from'] = $email['from']['email'];
	    
	    if(!empty($email['reply-to']))
	    {
	    	$email['replyto'] = $email['reply-to']['email'];
	    	unset($email['reply-to']);
	    }
	    
	    // SendGrid does not support CC
	    if(!empty($email['cc']))
	    {
	    	$email['to'] = array_merge($email['cc'], $email['to']);
	    }
	    	    
	    if(!empty($email['attachments']))
	    {
	    	$attachments = $this->_write_attachments();
	    	unset($email['attachments']);	    	
	    	foreach($attachments as $name => $path)
	    	{
	    		$email['files'][$name] = '@'.$path;
	    	}
	    }
	    
	    if(ee()->extensions->active_hook('escort_pre_send'))
		{
			$email = ee()->extensions->call('escort_pre_send', 'sendgrid', $email);
		}
	    
	    $post = array();
	    $this->_http_build_post($email, $post);

		return $this->_curl_request('https://api.sendgrid.com/api/mail.send.json', array('Authorization: Bearer ' . $api_key), $post);		
	}
	
	
	function _send_sparkpost($api_key)
	{
		$content = $this->email_out;
		$recipients = array();
		
		$headers = array(
			'Content-Type: application/json',
			'Authorization: '.$api_key
		);
		
		foreach($content['to'] as $to)
		{
			$recipients[] = array('address' => array('email' => $to));
		}
		unset($content['to']);
		
		/*
			Cc and Bcc are handled strangely
			See https://support.sparkpost.com/customer/portal/articles/1948014-how-to-add-cc-and-bcc-to-emails	
		*/
		if(!empty($content['cc']))
	    {
		    $cc_headers = array();
	    	foreach($content['cc'] as $cc)
	    	{
		    	$recipients[] = array('address' => array(
		    		'email' => $cc,
		    		'header_to' => $recipients[0]['address']['email']
		    	));
		    	$cc_headers[] = $cc;
	    	}
	    	$content['headers']['CC'] = implode(', ', $cc_headers);
	    	unset($content['cc']);
	    }

		if(!empty($content['bcc']))
	    {
	    	foreach($content['bcc'] as $bcc)
	    	{
		    	$recipients[] = array('address' => array(
		    		'email' => $bcc,
		    		'header_to' => $recipients[0]['address']['email']
		    	));
	    	}
	    	unset($content['bcc']);
	    }
		
		if(!empty($content['reply-to']))
	    {
	    	$content['reply_to'] = $content['reply-to']['email'];
	    	unset($content['reply-to']);
	    }

	    if(!empty($content['attachments']))
	    {    	
	    	foreach($content['attachments'] as $k => $attachment)
	    	{
	    		$content['attachments'][$k]['data'] = $content['attachments'][$k]['content'];
	    		unset($content['attachments'][$k]['content']);
	    	}
	    }
	    
	    $email = array(
			'recipients' => $recipients,
			'content' => $content
		);
			    
	    if(ee()->extensions->active_hook('escort_pre_send'))
		{
			$email = ee()->extensions->call('escort_pre_send', 'sparkpost', $email);
		}
		
		$email = json_encode($email);

		return $this->_curl_request('https://api.sparkpost.com/api/v1/transmissions', $headers, $email);
	}	

	
	/**
		Ultimately sends the email to each server.
	**/	
	function _curl_request($server, $headers = array(), $content, $htpw = null)
	{	
		$ch = curl_init($server);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, 1);
	    // Convert @ fields to CURLFile if available
	    if(is_array($content) && class_exists('CURLFile'))
	    {
		    foreach($content as $key => $value)
		    {
		        if(strpos($value, '@') === 0)
		        {
		            $filename = ltrim($value, '@');
		            $content[$key] = new CURLFile($filename);
		        }
		    }
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		if(!empty($headers))
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if(!empty($htpw))
		{
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $htpw);
		}
		$status = curl_exec($ch);
		// echo $status; exit();
		$curl_error = curl_error($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		return ($http_code != 200) ? false : true;
	}	
	

	/**
		Remove the Q encoding from our subject line
	**/
	function _decode_q($subject)
	{
	    $r = '';
	    $lines = preg_split('/['.$this->email_crlf.']+/', $subject); // split multi-line subjects
		foreach($lines as $line)
	    { 
	        $str = '';
	        // $line = str_replace('=9', '', $line); // Replace encoded tabs which ratch the decoding
	        $parts = imap_mime_header_decode(trim($line)); // split and decode by charset
	        foreach($parts as $part)
	        {
	            $str .= $part->text; // append sub-parts of line together
	        }
	        $r .= $str; // append to whole subject
	    }
	    
	    return $r;
	    // return utf8_encode($r);
	}
	
	
	/**
		Breaks the PITA MIME message we receive into its constituent parts
	**/
	function _body_and_attachments()
	{
		if($this->protocol == 'mail')
		{
			// The 'mail' protocol sets Content-Type in the headers
			if(strpos($this->email_in['header_str'], "Content-Type: text/plain") !== false)
			{	
				$this->email_out['text'] = $this->email_in['finalbody'];
			}
			elseif(strpos($this->email_in['header_str'], "Content-Type: text/html") !== false)
			{
				$this->email_out['html'] = $this->email_in['finalbody'];
			}
			else
			{
				preg_match('/Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $this->email_in['header_str'], $matches);
			}
		}	
		else
		{
			// SMTP and sendmail will set Content-Type in the body
			if(stripos($this->email_in['finalbody'], "Content-Type: text/plain") === 0)
			{	
				$this->email_out['text'] = $this->_clean_chunk($this->email_in['finalbody']);
			}
			elseif(stripos($this->email_in['finalbody'], "Content-Type: text/html") === 0)
			{
				$this->email_out['html'] = $this->_clean_chunk($this->email_in['finalbody']);
			}
			else
			{
				preg_match('/^Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $this->email_in['finalbody'], $matches);
			}
		}	
		
		// Extract content and attachments from multipart messages
		if(!empty($matches) && !empty($matches[1]))
		{
			$boundary = $matches[1];
			$chunks = explode('--' . $boundary, $this->email_in['finalbody']);
			foreach($chunks as $chunk)
			{
				if(stristr($chunk, "Content-Type: text/plain") !== false)
				{
					$this->email_out['text'] = $this->_clean_chunk($chunk);
				}
				
				if(stristr($chunk, "Content-Type: text/html") !== false)
				{
					$this->email_out['html'] = $this->_clean_chunk($chunk);
				}
				
				// Attachments
				if(stristr($chunk, "Content-Disposition: attachment") !== false)
				{
					preg_match('/Content-Type: (.*?); name=["|\'](.*?)["|\']/is', $chunk, $attachment_matches);
					if(!empty($attachment_matches))
					{
						if(!empty($attachment_matches[1]))
						{
							$type = $attachment_matches[1];
						}
						if(!empty($attachment_matches[2]))
						{
							$name = $attachment_matches[2];
						}
						$attachment = array(
							'type' => trim($type),
							'name' => trim($name),
							'content' => $this->_clean_chunk($chunk)
						);
						$this->email_out['attachments'][] = $attachment;
					}
				}
				
				if(stristr($chunk, "Content-Type: multipart") !== false)
				{
					// Another multipart chunk - contains the HTML and Text messages, here because we also have attachments
					preg_match('/Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $chunk, $inner_matches);
					if(!empty($inner_matches) && !empty($inner_matches[1]))
					{
						$inner_boundary = $inner_matches[1];
						$inner_chunks = explode('--' . $inner_boundary, $chunk);
						foreach($inner_chunks as $inner_chunk)
						{
							if(stristr($inner_chunk, "Content-Type: text/plain") !== false)
							{
								$this->email_out['text'] = $this->_clean_chunk($inner_chunk);
							}
							
							if(stristr($inner_chunk, "Content-Type: text/html") !== false)
							{
								$this->email_out['html'] = $this->_clean_chunk($inner_chunk);
							}
						}
					}
				}
			}
		}
		
		if(!empty($this->email_out['html']))
		{
			// HTML emails will have been run through quoted_printable_encode
			$this->email_out['html'] = quoted_printable_decode($this->email_out['html']);
		}
	}
	

	/**
		Explodes a string which contains either a name and email address or just an email address into an array
	**/
	function _name_and_email($str)
	{
		$r = array(
			'name' => '',
			'email' => ''
		);
		
		$str = str_replace('"', '', $str);
		if(preg_match('/<([^>]+)>/', $str, $email_matches))
		{
			$r['email'] = trim($email_matches[1]);
			$str = trim(preg_replace('/<([^>]+)>/', '', $str));
			if(!empty($str) && $str != $r['email'])
			{
				$r['name'] = utf8_encode($str);
			}
		}
		else
		{
			$r['email'] = trim($str);
		}
		return $r;
	}
	
	/**
		Explodes a comma-delimited string of email addresses into an array
	**/	
	function _recipient_array($recipient_str)
	{
		$recipients = explode(',', $recipient_str);
		$r = array();
		foreach($recipients as $recipient)
		{
			$r[] = trim($recipient);
		}
		return $r;
	}
	
	/**
		Implodes an array of email addresses and names into a comma-delimited string
	**/		
	function _recipient_str($recipient_array, $singular = false)
	{
		if($singular == true)
		{
			if(empty($recipient_array['name']))
			{
				return $recipient_array['email'];
			}
			else
			{
				return $recipient_array['name'].' <'.$recipient_array['email'].'>';
			}
		}
		$r = array();
		foreach($recipient_array as $k => $recipient)
		{
			if(!is_array($recipient))
			{
				$r[] = $recipient;
			}
			else
			{
				if(empty($recipient['name']))
				{
					$r[] = $recipient['email'];
				}
				else
				{
					$r[] = $recipient['name'].' <'.$recipient['email'].'>';
				}
			}
		}
		return implode(',', $r);
	}
	
	/**
		Removes cruft from a multipart message chunk
	**/		
	function _clean_chunk($chunk)
	{
		return trim(preg_replace("/Content-(Type|ID|Disposition|Transfer-Encoding):.*?".NL."/is", "", $chunk));
	}
	
	
	/**
		Writes our array of base64-encoded attachments into actual files in the tmp directory
	**/		
	function _write_attachments()
	{
		$r = array();
		ee()->load->helper('file');
    	foreach($this->email_out['attachments'] as $attachment)
    	{
    		if(write_file(realpath(sys_get_temp_dir()).'/'.$attachment['name'], base64_decode($attachment['content'])))
    		{
    			$r[$attachment['name']] = realpath(sys_get_temp_dir()).'/'.$attachment['name'];
    		}
    	}
    	return $r;
	}
	
	/**
		Translates a multi-dimensional array into the odd kind of array expected by cURL post
	**/		
	function _http_build_post($arrays, &$new = array(), $prefix = null)
	{	
	    foreach($arrays as $key => $value)
	    {
		    $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
	        if(is_array($value))
	        {
	            $this->_http_build_post($value, $new, $k);
	        }
	        else
	        {
	            $new[$k] = $value;
	        }
	    }
	}
	
	
	function _debug($value, $exit = true)
	{
		echo '<html><meta charset="UTF-8"><body><pre><code>';
		if(is_array($value))
		{
			print_r($value);
		}
		else
		{
			echo($value);
		}
		echo '</code></pre></body></html>';
		if($exit) exit();
	}
	
	
	function ee_version()
	{
		return substr(APP_VER, 0, 1);
	}
	

	function activate_extension()
	{
		$this->settings = array();
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'email_send',
			'hook'		=> 'email_send',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		ee()->db->insert('extensions', $data);			
		
	}	
	

	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}


	function update_extension($version = '')
	{
		if(version_compare($version, $this->version) === 0)
		{
			return FALSE;
		}
		return TRUE;		
	}	
	

}