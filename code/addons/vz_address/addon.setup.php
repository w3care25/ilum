<?php

return array(
  'author'      => 'Eli Van Zoeren',
  'author_url'  => 'https://devot-ee.com/developers/eli-van-zoeren',
  'name'        => 'VZ Address',
  'description' => 'Combined address (street/city/state/zip/country) fieldtype with flexible output options.',
  'version'     => '3.0.2',
  'namespace'   => 'EliVanZoeren\VZAddress',
  'settings_exist' => TRUE,
  'docs_url' 	=> 'https://github.com/elivz/vz_address.ee_addon',
	  'fieldtypes' => array(
	  'vz_address' => array(
	    'name' => 'VZ Address',
	    'compatibility' => 'grid'
	  )
	)
);

?>