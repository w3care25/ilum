<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seo_meta_data_upd {
		
	var $module_name = "Seo_meta_data";

    function __construct() { 
		// Make a local reference to the ExpressionEngine super object
		$this->EE = get_instance();
    } 

    /**
     * Installer for the Seo_meta_data module
     */
    function install() {	
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
		
		$settings = array(
			'seo_meta_data_show_title_field'        => 'y',
			'seo_meta_data_show_keywords_field'     => 'n',
			'seo_meta_data_show_description_field'  => 'y',
			'seo_meta_data_show_h1_field'           => 'n',
			'seo_meta_data_show_h2_field'           => 'n',
			'seo_meta_data_show_robots_field'       => 'y',
			'seo_meta_data_show_canon_field'        => 'y',
			'seo_meta_data_sitemap_filename'        => 'sitemap.xml',
			'seo_meta_data_dev_mode'                => 'n',
			'seo_meta_data_tag_manager_id'          => '',
			'seo_meta_title_length'                 => 75
		);
		
		$addon_info = ee('Addon')->get('seo_meta_data');
		
		$data = array(
			'module_name' 	        => $this->module_name,
			'module_version'        => $addon_info->get('version'),
			'has_cp_backend'        => 'y',
            'has_publish_fields'    => 'y',
            'settings'              => serialize($settings),
		);

		$this->EE->db->insert('modules', $data);		

        $this->EE->load->dbforge();

        $seo_meta_data_content_fields = array(
            'seo_meta_data_content_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,),
            'site_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'null' => FALSE,),
            'entry_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'null' => FALSE,),
            'meta_title' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),          
            'meta_keywords' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'meta_description' => array(
                'type' => 'text',),
            'meta_h1' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'meta_h2' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'meta_robots' => array(
                'type' => 'varchar',
                'constraint' => '32',
                'null' => TRUE),
            'meta_canon' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
        );

        $this->EE->dbforge->add_field($seo_meta_data_content_fields);
        $this->EE->dbforge->add_key('seo_meta_data_content_id', TRUE);
        $this->EE->dbforge->create_table('seo_meta_data_content');

        $this->EE->load->library('layout');
        $this->EE->layout->add_layout_tabs($this->tabs(), 'seo_meta_data');

		return TRUE;
	}

    function tabs() {
        $tabs['seo_meta_data'] = array(
            'seo_meta_data_title'=> array(
                'visible'	=> 'true',
                'collapse'	=> 'false',
                'htmlbuttons'	=> 'false',
                'width'		=> '100%'
                ),
            'seo_meta_data_keywords'=> array(
                'visible'	=> 'true',
                'collapse'	=> 'false',
                'htmlbuttons'	=> 'false',
                'width'		=> '100%'
                ),
            'seo_meta_data_description' => array(
                'visible'	=> 'true',
                'collapse'	=> 'false',
                'htmlbuttons'	=> 'false',
                'width'		=> '100%',
                ),            
            );

        return $tabs;
    }

	function uninstall() { 				
        $this->EE->load->dbforge();
        
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

        $this->EE->dbforge->drop_table('seo_meta_data_content');

        $this->EE->load->library('layout');
        $this->EE->layout->delete_layout_tabs($this->tabs(), 'seo_meta_data');

		return TRUE;
	}

	function update($current = '') {
	    
	    $addon_info = ee('Addon')->get('seo_meta_data');

		if (version_compare($current, $addon_info->get('version'), '=')) {
			return FALSE;
		}
		
		if (version_compare($current, $addon_info->get('version'), '<')) {
			$this->EE->load->dbforge();

            if (ee()->db->field_exists('meta_title', 'seo_meta_data_content') == false) {
		        $fields = array(
		            'meta_title' => array(
		                'type' => 'varchar',
		                'constraint' => '256',
		                'null' => FALSE)
		        );
		        
		        $this->EE->dbforge->add_column('seo_meta_data_content', $fields);
            }
            
            if (ee()->db->field_exists('meta_h1', 'seo_meta_data_content') == false) {
                $fields = array(
		            'meta_h1' => array(
		                'type' => 'varchar',
		                'constraint' => '256',
		                'null' => FALSE)
		        );
		        
		        $this->EE->dbforge->add_column('seo_meta_data_content', $fields);
            }
            
            if (ee()->db->field_exists('meta_h2', 'seo_meta_data_content') == false) {
		        $fields = array(
		            'meta_h2' => array(
		                'type' => 'varchar',
		                'constraint' => '256',
		                'null' => FALSE)
		        );
		        
		        $this->EE->dbforge->add_column('seo_meta_data_content', $fields);
            }
            
            if (ee()->db->field_exists('meta_robots', 'seo_meta_data_content') == false) {
		        $fields = array(
		            'meta_robots' => array(
                    'type' => 'varchar',
                    'constraint' => '32',
                    'null' => TRUE)
		        );
		        
		        $this->EE->dbforge->add_column('seo_meta_data_content', $fields);
            }
            
             if (ee()->db->field_exists('meta_canon', 'seo_meta_data_content') == false) {
		        $fields = array(
		            'meta_canon' => array(
                        'type' => 'varchar',
                        'constraint' => '32',
                        'null' => TRUE)
		        );
		        
		        $this->EE->dbforge->add_column('seo_meta_data_content', $fields);
            }
		}
	
	    return TRUE;
	}

}

?>