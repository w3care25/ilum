<?php

use EllisLab\ExpressionEngine\Service\Addon\Addon;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

abstract class SimpleGrids extends EE_Fieldtype
{
    public $settings = [];
    public $has_array_data = true;
    public $info = [];
    protected $cache = [];
    private $fieldName;

    /**
     * Constructor
     *
     * @access  public
     */
    public function __construct($field)
    {
        /** @var Addon $addon */
        $addon = ee('Addon')->get('simple_grids');
        $this->info = [
            'name'    => $addon->getName(),
            'version' => SIMPLE_GRIDS_VERSION
        ];

        $this->fieldName = $field;
    }

    public function validate($data)
    {
        return true;
    }

    /**
     * @param $name
     * @return bool
     */
    public function accepts_content_type($name)
    {
        $acceptedTypes = [
            'channel',
            'grid',
            'blocks/1',
            'fluid_field',
        ];

        return in_array($name, $acceptedTypes);
    }

    /**
     * @param $data
     * @return string
     */
    public function save($data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function grid_save_settings($settings)
    {
        return $this->save_settings($settings);
    }

    /**
     * Default field display
     *
     * @param $data
     * @param null $fieldName
     * @param null $fieldId
     * @return string
     */
    public function display_field($data)
    {
        $this->loadAssets();
        return $this->renderField($data, $this->field_name);
    }

    /**
     * @param $data
     * @return string
     */
    public function grid_display_field($data)
    {
        $this->loadAssets();
        return $this->renderField($data, $this->field_name);
    }

    /**
     * @param $data
     * @return array
     */
    public function display_settings($data)
    {
        return $this->getFieldSettings($data);
    }

    /**
     * @param $data
     * @return array
     */
    public function grid_display_settings($data)
    {
        return $this->getFieldSettings($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function pre_process($data)
    {
        if ($data && !is_array($data)) {
            $data = json_decode(html_entity_decode($data, ENT_COMPAT, 'UTF-8'));
        }

        if (ee('LivePreview')->hasEntryData()) {
            $data = $data['rows'];
        }

        return $data;
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return string
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        return $tagdata;
    }

    /**
     * @param $data
     * @param $fieldName
     * @return string
     * @access private
     */
    protected function renderField($data, $fieldName = '')
    {
        return $data;
    }

    /**
     * @param null $settings
     * @return array
     */
    protected function getFieldSettings($settings = null)
    {
        return $settings;
    }

    /**
     * Load all CSS and JS assets in the CP
     */
    protected function loadAssets()
    {
        if (REQ != 'CP') {
            return;
        }

        if (!isset(ee()->session->cache['simpleGridsShared'])) {
            ee()->cp->add_to_head('
                <link href="' . URL_THIRD_THEMES . 'simple_grids/shared/simple-gt.css" rel="stylesheet" />
            ');

            ee()->session->cache['simpleGridsShared'] = true;
        }

        if (!isset(ee()->session->cache['simpleGrid']) && $this->fieldName === 'simpleGrid') {
            ee()->cp->add_to_head('
                <link href="' . URL_THIRD_THEMES . 'simple_grids/simple_grid/simple-grid.css" rel="stylesheet" />
            ');

            ee()->cp->add_to_foot('
                <script type="text/javascript" src="'. URL_THIRD_THEMES .'simple_grids/simple_grid/grid.js"></script>
                <script type="text/javascript" src="'. URL_THIRD_THEMES .'simple_grids/simple_grid/fluid.js"></script>
            ');

            ee()->session->cache['simpleGrid'] = true;
        }

        if (!isset(ee()->session->cache['simpleTable']) && $this->fieldName === 'simpleTable') {
            ee()->cp->add_to_head('
                <link href="'. URL_THIRD_THEMES.'simple_grids/simple_table/simple-table.css" rel="stylesheet" />
            ');

            ee()->cp->add_to_foot('
                <script type="text/javascript" src="'. URL_THIRD_THEMES .'simple_grids/simple_table/grid.js"></script>
                <script type="text/javascript" src="'. URL_THIRD_THEMES .'simple_grids/simple_table/fluid.js"></script>
                <script type="text/javascript" src="'. URL_THIRD_THEMES .'simple_grids/simple_table/simple-table.js"></script>
            ');

            ee()->session->cache['simpleTable'] = true;
        }
    }
}
