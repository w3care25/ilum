<?php

/**
 * Hop PushEE - Config
 *
 * NSM Addon Updater config file.
 *
 * @package		Hop Studios:Hop PushEE
 * @author		Hop Studios, Inc.
 * @copyright	Copyright (c) 2019, Hop Studios, Inc.
 * @version		2.0.0
 */

$config['name']='Hop PushEE';
$config['version']='2.0.0';
$config['nsm_addon_updater']['versions_xml']='https://www.hopstudios.com/software/';

// Version constant
if (!defined('HOP_PUSHEE_VERSION')) {
	define('HOP_PUSHEE_VERSION', $config['version']);
}
