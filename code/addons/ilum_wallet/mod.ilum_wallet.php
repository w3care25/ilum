<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'/ilum_wallet/libraries/stripe/init.php';

class Ilum_wallet {
    public $method = '';
    public $query_string = array();
    public $body = array();
    public $headers = array();
    public $endpoint = array();
    var $settings;
    var $module_name = "Ilum_wallet";
    var $stripe_secret_key;
    
	public function __construct() {
	    //Load variables and libraries
		$this->EE = get_instance();
        $this->EE->load->library('auth');
        $this->EE->load->library('logger');
        
        //Bring in language file
        ee()->lang->loadfile('ilum_wallet');
        
        //Settings
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = '".$this->module_name."'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
        
        //Grab API Data
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->query_string = $_GET;
		$this->body = json_decode(file_get_contents('php://input'),true);
		$this->headers = $this->request_headers(); //apache_request_headers();
		$this->endpoint = ee()->uri->uri_string();
		
		//Initiate Stripe
		$this->stripe_secret_key = $this->settings['wallet_stripe_secret_live_key'];
		if ($this->settings['wallet_stripe_test_mode'] == 'y') {
		    $this->stripe_secret_key = $this->settings['wallet_stripe_secret_test_key'];
		}
		\Stripe\Stripe::setApiKey($this->stripe_secret_key);
		\Stripe\Stripe::setApiVersion("2019-02-19");
		//\Stripe\Stripe::setApiVersion("2020-08-27");
	}
	
	public function action_id($action_o = '') {
	    $action = trim($this->EE->TMPL->fetch_param('action')) ?: $action_o;
	    $action_id = '';
	    
	    $query = ee()->db->select('action_id')->from('actions')->where(array('class' => $this->module_name, 'method' => $action))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        $action_id = $query->row('action_id');
	    }
	    
