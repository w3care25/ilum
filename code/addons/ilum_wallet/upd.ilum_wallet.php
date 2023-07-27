<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ilum_wallet_upd {
		
	var $module_name = "Ilum_wallet";
	var $version = "1.1.0";

    function __construct()
    { 
		$this->EE = get_instance();
    } 

    function install() 
	{	
		$site_id = $this->EE->config->item('site_id');
		if($site_id == 0) {
			$site_id = 1;
		}
		
		$addon_info = ee('Addon')->get('ilum_wallet');
		
		$data = array(
			'module_name' 	        => $this->module_name,
			'module_version'        => $addon_info->get('version'),
			'has_cp_backend'        => 'y',
            'has_publish_fields'    => 'n'
		);

		$this->EE->db->insert('modules', $data);	
		
		$this->EE->load->dbforge();
		
		$fields = array(
			'id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE,
                'auto_increment' => TRUE),
            'user' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'key' => array(
                'type' => 'varchar',
                'constraint' => '64',
                'null' => FALSE),
			'active' => array(
                'type' => 'varchar',
                'constraint' => '16',
                'null' => FALSE,
                'default' => 'open')
        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('id', TRUE);
        $this->EE->dbforge->create_table('ilum_wallet_api_keys');
        
        $fields = array(
			'member_id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE,
                'auto_increment' => FALSE),
            'balance' => array(
                'type' => 'decimal',
                'constraint' => '11,2',
                'null' => FALSE),
            'balance_ilum' => array(
                'type' => 'int',
                'constraint' => '11,2',
                'null' => FALSE)
        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('member_id', TRUE);
        $this->EE->dbforge->create_table('ilum_wallet_balance');
        
        $fields = array(
			'id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE,
                'auto_increment' => TRUE),
            'pi_id' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'client_secret' => array(
                'type' => 'varchar',
                'constraint' => '512',
                'null' => FALSE),
            'member_id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE),
            'full_return' => array(
                'type' => 'text'),
            'full_complete' => array(
                'type' => 'text'),
            'amount' => array(
                'type' => 'decimal',
                'constraint' => '7,2',
                'null' => FALSE),
            'amount_ilum' => array(
                'type' => 'decimal',
                'constraint' => '7,2',
                'null' => FALSE),
            'status' => array(
                'type' => 'int',
                'constraint' => '1',
                'unsigned' => TRUE),
            'time' => array(
                'type' => 'int',
                'constraint' => '32',
                'unsigned' => TRUE)
        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('id', TRUE);
        $this->EE->dbforge->create_table('ilum_wallet_payment_intents');
        
        $fields = array(
			'id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE,
                'auto_increment' => TRUE),
            'member_id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE),
            'type' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE),
            'method' => array(
                'type' => 'varchar',
                'constraint' => '1',
                'null' => FALSE),
            'txn' => array(
                'type' => 'varchar',
                'constraint' => '128',
                'null' => FALSE),
            'amount' => array(
                'type' => 'decimal',
                'constraint' => '7,2',
                'null' => FALSE),
            'amount_ilum' => array(
                'type' => 'decimal',
                'constraint' => '7,2',
                'null' => FALSE),
			'title' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'txn_desc' => array(
                'type' => 'varchar',
                'constraint' => '512',
                'null' => FALSE),
            'location' => array(
                'type' => 'varchar',
                'constraint' => '128',
                'null' => FALSE),
            'location_id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE),
            'to_member_id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE),
            'funds_added_from' => array(
                'type' => 'varchar',
                'constraint' => '16',
                'null' => FALSE),
            'time' => array(
                'type' => 'int',
                'constraint' => '32',
                'unsigned' => TRUE)
        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('id', TRUE);
        $this->EE->dbforge->create_table('ilum_wallet_transactions');
        
        $fields = array(
            'id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE,
                'auto_increment' => TRUE),
            'short_name' => array(
                'type' => 'varchar',
                'constraint' => '128',
                'null' => FALSE),
            'html' => array(
                'type' => 'int',
                'constraint' => '1',
                'null' => FALSE),
            'subject' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'template' => array(
                'type' => 'text',
                'null' => FALSE)
        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('id', TRUE);
        $this->EE->dbforge->create_table('ilum_wallet_email_templates');
        
        ee()->db->insert('ilum_wallet_email_templates', array('short_name' => 'funds_added'));
        ee()->db->insert('ilum_wallet_email_templates', array('short_name' => 'new_payment_method'));
        ee()->db->insert('ilum_wallet_email_templates', array('short_name' => 'payment_method_deleted'));
        ee()->db->insert('ilum_wallet_email_templates', array('short_name' => 'new_default_payment_method'));
        ee()->db->insert('ilum_wallet_email_templates', array('short_name' => 'my_settings_updated'));
        
        $fields = array(
            'id' => array(
                'type' => 'int',
                'constraint' => '11',
                'unsigned' => TRUE,
                'auto_increment' => TRUE),
            'member_id' => array(
                'type' => 'int',
                'constraint' => '11',
                'null' => FALSE),
            'to_name' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'to_email' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'from_name' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'from_email' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'reply_to_email' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'html' => array(
                'type' => 'int',
                'constraint' => '1',
                'null' => FALSE),
            'subject' => array(
                'type' => 'varchar',
                'constraint' => '256',
                'null' => FALSE),
            'message' => array(
                'type' => 'text',
                'null' => FALSE),
            'errors' => array(
                'type' => 'text',
                'null' => FALSE),
            'timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('id', TRUE);
        $this->EE->dbforge->create_table('ilum_wallet_email_logs');
        
        $hooks = array(
            'user_register_end',
            'user_edit_end',
            'sm_after_social_login'
		);
		
		foreach($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array(
				'class'    => $module_name.'_ext',
				'method'   => $hook,
				'hook'     => $hook,
				'settings' => serialize(array()),
				'version'  => $this->version,
				'enabled'  => 'y'
			));
		}
		
		$data = array(
		   'class'     => $this->module_name,
		   'method'    => 'stripe_new_credit_card_post'
		);
		ee()->db->insert('actions', $data);
		
		$data = array(
		   'class'     => $this->module_name,
		   'method'    => 'plaid_new_bank_account_post'
		);
		ee()->db->insert('actions', $data);
		
		$data = array(
		   'class'     => $this->module_name,
		   'method'    => 'token_set_default'
		);
		ee()->db->insert('actions', $data);
		
		$data = array(
		   'class'     => $this->module_name,
		   'method'    => 'token_delete'
		);
		ee()->db->insert('actions', $data);
		
		$data = array(
		   'class'     => $this->module_name,
		   'method'    => 'add_funds_post'
		);
		ee()->db->insert('actions', $data);
		$data = array(
		   'class'     => $this->module_name,
		   'method'    => 'paypal_ipn',
		   'exempt'    => 1
		);
		ee()->db->insert('actions', $data);
        
        $data = array(
		   'class'     => $this->module_name,
		   'method'    => 'ick_add_card'
		);
		ee()->db->insert('actions', $data);
        
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
		
		$this->EE->db->where('class', $this->module_name.'_ext');
		$this->EE->db->delete('extensions');
		
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('ilum_wallet_api_keys');
		$this->EE->dbforge->drop_table('ilum_wallet_balance');
		$this->EE->dbforge->drop_table('ilum_wallet_transactions');
		$this->EE->dbforge->drop_table('ilum_wallet_email_templates');
		$this->EE->dbforge->drop_table('ilum_wallet_email_logs');

		return TRUE;
	}
	
	function update($current = '') {
        $addon_info = ee('Addon')->get('ilum_wallet');
        
		if (version_compare($current, $addon_info->get('version'), '=')) {
			return FALSE;
		}
		
		if (version_compare($current, $addon_info->get('version'), '<')) {
			
		}
	
	    return TRUE;
	}

}

?>