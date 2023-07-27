<?php

use Basee\Updater;

/**
 * @package     ExpressionEngine
 * @subpackage  Fieldtypes
 * @category    Simple Grids & Tables
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2018 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/simple-grids-tables
 * @license
 *
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

require PATH_THIRD.'simple_grids/addon.setup.php';

class Simple_grids_upd
{
    /**
     * @var string
     */
    public $version = SIMPLE_GRIDS_VERSION;

    /**
     * Initiate something every time the Run Update Modules button is clicked
     */
    public function __construct() {}

    /**
     * Installation Method
     *
     * @return bool
     */
    public function install()
    {
        $updater = new Updater();
        $updater
            ->setFilePath(PATH_THIRD.'simple_grids/updates')
            ->fetchUpdates(0, true)
            ->runUpdates();

        return true;
    }

    /**
     * Uninstall
     *
     * @return  bool
     */
    public function uninstall()
    {
        ee()->db->select('module_id')
            ->get_where('modules', array(
                'module_name' => SIMPLE_GRIDS_NAME_SHORT
            ))->row('module_id');

        ee()->db->where('module_name', SIMPLE_GRIDS_NAME_SHORT)
            ->delete('modules');

        ee()->db->where('class', SIMPLE_GRIDS_NAME_SHORT)
            ->delete('actions');

        return true;
    }

    /**
     * Module Updater
     *
     * @param string $current
     * @return bool true
     */
    public function update($current = '')
    {
        $updater = new Updater();
        $updater
            ->setFilePath(PATH_THIRD.'simple_grids/updates')
            ->fetchUpdates($current)
            ->runUpdates();

        return true;
    }
}
