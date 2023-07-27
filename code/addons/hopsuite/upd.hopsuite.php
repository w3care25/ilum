<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hopsuite/helper.php';

class hopsuite_upd
{
	var $version = HOPSUITE_VERSION;

	function install()
	{
		ee()->load->dbforge();

		//Add module to EE modules list
		$data = array(
		   'module_name' => 'Hopsuite',
		   'module_version' => $this->version,
		   'has_cp_backend' => 'y',
		   'has_publish_fields' => 'n'
		);

		ee()->db->insert('modules', $data);

		//Create module tables
		//As usual, we need a table for the settings as EE doesn't have one...
		$fields = array(
			'setting_name'		=> array('type' => 'VARCHAR', 'constraint' => '100'),
			'value' 			=> array('type' => 'TEXT')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('setting_name', TRUE);

		ee()->dbforge->create_table('hopsuite_settings');

		//Save settings (the default ones will be stored)
		Hopsuite_helper::save_settings();

		return TRUE;
	}

	function update($current = '')
	{
		ee()->load->dbforge();

		if (version_compare($current, '1.1.3', '='))
		{
			return FALSE;
		}

		/*
		if (version_compare($current, '2.0', '<'))
		{
			// Do your update code here
		}
		*/

		return TRUE;
	}

	function uninstall()
	{
		//Uninstall the module
		ee()->load->dbforge();

		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Hopsuite'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('modules');

		//Remove the module tables from the database
    	ee()->dbforge->drop_table('hopsuite_settings');

		return TRUE;
	}
}
