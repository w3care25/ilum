<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Monstrous_email_upd {
		
	var $module_name = "Monstrous_email";

    function __construct()
    { 
		// Make a local reference to the ExpressionEngine super object
		$this->EE = get_instance();
    } 

    function install() 
	{	
		$site_id = $this->EE->config->item('site_id');
		if($site_id == 0) {
			$site_id = 1;
		}
		
		if (ee()->db->field_exists('settings', 'modules') == false) {
		    ee()->load->dbforge();
		    
	        $fields = array(
	            'settings' => array(
                'type' => 'text',
                'null' => 'No')
	        );
	        
	        $this->EE->dbforge->add_column('modules', $fields);
        }
		
		$addon_info = ee('Addon')->get('monstrous_email');
		
		$data = array(
			'module_name' 	        => $this->module_name,
			'module_version'        => $addon_info->get('version'),
			'has_cp_backend'        => 'y',
            'has_publish_fields'    => 'n',
            'settings'              => ''
		);

		$this->EE->db->insert('modules', $data);		

		return TRUE;
	}
	
	function uninstall() 
	{ 				
        $this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->module_name));
		
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
		
		$this->EE->db->where('module_name', $this->module_name);
		$this->EE->db->delete('modules');
		
		$this->EE->db->where('class', $this->module_name);
		$this->EE->db->delete('actions');
		
		$this->EE->db->where('class', $this->module_name.'_mcp');
		$this->EE->db->delete('actions');

		return TRUE;
	}
	
	function update($current = '') {
        $addon_info = ee('Addon')->get('monstrous_email');
        
		if (version_compare($current, $addon_info->get('version'), '=')) {
			return FALSE;
		}
		
		if (version_compare($current, $addon_info->get('version'), '<')) {
			
		}
	
	    return TRUE;
	}

}

?>