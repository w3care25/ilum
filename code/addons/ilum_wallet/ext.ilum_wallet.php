<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
require_once PATH_THIRD.'/ilum_wallet/libraries/stripe/init.php';
 
class Ilum_wallet_ext {
    
    var $settings;
    var $version = '1.1.0';
			
	public function __construct($settings='')
	{
	   	$this->EE = get_instance();
	   	
	   	$query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Ilum_wallet'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
		
		$this->EE->load->library('logger');
		
		$stripe_secret_key = $this->settings['wallet_stripe_secret_live_key'];
		if ($this->settings['wallet_stripe_test_mode'] == 'y') {
		    $stripe_secret_key = $this->settings['wallet_stripe_secret_test_key'];
		}
		\Stripe\Stripe::setApiKey($stripe_secret_key);
	}
	
	function user_register_end($obj, $data, $member_id) {
	    $this->autoset_timezone();
	    
	    ee()->db->insert(
            'ilum_wallet_balance',
            array(
                'member_id'     => $member_id,
                'balance'       => 0.00,
                'balance_ilum'  => 0.00
            )
        );
            
	    $stripe_customer_id = $this->get_stripe_customer_id($member_id);
	    
	    //Birthday
	    $birthday = ee()->input->post('birthday');
	    $birthdate = explode('/', $birthday);
	    if (isset($birthdate[0])) {
	        $this->update_member_field($member_id, $this->settings['wallet_birth_month_field'], $birthdate[0]);
	    }
	    if (isset($birthdate[1])) {
	        $this->update_member_field($member_id, $this->settings['wallet_birth_day_field'], $birthdate[1]);
	    }
	    if (isset($birthdate[2])) {
	        $this->update_member_field($member_id, $this->settings['wallet_birth_year_field'], $birthdate[2]);
	    }
	    
        return;
	}
	
