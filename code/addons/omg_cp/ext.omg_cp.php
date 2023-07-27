<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * OMG CP
 * 
 */
 
class Omg_cp_ext {
	
	var $version = '4.0.3';
			
	public function __construct($settings='')
	{
	   	$this->EE = get_instance();
	   	$this->settings = $settings;
	}
		 
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 */
	function activate_extension()
	{   
	    $hooks = array(
			'after_channel_entry_update',
			'cp_js_end',
			'cp_css_end',
			'after_channel_entry_delete',
			'after_channel_entry_insert',
			'cp_members_member_delete_end',
			'template_post_parse'
		);
		
		foreach($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array(
				'class'    => get_class($this),
				'method'   => $hook,
				'hook'     => $hook,
				'settings' => '',
				'version'  => $this->version,
				'enabled'  => 'y'
			));
		}
	}
	
	// --------------------------------
	//  Settings
	// --------------------------------
	
	function settings()
	{
		$settings = array();
		
		$groups = array();
		$query = ee()->db->select('group_id, group_title')->from('member_groups')->where(array('group_id !=' => 1, 'can_access_cp' => 'y'))->order_by('group_id', 'asc')->get();
		
		foreach($query->result_array() AS $row) {
		    $groups[$row['group_id']] = $row['group_title'];
		}
		
        $settings['admin_edit_btn'] = array('c', $groups, array());
        
        $settings['admin_edit_btn_template'] = array('t', array('rows' => '20'), '');
        
        $settings['alert_template'] = array('t', array('rows' => '20'), '');
        
        $this->loadCodeMirrorAssets('textarea');

		return $settings;
	}
	
	/**
	 * after_channel_entry_update ext hook
	 */
	function after_channel_entry_update($entry, $values, $modified)
	{		
		$entry_id = $entry->entry_id;
		$channel_id = $entry->channel_id;
		$member_id = ee()->session->userdata('member_id');
		
		$query = ee()->db->select('version_id, version_date, version_data')->from('entry_versioning')->where(array('entry_id' => $entry_id))->limit(1)->order_by('version_id', 'desc')->get();
		if ($query->num_rows() > 0) {
			$version_id = $query->row('version_id');
			$version_date = $query->row('version_date');
			$version_data = $query->row('version_data');
			
			//ee()->db->delete('entry_versioning', array('entry_id' => $entry_id, 'version_date' => $version_date, 'version_id !=' => $version_id));
			
			//ee()->db->update('entry_versioning', array('author_id' => $member_id), array('version_id' => $version_id));
			
			$this->EE->load->library('logger');
			$this->EE->logger->log_action("Edited entry $entry_id from channel $channel_id");
		}
		
	}
	
		/**
	 * cp_js_end ext hook
	 */
	function cp_js_end()
	{
	    $js = ee()->extensions->last_call ?: '';
	    
		$js .= "$('.app-support').html('<a href=\"https://www.omahamediagroup.com/support\" rel=\"external\" target=\"_blank\">Support</a> <b class=\"sep\">&middot;</b> <a href=\"mailto:support@omahamediagroup.com\">New Ticket</a> <b class=\"sep\">&middot;</b> <a href=\"/welcome\" rel=\"external\" target=\"_blank\">User Guide</a>');";
		$js .= "$('.app-footer__license').html('<a href=\"https://www.omahamediagroup.com\" target=\"_blank\" rel=\"external\"><img src=\"https://v4wu8f00-a.akamaihd.net/omg/welcome-page/omg-monster.png\" alt=\"Omaha Media Group\" title=\"Omaha Media Group\" width=\"50px\" height=\"50px\"></a>');";
		$js .= "document.body.addEventListener(
		    'load',
		    function(event){
		        var elm = event.target;
		        if( elm.tagName === 'IMG') { // or any other filtering condition
		            $('.modal-file .filepicker-item img').each(function() {
		            	var title = $(this).prop('alt');
		            	if ($(this).closest('td').find('label').length) { } else {
		            		$(this).closest('.filepicker-item').after('<label>'+title+'</label>');
		            	}
		            });
		        }
		    },
		    true // Capture event
		);";
		return $js;
	}
	
	/**
	 * cp_css_end ext hook
	 */
	function cp_css_end()
	{
	    $css = ee()->extensions->last_call ?: '';
		
		/*$css .= ".nav-main .nav-sub-menu ul, .nav-custom .nav-sub-menu ul { width: 200px !important; }";*/
		$css .= ".modal-file label { font-size: 9px; max-width: 158px; display: block; overflow: hidden; padding-top: 5px; margin: 0 auto; }";
		return $css;
	}
	
	/**
	 * entry_submission_absolute_end
	 *
	 * @param entry_id
	 * @param meta
	 * @return void
	 */
	public function after_channel_entry_insert($entry, $values)
	{
		$entry_id = $entry->entry_id;
		$channel_id = $entry->channel_id;
		
		$this->EE->load->library('logger');
		$this->EE->logger->log_action("Created entry $entry_id from channel $channel_id");
	}
	// ----------------------------------------------------------------------
	
	/**
	 * delete_entries_loop
	 *
	 * @param val
	 * @param channel_id
	 * @return void
	 */
	public function after_channel_entry_delete($entry, $values)
	{
		$entry_id = $entry->entry_id;
		$channel_id = $entry->channel_id;
		
		$this->EE->load->library('logger');
		$this->EE->logger->log_action("Deleted entry $entry_id from channel $channel_id");
	}
	
	//cp_members_member_delete_end
	public function cp_members_member_delete_end($member_ids)
	{
		foreach($member_ids AS $member_id) {
			$this->EE->load->library('logger');
			$this->EE->logger->log_action("Deleted user $member_id");
		}
		
	}
	
	public function template_post_parse($final_template, $is_partial, $site_id) {
	    $is_ajax = '';
	    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	        $is_ajax = $_SERVER['HTTP_X_REQUESTED_WITH'];
	    }
	    
	    if ($is_partial === FALSE AND ee()->session->userdata('group_id') == 1 AND $is_ajax != "XMLHttpRequest") {
	        echo $is_ajax;
	        $final_template .= "<input type='hidden' id='admin-debug-server-ip' value='".$this->EE->input->server('SERVER_ADDR')."' />\n";
	        $final_template .= "<input type='hidden' id='admin-debug-server-name' value='".gethostname()."' />\n";
	    }
	    
	    return $final_template;
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
		    $query = ee()->db->select('extension_id')->from('extensions')->where(array('class' => __CLASS__, 'hook' => 'cp_members_member_delete_end'))->limit(1)->get();
		    if ($query->num_rows() == 0) {
    			$hooks = array(
    				'cp_members_member_delete_end'		
    			);
    			
    			foreach($hooks as $hook)
    			{
    				$this->EE->db->insert('extensions', array(
    					'class'    => get_class($this),
    					'method'   => $hook,
    					'hook'     => $hook,
    					'settings' => serialize($this->settings),
    					'version'  => $this->version,
    					'enabled'  => 'y'
    				));
    			}
    			
    			ee()->db->where('class', __CLASS__);
    			ee()->db->update(
    			        'extensions',
    			        array('version' => $this->version)
    			);
		    }
			
			ee()->db->delete('extensions', array('class' => __CLASS__, 'hook' => 'freeform_module_admin_notification'));
			ee()->db->delete('extensions', array('class' => __CLASS__, 'hook' => 'freeform_module_user_notification'));
			ee()->db->delete('extensions', array('class' => __CLASS__, 'hook' => 'freeform_recipient_email'));
			
			//Template_post_parse hook add (4.0.3)
			$query = ee()->db->select('extension_id')->from('extensions')->where(array('class' => __CLASS__, 'hook' => 'template_post_parse'))->limit(1)->get();
			if ($query->num_rows() == 0) {
			    $hooks = array(
    				'template_post_parse'		
    			);
    			
    			foreach($hooks as $hook)
    			{
    				$this->EE->db->insert('extensions', array(
    					'class'    => get_class($this),
    					'method'   => $hook,
    					'hook'     => $hook,
    					'settings' => serialize($this->settings),
    					'version'  => $this->version,
    					'enabled'  => 'y'
    				));
    			}
    			
    			ee()->db->where('class', __CLASS__);
    			ee()->db->update(
    			        'extensions',
    			        array('version' => $this->version)
    			);
			}
		}
	
	        return TRUE;
	}
	
	protected function loadCodeMirrorAssets($selector = 'template_data')
	{
		ee()->javascript->set_global(
			'editor.lint', $this->_get_installed_plugins_and_modules()
		);

		$height = '250px';

		if ($height !== FALSE)
		{
			ee()->javascript->set_global(
				'editor.height', $height
			);
		}

		ee()->cp->add_to_head(ee()->view->head_link('css/codemirror.css'));
		ee()->cp->add_to_head(ee()->view->head_link('css/codemirror-additions.css'));
		ee()->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'ui'		=> 'resizable',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/lint',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',
				)
			)
		);
		ee()->javascript->output("$('".$selector."').toggleCodeMirror();");
	}

	/**
	 *  Returns installed module information for CodeMirror linting
	 */
	private function _get_installed_plugins_and_modules()
	{
		$addons = array_keys(ee('Addon')->all());

		$modules = ee('Model')->get('Module')->all()->pluck('module_name');
		$plugins = ee('Model')->get('Plugin')->all()->pluck('plugin_package');

		$modules = array_map('strtolower', $modules);
		$plugins = array_map('strtolower', $plugins);
		$installed = array_merge($modules, $plugins);

		return array(
			'available' => $installed,
			'not_installed' => array_values(array_diff($addons, $installed))
		);
	}
	
}