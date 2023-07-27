<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/settings_helper.php';

class Hop_pushee_upd
{
	public $version = HOP_PUSHEE_VERSION;

	public function install()
	{
		ee()->load->dbforge();

		//Add module to EE modules list
		$data = array(
			'module_name' => 'Hop_pushee',
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);

		ee()->db->insert('modules', $data);

		// Insert hook
		ee()->db->insert('extensions',
			array(
				'class'		=> 'Hop_pushee_ext',
				'method'	=> 'after_channel_entry_save',
				'hook'		=> 'after_channel_entry_save',
				'settings'	=> serialize(array()),
				'priority'	=> 2,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			)
		);

		//Create module tables
		$fields = array(
			'setting_name'		=> array('type' => 'VARCHAR', 'constraint' => '100'),
			'value'				=> array('type' => 'TEXT')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('setting_name', TRUE);

		ee()->dbforge->create_table('hop_pushee_settings');

		unset($fields);

		$fields = array(
			'local_history_id'	=> array('type' => 'INT', 'constraint' => '10', 'auto_increment' => TRUE),
			'entry_id'			=> array('type' => 'INT', 'constraint' => '10'),
			'date_sent'			=> array('type' => 'INT', 'constraint' => '10'),
			'notification_id'	=> array('type' => 'VARCHAR', 'constraint' => '200'),
			'notes'				=> array('type' => 'TEXT')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('local_history_id', TRUE);

		ee()->dbforge->create_table('hop_pushee_notifications');

		return TRUE;
	}

	public function update($current = '')
	{
		ee()->load->dbforge();

		if (version_compare($current, HOP_PUSHEE_VERSION, '='))
		{
			return FALSE;
		}

		if ($current == '1.0.0')
		{
			// Update the hook
			ee()->db->where('class', 'Hop_pushee_ext');
			ee()->db->update('extensions', array('method' => 'after_channel_entry_save', 'hook' => 'after_channel_entry_save')); 
		}

		return TRUE;
	}

	public function uninstall()
	{
		//Uninstall the module
		ee()->load->dbforge();

		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Hop_pushee'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('modules');

		ee()->db->where('class', 'Hop_pushee_ext');
		ee()->db->delete('extensions');

		//Remove the module tables from the database
		ee()->dbforge->drop_table('hop_pushee_settings');
		ee()->dbforge->drop_table('hop_pushee_notifications');

		return TRUE;
	}

}