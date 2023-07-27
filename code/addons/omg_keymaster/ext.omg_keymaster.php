<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Omg_keymaster_ext {
    
    var $name           = 'OMG Keymaster';
    var $version        = '1.0.0';
    var $description    = 'I am the Keymaster!';
    var $settings_exist = 'y';
    var $docs_url       = 'https://bitbucket.org/omg-monkee/omg-keymaster-ee4-plugin/src/master/';
	
	public function __construct($settings='')
	{
	   	$this->EE = get_instance();
	   	$this->settings = $settings;
	   	$this->EE->load->library('logger');
	}
		 
	function activate_extension() {
	    $site_id = $this->EE->config->item('site_id');
		if($site_id == 0) {
			$site_id = 1;
		}
		
		$addon_info = ee('Addon')->get('omg_keymaster');
		
	    $hooks = array(
			'login_authenticate_start' => 'authenticate'
		);
		
		foreach($hooks as $hook => $method)
		{
			$this->EE->db->insert('extensions', array(
				'class'    => get_class($this),
				'method'   => $method,
				'hook'     => $hook,
				'settings' => '',
				'version'  => $addon_info->get('version'),
				'enabled'  => 'y'
			));
		}
	}
	
	// --------------------------------
	//  Settings
	// --------------------------------
	
	function settings() {
		$settings = array();
		
        $settings['km_site_domain'] = array('i', '', '');
        
        $settings['km_api_key'] = array('i', '', '');

		return $settings;
	}
	
	public function authenticate() {
	    //Grab POST data
	    $username = ee()->input->post('username');
	    $password = ee()->input->post('password');
	    $email = '';
	    
	    //Check for matching user and password
	    $query = ee()->db->select('email')->from('members')->where(array('username' => $username))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        $email = $query->row('email');
	    }
	    
	    $body = array(
	        'username'      => $username,
	        'password'      => $this->simple_crypt($password),
	        'email'         => $email
	    );
	    
	    //Make the Call
	    $response = $this->api_call('authenticate', 'POST', $body);
	    
	    if (isset($response['status'])) {
	        $email = isset($response['data'][0]['email']) ? $response['data'][0]['email'] : '';
	        $username = isset($response['data'][0]['username']) ? $response['data'][0]['username'] : '';
	        $screen_name = isset($response['data'][0]['screen_name']) ? $response['data'][0]['screen_name'] : '';
	        $group_id = isset($response['data'][0]['group_id']) ? $response['data'][0]['group_id'] : '';
	        
	        switch ($response['status']) {
                case 200:
                case 451:
                    $this->update_member($email, $username, $screen_name, $password, $group_id);
                    break;
                case 403:
                    $_POST['password'] = "!";
                    break;
                default:
                    echo "i equals 2";
                    break;
            }
	    }
	    
	    //return back to EE login procedure
	    return;
	}
	
	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 */
	function disable_extension()
	{
	    $this->EE->db->where('class', __CLASS__);
	    $this->EE->db->delete('extensions');
	}
	
	/* Update Extension */
	function update_extension($current = '') {

		if (version_compare($current, $this->version, '=')) {
			return FALSE;
		}
		
		if (version_compare($current, $this->version, '<')) {
		   
		}
	
	    return TRUE;
	}
	
	/* Private Helper Functions */
	private function api_call($endpoint = '', $method = "POST", $body = array(), $query_string = array()) {
	    $query_string = '?'.http_build_query($query_string);
	    
	    $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://gatekeeper.omgdeploy.com/".$endpoint.$query_string,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_POSTFIELDS => json_encode($body),
          CURLOPT_HTTPHEADER => array(
            "Authorization: ".$this->settings['km_api_key'],
            "Content-Type: application/json",
            "From: ".$this->settings['km_site_domain'],
            "cache-control: no-cache"
          ),
        ));
        
        try {
            $response = json_decode(curl_exec($curl), TRUE);
        } catch (Exception $e) {
            return;
        }
        
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
          return;
        } else if (isset($response['status']) AND $response['status'] == 401) {
          echo json_encode($response);
          die();
        } else {
          return $response;
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
	
	private function simple_crypt( $string, $action = 'e' ) {
        // you may change these values to your own
        $secret_key = 'I_@m_th3_G@t3k33p3r!';
        $secret_iv = 'I_@m_th3_K3ym@st3r!';
     
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
     
        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }
     
        return $output;
    }
	
	private function update_member($email, $username, $screen_name, $password, $group_id=6) {
	    $member_id = '';
	    if ($email != '' AND $username != '' AND $screen_name != '' AND $password != '') {
	        //Editors group
	        if ($group_id == 6) {
	            $query = ee()->db->select('group_id')->from('member_groups')->where(array('group_title' => 'Editors'))->limit(1)->get();
	            if ($query->num_rows() > 0) {
	                $group_id = $query->row('group_id');
	            } else {
	                $group_id = 1; //Fail up? Let them in anyway
	            }
	        }
	        
	        //test for existing member
	        $query = ee()->db->select('member_id')->from('members')->where(array('username' => $username))->limit(1)->get();
	        if ($query->num_rows() > 0) {
	            $member_id = $query->row('member_id');
	        } else {
	            $query = ee()->db->select('member_id')->from('members')->where(array('email' => $email))->limit(1)->get();
	            if ($query->num_rows() > 0) {
	                $member_id = $query->row('member_id');
	            }
	        }
	        
	        if (is_numeric($member_id)) { //Update
	            //data for members table
                $pass_data = $this->hash_pass($password);
                
                //update member
                ee()->db->update(
                    'members',
                    array(
                        'group_id' 		=> $group_id,
                        'username' 		=> $username,
                        'screen_name' 	=> $screen_name,
                        'password'		=> $pass_data['password'],
                        'salt'			=> $pass_data['salt'],
                        'email' 		=> $email,
                        'ip_address'    => ee()->input->ip_address()
                    ),
                    array(
                        'member_id'     => $member_id
                    )
                );
	        } else { //Insert
                //data for members table
                $pass_data = $this->hash_pass($password);
                
                //create member
                ee()->db->insert(
                    'members',
                    array(
                        'group_id' 		=> $group_id,
                        'username' 		=> $username,
                        'screen_name' 	=> $screen_name,
                        'password'		=> $pass_data['password'],
                        'salt'			=> $pass_data['salt'],
                        'unique_id'		=> ee()->functions->random('encrypt'),
                        'crypt_key'		=> ee()->functions->random('encrypt'),
                        'email' 		=> $email,
                        'join_date'		=> ee()->localize->now,
                        'ip_address'    => ee()->input->ip_address()
                    )
                );
                $member_id = ee()->db->insert_id();
            
                ee()->db->insert('member_data', array('member_id' => $member_id));
	        }
	    }
	    
	    return $member_id;
	}
}