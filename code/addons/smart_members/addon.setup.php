<?php

require PATH_THIRD.'smart_members/config.php';

return array(
	'author'      		=> SM_AUTHOR,
	'author_url'  		=> SM_AUTHOR_URL,
	'name'        		=> SM_NAME,
	'description' 		=> 'Manage the members and member fields in smart way.',
	'version'     		=> SM_VER,
	'namespace'   		=> 'ZealousWeb\Addons\SmartMembers',
	'settings_exist'	=> TRUE,
	'docs_url' 			=> SM_DOC_URL,
	'models' 			=> array(
		"SmMemberField" => 'Model\SmMemberField',
	),
);