	function user_edit_end($member_id, $data, $cfields) {
	    $stripe_customer_id = $this->get_stripe_customer_id($member_id);
	    
	    $query = ee()->db->select('m.screen_name AS screen_name, m.email AS email, m.unique_id AS unique_id, ma.m_field_id_'.$this->settings['wallet_address_field'].' AS address, ma2.m_field_id_'.$this->settings['wallet_address2_field'].' AS address2, mc.m_field_id_'.$this->settings['wallet_city_field'].' AS city, ms.m_field_id_'.$this->settings['wallet_state_field'].' AS state, mz.m_field_id_'.$this->settings['wallet_zip_field'].' AS zip, mcn.m_field_id_'.$this->settings['wallet_country_field'].' AS country, mp.m_field_id_'.$this->settings['wallet_phone_field'].' AS phone, mfn.m_field_id_'.$this->settings['wallet_first_name_field'].' AS first_name, mln.m_field_id_'.$this->settings['wallet_last_name_field'].' AS last_name, mcy.m_field_id_'.$this->settings['wallet_company_field'].' AS company')
            ->from('members m')
            ->join('member_data_field_'.$this->settings['wallet_first_name_field'].' mfn', 'm.member_id = mfn.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_last_name_field'].' mln', 'm.member_id = mln.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_company_field'].' mcy', 'm.member_id = mcy.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_address_field'].' ma', 'm.member_id = ma.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_address2_field'].' ma2', 'm.member_id = ma2.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_city_field'].' mc', 'm.member_id = mc.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_state_field'].' ms', 'm.member_id = ms.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_zip_field'].' mz', 'm.member_id = mz.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_country_field'].' mcn', 'm.member_id = mcn.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_phone_field'].' mp', 'm.member_id = mp.member_id', 'left')
            ->where(array('m.member_id' => $member_id))
            ->limit(1)
            ->get();
        if ($query->num_rows() > 0) {
            //Stripe - Customer Update
            try {
                $cu = \Stripe\Customer::retrieve($stripe_customer_id);
                $cu->email = $query->row('email');
                $cu->description = $query->row('screen_name');
                $cu->shipping = array(
                    'name'          => $query->row('screen_name'),
                    'phone'         => $query->row('phone'),
                    'address' => array(
                        'line1'         => $query->row('address'),
                        'line2'         => $query->row('address2'),
                        'city'          => $query->row('city'),
                        'state'         => $query->row('state'),
                        'postal_code'   => $query->row('zip'),
                        'country'       => $query->row('country')
                    )
                );
    	        $cu->metadata = array(
    	            'member_id'     => $member_id,
                    'unique_id'     => $query->row('unique_id')
                );
                $cu->save();
            } catch (Exception $e) {
                //No errors needed
            }
            
            $this->send_email('my_settings_updated', $member_id);
            
            $query_uuid =  ee()->db->select('m_field_id_'.$this->settings['wallet_unique_id_field'].' AS unique_id')->from('member_data_field_'.$this->settings['wallet_unique_id_field'])->where(array('member_id' => $member_id))->limit(1)->get();
            if ($query_uuid->num_rows() > 0) {
                //Send API Call
                $body = array(
        		    'unique_id'     => $query_uuid->row('unique_id'),
        		    'email'         => $query->row('email'),
        		    'first_name'    => $query->row('first_name'),
        		    'last_name'     => $query->row('last_name'),
        		    'screen_name'   => $query->row('first_name').' '.$query->row('last_name'),
        		    'company'       => $query->row('company'),
        		    'phone'         => $query->row('phone'),
        		    'address'       => array(
        		        'street'        => $query->row('address'),
        		        'street_2'      => $query->row('address2'),
        		        'city'          => $query->row('city'),
        		        'region'        => $query->row('state'),
        		        'postal_code'   => $query->row('zip'),
        		        'country'       => $query->row('country')
        		    )
        		);
        	    $response = $this->api_call('user/edit', 'POST', $body);
        	    
        	    $password = ee()->input->post('password');
        	    $password_confirm = ee()->input->post('password_confirm');
        	    $current_password = ee()->input->post('current_password');
        	    
        	    if ($password != '' AND $password == $password_confirm AND $current_password != '') {
        	        //Send API Call
        	        $body = array(
            		    'unique_id'     => $query_uuid->row('unique_id'),
            		    'new_password'  => $password,
            		    'current_password' => $current_password
            		);
        		    $response = $this->api_call('user/change_password', 'POST', $body);
        	    }
            }
        }
        
        return;
	}
	
	function sm_after_social_login($data) {
	    $this->update_member_field(ee()->session->userdata('member_id'), $this->settings['wallet_app_header_field'], 0);
	    
	    return;
	}
	
	function member_member_login_single($data) {
	    $this->update_member_field(ee()->session->userdata('member_id'), $this->settings['wallet_app_header_field'], 0);
	    
	    return;
	}
	
	//Private Functions
	private function autoset_timezone() {
	    $ip = ee()->session->userdata('ip_address');
		$api_url = 'http://ip-api.com/json/'.$ip.'?key='.$this->settings['wallet_ip_api_key'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);
		$info = json_decode($response);
		$timezone = $info->timezone;
		
		if (strpos($timezone, 'America/') === 0) {
		    $time_format = 12;
		    $date_format = "%n/%j/%Y";
		} else {
		    $time_format = 24;
		    $date_format = "%Y-%m-%d";
		}
		
		ee()->db->update('members', array('timezone' => $timezone, 'time_format' => $time_format, 'date_format' => $date_format), array('member_id' => ee()->session->userdata('member_id')));
		
		return;
	}
	
	private function lang($key_override = '') {
	    $key = trim($this->EE->TMPL->fetch_param('key')) ?: '';
	    if ($key == '' AND $key_override != '') { $key = $key_override; }
	    $output = lang('wallet_'.$key);
	    if ($output == 'wallet_'.$key) {
	        $output = lang($key);
	        if ($output == $key) {
	            $output = ucwords(str_replace('_', ' ', $key));
	            
	            $this->EE->logger->log_action("Fix the lang file: wallet_$key needs to be added.");
	        }
	    }
	    return $output;
	}
	
	private function dump_and_die($die, $output1, $output2 = '', $output3 = '') {
	    echo "<pre>";
        print_r($output1);
        print_r($output2);
        print_r($output3);
        echo "</pre>";
        if ($die == 1) {
            die();
        } else {
            return;
        }
	}
	
	private function get_stripe_customer_id($member_id = '') {
	    if ($member_id == '') {
	        $member_id = ee()->session->userdata('member_id');
	    }
	    $stripe_customer_id = '';
	    $query = ee()->db->select('m_field_id_'.$this->settings['wallet_stripe_customer_id_field'].' AS stripe_customer_id')
	        ->from('member_data_field_'.$this->settings['wallet_stripe_customer_id_field'])
	        ->where(array('member_id' => $member_id))
            ->limit(1)
            ->get();
        if ($query->num_rows() > 0) {
            $stripe_customer_id = $query->row('stripe_customer_id');
        }
        
        if ($stripe_customer_id == '') {
            $query = ee()->db->select('m.screen_name AS screen_name, m.email AS email, m.unique_id AS unique_id, ma.m_field_id_'.$this->settings['wallet_address_field'].' AS address, ma2.m_field_id_'.$this->settings['wallet_address2_field'].' AS address2, mc.m_field_id_'.$this->settings['wallet_city_field'].' AS city, ms.m_field_id_'.$this->settings['wallet_state_field'].' AS state, mz.m_field_id_'.$this->settings['wallet_zip_field'].' AS zip, mcn.m_field_id_'.$this->settings['wallet_country_field'].' AS country, mp.m_field_id_'.$this->settings['wallet_phone_field'].' AS phone')
                ->from('members m')
                ->join('member_data_field_'.$this->settings['wallet_address_field'].' ma', 'm.member_id = ma.member_id', 'left')
                ->join('member_data_field_'.$this->settings['wallet_address2_field'].' ma2', 'm.member_id = ma2.member_id', 'left')
                ->join('member_data_field_'.$this->settings['wallet_city_field'].' mc', 'm.member_id = mc.member_id', 'left')
                ->join('member_data_field_'.$this->settings['wallet_state_field'].' ms', 'm.member_id = ms.member_id', 'left')
                ->join('member_data_field_'.$this->settings['wallet_zip_field'].' mz', 'm.member_id = mz.member_id', 'left')
                ->join('member_data_field_'.$this->settings['wallet_country_field'].' mcn', 'm.member_id = mcn.member_id', 'left')
                ->join('member_data_field_'.$this->settings['wallet_phone_field'].' mp', 'm.member_id = mp.member_id', 'left')
                ->where(array('m.member_id' => $member_id))
                ->limit(1)
                ->get();
            if ($query->num_rows() > 0) {
                $success = 1;
        	    $error = '';
        	    
                //Stripe - Customer Create
                try {
                    $customer = \Stripe\Customer::create([
                        'email' => $query->row('email'),
                        'description' => $query->row('screen_name'),
                        'shipping' => array(
                            'name' => $query->row('screen_name'),
                            'address' => array(
                                'line1'         => $query->row('address'),
                                'line2'         => $query->row('address2'),
                                'city'          => $query->row('city'),
                                'state'         => $query->row('state'),
                                'postal_code'   => $query->row('zip'),
                                'country'       => $query->row('country')
                            )
                        ),
                        'metadata'      => array(
                            'member_id'     => $member_id,
                            'unique_id'     => $query->row('unique_id')
                        )
                    ]);
                } catch(\Stripe\Error\Card $e) {
                  // Since it's a decline, \Stripe\Error\Card will be caught
                  $success = 0;
                  $body = $e->getJsonBody();
                  $err  = $body['error'];
                  $error = json_encode($err);
                } catch (\Stripe\Error\RateLimit $e) {
                  $success = 0;
                  $error = "RateLimit: Too many requests made to the API too quickly";
                } catch (\Stripe\Error\InvalidRequest $e) {
                  $success = 0;
                  $error = "InvalidRequest: Invalid parameters were supplied to Stripe's API";
                } catch (\Stripe\Error\Authentication $e) {
                  $success = 0;
                  $error = "Authentication: Authentication with Stripe's API failed";
                } catch (\Stripe\Error\ApiConnection $e) {
                  $success = 0;
                  $error = "ApiConnection: Network communication with Stripe failed";
                } catch (\Stripe\Error\Base $e) {
                  $success = 0;
                  $error = "Base: A very generic error";
                } catch (Exception $e) {
                  $success = 0;
                  $error = "Exception: Something else happened, completely unrelated to Stripe - ".$e;
                }
                
                if ($success == 1) {
                    //Save ID
                    $stripe_customer_id = $customer->id;
                    $this->EE->logger->log_action("NEW CUSTOMER - Stripe Customer ID created for $member_id ($stripe_customer_id)");
                    $this->update_member_field($member_id, $this->settings['wallet_stripe_customer_id_field'], $stripe_customer_id);
                } else {
                    $this->EE->logger->log_action("NEW CUSTOMER - FAILED to create Stripe Customer iD for $member_id ".$error);
                }
            }
        }
        
        return $stripe_customer_id;
	}
	
	private function send_email($template, $member_id, $custom_data = array()) {
        $query = ee()->db->select('m.username AS username, m.screen_name AS screen_name, m.email AS email, mfn.m_field_id_'.$this->settings['wallet_first_name_field'].' AS first_name, mln.m_field_id_'.$this->settings['wallet_last_name_field'].' AS last_name')
            ->from('members m')
            ->join('member_data_field_'.$this->settings['wallet_first_name_field'].' mfn', 'm.member_id = mfn.member_id', 'left')
            ->join('member_data_field_'.$this->settings['wallet_last_name_field'].' mln', 'm.member_id = mln.member_id', 'left')
            ->where(array('m.member_id' => $member_id, 'm.accept_messages' => 'y'))
            ->limit(1)
            ->get();
        if ($query->num_rows() > 0) {
            $data = array(
                'username'              => $query->row('username'),
                'screen_name'           => $query->row('screen_name'),
                'email'                 => $query->row('email'),
                'first_name'            => $query->row('first_name'),
                'last_name'             => $query->row('last_name')
            );
            $data['site_name'] = ee()->config->item('site_name');
            $data['site_url'] = ee()->config->item('site_url');
            
            //Get Email Template
            $email = ee()->db->select('*')->from('ilum_wallet_email_templates')->where(array('short_name' => $template))->limit(1)->get();
            
            if ($email->num_rows() > 0) {
                $message = $email->row('template');
                
                ee()->load->library('email');
                ee()->load->helper('text');
                
                ee()->email->wordwrap = true;
                if ($email->row('html') == 1) {
                    ee()->email->mailtype = 'html';
                } else {
                    ee()->email->mailtype = 'text';
                }
                $errors = '';
                
                //Parse Template
                foreach ($data AS $key => $val) {
                    $message = str_replace('{'.$key.'}', $val, $message);
                }
                foreach ($custom_data AS $key => $val) {
                    $message = str_replace('{custom_'.$key.'}', $val, $message);
                }
                
                //Send Email
                ee()->email->from($this->settings['wallet_emails_from_email'], $this->settings['wallet_emails_from_name']);
                ee()->email->reply_to($this->settings['wallet_emails_reply_to']);
                ee()->email->to($data['email']);
                ee()->email->subject($email->row('subject'));
                ee()->email->message(entities_to_ascii($message));
                ee()->email->send();
                
                //Check for errors
                if ( ! ee()->email->send()) {
                    $errors = ee()->email->print_debugger();
                
                    // Send failed, data was not cleared
                    ee()->email->clear();
                }
                
                //Log Email
                ee()->db->insert(
                    'ilum_wallet_email_logs',
                    array(
                        'member_id'         => $member_id,
                        'to_name'           => $data['screen_name'],
                        'to_email'          => $data['email'],
                        'from_name'         => $this->settings['wallet_emails_from_name'],
                        'from_email'        => $this->settings['wallet_emails_from_email'],
                        'reply_to_email'    => $this->settings['wallet_emails_reply_to'],
                        'html'              => $email->row('html'),
                        'subject'           => $email->row('subject'),
                        'message'           => $message,
                        'errors'            => $errors
                    )
                );
            }
        }
        
        return;
    }
	
	private function update_member_field($member_id, $field_id, $value = '', $ft = 'none') {
        if (is_numeric($member_id) AND is_numeric($field_id)) {
            $query = ee()->db->select('id')->from('member_data_field_'.$field_id)->where(array('member_id' => $member_id))->limit(1)->get();
            if ($query->num_rows() > 0) {
                ee()->db->update('member_data_field_'.$field_id, array('m_field_id_'.$field_id => $value, 'm_field_ft_'.$field_id => $ft), array('member_id' => $member_id));
            } else {
                ee()->db->insert('member_data_field_'.$field_id, array('member_id' => $member_id, 'm_field_id_'.$field_id => $value, 'm_field_ft_'.$field_id => $ft));
            }
        }
        
        return;
    }
	
	/* API Callers */
	private function api_call($endpoint = '', $method = "GET", $body = array(), $query_string = array(), $headers = array(), $only200 = '0') {
	    require_once("class/api.ilum.php");
	    $api = new ilum_api;
	    return $api->ilumApi($endpoint, $method, $body, $query_string, $headers, $only200);
	}
}