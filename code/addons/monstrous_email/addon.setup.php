<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD."monstrous_email/language/english/monstrous_email_lang.php");

return array(
    'author'                => 'Omaha Media Group, LLC',
    'author_url'            => 'https://www.omahamediagroup.com',
    'name'                  => $lang['monstrous_email'],
    'description'           => $lang['monstrous_email_description'],
    'version'               => '1.0.0',
    'namespace'             => 'OMG\MonstrousEmail',
    'settings_exist'        => TRUE,
    'docs_url'	            => $lang['monstrous_email_docs_url']
);

?>