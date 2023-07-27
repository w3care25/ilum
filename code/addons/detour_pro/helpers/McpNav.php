<?php

namespace EEHarbor\DetourPro\Helpers;

use EEHarbor\DetourPro\FluxCapacitor\Helpers\McpNav as FluxNav;

class McpNav extends FluxNav
{
    protected function defaultItems()
    {
        $default_items = array(
            'index' => lang('nav_home'),
            'settings' => lang('nav_settings'),
            'purge_hits' => lang('nav_purge'),
            'https://eeharbor.com/detour-pro/documentation' => lang('nav_documentation'),
        );

        return $default_items;
    }

    protected function defaultButtons()
    {
        return array(
            'index' => array('addUpdate' => 'Add'),
        );
    }

    protected function defaultActiveMap()
    {
        return array(
            'detour_pro' => 'index',
            'addUpdate' => 'index',
        );
    }

    public function postGenerateNav()
    {
    }
}