	    return $action_id;
	}
	
	public function add_funds_form() {
	    $tagdata = $this->EE->TMPL->tagdata;
		$class = ee()->TMPL->fetch_param('class') ?: '';
		$id = ee()->TMPL->fetch_param('id') ?: '';
		$return = ee()->TMPL->fetch_param('return') ?: '';
		$error_return = ee()->TMPL->fetch_param('error_return') ?: '';
		
		$return = str_replace('{wallet_env}', $this->settings['wallet_env'], $return);
		
		$act = $this->action_id('add_funds_post');
		    
		$form_details = array(
		    'name'            => 'add_funds',
		    'id'              => $id,
		    'class'           => $class,
		    'hidden_fields'   => array('ACT' => $act, 'return' => $return, 'error_return' => $error_return, 'conversion' => $this->settings['wallet_currency_ratio'], 'processing_percent' => $this->settings['wallet_processing_percent'], 'processing_fee' => $this->settings['wallet_processing_fee']),
		    'secure'          => TRUE
		);
		
		return $this->EE->functions->form_declaration($form_details) . $tagdata . "</form>";
	}
	
	public function ajax_table_activity() {
	    // DB table to use
        $table = 'exp_ilum_wallet_transactions';
        
        // Table's primary key
        $primaryKey = 'id';
        
        // Other variables
        $member_id = ee()->session->userdata('member_id');

        $columns = array(
        	array('db' => 'time', 'dt' => 0, //Time
            	'formatter' => function($d, $row) {
            	    $class = '';
            	    if ($row['type'] == '1' OR $row['type'] == 4) { $class = "success"; } else if ($row['type'] == 3) { $class = "danger"; }
                    return '<div class="text-center '.$class.'">'.ee()->localize->format_date('%M %d, %Y', $d, TRUE).'<br>'.ee()->localize->format_date('%g:%i:%s %a', $d, TRUE).'</div>';
                }), 
        	array('db' => 'title', 'dt' => 1, //Title + Desc
        		'formatter' => function($d, $row) {
        			return '<h6>'.$d.'</h6>'.$row['txn_desc'].'<br><small class="text-muted"><b>'.lang('wallet_transaction_id').'</b>: '.$row['txn'].'</small>';
        		}),
        	array('db' => 'amount', 'dt' => 2, //Method + Amount
        		'formatter' => function($d, $row) {
        			return '<h5 class="text-center">'.$row['method'].$this->convert_amount($d).'</h5>';
        		}),
            array('db' => 'txn_desc', 'dt' => ''),
            array('db' => 'method', 'dt' => ''),
            array('db' => 'type', 'dt' => ''),
            array('db' => 'txn', 'dt' => ''),
            array('db' => 'id', 'dt' => '')
        );
        
        require(SYSPATH."/user/config/config.php");
        
        $sql_details = array(
        	'user' => $config['database']['expressionengine']['username'],
        	'pass' => $config['database']['expressionengine']['password'],
        	'db'   => $config['database']['expressionengine']['database'],
        	'host' => $config['database']['expressionengine']['hostname']
        );
        
        $where = "`member_id` = '$member_id'";
        
        require(PATH_THIRD."/omg_cp/datatables/class/ssp.class.php");
        
        return json_encode(
        	SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, '', $where)
        );
	}
	
	public function app_header() {
	    $tagdata = $this->EE->TMPL->tagdata;
	    $data = $tagdata;
	    $member_id = ee()->session->userdata('member_id');
	    
	    if (is_numeric($member_id) AND $member_id > 0) {
	        $query = ee()->db->select('uuid.m_field_id_'.$this->settings['wallet_unique_id_field'].' AS unique_id, ah.m_field_id_'.$this->settings['wallet_app_header_field'].' AS app_header')->from('member_data_field_'.$this->settings['wallet_unique_id_field'].' uuid')->join('member_data_field_'.$this->settings['wallet_app_header_field'].' ah', 'uuid.member_id = ah.member_id', 'left')->where(array('uuid.member_id' => $member_id))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $unique_id = $query->row('unique_id');
	            $app_header = $query->row('app_header');
	            
	            if ($app_header == 1) {
    	            $curl = curl_init();
    	            
    	            curl_setopt_array($curl, array(
                      CURLOPT_URL => "https://".$this->settings['wallet_env']."app.ilum.app/_layouts/ajax_header/".$unique_id,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                      CURLOPT_POSTFIELDS => json_encode(array())
                    ));
                    
                    try {
                        $response = curl_exec($curl);
                    } catch (Exception $e) {
                        curl_close($curl);
                        return $data;
                    }
                    
                    curl_close($curl);
                    if (trim($response) != '') {
                        $data = $response;
                    }
	            }
	        }
	    }
	    
	    return $data;
	}
	
	public function app_uuid_login() {
	    $unique_id = ee()->input->get('unique_id');
	    
	    if ($unique_id != '') {
	        $query = ee()->db->select('member_id')->from('member_data_field_'.$this->settings['wallet_unique_id_field'])->where(array('m_field_id_'.$this->settings['wallet_unique_id_field'] => $unique_id))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $member_id = $query->row('member_id');
	            
	            $session = ee()->session->create_new_session($member_id);
	            $this->EE->logger->log_action("Logged In From App");
	            
	            $this->update_member_field($member_id, $this->settings['wallet_app_header_field'], 1);
	            
        	    $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	            ee()->functions->redirect($current_url);
	        }
	    }
	    
	    return;
	}
	
	public function applepay_form() {
	    $tagdata = $this->EE->TMPL->tagdata;
		$class = ee()->TMPL->fetch_param('class') ?: '';
		$id = ee()->TMPL->fetch_param('id') ?: '';
		$return = ee()->TMPL->fetch_param('return') ?: '';
		$final_return = ee()->TMPL->fetch_param('final_return') ?: '';
		$cancel_return = ee()->TMPL->fetch_param('cancel_return') ?: '';
		
		$final_return = str_replace('{wallet_env}', $this->settings['wallet_env'], $final_return);
		
		$form_details = array(
		    'name'            => 'add_funds_applepay',
		    'action'          => $return,
		    'id'              => $id,
		    'class'           => $class,
		    'hidden_fields'   => array(
                'final_return'      => $final_return,
                'cancel_return'     => $cancel_return
            ),
		    'secure'          => TRUE
		);
    		
    	return $this->EE->functions->form_declaration($form_details) . $tagdata . "</form>";
	}
	
	public function applepay_checkout_form() {
	    if (ee()->input->get_post('client_secret') != '') { //Client Secret has been submitted
	        $client_secret = ee()->input->get_post('client_secret');
	        $member_id = ee()->session->userdata('member_id');
	        
	        //$this->dump_and_die(1, $client_secret.' '.$member_id);
	        
	        $query = ee()->db->select('*')->from('ilum_wallet_payment_intents')->where(array('client_secret' => $client_secret, 'member_id' => $member_id, 'status' => 0))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $stripe = new \Stripe\StripeClient(
                  $this->stripe_secret_key
                );
	        
	            $intent = $stripe->paymentIntents->retrieve(
                  $query->row('pi_id'),
                  []
                );
                
                //$this->dump_and_die(1, $intent);
                if ($intent->status == 'succeeded') {
                    $amount = $query->row('amount');
                    $amount_ilum = $query->row('amount_ilum');
                    
                    //Log Transaction
                    ee()->db->insert('ilum_wallet_transactions', 
                        array(
                            'member_id'         => $member_id,
                            'type'              => 1,
                            'method'            => '+',
                            'txn'               => 'af_'.$query->row('pi_id'),
                            'amount'            => $amount,
                            'amount_ilum'       => $amount_ilum,
                            'title'             => $this->lang('apple_pay'),
                            'txn_desc'          => $this->lang('txn_type_1'),
                            'location'          => '',
                            'location_id'       => 0,
                            'to_member_id'      => $member_id,
                            "funds_added_from"  => 'apple_pay',
                            'time'              => time()
                        )
                    );
                    
                    //Update Balance
                    $this->update_balance($amount, '+', $member_id);
                    
                    //Update PI Record
                    ee()->db->update('ilum_wallet_payment_intents', array('full_complete' => json_encode($intent), 'status' => 1), array('id' => $query->row('id')));
                    
                    ee()->functions->redirect(ee()->input->get_post('final_return'));
                }
	        }
	        
	        ee()->functions->redirect(ee()->input->get_post('error_return'));
	    } else {
    	    $tagdata = $this->EE->TMPL->tagdata;
    		$class = ee()->TMPL->fetch_param('class') ?: '';
    		$id = ee()->TMPL->fetch_param('id') ?: '';
    		$error_return = ee()->TMPL->fetch_param('error_return') ?: '';
    		$submit_return = ee()->TMPL->fetch_param('submit_return') ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    		
    		$final_return = ee()->input->post('final_return');
    		$cancel_return = ee()->input->post('cancel_return');
    		
    		$error_return = str_replace('CANCEL_RETURN', $cancel_return, $error_return);
    		
    		$amount = number_format(ee()->input->post('amount'), 2, '.', '');
    		$amount_ilum = $this->convert_amount($amount);
    		$amount_pennies = $amount * 100;
    		
    		$fee = number_format(($amount * ($this->settings['wallet_processing_percent'] / 100)) + $this->settings['wallet_processing_fee'], 2, '.', '');
	        $amount_fee = number_format($amount + $fee, 2, '.', '');
	        $amount_fee_pennies = number_format($amount_fee * 100, 0, '.', '');
	        //$this->dump_and_die(1, $fee.' - '.$amount_fee.' - '.$amount_fee_pennies);
    		
    		//$this->dump_and_die(1, $amount, $amount_ilum, $amount_pennies);
    		
    		$intent = \Stripe\PaymentIntent::create([
              'amount' => $amount_fee_pennies,
              'currency' => 'usd',
            ]);
            
            $client_secret = '';
            $error = '';
            if (isset($intent->client_secret)) {
                $pi_id = $intent->id;
    	        $client_secret = $intent->client_secret;
    	        
    	        ee()->db->insert(
                    'ilum_wallet_payment_intents',
                    array(
                        'pi_id'             => $pi_id,
                        'client_secret'     => $client_secret,
                        'member_id'         => ee()->session->userdata('member_id'),
                        'full_return'       => json_encode($intent),
                        'full_complete'     => '',
                        'amount'            => $amount,
                        'amount_ilum'       => $amount_ilum,
                        'status'            => 0,
                        'time'              => time()
                    )
                );
    	    } else {
    	        $error = lang('wallet_applepay_error');
    	    }
    		
    		$form_details = array(
    		    'name'            => 'applepay_checkout_form',
    		    'id'              => $id,
    		    'class'           => $class,
    		    'action'          => $submit_return,
    		    'hidden_fields'   => array(
                    'final_return'      => $final_return,
                    'cancel_return'     => $cancel_return,
                    'error_return'      => $error_return,
                    'client_secret'     => $client_secret
                ),
    		    'secure'          => TRUE
    		);
    		
    		$data = array();
    		$data[] = array(
    		    'final_return'      => $final_return,
    		    'cancel_return'     => $cancel_return,
    		    'error_return'      => $error_return,
    		    'client_secret'     => $client_secret,
    		    'amount'            => $amount_fee,
    		    'amount_ilum'       => $amount_ilum,
    		    'amount_pennies'    => $amount_fee_pennies,
    		    'error'             => $error
    		);
        		
        	return $this->EE->functions->form_declaration($form_details) . ee()->TMPL->parse_variables( $tagdata, $data ) . "</form>";
	    }
	}
	
	public function autoset_timezone() {
	    $ip = ee()->session->userdata('ip_address');
		$api_url = 'http://ip-api.com/json/'.$ip.'?key='.$this->settings['wallet_ip_api_key'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);
		$info = json_decode($response);
		if (isset($info->timezone)) {
    		$timezone = $info->timezone;
    		
    		if (strpos($timezone, 'America/') === 0) {
    		    $time_format = 12;
    		    $date_format = "%n/%j/%Y";
    		} else {
    		    $time_format = 24;
    		    $date_format = "%Y-%m-%d";
    		}
    		
    		ee()->db->update('members', array('timezone' => $timezone, 'time_format' => $time_format, 'date_format' => $date_format), array('member_id' => ee()->session->userdata('member_id')));
		}
		
		return;
	}
	
	public function cron_clear_logs() {
	    $days = ee()->TMPL->fetch_param('days') ?: '60';
	    $test_time = time() - (86400 * $days);
	    
	    //Backup
	    // Load the DB utility class
        ee()->load->dbutil();
        $query = ee()->db->query("SELECT * FROM exp_cp_log WHERE act_date < $test_time");
        $backup = ee()->dbutil->csv_from_result($query);
        write_file($_SERVER['DOCUMENT_ROOT'].'/_logs/'.time().'.csv', $backup);
	    
	    //Clear Old Logs
	    ee()->db->delete('cp_log', array('act_date <' => $test_time));
	    
	    $this->EE->logger->log_action("LOGS CLEARED: OLDER THAN ".$days.' DAYS ('.$test_time.')');
	    
	    return;
	}
	
	public function cron_clear_payment_intents() {
	    $test_time = time() - 86400;
	    
	    ee()->db->delete('ilum_wallet_payment_intents', array('status' => 0, 'time <' => $test_time));
	    
	    $this->EE->logger->log_action("PAYMENT INTENTS CLEARED: INCOMPLETE OLDER THAN 1 DAY (".$test_time.')');
	    
	    return;
	}
	
	public function lang($key_override = '') {
	    if ($key_override == '') {
	        $key = trim($this->EE->TMPL->fetch_param('key')) ?: '';
	    } else {
	        $key = '';
	    }
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
	
	public function min_age() {
	    $format = ee()->TMPL->fetch_param('format') ?: 'years';
	    $years = $this->settings['wallet_minimum_age'];
	    
	    if ($format == "date") {
	        $year = date('Y') - $years;
	        return date('m/d/').$year;
	    }
	    
	    return $years;
	}
	
	public function my_balance() {
	    $balance = "0.00";
	    $query = ee()->db->select('balance, balance_ilum')->from('ilum_wallet_balance')->where(array('member_id' => ee()->session->userdata('member_id')))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        //$balance = number_format($query->row('balance_ilum'), 2, '.', '');
	        $balance = number_format($this->convert_amount($query->row('balance')), 2, '.', '');
	    }
	    
	    if ($balance == 0) { $balance = "0.00"; }
	    
	    return $balance;
	}
	
	public function payment_methods() {
	    $stripe_customer_id = $this->get_stripe_customer_id();
	    $year = date('Y');
	    $month = date('n');
	    
	    $tagdata = $this->EE->TMPL->tagdata;
	    $data = array();
	    $count = 0;
	    
	    if ($stripe_customer_id != '') {
    	    //Get Default
    	    try {
              $response = \Stripe\Customer::retrieve($stripe_customer_id);
            } catch(\Stripe\Error\Card $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\RateLimit $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\InvalidRequest $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\Authentication $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\ApiConnection $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\Base $e) {
              return ee()->TMPL->no_results();
            } catch (Exception $e) {
              return ee()->TMPL->no_results();
            }
    	    $default_source = $response->default_source;
    	    
    	    //Get Tokens
    	    try {
              $response = \Stripe\Customer::retrieve($stripe_customer_id)->sources->all([
      'limit'=>100]);
            } catch(\Stripe\Error\Card $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\RateLimit $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\InvalidRequest $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\Authentication $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\ApiConnection $e) {
              return ee()->TMPL->no_results();
            } catch (\Stripe\Error\Base $e) {
              return ee()->TMPL->no_results();
            } catch (Exception $e) {
              return ee()->TMPL->no_results();
            }
            foreach ($response->data AS $source) {
                $count++;
                
                if ($source->object == "card") {
                    $brand = $source->brand;
                    $exp = $source->exp_month.'/'.$source->exp_year;
                    if (($year > $source->exp_year) OR ($year == $source->exp_year AND $month > $source->exp_month)) {
                        $expired = 1;
                    } else {
                        $expired = '';
                    }
                } else if ($source->object == "bank_account") {
                    $brand = $source->bank_name;
                    $exp = '';
                    $expired = '';
                }
                
                $default_method = '';
                if ($source->id == $default_source) {
                    $default_method = 1;
                }
                
                $data[] = array(
                    'payment_type'      => $source->object,
                    'payment_brand'     => $brand,
                    'payment_last4'     => $source->last4, 
                    'payment_exp'       => $exp,
                    'payment_token'     => $source->id,
                    'expired'           => $expired,
                    'default_method'    => $default_method
                );
            }
            
            //$this->dump_and_die(1, $response);
    	    
    	    if ($count > 0) {
    	        return $this->EE->TMPL->parse_variables( $tagdata, $data );
    	    }
	    }
	    
	    return ee()->TMPL->no_results();
	}
	
	public function paypal_form() {
	    $tagdata = $this->EE->TMPL->tagdata;
		$class = ee()->TMPL->fetch_param('class') ?: '';
		$id = ee()->TMPL->fetch_param('id') ?: '';
		$return = ee()->TMPL->fetch_param('return') ?: '';
		$pdt_return = ee()->TMPL->fetch_param('pdt_return') ?: '';
		
		$return = str_replace('{wallet_env}', $this->settings['wallet_env'], $return);
		$pdt_return = str_replace('{wallet_env}', $this->settings['wallet_env'], $pdt_return);
		
		$ipn_act = $this->action_id('paypal_ipn');
		
		$query = ee()->db->select('unique_id')->from('members')->where(array('member_id' => ee()->session->userdata('member_id')))->limit(1)->get();
		if ($query->num_rows() > 0) {
		    $unique_id = $query->row('unique_id');
		    
		    $paypal_url = "https://www.";
    		if ($this->settings['wallet_env'] != '') { $paypal_url .= 'sandbox.'; }
    		$paypal_url .= "paypal.com/cgi-bin/webscr"; 
    		    
    		$form_details = array(
    		    'name'            => 'add_funds',
    		    'action'          => $paypal_url,
    		    'id'              => $id,
    		    'class'           => $class,
    		    'hidden_fields'   => array(
                    'cmd'               => '_cart',
                    'upload'            => 1,
                    'business'          => $this->settings['paypal_account'],
                    'item_name_1'       => lang('wallet_ilum_bucks'),
                    'item_number_1'     => 1,
                    'quantity_1'        => 1,
                    'shipping_1'        => 0,
                    'item_name_2'       => lang('wallet_processing_fee'),
                    'item_number_1'     => 1,
                    'quantity_1'        => 1,
                    'shipping_1'        => 0,
                    'tax_cart'          => 0,
                    'discount_amount_cart' => 0,
                    'notify_url'        => "https://".$this->settings['wallet_env']."wallet.ilum.app/?ACT=".$ipn_act,
                    'return'            => $pdt_return,
                    'cbt'               => lang('wallet_back_to_ilum'),
                    'custom'            => $unique_id.'|'.$return,
                    'cancel_return'     => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
                ),
    		    'secure'          => TRUE
    		);
    		
    		return $this->EE->functions->form_declaration($form_details) . $tagdata . "</form>";
		}
		
		return;
	}
	
	public function paypal_ipn() {
	    $custom = explode('|', ee()->input->get_post('custom'));
        $unique_id = $custom[0];
	    $status = ee()->input->get_post('payment_status');
	    
	    $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $unique_id))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        $member_id = $query->row('member_id');
	        
	        $amount = ee()->input->get_post('mc_gross_1');
	        $amount_ilum = $this->convert_amount($amount);
	        
	        switch($status) {
    	        case 'Completed':
    	            $txn_id = ee()->input->get_post('txn_id');
            	            
    	            // check that txn_id has not been previously processed
    	            $query_txn = ee()->db->select('id')->from('ilum_wallet_transactions')->where(array('txn' => 'af_'.$txn_id))->limit(1)->get();
    	            
    	            if ($query_txn->num_rows() == 0) {
        	            //Log Transaction
                        ee()->db->insert('ilum_wallet_transactions', 
                            array(
                                'member_id'         => $member_id,
                                'type'              => 1,
                                'method'            => '+',
                                'txn'               => 'af_'.$txn_id,
                                'amount'            => $amount,
                                'amount_ilum'       => $amount_ilum,
                                'title'             => $this->lang('paypal'),
                                'txn_desc'          => $this->lang('txn_type_1'),
                                'location'          => '',
                                'location_id'       => 0,
                                'to_member_id'      => $member_id,
                                "funds_added_from"  => 'paypal',
                                'time'              => time()
                            )
                        );
                        
                        //Update Balance
                        $this->update_balance($amount, '+', $member_id);
    	            }
                    
    	            break;
    	    }
	    }

	    ee()->logger->log_action('PAYPAL IPN: '.json_encode($_POST));   
	}
	
	public function paypal_pdt() {
	    $error_return = trim($this->EE->TMPL->fetch_param('error_return')) ?: '';
	    
    	if ($this->settings['wallet_env'] != '') { 
    	    $pp_hostname = "www.sandbox.paypal.com";
    	} else {
    	    $pp_hostname = "www.paypal.com";
    	}
    	
    	// read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-synch';
 
        $tx_token = ee()->input->get('tx');
        $auth_token = $this->settings['paypal_pdt_token'];
        $req .= "&tx=$tx_token&at=$auth_token";
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://$pp_hostname/cgi-bin/webscr");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        //set cacert.pem verisign certificate path in curl using 'CURLOPT_CAINFO' field here,
        //if your server does not bundled with default verisign certificates.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: $pp_hostname"));
        $res = curl_exec($ch);
        curl_close($ch);
        
        //$this->dump_and_die(1, $res);

        if(!$res){
            //HTTP ERROR
            ee()->functions->redirect($error_return);
        }else{
             // parse the data
            $lines = explode("\n", trim($res));
            $keyarray = array();
            if (strcmp ($lines[0], "SUCCESS") == 0) {
                for ($i = 1; $i < count($lines); $i++) {
                    $temp = explode("=", $lines[$i],2);
                    $keyarray[urldecode($temp[0])] = urldecode($temp[1]);
                }
                
                $custom = explode('|', $keyarray['custom']);
                $unique_id = $custom[0];
                $return = $custom[1] ?? '/';
                
        	    $status = $keyarray['payment_status'];
        	    
        	    $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $unique_id))->limit(1)->get();
        	    if ($query->num_rows() > 0) {
        	        $member_id = $query->row('member_id');
        	        
        	        $amount = $keyarray['mc_gross_1'];
        	        $amount_ilum = $this->convert_amount($amount);
        	        
        	        switch($status) {
            	        case 'Completed':
            	            $txn_id = $keyarray['txn_id'];
            	            
            	            // check that txn_id has not been previously processed
            	            $query_txn = ee()->db->select('id')->from('ilum_wallet_transactions')->where(array('txn' => 'af_'.$txn_id))->limit(1)->get();
            	            
            	            if ($query_txn->num_rows() == 0) {
                	            //Log Transaction
                                ee()->db->insert('ilum_wallet_transactions', 
                                    array(
                                        'member_id'         => $member_id,
                                        'type'              => 1,
                                        'method'            => '+',
                                        'txn'               => 'af_'.$txn_id,
                                        'amount'            => $amount,
                                        'amount_ilum'       => $amount_ilum,
                                        'title'             => $this->lang('paypal'),
                                        'txn_desc'          => $this->lang('txn_type_1'),
                                        'location'          => '',
                                        'location_id'       => 0,
                                        'to_member_id'      => $member_id,
                                        "funds_added_from"  => 'paypal',
                                        'time'              => time()
                                    )
                                );
                                
                                //Update Balance
                                $this->update_balance($amount, '+', $member_id);
            	            }
            	            
            	            break;
            	    }
        	    }

	            ee()->logger->log_action('PAYPAL PDT: '.json_encode($keyarray));
	            
	            ee()->functions->redirect($return);
            } else if (strcmp ($lines[0], "FAIL") == 0) {
                ee()->logger->log_action('PAYPAL PDT FAILURE: '.json_encode($lines));
                //Error Return
                ee()->functions->redirect($error_return);
            }
        }
	}
	
	public function plaid_new_bank_account_form() {
	    $icon = trim($this->EE->TMPL->fetch_param('icon')) ?: '';
	    $class = trim($this->EE->TMPL->fetch_param('class')) ?: '';
	    $return = trim($this->EE->TMPL->fetch_param('return')) ?: '';
	    $error_return = trim($this->EE->TMPL->fetch_param('error_return')) ?: '';
	    
	    $act = $this->action_id('plaid_new_bank_account_post');
	    $key = $this->settings['wallet_plaid_public_key'];
	    $env = $this->settings['wallet_plaid_env'];
	    $label = $this->lang('new_bank_account');
	    $save = $this->lang('btn_save_settings');
	    $email = ee()->session->userdata('email');
	    
	    $output = <<<EOD
<button id="linkButton" class="$class"><i class="$icon"></i> $label</button>

<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
<script>
var linkHandler = Plaid.create({
  env: '$env',
  clientName: "$label",
  key: '$key',
  product: ['auth'],
  selectAccount: true,
  onSuccess: function(public_token, metadata) {
  	$('.card-loader').removeClass('hidden');
	// Send the public_token and account ID to your app server.
	$('#ba_public_token').val(public_token);
	$('#ba_account_id').val(metadata.account_id);
	$('#loading').removeClass('hidden');
	//console.log('public_token: ' + public_token);
	//console.log('account ID: ' + metadata.account_id);
	$('#ba_new').submit();
  },
  onExit: function(err, metadata) {
	// The user exited the Link flow.
	if (err != null) {
	  // The user encountered a Plaid API error prior to exiting.
	}
  },
});

// Trigger the Link UI
document.getElementById('linkButton').onclick = function() {
  linkHandler.open();
};
</script>

<form id="ba_new" action="/?ACT=$act" method="POST">
  <input type="hidden" name="return" value="$return" />
  <input type="hidden" name="error_return" value="$error_return" />
  <input type="hidden" name="public_token" id="ba_public_token" value="" />
  <input type="hidden" name="account_id" id="ba_account_id" value="" />
</form>
EOD;
        
        return $output;
	}
	
	public function stripe_create_new_session() {
	    $act = $this->action_id('stripe_new_credit_card_post');

	    $unique_id = '';
	    $query = ee()->db->select('unique_id')->from('members')->where(array('member_id' => ee()->session->userdata('member_id')))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        $unique_id = $query->row('unique_id');
	    }
	    
	    $session = \Stripe\Checkout\Session::create([
          'payment_method_types' => ['card'],
          'mode' => 'setup',
          'customer' => $this->get_stripe_customer_id(),
          'client_reference_id' => $unique_id,
          'success_url' => "https://".$this->settings['wallet_env']."wallet.ilum.app/?ACT=".$act."&session_id={CHECKOUT_SESSION_ID}",
          'cancel_url' => "https://".$this->settings['wallet_env']."wallet.ilum.app/payment-methods",
        ]);
        
        //$this->dump_and_die(1, $session);
        return $session->id;
	}
	
	public function stripe_new_credit_card_form() {
	    $return = trim($this->EE->TMPL->fetch_param('return')) ?: '';
	    $error_return = trim($this->EE->TMPL->fetch_param('error_return')) ?: '';
	    $class = trim($this->EE->TMPL->fetch_param('class')) ?: '';
	    
	    $act = $this->action_id('stripe_new_credit_card_post');
	    $key = $this->settings['wallet_stripe_publish_live_key'];
	    if ($this->settings['wallet_stripe_test_mode'] == 'y') {
	        $key = $this->settings['wallet_stripe_publish_test_key'];
	    }
	    $label = $this->lang('new_credit_card');
	    $save = $this->lang('btn_save_settings');
	    $email = ee()->session->userdata('email');
	    
	    $output = <<<EOD
<form id="cc_new" action="/?ACT=$act" method="POST">
  <input type="hidden" name="return" value="$return" />
  <input type="hidden" name="error_return" value="$error_return" />
  <script
	src="https://checkout.stripe.com/checkout.js" class="stripe-button"
	data-key="$key"
	data-name="$label"
	data-panel-label="$save"
	data-label="$label"
	data-allow-remember-me=false
	data-locale="auto"
	data-email="$email"
	closed="loading">
  </script>
</form>
EOD;
        
        return $output;
	}
	
	public function stripe_public_key() {
	    $stripe_public_key = $this->settings['wallet_stripe_publish_test_key'];
		if ($this->settings['wallet_stripe_test_mode'] == 'y') {
		    $stripe_public_key = $this->settings['wallet_stripe_publish_test_key'];
		}
		return $stripe_public_key;
	}
	
	public function settings_update() {
	    $member_id = ee()->session->userdata('member_id');
	    $setting = ee()->TMPL->fetch_param('setting') ?: '';
	    $value = ee()->TMPL->fetch_param('value') ?: '';
	    
	    if ($setting != '') {
	        ee()->db->update('members', array($setting => $value), array('member_id' => $member_id));
	    }
	    
	    return $value;
	}
	
	public function stripe() {
	    $this->api_endpoint_start('proxibid');
        
	    $query_string = '?'.http_build_query($this->body['query_string']);
	    
	    $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->env['proxibid'][$this->body['env']].'/core/category'.$this->body['endpoint'].$query_string,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $this->body['method'],
          CURLOPT_POSTFIELDS => json_encode($this->body['body']),
          CURLOPT_HTTPHEADER => $this->body['headers']
        ));
        
        if ($this->body['only200'] == 1) {
            try {
                $response = curl_exec($curl);
            } catch (Exception $e) {
                $call_details .= "Exception Occurred: ".$e."<br><br>";
                return $response;
            }
	    } else {
	        $response = curl_exec($curl);
	    }
        
        $err = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($this->body['only200'] != 1) { ee('Response')->setStatus($status); }
        curl_close($curl);
        
        //Log Full API Call
        $call_details .= $this->body['method']." core/category".$this->body['endpoint'].$query_string."<br>";
        $call_details .= "Host: ".$this->env['proxibid'][$this->body['env']]."<br>";
        foreach($this->body['headers'] AS $header) {
            $call_details .= $header."<br>";
        }
        
        if (is_array($this->body['body']) AND sizeof($this->body['body']) > 0) {
            foreach($this->body['body'] AS $key => $val) {
                if (strpos(strtolower($key), "password") !== false AND $key != "passwordToken") {
                    $this->body['body'][$key] = "REDACTED";
                }
            }
            $call_details .= "<br>".json_encode($this->body['body']);
        }
        
        $call_details .= "<br><br>RESPONSE (".$status.")<br>".json_encode($response);
        if ($err != '') {
            $call_details .= "<br><br>CURL ERROR: ".$err;
        }
        
        if ($this->body['endpoint'] != "/api/v1/keepSessionAlive") {
            $this->EE->logger->log_action("Proxibid API Call: <br><br>".$call_details);
        }
        
        return $response;
	}
	
	//Actions
	public function add_funds_post() {
	    $member_id = ee()->session->userdata('member_id');
	    $return = ee()->input->post('return');
	    $error_return = ee()->input->post('error_return');
	    $payment_method = ee()->input->post('payment_method');
	    $amount = number_format(ee()->input->post('amount'), 2, '.', '');
	    
	    $stripe_customer_id = $this->get_stripe_customer_id();
	    $amount_ilum = $this->convert_amount($amount);
	    $method_details = $this->payment_method($payment_method);
	    
	    $success = 1;
	    $error = '';
	    
	    $desc = $this->lang('stripe_funds_added');
	    
	    if ($payment_method != '' AND is_numeric($amount) AND $amount >= .50) {
	        $amount_pennies = number_format($amount * 100, 0, '.', '');
	        
	        $fee = number_format(($amount * ($this->settings['wallet_processing_percent'] / 100)) + $this->settings['wallet_processing_fee'], 2, '.', '');
	        $amount_fee = number_format($amount + $fee, 2, '.', '');
	        $amount_fee_pennies = number_format($amount_fee * 100, 0, '.', '');
	        //$this->dump_and_die(1, $fee.' - '.$amount_fee.' - '.$amount_fee_pennies);

	        try {
                $response = \Stripe\Charge::create([
                  "amount"      => $amount_fee_pennies,
                  "currency"    => "usd",
                  "source"      => $payment_method,
                  "description" => $desc,
                  "customer"    => $stripe_customer_id,
                  "metadata"    => array(
                    "type"          => 1,
                    "amount_ilum"   => $amount_ilum,
                    "title"         => $method_details['payment_brand'].' ****'.$method_details['payment_last4'],
                    "location"      => '',
                    "member_id"     => $member_id,
                    "to_member_id"  => $member_id
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
                //Log Transaction
                ee()->db->insert('ilum_wallet_transactions', 
                    array(
                        'member_id'         => $member_id,
                        'type'              => 1,
                        'method'            => '+',
                        'txn'               => $response->id,
                        'amount'            => $amount,
                        'amount_ilum'       => $amount_ilum,
                        'title'             => $method_details['payment_brand'].' ****'.$method_details['payment_last4'],
                        'txn_desc'          => $this->lang('txn_type_1'),
                        'location'          => '',
                        'location_id'       => 0,
                        'to_member_id'      => $member_id,
                        "funds_added_from"  => 'stripe',
                        'time'              => time()
                    )
                );
                
                //Update Balance
                $this->update_balance($amount, '+');
                
                //Send Email
                $custom_data = array(
                    'ilum_bucks'            => $amount_ilum,
                    'payment_method'        => $method_details['payment_brand'].' ****'.$method_details['payment_last4'],
                    'date'                  => ee()->localize->human_time(),
                    'details'               => $this->lang('txn_type_1'),
                    'amount'                => '$'.$amount
                );
                $this->send_email('funds_added', $member_id, $custom_data);
                
                //Update Logs
                $this->EE->logger->log_action("ADD FUNDS ($payment_method) added $amount to $member_id ($stripe_customer_id)");
                ee()->functions->redirect($return);
            }
	    }
	    
	    $this->EE->logger->log_action("ADD FUNDS - FAILED ($payment_method) failed to add $amount to $member_id ($stripe_customer_id) - ".$error);
	    ee()->functions->redirect($error_return);
	}
	
	public function plaid_new_bank_account_post() {
	    $member_id = ee()->session->userdata('member_id');
	    $return = ee()->input->post('return');
	    $error_return = ee()->input->post('error_return');
	    $public_token = ee()->input->post('public_token');
	    $account_id = ee()->input->post('account_id');
	    $stripe_customer_id = $this->get_stripe_customer_id();
	    $success = 1;
	    
	    //Validate
	    $headers[] = 'Content-Type: application/json';
        $params = array(
           'client_id' => $this->settings['wallet_plaid_client_id'],
           'secret' => $this->settings['wallet_plaid_secret_key'],
           'public_token' => $public_token
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://".$this->settings['wallet_plaid_env'].".plaid.com/item/public_token/exchange");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if(!$result = curl_exec($ch)) {
           $this->EE->logger->log_action("NEW BANK ACCOUNT - FAILED ($stripe_bank_account_token) was not added to $member_id ($stripe_customer_id) - Plaid Token Exchange");
           //trigger_error(curl_error($ch));
           ee()->functions->redirect($error_return);
        }
        curl_close($ch);
        
        $jsonParsed = json_decode($result);
        
        $btok_params = array(
           'client_id' => $this->settings['wallet_plaid_client_id'],
           'secret' => $this->settings['wallet_plaid_secret_key'],
           'access_token' => $jsonParsed->access_token,
           'account_id' => $account_id
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://".$this->settings['wallet_plaid_env'].".plaid.com/processor/stripe/bank_account_token/create");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($btok_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if(!$result = curl_exec($ch)) {
           //trigger_error(curl_error($ch));
           $this->EE->logger->log_action("NEW BANK ACCOUNT - FAILED ($stripe_bank_account_token) was not added to $member_id ($stripe_customer_id) - Plaid Token Create");
           ee()->functions->redirect($error_return);
        }
        curl_close($ch);
        
        $btok_parsed = json_decode($result, TRUE);
        $stripe_bank_account_token = $btok_parsed['stripe_bank_account_token'];
        $error = "";
        
        try {
          $cu = \Stripe\Customer::retrieve($stripe_customer_id);
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
            try {
                $cu->sources->create(["source" => $stripe_bank_account_token]);
                $cu->save();
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
        }
            
        if ($success == 1) {
            //Send Email
            $method_details = $this->payment_method($stripe_bank_account_token);
            $custom_data = array(
                'payment_method'        => $method_details['payment_brand'].' ****'.$method_details['payment_last4'] 
            );
            $this->send_email('new_payment_method', $member_id, $custom_data);
            
            $this->EE->logger->log_action("NEW BANK ACCOUNT ($stripe_bank_account_token) added to $member_id ($stripe_customer_id)");
            ee()->functions->redirect($return);
        }
	    
	    $this->EE->logger->log_action("NEW BANK ACCOUNT - FAILED ($stripe_bank_account_token) was not added to $member_id ($stripe_customer_id) - ".$error);
	    ee()->functions->redirect($error_return);
	}
	
	public function stripe_new_credit_card_post() {
	    if (ee()->input->get_post('session_id') != '') {
	        $session_id = ee()->input->get_post('session_id');
	        $session = \Stripe\Checkout\Session::retrieve($session_id);
	        
	        $member_id = ee()->session->userdata('member_id');
	        $stripe_customer_id = $this->get_stripe_customer_id();
	        
	        if (isset($session->setup_intent)) {
	            $intent = \Stripe\SetupIntent::retrieve($session->setup_intent);
	            if (isset($intent->payment_method)) {
	                $stripe_card_token = $intent->payment_method;
	                
	                $success = 1;
	                
	                try {
                        $cu = \Stripe\Customer::retrieve($stripe_customer_id);
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
                        try {
                            //$cu->sources->create(["source" => $stripe_card_token]);
                            //$cu->save();
                            $stripe = new \Stripe\StripeClient(
                              $this->stripe_secret_key
                            );
                            $stripe->paymentMethods->attach(
                              $stripe_card_token,
                              ['customer' => $stripe_customer_id]
                            );
                            
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
                    }
                    
                    //$this->dump_and_die(1, $error);
                        
                    if ($success == 1) {
                        //Send Email
                        $method_details = $this->payment_method($stripe_card_token);
                        $custom_data = array(
                            'payment_method'        => $method_details['payment_brand'].' ****'.$method_details['payment_last4'] 
                        );
                        $this->send_email('new_payment_method', $member_id, $custom_data);
                        
                        $this->EE->logger->log_action("NEW CREDIT CARD ($stripe_card_token) added to $member_id ($stripe_customer_id)");
                        ee()->functions->redirect('/payment-methods/cc_success');
                    }
	            }
	        }
	        
	        $this->EE->logger->log_action("NEW CREDIT CARD - FAILED ($session_id) was not added to $member_id ($stripe_customer_id) ".$error);
    	    ee()->functions->redirect('/payment-methods/cc_failed');
	    } else {
    	    $member_id = ee()->session->userdata('member_id');
    	    $return = ee()->input->post('return');
    	    $error_return = ee()->input->post('error_return');
    	    $stripe_card_token = ee()->input->post('stripeToken');
    	    $stripe_customer_id = $this->get_stripe_customer_id();
    	    $success = 1;
    	    $error = '';
    	    
            try {
                $cu = \Stripe\Customer::retrieve($stripe_customer_id);
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
                try {
                    $cu->sources->create(["source" => $stripe_card_token]);
                    $cu->save();
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
            }
                
            if ($success == 1) {
                //Send Email
                $method_details = $this->payment_method($stripe_card_token);
                $custom_data = array(
                    'payment_method'        => $method_details['payment_brand'].' ****'.$method_details['payment_last4'] 
                );
                $this->send_email('new_payment_method', $member_id, $custom_data);
                
                $this->EE->logger->log_action("NEW CREDIT CARD ($stripe_card_token) added to $member_id ($stripe_customer_id)");
                ee()->functions->redirect($return);
            }
    	    
    	    $this->EE->logger->log_action("NEW CREDIT CARD - FAILED ($stripe_card_token) was not added to $member_id ($stripe_customer_id) ".$error);
    	    ee()->functions->redirect($error_return);
	    }
	}
	
	public function token_delete() {
	    $member_id = ee()->session->userdata('member_id');
	    $token = ee()->input->get('token');
	    $return = ee()->input->get('ret');
	    $error_return = ee()->input->get('error');
	    $stripe_customer_id = $this->get_stripe_customer_id();
	    $success = 1;
	    $error = '';
	    
	    $method_details = $this->payment_method($token);
	    $custom_data = array(
            'payment_method'        => $method_details['payment_brand'].' ****'.$method_details['payment_last4'] 
        );
	    
	    try {
            $cu = \Stripe\Customer::retrieve($stripe_customer_id);
            $cu->sources->retrieve($token)->delete();
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
            //Send Email
            $this->send_email('payment_method_deleted', $member_id, $custom_data);
            
            $this->EE->logger->log_action("DELETE PAYMENT METHOD ($token) was removed from $member_id ($stripe_customer_id)");
            ee()->functions->redirect($return);
        }
	    
	    $this->EE->logger->log_action("DELETE PAYMENT METHOD - FAILED ($token) was not deleted from $member_id ($stripe_customer_id) ".$error);
	    ee()->functions->redirect($error_return);
	}
	
	public function token_set_default() {
	    $member_id = ee()->session->userdata('member_id');
	    $token = ee()->input->get('token');
	    $return = ee()->input->get('ret');
	    $error_return = ee()->input->get('error');
	    $stripe_customer_id = $this->get_stripe_customer_id();
	    $success = 1;
	    $error = '';
	    
	    try {
            $response = \Stripe\Customer::update($stripe_customer_id, ["default_source" => $token]);
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
            //Send Email
            $method_details = $this->payment_method($token);
            $custom_data = array(
                'payment_method'        => $method_details['payment_brand'].' ****'.$method_details['payment_last4'] 
            );
            $this->send_email('new_default_payment_method', $member_id, $custom_data);
            
            $this->EE->logger->log_action("NEW DEFAULT PAYMENT METHOD ($token) set for $member_id ($stripe_customer_id)");
            ee()->functions->redirect($return);
        }
	    
	    $this->EE->logger->log_action("NEW DEFAULT PAYMENT METHOD - FAILED ($token) was not set as default for $member_id ($stripe_customer_id) ".$error);
	    ee()->functions->redirect($error_return);
	}
	
	//API Functions
	public function api_add_funds() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['unique_id']) AND $this->body['unique_id'] != '' AND isset($this->body['amount']) AND is_numeric($this->body['amount']) AND $this->body['amount'] > 0) {
	        $unique_id = $this->body['unique_id'];
	        $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $unique_id))->limit(1)->get();
	        $amount = number_format($this->body['amount'], 2, '.', '');
	        if ($query->num_rows() > 0) {
	            $balance = $this->get_wallet_balance($query->row('member_id'));
	            
	            if (is_numeric($balance['balance'])) {
	                $txn = '';
	                $note = '';
	                $location = '';
	                $location_id = 0;
	                $to_member_id = 0;
	                $amount_ilum = $this->convert_amount($amount);
	                
	                if (isset($this->body['transaction_id'])) { $txn = $this->body['transaction_id']; }
	                if (isset($this->body['note'])) { $note = $this->body['note']; }
	                if (isset($this->body['location_name'])) { $location = $this->body['location_name']; }
	                if (isset($this->body['location_id'])) { $location_id = $this->body['location_id']; }
	                if (isset($this->body['to_unique_id'])) { 
	                    $query_to = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $this->body['to_unique_id']))->limit(1)->get();
	                    if ($query_to->num_rows() > 0) {
	                        $to_member_id = $query_to->row('member_id');
	                    }
	                }
	                
	                //Log Transaction
                    ee()->db->insert('ilum_wallet_transactions', 
                        array(
                            'member_id'         => $query->row('member_id'),
                            'type'              => 4,
                            'method'            => '+',
                            'txn'               => 'af_'.$txn,
                            'amount'            => $amount,
                            'amount_ilum'       => $amount_ilum,
                            'title'             => lang('wallet_funds_added'),
                            'txn_desc'          => $note,
                            'location'          => $location,
                            'location_id'       => $location_id,
                            'to_member_id'      => $to_member_id,
                            "funds_added_from"  => 'api',
                            'time'              => time()
                        )
                    );
                    
                    //Update Balance
                    $this->update_balance($amount, '+', $query->row('member_id'));
                    
                    $balance = $this->get_wallet_balance($query->row('member_id'));
                    
	                $data = array();
    	            $data[] = array(
    	                'balance'           => number_format($balance['balance'], 2, '.', ''),
    	                'balance_ilum'      => number_format($balance['balance_ilum'], 2, '.', ''),
    	                'conversion_rate'   => $this->settings['wallet_currency_ratio']
    	            );
    	            
    	            $output = array(
    	                'status'    => 200,
    	                'message'   => lang('success'),
    	                'data'      => $data
    	            );
    	            
    	            return $this->set_output($output);
	            } else {
	                $output = array(
    	                'status'    => 403,
    	                'message'   => lang('wallet_insufficient_funds'),
    	                'data'      => $data
    	            );
    	            
    	            return $this->set_output($output);
	            }
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_balance() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "GET" AND isset($this->body['unique_id'])) {
	        $unique_id = $this->body['unique_id'];
	        $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $unique_id))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $balance = $this->get_wallet_balance($query->row('member_id'));
	            
	            $data = array();
	            $data[] = array(
	                'balance'           => number_format($balance['balance'], 2, '.', ''),
	                'balance_ilum'      => number_format($balance['balance_ilum'], 2, '.', ''),
	                'conversion_rate'   => $this->settings['wallet_currency_ratio']
	            );
	            
	            $output = array(
	                'status'    => 200,
	                'message'   => lang('success'),
	                'data'      => $data
	            );
	            
	            return $this->set_output($output);
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_deduct() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['unique_id']) AND $this->body['unique_id'] != '' AND isset($this->body['amount']) AND is_numeric($this->body['amount']) AND $this->body['amount'] > 0) {
	        $unique_id = $this->body['unique_id'];
	        $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $unique_id))->limit(1)->get();
	        $amount = number_format($this->body['amount'], 2, '.', '');
	        if ($query->num_rows() > 0) {
	            $balance = $this->get_wallet_balance($query->row('member_id'));
	            
	            if ($balance['balance'] >= $amount) {
	                $txn = '';
	                $note = '';
	                $location = '';
	                $location_id = 0;
	                $to_member_id = 0;
	                $amount_ilum = $this->convert_amount($amount);
	                
	                if (isset($this->body['transaction_id'])) { $txn = $this->body['transaction_id']; }
	                if (isset($this->body['note'])) { $note = $this->body['note']; }
	                if (isset($this->body['location_name'])) { $location = $this->body['location_name']; }
	                if (isset($this->body['location_id'])) { $location_id = $this->body['location_id']; }
	                if (isset($this->body['to_unique_id'])) { 
	                    $query_to = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $this->body['to_unique_id']))->limit(1)->get();
	                    if ($query_to->num_rows() > 0) {
	                        $to_member_id = $query_to->row('member_id');
	                    }
	                }
	                
	                //Log Transaction
                    ee()->db->insert('ilum_wallet_transactions', 
                        array(
                            'member_id'         => $query->row('member_id'),
                            'type'              => 2,
                            'method'            => '-',
                            'txn'               => 'df_'.$txn,
                            'amount'            => $amount,
                            'amount_ilum'       => $amount_ilum,
                            'title'             => lang('wallet_ilum_gift'),
                            'txn_desc'          => $note,
                            'location'          => $location,
                            'location_id'       => $location_id,
                            'to_member_id'      => $to_member_id,
                            "funds_added_from" => 'api',
                            'time'              => time()
                        )
                    );
                    
                    //Update Balance
                    $this->update_balance($amount, '-', $query->row('member_id'));
                    
                    $balance = $this->get_wallet_balance($query->row('member_id'));
                    
	                $data = array();
    	            $data[] = array(
    	                'balance'           => number_format($balance['balance'], 2, '.', ''),
    	                'balance_ilum'      => number_format($balance['balance_ilum'], 2, '.', ''),
    	                'conversion_rate'   => $this->settings['wallet_currency_ratio']
    	            );
    	            
    	            $output = array(
    	                'status'    => 200,
    	                'message'   => lang('success'),
    	                'data'      => $data
    	            );
    	            
    	            return $this->set_output($output);
	            } else {
	                $output = array(
    	                'status'    => 403,
    	                'message'   => lang('wallet_insufficient_funds'),
    	                'data'      => $data
    	            );
    	            
    	            return $this->set_output($output);
	            }
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_logout() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['unique_id']) AND $this->body['unique_id'] != '') {
	        $unique_id = $this->body['unique_id'];
	        
	        $query = ee()->db->select('uuid.member_id AS member_id')->from('member_data_field_'.$this->settings['wallet_unique_id_field'].' uuid')->join('member_data_field_'.$this->settings['wallet_app_header_field'].' ah', 'uuid.member_id = ah.member_id', 'left')->where(array('uuid.m_field_id_'.$this->settings['wallet_unique_id_field'] => $unique_id, 'ah.m_field_id_'.$this->settings['wallet_app_header_field'] => 1))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $member_id = $query->row('member_id');
	            ee()->db->delete('sessions', array('member_id' => $member_id));
	            $this->update_member_field($member_id, $this->settings['wallet_app_header_field'], 0);
	            
	            $output = array(
                    'status'    => 200,
                    'message'   => lang('wallet_logout_success'),
                    'data'      => array()
                );
                return $this->set_output($output);
	        }
	        
	        $output = array(
                'status'    => 200,
                'message'   => lang('wallet_no_logout'),
                'data'      => array()
            );
            return $this->set_output($output);
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_match_account() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['email']) AND isset($this->body['password']) AND isset($this->body['birthday']) AND isset($this->body['phone']) AND isset($this->body['accept_terms']) AND $this->body['accept_terms'] == 'y' AND $this->body['address'] AND isset($this->body['first_name']) AND isset($this->body['last_name'])) {
	        $query = ee()->db->select('unique_id')->from('members')->where(array('email' => $this->body['email']))->limit(1)->get();
	        if ($query->num_rows() == 0) {
	            //Age check
	            $birthday = $this->body['birthday']['month'].'/'.$this->body['birthday']['day'].'/'.$this->body['birthday']['year'];
		        $dob = strtotime(str_replace("/","-",$birthday));       
                $tdate = time();
                
                $age = 0;
                while( $tdate > $dob = strtotime('+1 year', $dob)) {
                    $age++;
                }
                
                if ($age >= $this->settings['wallet_minimum_age']) {
                    $member_id = $this->create_member($this->body['email'], $this->body['email'], $this->body['first_name'].' '.$this->body['last_name'], $this->body['password'], array());
                    $query_uid = ee()->db->select('unique_id')->from('members')->where(array('member_id' => $member_id))->limit(1)->get();
                    if ($query_uid->num_rows() > 0) {
                        $data = $this->body;
                        $this->sync_account($data);
                        
                        //Return
                        $member_data = $this->member_details($data['email']);
        	            $output = array(
        	                'status'    => 200,
        	                'message'   => lang('wallet_match_success'),
        	                'data'      => $member_data
        	            );
        	            return $this->set_output($output);
                    }
                } else {
                    $output = array(
                        'status'    => 406,
                        'message'   => lang('wallet_register_not_acceptible'),
                        'data'      => array()
                    );
                    
                    return $this->set_output($output);
                }
	        } else {
	            $data = $this->body;
                $this->sync_account($data, $data['password']);
                
                //Return
                $member_data = $this->member_details($data['email']);
	            $output = array(
	                'status'    => 200,
	                'message'   => lang('wallet_match_success'),
	                'data'      => $member_data
	            );
	            
	            return $this->set_output($output);
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_refund() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['unique_id']) AND $this->body['unique_id'] != '' AND isset($this->body['amount']) AND is_numeric($this->body['amount']) AND $this->body['amount'] > 0) {
	        $unique_id = $this->body['unique_id'];
	        $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $unique_id))->limit(1)->get();
	        $amount = number_format($this->body['amount'], 2, '.', '');
	        if ($query->num_rows() > 0) {
	            $balance = $this->get_wallet_balance($query->row('member_id'));
	            
	            if (is_numeric($balance['balance'])) {
	                $txn = '';
	                $note = '';
	                $location = '';
	                $location_id = 0;
	                $to_member_id = 0;
	                $amount_ilum = $this->convert_amount($amount);
	                
	                if (isset($this->body['transaction_id'])) { $txn = $this->body['transaction_id']; }
	                if (isset($this->body['note'])) { $note = $this->body['note']; }
	                if (isset($this->body['location_name'])) { $location = $this->body['location_name']; }
	                if (isset($this->body['location_id'])) { $location_id = $this->body['location_id']; }
	                if (isset($this->body['to_unique_id'])) { 
	                    $query_to = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $this->body['to_unique_id']))->limit(1)->get();
	                    if ($query_to->num_rows() > 0) {
	                        $to_member_id = $query_to->row('member_id');
	                    }
	                }
	                
	                //Log Transaction
                    ee()->db->insert('ilum_wallet_transactions', 
                        array(
                            'member_id'         => $query->row('member_id'),
                            'type'              => 3,
                            'method'            => '+',
                            'txn'               => 'rf_'.$txn,
                            'amount'            => $amount,
                            'amount_ilum'       => $amount_ilum,
                            'title'             => lang('wallet_refund'),
                            'txn_desc'          => $note,
                            'location'          => $location,
                            'location_id'       => $location_id,
                            'to_member_id'      => $to_member_id,
                            "funds_added_from" => 'api',
                            'time'              => time()
                        )
                    );
                    
                    //Update Balance
                    $this->update_balance($amount, '+', $query->row('member_id'));
                    
                    $balance = $this->get_wallet_balance($query->row('member_id'));
                    
	                $data = array();
    	            $data[] = array(
    	                'balance'           => number_format($balance['balance'], 2, '.', ''),
    	                'balance_ilum'      => number_format($balance['balance_ilum'], 2, '.', ''),
    	                'conversion_rate'   => $this->settings['wallet_currency_ratio']
    	            );
    	            
    	            $output = array(
    	                'status'    => 200,
    	                'message'   => lang('success'),
    	                'data'      => $data
    	            );
    	            
    	            return $this->set_output($output);
	            } else {
	                $output = array(
    	                'status'    => 403,
    	                'message'   => lang('wallet_insufficient_funds'),
    	                'data'      => $data
    	            );
    	            
    	            return $this->set_output($output);
	            }
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_sync_account() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['unique_id']) AND $this->body['unique_id'] != '') {
	        $unique_id = $this->body['unique_id'];
	        $query = ee()->db->select('member_id')->from('member_data_field_'.$this->settings['wallet_unique_id_field'])->where(array('m_field_id_'.$this->settings['wallet_unique_id_field'] => $unique_id))->limit(1)->get();
	        if ($query->num_rows() > 0) {
                $data = $this->body;
                $this->sync_account($data);
                
                $member_id = $query->row('member_id');
                $email = '';
                if (isset($data['email'])) { $email = $data['email']; }
                else {
                    $query_email = ee()->db->select('email')->from('members')->where(array('member_id' => $member_id))->limit(1)->get();
                    if ($query_email->num_rows() > 0) {
                        $email = $query_email->row('email');
                    }
                }
                
                //Return
                $member_data = $this->member_details($email);
	            $output = array(
	                'status'    => 200,
	                'message'   => lang('success'),
	                'data'      => $member_data
	            );
	            return $this->set_output($output);
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	public function api_update_password() {
	    $output = $this->api_endpoint_start();
	    if ($output != '') {
	        return $this->set_output($output);
	    }
	    
	    if ($this->method == "POST" AND isset($this->body['unique_id']) AND isset($this->body['new_password'])) {
	        $query = ee()->db->select('member_id')->from('members')->where(array('unique_id' => $this->body['unique_id']))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $this->update_password($query->row('member_id'), $this->body['new_password']);
	            
	            $output = array(
	                'status'    => 200,
	                'message'   => lang('success'),
	                'data'      => array()
	            );
	            
	            return $this->set_output($output);
	        }
	    }
	    
	    $output = array(
            'status'    => 400,
            'message'   => lang('wallet_bad_request'),
            'data'      => array()
        );
        
        return $this->set_output($output);
	}
	
	//Private Helper Functions
	private function api_endpoint_start() {
		//Authorize API Key and From
		if (ee()->uri->segment(1) != "crons") {
    		$user = '';
    		if (isset($this->headers['From'])) {
    		    $user = $this->headers['From'];
    		}
    		
    		$query = ee()->db->select('id')->from('ilum_wallet_api_keys')->where(array('user' => $user, 'key' => $this->headers['Authorization'], 'active' => 'open'))->limit(1)->get();
    		if ($query->num_rows() == 0) {
    		    $output = array(
                    'status'    => 401,
                    'message'   => lang('unauthorized'),
                    'data'      => array()
                );
                
                return $output;
    		}
		}
		
		return;
	}
	
	private function create_member($email, $username, $screen_name, $password, $member_data, $group_id = 5) {
	    if ($email != '' AND $username != '' AND $screen_name != '' AND $password != '') {
	        //test for conflicts
	        $query = ee()->db->select('member_id')->from('members')->where(array('username' => $username))->limit(1)->get();
	        if ($query->num_rows() == 0) {
                //data for members table
                $pass_data = $this->hash_pass($password);
            
                $password = $pass_data['password'];
                $salt = $pass_data['salt'];
            
                if (isset($this->headers['X-Forwarded-For'])) {
                    $ip_address = $this->headers['X-Forwarded-For'];
                } else if (isset($this->headers['User-Agent'])) {
                    $ip_address = $this->headers['User-Agent'];
                } else {
                    $ip_address = e()->input->ip_address();
                }

                $unique_id = ee()->functions->random('encrypt');
                $join_date = ee()->localize->now;
                
                //create member
                ee()->db->insert(
                    'members',
                    array(
                        'group_id' 		=> $group_id,
                        'username' 		=> $username,
                        'screen_name' 	=> $screen_name,
                        'password'		=> $password,
                        'salt'			=> $salt,
                        'unique_id'		=> $unique_id,
                        'crypt_key'		=> ee()->functions->random('encrypt'),
                        'email' 		=> $email,
                        'join_date'		=> $join_date,
                        'ip_address'    => ee()->input->ip_address(),
                        'accept_messages' => 'y'
                    )
                );
                $member_id = ee()->db->insert_id();
            
                ee()->db->insert('member_data', array('member_id' => $member_id));
                
                //member data fields
                foreach($member_data AS $key => $val) {
                    ee()->db->insert('member_data_field_'.$key, array('member_id' => $member_id, 'm_field_id_'.$key => $val, 'm_field_ft_'.$key => 'none'));
                }
                
                $this->EE->logger->log_action("Created Member $member_id");
	        }
	    }
	    
	    return $member_id;
	}
	
	private function convert_amount($amount, $method = 1) {
	    if (is_numeric($amount)) {
    	    $ratio = $this->settings['wallet_currency_ratio'];
    	    
    	    if ($method == 1) {
    	        return number_format(round(($amount * $ratio) * 100) / 100, 2, '.', '');
    	    } else if ($method == 2) {
    	        return number_format(round(($amount / $ratio) * 100) / 100, 2, '.', '');
    	    }
	    }
	    
	    return;
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
	
	private function hash_pass($password) {
	    ee()->load->library('auth');
	    $pass_data = ee()->auth->hash_password(stripslashes($password));
	    return $pass_data; //returns array
	}
	
	private function member_details($username) {
	    $member_data = array();
	    
	    $query = ee()->db->select('m.unique_id AS unique_id, m.username AS username, m.screen_name AS screen_name, m.email AS email, m.accept_messages AS accept_messages, m.timezone AS timezone, m.time_format AS time_format, m.date_format AS date_format, mfn.m_field_id_'.$this->settings['wallet_first_name_field'].' AS first_name, mln.m_field_id_'.$this->settings['wallet_last_name_field'].' AS last_name, ma.m_field_id_'.$this->settings['wallet_address_field'].' AS address, ma2.m_field_id_'.$this->settings['wallet_address2_field'].' AS address2, mc.m_field_id_'.$this->settings['wallet_city_field'].' AS city, ms.m_field_id_'.$this->settings['wallet_state_field'].' AS state, mz.m_field_id_'.$this->settings['wallet_zip_field'].' AS zip, mct.m_field_id_'.$this->settings['wallet_country_field'].' AS country, mp.m_field_id_'.$this->settings['wallet_phone_field'].' AS phone, mpe.m_field_id_'.$this->settings['wallet_phone_email_field'].' AS phone_email, mbd.m_field_id_'.$this->settings['wallet_birth_day_field'].' AS b_day, mbm.m_field_id_'.$this->settings['wallet_birth_month_field'].' AS b_month, mby.m_field_id_'.$this->settings['wallet_birth_year_field'].' AS b_year')
	        ->from('members m')
	        ->join('member_data_field_'.$this->settings['wallet_first_name_field'].' mfn', 'mfn.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_last_name_field'].' mln', 'mln.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_address_field'].' ma', 'ma.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_address2_field'].' ma2', 'ma2.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_city_field'].' mc', 'mc.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_state_field'].' ms', 'ms.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_zip_field'].' mz', 'mz.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_country_field'].' mct', 'mct.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_phone_field'].' mp', 'mp.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_phone_email_field'].' mpe', 'mpe.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_birth_day_field'].' mbd', 'mbd.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_birth_month_field'].' mbm', 'mbm.member_id = m.member_id', 'left')
	        ->join('member_data_field_'.$this->settings['wallet_birth_year_field'].' mby', 'mfn.member_id = m.member_id', 'left')
	        ->where(array('username' => $username))
	        ->limit(1)
	        ->get();
	        
	    if ($query->num_rows() > 0) {
	        $member_data = array(
	            'unique_id'     => $query->row('unique_id'),
	            'username'      => $query->row('username'),
	            'screen_name'   => $query->row('screen_name'),
	            'email'         => $query->row('email'),
	            'phone'         => $query->row('phone'),
	            'phone_email'   => $query->row('phone_email'),
	            'address'       => array(
	                'street'        => $query->row('address'),
	                'street_2'      => $query->row('address2'),
	                'city'          => $query->row('city'),
	                'region'        => $query->row('state'),
	                'postal_code'   => $query->row('zip'),
	                'country'       => $query->row('country')
	            ),
	            'birthday'      => array(
	                'day'           => $query->row('b_day'),
	                'month'         => $query->row('b_month'),
	                'year'          => $query->row('b_year')
	            ),
	            'time'          => array(
	                'timezone'      => $query->row('timezone'),
	                'time_format'   => $query->row('time_format'),
	                'date_format'   => $query->row('date_format')
	            ),
	            'accept_messages'   => $query->row('accept_messages')
	        );
	    }
	    
	    return $member_data;
	}
	
	private function get_wallet_balance($member_id = '') {
	    if ($member_id == '') { ee()->session->userdata('member_id'); }
	    
	    $output = array(
	        'balance'       => 0.00,
	        'balance_ilum'  => 0.00
	    );
	    
	    $query = ee()->db->select('balance, balance_ilum')->from('ilum_wallet_balance')->where(array('member_id' => $member_id))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        $output['balance'] = $query->row('balance');
	        $output['balance_ilum'] = $query->row('balance_ilum');
	    } else {
	        ee()->db->insert(
                'ilum_wallet_balance',
                array(
                    'member_id'     => $member_id,
                    'balance'       => $output['balance'],
                    'balance_ilum'  => $output['balance_ilum']
                )
            );
	    }
	    
	    return $output;
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
	
	private function payment_method($method = '') {
	    $data = array(
	        'payment_type'      => '',
            'payment_brand'     => '',
            'payment_last4'     => '', 
            'payment_exp'       => '',
            'payment_token'     => '',
            'expired'           => ''
	    );
	    
	    $stripe_customer_id = $this->get_stripe_customer_id();
	    $year = date('Y');
	    $month = date('n');
	    $success = 1;
	    
	    if ($method != '') {
	        //Get Token
    	    try {
              $customer = \Stripe\Customer::retrieve($stripe_customer_id);
              $source = $customer->sources->retrieve($method);
            } catch(\Stripe\Error\Card $e) {
              $success = 0;
            } catch (\Stripe\Error\RateLimit $e) {
              $success = 0;
            } catch (\Stripe\Error\InvalidRequest $e) {
              $success = 0;
            } catch (\Stripe\Error\Authentication $e) {
              $success = 0;
            } catch (\Stripe\Error\ApiConnection $e) {
              $success = 0;
            } catch (\Stripe\Error\Base $e) {
              $success = 0;
            } catch (Exception $e) {
              $success = 0;
            }

            if ($success == 1) {
                if ($source->object == "card") {
                    $brand = $source->brand;
                    $exp = $source->exp_month.'/'.$source->exp_year;
                    if (($year > $source->exp_year) OR ($year == $source->exp_year AND $month > $source->exp_month)) {
                        $expired = 1;
                    } else {
                        $expired = '';
                    }
                } else if ($source->object == "bank_account") {
                    $brand = $source->bank_name;
                    $exp = '';
                    $expired = '';
                }
                    
                $data = array(
                    'payment_type'      => $source->object,
                    'payment_brand'     => $brand,
                    'payment_last4'     => $source->last4, 
                    'payment_exp'       => $exp,
                    'payment_token'     => $source->id,
                    'expired'           => $expired
                );
            }
	    }
	    
	    return $data;
	}
    
    private function request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[implode('-', array_map('ucfirst', explode('-', strtolower($arh_key))))] = $val;
            }
        }
      
        foreach ($arh AS $key => $val) {
            switch ($key) {
                case "Connection":
                case "Accept-Encoding":
                case "Cookie":
                case "Accept":
                case "Host":
                case "Postman-Token":
                    unset($arh[$key]);
                    break;
                default:
                    break;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $arh['Content-Type'] = $_SERVER['CONTENT_TYPE'];
            if ($arh['Content-Type'] == '') {
                $arh['Content-Type'] = 'application/json';
            }
        }
      
        return( $arh );
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
    
    private function set_output($output) {
        //Log Full API Call
        if (sizeof($this->query_string) > 0) {
	        $query_string = '?'.http_build_query($this->query_string);
	    } else {
	        $query_string = '';
	    }
	    
        $call_details = $this->method.' '.$this->endpoint.$query_string."<br>";
        $call_details .= "Host: https://".$this->settings['wallet_env']."wallet.ilum.app<br>";
        foreach($this->headers AS $key => $val) {
            $call_details .= $key.": ".$val."<br>";
        }
        $body = $this->body;
        if (is_array($body) AND sizeof($body) > 0) {
            foreach($body AS $key => $val) {
                if (strpos(strtolower($key), "password") !== false AND $key != "passwordToken") {
                    $body[$key] = "REDACTED";
                }
            }
            $call_details .= "<br>".json_encode($body);
        }
        
        $call_details .= "<br><br>RESPONSE (".$output['status'].")<br>".json_encode($output);

        $this->EE->logger->log_action($call_details);
        
        //Return
        ee('Response')->setHeader('Content-Type: application/json');
        if ((!isset($this->body['only200']) OR $this->body['only200'] != 1) AND isset($output['status'])) { 
            ee('Response')->setStatus($output['status']);
        } else {
            ee('Response')->setStatus('200');
        }
        return json_encode($output);
    }
    
    private function sync_account($data, $password = '') {
        $member_id = '';
        
        if (isset($data['email'])) {
            $data['username'] = $data['email'];
        }
        
        if (isset($data['unique_id'])) {
            $unique_id = $data['unique_id'];
            $query = ee()->db->select('member_id')->from('member_data_field_'.$this->settings['wallet_unique_id_field'])->where(array('m_field_id_'.$this->settings['wallet_unique_id_field'] => $unique_id))->limit(1)->get();
            if ($query->num_rows() == 0) {
                if (isset($data['email'])) {
                    $query_email = ee()->db->select('member_id')->from('members')->where(array('username' => $data['email']))->limit(1)->get();
                    if ($query_email->num_rows() > 0) {
                        $member_id = $query_email->row('member_id');
                    }
                } else {
                    return;
                }
            } else {
                $member_id = $query->row('member_id');
            }
            
            if (is_numeric($member_id)) {
                if ($password != '') {
                    $pass_data = $this->hash_pass($password);
                
                    $password = $pass_data['password'];
                    $salt = $pass_data['salt'];
                    
                    ee()->db->update('members', array('password' => $password, 'salt' => $salt), array('member_id' => $member_id));
                }
                
                foreach($data AS $key => $val) {
                    switch ($key) {
                        case 'unique_id':
                            $this->update_member_field($member_id, $this->settings['wallet_unique_id_field'], $val);
                            break;
                        case 'first_name':
                            $this->update_member_field($member_id, $this->settings['wallet_first_name_field'], $val);
                            break;
                        case 'last_name':
                            $this->update_member_field($member_id, $this->settings['wallet_last_name_field'], $val);
                            break;
                        case 'company':
                            $this->update_member_field($member_id, $this->settings['wallet_company_field'], $val);
                            break;
                        case 'phone':
                            $this->update_member_field($member_id, $this->settings['wallet_phone_field'], $val);
                            break;
                        case 'phone_email':
                            $this->update_member_field($member_id, $this->settings['wallet_phone_email_field'], $val);
                            break;
                        case 'address':
                            $this->update_member_field($member_id, $this->settings['wallet_address_field'], $val['street']);
                            $this->update_member_field($member_id, $this->settings['wallet_address2_field'], $val['street_2']);
                            $this->update_member_field($member_id, $this->settings['wallet_city_field'], $val['city']);
                            $this->update_member_field($member_id, $this->settings['wallet_state_field'], $val['region']);
                            $this->update_member_field($member_id, $this->settings['wallet_zip_field'], $val['postal_code']);
                            $this->update_member_field($member_id, $this->settings['wallet_country_field'], $val['country']);
                            break;
                        case 'birthday':
                            $this->update_member_field($member_id, $this->settings['wallet_birth_day_field'], $val['day']);
                            $this->update_member_field($member_id, $this->settings['wallet_birth_month_field'], $val['month']);
                            $this->update_member_field($member_id, $this->settings['wallet_birth_year_field'], $val['year']);
                            break;
                        case 'time':
                            ee()->db->update('members', array('timezone' => $val[0]['timezone'], 'time_format' => $val[0]['time_format'], 'date_format' => $val[0]['date_format']), array('member_id' => $member_id));
                            break;
                        case 'accept_push':
                            ee()->db->update('members', array('notify_of_pm' => $val), array('member_id' => $member_id));
                            break;
                        case 'password':
                        case 'accept_terms':
                        case 'join_date':
                        case 'last_activity':
                        case 'accept_messages':
                        case 'profile_photo':
                        case 'admin_locations':
                        case 'location_admin':
                        case 'type':
                        case 'send_gifts':
                        case 'gift_location':
                        case 'auto_bank':
                        case 'post_privacy':
                        case 'player_id':
                        case 'home_coordinates':
                        case 'apple_user':
                            break;
                        default:
                            ee()->db->update('members', array($key => $val), array('member_id' => $member_id));
                            break;
                    }
                }
            }
        }
        
        return $member_id;
    }
    
    private function update_balance($amount, $method = "+", $member_id = '') {
        if (is_numeric($amount)) {
            if ($member_id == '') { $member_id = ee()->session->userdata('member_id'); }
            $amount = number_format($amount, 2, '.', '');
            $amount_ilum = number_format($this->convert_amount($amount), 2, '.', '');
            $balance = $this->get_wallet_balance($member_id);
            
            if ($method == "+") {
                $new_balance = number_format($balance['balance'] + $amount, 2, '.', '');
                $new_balance_ilum = number_format($balance['balance_ilum'] + $amount_ilum, 2, '.', '');
            } else if ($method == "-") {
                $new_balance = number_format($balance['balance'] - $amount, 2, '.', '');
                $new_balance_ilum = number_format($balance['balance_ilum'] - $amount_ilum, 2, '.', '');
            }
            
            ee()->db->update(
                'ilum_wallet_balance',
                array(
                    'balance'       => $new_balance,
                    'balance_ilum'  => $new_balance_ilum
                ),
                array(
                    'member_id' => $member_id
                )
            );
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
    
    private function update_password($member_id, $password) {
        $pass_data = $this->hash_pass($password);
            
        $password = $pass_data['password'];
        $salt = $pass_data['salt'];
        
        ee()->db->update('members', array('password' => $password, 'salt' => $salt), array('member_id' => $member_id));
        
        return;
    }
    
    /* API Callers */
	private function api_call($endpoint = '', $method = "GET", $body = array(), $query_string = array(), $headers = array(), $only200 = '0') {
	    require_once("class/api.ilum.php");
	    $api = new ilum_api;
	    return $api->ilumApi($endpoint, $method, $body, $query_string, $headers, $only200);
	}
	public function ick_live_security_key() {
	    $ick_live_security_key = $this->settings['wallet_ick_live_security_key'];
		return $ick_live_security_key;
	}
	public function ick_test_mode() {
	    $ick_test_mode = $this->settings['wallet_ick_test_mode'];
		return $ick_test_mode;
	}
	public function ick_add_card_form() {
	    $return = trim($this->EE->TMPL->fetch_param('return')) ?: '/payment-methods/success_ick';
	    $error_return = trim($this->EE->TMPL->fetch_param('error_return')) ?: '/payment-methods/';
	    $class = trim($this->EE->TMPL->fetch_param('class')) ?: '';
	    $act = $this->action_id('ick_add_card');
	    $output = '<form id="ick_add_new" action="/?ACT='.$act.'" method="POST">
	    <input type="hidden" name="return" value="'.$return.'" />
        <input type="hidden" name="error_return" value="'.$error_return.'" />
        						        <div class="row">
        						            <div class="form-group col-md-6">
                                                <input type="text" class="form-control" id="ick_first_name" name="ick_first_name" placeholder="First Name" maxlength="25" required>
                                            </div> 
                                            <div class="form-group col-md-6">
                                                <input type="text" class="form-control" id="ick_last_name" name="ick_last_name" placeholder="Last Name" maxlength="25" required>
                                            </div>
        						        </div>
        						        <div class="row">
        						            <div class="form-group col-md-12">
                                                <input type="text" class="form-control" id="ick_card_number" name="ick_card_number" placeholder="Credit Card Number" minlength="16" maxlength="16" required>
                                            </div>
        						        </div>
        						        <div class="row">
        						            <div class="form-group col-md-6">
                                                <input type="text" class="form-control" id="ick_card_expiration" name="ick_card_expiration" placeholder="Credit Card Expiration" minlength="4" maxlength="4" required>
                                                <small id="ick_card_expiration" class="form-text text-muted">Format: MMYY</small>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <input type="text" class="form-control" id="ick_card_cvv" name="ick_card_cvv" placeholder="Credit Card CVV" minlength="3" maxlength="4" required>
                                            </div>
        						        </div>
        						        <div class="modal-footer text-right">
            						    <button type="submit" class="btn btn-default btn-lg rippler rippler-inverse">Save</button>
            						  </div>
        						  </form>';
		return $output;
	}
	public function ick_add_card() {
	    $member_id = ee()->session->userdata('member_id');
	    $return = ee()->input->post('return');
	    $error_return = ee()->input->post('error_return');
	    $first_name = ee()->input->post('ick_first_name');
	    $last_name = ee()->input->post('ick_last_name');
	    $card_number = ee()->input->post('ick_card_number');
	    $card_expiration = ee()->input->post('ick_card_expiration');
	    $card_cvv = ee()->input->post('ick_card_cvv');
	    $ick_live_security_key = $this->settings['wallet_ick_live_security_key'];
	    $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://ick.transactiongateway.com/api/transact.php',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array(
              'customer_vault' => 'add_customer',
              'security_key' => $ick_live_security_key,
              'ccnumber' => $card_number,
              'ccexp' => $card_expiration,
              'first_name' => $first_name,
              'last_name' => $last_name
              ),
        ));
        $responsees = curl_exec($curl);
        curl_close($curl);
        if( $responsees ){
            parse_str($responsees, $getArray);
            $responsetext = $getArray['responsetext'];
            if( $getArray['response'] == 1 && $getArray['response_code'] == 100 ){
                $customer_vault_id = $getArray['customer_vault_id'];
                $title = "Success";
                ee()->db->insert(
                    'ick_fields',
                    array(
                        'member_id' 		=> $member_id,
                        'customer_id' 		=> $customer_vault_id
                    )
                );
                ee()->db->insert_id();
            }else{
               $title = "Failed";
            }
        }else{
            $title = "Failed";
            $responsetext = "Invalid Card Details.";
        }
        $data = array(
            'title' => $title,
            'content' => $responsetext,
            'link' => array($return, "Back to site")
        );
        ee()->output->show_message($data); 
	}
}
?>