<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class wcloner_upd {

	public $version = '1.1.0';

	public function __construct()
	{
	}

	// ----------------------------------------------------------------

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	function install()
	{
		$data = array(
		   'module_name' => 'wcloner',
		   'module_version' => $this->version,
		   'has_cp_backend' => 'n',
		   'has_publish_fields' => 'n'
		);

		ee()->db->insert('modules', $data);

		$data = array(
   			'class'     => 'wcloner' ,
   			'method'    => 'clone_entry',
   			'csrf_exempt' => 0
		);
		ee()->db->insert('actions', $data);


		return TRUE;
	}


	function uninstall()
	{
		ee()->db->delete('modules', array('module_name' => 'wcloner'));
		ee()->db->delete('actions', array('class' => 'wcloner'));

		return TRUE;
	}

	function update($current = '')
	{

		if ($current === $this->version)
		{
			return FALSE;
		}

		return TRUE;
	}

}