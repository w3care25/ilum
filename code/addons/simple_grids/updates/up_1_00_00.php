<?php

use Basee\Update\AbstractUpdate;

class Update_1_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        ee()->db->insert('modules', [
            'module_name'           => SIMPLE_GRIDS_NAME_SHORT,
            'module_version'        => SIMPLE_GRIDS_VERSION,
            'has_cp_backend'        => 'n',
            'has_publish_fields'    => 'n'
        ]);
    }
}
