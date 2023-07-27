<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Monstrous_email_ft extends EE_Fieldtype {

    var $info = array(
        'name'      => 'Monstrous Email Form',
        'version'   => '1.0.0'
    );

    // --------------------------------------------------------------------
    
    function install()
    {
        return array();
    }
    
    function display_settings($data)
    {
        return;
    }

    function display_field($data)
    {
        $options = array();
        
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
        
        if (isset($this->settings['monstrous_email_url']) AND $this->settings['monstrous_email_url'] != '' AND isset($this->settings['monstrous_email_key']) AND $this->settings['monstrous_email_key'] != '') {
            
            $url = $this->settings['monstrous_email_url'].'/admin/api.php?api_key='.$this->settings['monstrous_email_key'].'&api_action=form_getforms&api_output=json';
            
            $ch = curl_init(); 
        	curl_setopt($ch, CURLOPT_URL, $url); 
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        	$output = curl_exec($ch); 
        	curl_close($ch);
            $output = json_decode($output, TRUE);
            
            $options[] = '';

            foreach($output AS $form) {
                if (isset($form['id'])) {
                    $options[$form['id']] = $form['name'].' (Form ID '.$form['id'].')';
                }
		    }
		    
    		return form_dropdown($this->field_name, $options, $data);
        }
        return 'error';
    }
    
    function replace_tag($data, $params = array(), $tagdata = FALSE)
    {
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
        
        if (isset($this->settings['monstrous_email_url']) AND $this->settings['monstrous_email_url'] != '' AND isset($this->settings['monstrous_email_key']) AND $this->settings['monstrous_email_key'] != '' AND is_numeric($data)) {
            
            $url = $this->settings['monstrous_email_url'].'/admin/api.php?api_key='.$this->settings['monstrous_email_key'].'&api_action=form_html&api_output=json&id='.$data;
            
            $ch = curl_init(); 
        	curl_setopt($ch, CURLOPT_URL, $url); 
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        	$output = curl_exec($ch); 
        	curl_close($ch);
        	
            return $output;
        }
        
        return;
    }
    
    public function accepts_content_type($name)
    {
        return ($name == 'channel' || $name == 'grid' || $name == 'fluid_field');
    }
}