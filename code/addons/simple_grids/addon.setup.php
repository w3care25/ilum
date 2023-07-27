<?php
// Build: 270f3f50
require 'vendor/autoload.php';

if (!defined('SIMPLE_GRIDS_VERSION')) {
    define('SIMPLE_GRIDS_VERSION', '1.0.3');
    define('SIMPLE_GRIDS_NAME', 'Simple Grids & Tables');
    define('SIMPLE_GRIDS_NAME_SHORT', 'Simple_Grids');
}

$config = [
    'author'      => 'BoldMinded',
    'author_url'  => 'https://boldminded.com/add-ons/simple-grids-tables',
    'docs_url'    => 'http://docs.boldminded.com/simple-grids-tables',
    'name'        => 'Simple Grids',
    'description' => '',
    'version'     => SIMPLE_GRIDS_VERSION,
    'namespace'   => 'BoldMinded\SimpleGrids',
    'settings_exist' => false,
    'fieldtypes' => [
        'simple_grid' => [
            'name' => 'Simple Grid',
            'compatibility' => 'text'
        ],
        'simple_table' => [
            'name' => 'Simple Table',
            'compatibility' => 'text'
        ],
    ],
];

return $config;
