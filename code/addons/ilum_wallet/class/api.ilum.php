<?php
class ilum_api {
    public function __construct() {
		$this->EE = get_instance();
        $this->EE->load->library('logger');
        
        //Settings
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Ilum_wallet'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
	}
	
    public function ilumApi($endpoint = '', $method = "GET", $body = array(), $query_string = array(), $headers = array(), $only200 = '0') {
        //Settings
        $settings = array();
    	$query = $this->EE->db->select('settings')->from('modules')->where(array('module_name' => 'Ilum_wallet'))->limit(1)->get();
    	$settings_results = $query->result_array();
        if ($query->row('settings') != FALSE) {
            $settings = @unserialize($settings_results[0]['settings']);
        }
        
	    $curl = curl_init();
	    
	    $headers_output = array(
	        "Authorization: ".$settings['wallet_ilum_api_key'],
	        "X-Forwarded-For:".$_SERVER['REMOTE_ADDR'],
	        "User-Agent:".$_SERVER['REMOTE_ADDR'],
            "Content-Type: application/json",
            "Cache-Control: no-cache",
            "From: ".$settings['wallet_ilum_api_user']
	    );
	    
	    foreach ($headers AS $key => $val) {
	        $headers_output[] = $key.": ".$val;
	    }
	    
	    if (sizeof($query_string) > 0) {
	        $query_string = '?'.http_build_query($query_string);
	    } else {
	        $query_string = '';
	    }

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$this->settings['wallet_env']."api.ilum.app/v1/".$endpoint.$query_string,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_POSTFIELDS => json_encode($body),
          CURLOPT_HTTPHEADER => $headers_output
        ));
        
        $call_details = '';
        if ($only200 == 1) {
            try {
                $response = json_decode(curl_exec($curl), TRUE);
            } catch (Exception $e) {
                $call_details .= "Exception Occurred: ".$e."<br><br>";
                return $response;
            }
	    } else {
	        $response = json_decode(curl_exec($curl), TRUE);
	    }
        
        $err = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        //Log Full API Call
        $call_details .= $method." v1/".$endpoint.$query_string."<br>";
        $call_details .= "Host: https://".$this->settings['wallet_env']."api.ilum.app<br>";
        foreach($headers_output AS $val) {
            $call_details .= $val."<br>";
        }
        if (is_array($body) AND sizeof($body) > 0) {
            foreach($body AS $key => $val) {
                if (strpos(strtolower($key), "password") !== false AND $key != "passwordToken") {
                    $body[$key] = "REDACTED";
                }
            }
            $call_details .= "<br>".json_encode($body);
        }
        
        $call_details .= "<br><br>RESPONSE (".$status.")<br>".json_encode($response);
        if ($err != '') {
            $call_details .= "<br><br>CURL ERROR: ".$err;
        }

        $this->EE->logger->log_action("Ilum API Call: <br><br>".$call_details);
        
        return $response;
	}
	
	//Private Helper Functions
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

        $arh['Content-Type'] = $_SERVER['CONTENT_TYPE'];
      
        return( $arh );
    }
}
?>