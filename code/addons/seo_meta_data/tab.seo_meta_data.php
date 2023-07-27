<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seo_meta_data_tab {

    var $settings = array();
    
    public function __construct() {
        $this->EE = get_instance();
        $this->EE->lang->loadfile('seo_meta_data');
        
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Seo_meta_data'");
        if ($query->row('settings') != FALSE)
        {
            $this->settings = @unserialize($query->row('settings'));
        }
    }

    public function display($channel_id, $entry_id = '') {
        $settings = array();
        
        $revision = ee()->input->get('version');
        if ($revision != '') {
            ee('CP/Alert')->makeInline('seometa_warning')
              ->asWarning()
              ->withTitle(lang('seometa_warning'))
              ->addToBody(lang('seometa_revisions_not_affected'))
              ->now();
        }

        $meta_keywords = $meta_description = $meta_title = $meta_h1 = $meta_h2 = $meta_canon = $meta_robots = '';
        if($entry_id)
        {
            $table_name = 'seo_meta_data_content';
            $where = array(
                 'entry_id' => $entry_id,
                 'site_id'  => $this->EE->config->item('site_id')
             );

            if ($this->EE->extensions->active_hook('seo_meta_data_tab_content') === TRUE)
            {
                $hook_result = $this->return_data = $this->EE->extensions->call('seo_meta_data_tab_content', $where, $table_name);
                if($hook_result && isset($hook_result['where'])) {
                    $where = $hook_result['where'];
                }
                if($hook_result && isset($hook_result['table_name'])) {
                    $table_name = $hook_result['table_name'];
                }

                if ($this->EE->extensions->end_script === TRUE) return;
            }

            $q = $this->EE->db->get_where($table_name, $where);

            if($q->num_rows())
            {
            	$meta_title = $q->row('meta_title');
                $meta_keywords = $q->row('meta_keywords');
                $meta_description = $q->row('meta_description');
                $meta_h1 = $q->row('meta_h1');
                $meta_h2 = $q->row('meta_h2');
                $meta_robots = $q->row('meta_robots');
                $meta_canon = $q->row('meta_canon');
            }
        }
        
        if(isset($this->settings['seo_meta_data_show_title_field']) AND $this->settings['seo_meta_data_show_title_field'] != 'n') {

            $settings['seo_meta_data_title'] = array(
               'field_id' => 'seo_meta_data_title',
               'field_label' => lang('seo_title'),
               'field_required' => 'n',
               'field_data' => $meta_title,
               'field_list_items' => '',
               'field_fmt' => '',
               'field_instructions' => str_replace('MAXLENGTH', $this->settings['seo_meta_title_length'], lang('meta_title_instructions')),
               'field_show_fmt' => 'n',
               'field_fmt_options' => array(),
               'field_pre_populate' => 'n',
               'field_text_direction' => 'ltr',
               'field_type' => 'text',
               'field_maxl' => $this->settings['seo_meta_title_length']
           );
        }

        if(isset($this->settings['seo_meta_data_show_keywords_field']) AND $this->settings['seo_meta_data_show_keywords_field'] != 'n') {

            $settings['seo_meta_data_keywords'] = array(
               'field_id' => 'seo_meta_data_keywords',
               'field_label' => lang('seometa_keywords'),
               'field_required' => 'n',
               'field_data' => $meta_keywords,
               'field_list_items' => '',
               'field_fmt' => '',
               'field_instructions' => lang('meta_keywords_instructions'),
               'field_show_fmt' => 'n',
               'field_fmt_options' => array(),
               'field_pre_populate' => 'n',
               'field_text_direction' => 'ltr',
               'field_type' => 'text',
               'field_maxl' => 255
           );
        }

    	if(isset($this->settings['seo_meta_data_show_description_field']) AND $this->settings['seo_meta_data_show_description_field'] != 'n') {
    		$settings['seo_meta_data_description'] = array(
    		   'field_id' => 'seo_meta_data_description',
    		   'field_label' => lang('seometa_description'),
    		   'field_required' => 'n',
    		   'field_data' => $meta_description,
    		   'field_list_items' => '',
    		   'field_fmt' => '',
    		   'field_instructions' => lang('meta_description_instructions'),
    		   'field_show_fmt' => 'n',
    		   'field_fmt_options' => array(),
    		   'field_pre_populate' => 'n',
    		   'field_text_direction' => 'ltr',
    		   'field_type' => 'text',
                   	   'field_maxl' => 320
    
    	   );
    	
    	}
    	
    	if(isset($this->settings['seo_meta_data_show_h1_field']) AND $this->settings['seo_meta_data_show_h1_field'] != 'n') {
    		$settings['seo_meta_data_h1'] = array(
    		   'field_id' => 'seo_meta_data_h1',
    		   'field_label' => lang('seometa_h1'),
    		   'field_required' => 'n',
    		   'field_data' => $meta_h1,
    		   'field_list_items' => '',
    		   'field_fmt' => '',
    		   'field_instructions' => lang('meta_h1_instructions'),
    		   'field_show_fmt' => 'n',
    		   'field_fmt_options' => array(),
    		   'field_pre_populate' => 'n',
    		   'field_text_direction' => 'ltr',
    		   'field_type' => 'text',
                   	   'field_maxl' => 255
    
    	   );
    	}
    	
    	if(isset($this->settings['seo_meta_data_show_h2_field']) AND $this->settings['seo_meta_data_show_h2_field'] != 'n') {
    		$settings['seo_meta_data_h2'] = array(
    		   'field_id' => 'seo_meta_data_h2',
    		   'field_label' => lang('seometa_h2'),
    		   'field_required' => 'n',
    		   'field_data' => $meta_h2,
    		   'field_list_items' => '',
    		   'field_fmt' => '',
    		   'field_instructions' => lang('meta_h2_instructions'),
    		   'field_show_fmt' => 'n',
    		   'field_fmt_options' => array(),
    		   'field_pre_populate' => 'n',
    		   'field_text_direction' => 'ltr',
    		   'field_type' => 'text',
                   	   'field_maxl' => 255
    
    	   );
    	}

        if(isset($this->settings['seo_meta_data_show_robots_field']) AND $this->settings['seo_meta_data_show_robots_field'] != 'n') {
           $settings['seo_meta_data_robots'] = array(
               'field_id' => 'seo_meta_data_robots',
               'field_label' => lang('seo_robots'),
               'field_required' => 'n',
               'field_data' => $meta_robots,
               'field_list_items' => array('index,follow' => "index,follow", 'noindex,follow' => "noindex,follow", 'index,nofollow' => 'index,nofollow', 'noindex,nofollow' => 'noindex,nofollow'),
               'field_fmt' => '',
               'field_instructions' => lang('meta_robots_instructions'),
               'field_show_fmt' => 'n',
               'field_fmt_options' => array(),
               'field_pre_populate' => 'n',
               'field_text_direction' => 'ltr',
               'field_type' => 'select'
           );
        }
           
        if(isset($this->settings['seo_meta_data_show_canon_field']) AND $this->settings['seo_meta_data_show_canon_field'] != 'n') {
    		$settings['seo_meta_data_canon'] = array(
    		   'field_id' => 'seo_meta_data_canon',
    		   'field_label' => lang('seometa_canon'),
    		   'field_required' => 'n',
    		   'field_data' => $meta_canon,
    		   'field_list_items' => '',
    		   'field_fmt' => '',
    		   'field_instructions' => lang('meta_canon_instructions'),
    		   'field_show_fmt' => 'n',
    		   'field_fmt_options' => array(),
    		   'field_pre_populate' => 'n',
    		   'field_text_direction' => 'ltr',
    		   'field_type' => 'text',
                   	   'field_maxl' => 255
    
    	   );
    	}
           
        //Ping Search Engines
        if (isset($this->settings['seo_meta_data_dev_mode']) AND $this->settings['seo_meta_data_dev_mode'] != "y") {
            // set checked to true if not editing an existing entry
    		$checked = !$entry_id ? TRUE : FALSE;
    
    		$settings['ping_sitemap'] = array(
    				'field_id'		=> 'ping_sitemap',
    				'field_label'		=> 'Sitemap',
    				'field_type'		=> 'checkboxes',
    				'field_list_items'	=> array(lang('ping_search_engines') => lang('ping_search_engines')),
    				'field_required' 	=> 'n',
    				'field_data'		=> ($checked ? lang('ping_search_engines') : ''),
    				'field_pre_populate'	=> 'n',
    				'field_instructions'	=> lang('sitemap_ping_instructions'),
    				'field_text_direction'	=> 'ltr'
    		);
        }
        
        return $settings;
    }

    function validate($channel_entry, $params) {
        return TRUE;
    }

    function save($channel_entry, $params) {
        $site_id = $channel_entry->site_id;
        $entry_id = $channel_entry->entry_id;

        $content = array(
            'site_id' => $site_id,
            'entry_id' => $entry_id,
            'meta_title' => isset($params['seo_meta_data_title']) ? $params['seo_meta_data_title'] : '',
            'meta_keywords' => isset($params['seo_meta_data_keywords']) ? $params['seo_meta_data_keywords'] : '',
            'meta_description' => $params['seo_meta_data_description'],
            'meta_h1' => isset($params['seo_meta_data_h1']) ? $params['seo_meta_data_h1'] : '',
            'meta_h2' => isset($params['seo_meta_data_h2']) ? $params['seo_meta_data_h2'] : '',
            'meta_robots' => isset($params['seo_meta_data_robots']) ? $params['seo_meta_data_robots'] : 'index,follow',
            'meta_canon' => isset($params['seo_meta_data_canon']) ? $params['seo_meta_data_canon'] : '',
        );

        $table_name = 'seo_meta_data_content';
        $where = array(
            'entry_id' => $entry_id,
            'site_id' => $site_id
        );

        $default_where = $where;
        $default_content = $content;
        $default_table_name = $table_name;

        if ($this->EE->extensions->active_hook('seo_meta_data_tab_content_save') === TRUE) {

            $hook_result = $this->return_data = $this->EE->extensions->call('seo_meta_data_tab_content_save', $where, $table_name, $content);
            if($hook_result && isset($hook_result['where'])) {
                $where = $hook_result['where'];
            }
            if($hook_result && isset($hook_result['table_name'])) {
                $table_name = $hook_result['table_name'];
            }
            if($hook_result && isset($hook_result['content'])) {
                $content = $hook_result['content'];
            }

            if ($this->EE->extensions->end_script === TRUE) return;
        }

        $q = $this->EE->db->get_where($table_name, $where);

        if($q->num_rows())
        {
            $this->EE->db->where($where);
            $this->EE->db->update($table_name, $content);
        }
        else
        {
            $this->EE->db->insert($table_name, $content);
        }

        if($table_name != $default_table_name) {
            $q = $this->EE->db->get_where($default_table_name, $default_where);

            if($q->num_rows())
            {
                $this->EE->db->where($default_where);
                $this->EE->db->update($default_table_name, $default_content);
            }
            else
            {
                $this->EE->db->insert($default_table_name, $default_content);
            }
        }
        
        if (isset($params['ping_sitemap']) AND $params['ping_sitemap'] AND (isset($this->settings['seo_meta_data_dev_mode']) AND $this->settings['seo_meta_data_dev_mode'] != "y")) {
    		$this->ping_sitemap();
		}
    }

    function delete($entry_ids) {

        foreach($entry_ids as $i => $entry_id)
        {
            $this->EE->db->where('entry_id', $entry_id);
            $this->EE->db->delete('seo_meta_data_content');
        }
    }
    
    // --------------------------------------------------------------------

	/**
	  *  Ping Sitemap
	  */
	public function ping_sitemap()
	{
		$result = '';

		$results = array();

		$urls = array();

		// google
		$urls['Google'] = "http://www.google.com/webmasters/sitemaps/ping?sitemap=";

		// yahoo - have stopped their sitemap ping service
		//$urls['Yahoo'] = "http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=ee_yahoo_map_update&url=";

		// bing
		$urls['Bing'] = "http://www.bing.com/webmaster/ping.aspx?siteMap=";

		// ask.com
		//$urls['Ask'] = "http://submissions.ask.com/ping?sitemap=";

		// moreover - removed as service seems to be no longer available
		//$urls['Moreover'] = "http://api.moreover.com/ping?u=";


		foreach ($urls as $key => $url)
		{
			$url = $url.ee()->config->slash_item('site_url').$this->settings['seo_meta_data_sitemap_filename'];

			// cURL method
			if (function_exists('curl_init'))
			{
				$results[$key] = $this->_curl_ping($url);
			}

			// fsocket method
			else
			{
				$results[$key] = $this->_socket_ping($url);
			}
		}


		$this->_confirmation_message($results);
	}

	// --------------------------------------------------------------------

	/**
	  *  Return confirmation message
	  */
	private function _confirmation_message($results)
	{
		$success_message = '';
		$failure_message = '';

		foreach ($results as $key => $result)
		{
			if ($result == '1')
			{
				$success_message .= '<b>'.$key.'</b> was successfully notified about this entry<br/>';
			}

			else if ($result == '0')
			{
				$failure_message .= 'An error was encountered while trying to notify <b>'.$key.'</b> about this entry<br/>';
			}
		}

		if ($success_message)
		{
			ee('CP/Alert')->makeInline('sitemap-confirmation-message')
				->withTitle('Sitemap')
      				->addToBody($success_message)
      				->asSuccess()
      				->defer();
		}

		if ($failure_message)
		{
			ee('CP/Alert')->makeInline('sitemap-confirmation-message')
				->withTitle('Sitemap')
      				->addToBody($failure_message )
      				->asWarning()
      				->defer();
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Use the cURL method to send ping
	  */
	private function _curl_ping($url)
	{
		$curl_handle = curl_init($url);
		curl_setopt($curl_handle, CURLOPT_HEADER, TRUE);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($curl_handle);
		curl_close($curl_handle);

		$response_code = trim(substr($response, 9, 4));

		if ($response_code == 200)
		{
			return '1';
		}

		else
		{
			return '0';
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Use the socket method to send ping
	  */
	private function _socket_ping($url)
	{
		$url = parse_url($url);

		if (!isset($url["port"]))
		{
			$url["port"] = 80;
		}

		if (!isset($url["path"]))
		{
			$url["path"] = "/";
		}

		$fp = @fsockopen($url["host"], $url["port"], $errno, $errstr, 30);

		if ($fp)
		{
			$http_request = "HEAD ".$url["path"]."?".$url["query"]." HTTP/1.1\r\n"."Host: ".$url["host"]."\r\n"."Connection: close\r\n\r\n";
			fputs($fp, $http_request);
	  		$response = fgets($fp, 1024);
			fclose($fp);

			$response_code = trim(substr($response, 9, 4));

			if ($response_code == 200)
			{
				return '1';
			}
		}

		return '0';
	}

}

?>