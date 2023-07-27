<?php

namespace EEHarbor\Wygwam\Services;

use EEHarbor\Wygwam\Helpers\MigrationSource;
use EEHarbor\Wygwam\FluxCapacitor\Services\McpNav as FluxNav;

class McpNav extends FluxNav
{
    protected function defaultItems($items = array())
    {
        $default_items = array(
            'index' => 'Editor Configurations',
            'settings' => 'Settings'
        );

        return array_merge($default_items, $items);
    }

    protected function defaultButtons()
    {
        return array(
            'index' => array('editConfig' => 'New'),
        );
    }

    protected function defaultActiveMap()
    {
        return array(
            // Wygwam
            'editConfig' => 'index',
        );
    }

    public function postGenerateNav()
    {
    }

    public function badge($content, $style = 'info')
    {
        return '<span class="st-'.$style.'" style="float:right;">'.$content.'</span>';
    }
}
