<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

class Omg_keymaster {
    public $settings = array();

	public function __construct() {
		$this->EE = get_instance();
		
	   	$query = ee()->db->select('settings')->from('extensions')->where(array('class' => 'Omg_keymaster_ext'))->limit(1)->get();
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
        
	   	$this->EE->load->library('logger');
	}

    public function api() {
        $method = ee()->TMPL->fetch_param('method') ?: 'GET';
        $endpoint = ee()->TMPL->fetch_param('endpoint') ?: '';
        $query_string = ee()->TMPL->fetch_param('query_string') ?: '';
        $body = ee()->TMPL->fetch_param('body') ?: '';
        
        if ($query_string != '') {
	        $query_string = '?'.$query_string;
        }
        
        $body = array();
        
	    $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://gatekeeper.omgdeploy.com/".$endpoint.$query_string,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_POSTFIELDS => $body,
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
        } else {
          if (isset($response['status']) AND $response['status'] = 200) {
              $tagdata = $this->EE->TMPL->tagdata;
              $data = $response['data'];
              return ee()->TMPL->parse_variables($tagdata, $data);
          }
        }
        
        return ee()->TMPL->no_results();
    }
}
?>