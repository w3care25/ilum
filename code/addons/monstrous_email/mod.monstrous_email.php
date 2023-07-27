<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Monstrous_email {
    
    var $settings;
    
    public function __construct() {
    	$this->EE = get_instance(); // Make a local reference to the ExpressionEngine super object
        
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Monstrous_email'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
        if (!isset($this->settings['monstrous_email_url'])) {
            $this->settings['monstrous_email_url'] = '';
        }
        if (!isset($this->settings['monstrous_email_key'])) {
            $this->settings['monstrous_email_key'] = '';
        }
    }
    
    public function forms() {
        $form_id = trim($this->EE->TMPL->fetch_param('form_id')) ?: '';
        
        if (isset($this->settings['monstrous_email_url']) AND $this->settings['monstrous_email_url'] != '' AND isset($this->settings['monstrous_email_key']) AND $this->settings['monstrous_email_key'] != '') {
            
            $tagdata = $this->EE->TMPL->tagdata;
		    $data = array();
            
            $url = $this->settings['monstrous_email_url'].'/admin/api.php?api_key='.$this->settings['monstrous_email_key'].'&api_action=form_getforms&api_output=json';
            
            $ch = curl_init(); 
        	curl_setopt($ch, CURLOPT_URL, $url); 
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        	$output = curl_exec($ch); 
        	curl_close($ch);
            $output = json_decode($output, TRUE);
            $i = 0;
            
            foreach($output AS $form) {
                if (isset($form['id']) AND ($form_id == '' OR $form_id == $form['id'])) {
                    $i++;
    		        $data[] = array(
                        "monstrous_form_id"     => $form['id'], 
                        "monstrous_form_name"   => $form['name']
                    );
                }
		    }
		    
		    // Construct $variables array for use in parse_variables method
    		$variables = array();
    		$variables[] = $data;
    		
    		if ($i == 0) {
    		    return ee()->TMPL->no_results();
    		} else {
    		    $this->return_data = $this->EE->TMPL->parse_variables( $tagdata, $data );
    		    return $this->return_data;
    		}
        }
        return;
    }
    
    public function form_html() {
        $form_id = trim($this->EE->TMPL->fetch_param('form_id')) ?: '';
        
        if (isset($this->settings['monstrous_email_url']) AND $this->settings['monstrous_email_url'] != '' AND isset($this->settings['monstrous_email_key']) AND $this->settings['monstrous_email_key'] != '' AND is_numeric($form_id)) {
            
            $url = $this->settings['monstrous_email_url'].'/admin/api.php?api_key='.$this->settings['monstrous_email_key'].'&api_action=form_html&api_output=json&id='.$form_id;
            
            $ch = curl_init(); 
        	curl_setopt($ch, CURLOPT_URL, $url); 
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        	$output = curl_exec($ch); 
        	curl_close($ch);
        	
            return $output;
        }
        return;
    }
    
}

?>