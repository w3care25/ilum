<?php

use EllisLab\ExpressionEngine\Library\CP\GridInput;
use Litzinger\FileField\FileField;

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

require_once 'abstract.simple_grids.php';

class Simple_grid_ft extends SimpleGrids
{
    public function __construct()
    {
        parent::__construct('simpleGrid');
    }

    /**
     * @inheritdoc
     */
    public function save($data)
    {
        $cleanData = [];
        $columnId = 1;

        if (isset($data['rows'])) {
            foreach ($data['rows'] as $colId => $row) {
                $cleanData[$columnId] = $row;

                $columnId++;
            }
        }

        return json_encode($cleanData);
    }

    /**
     * @inheritdoc
     */
    public function save_settings($settings = [])
    {
        $saveSettings = isset($settings['simple_grid']) ? $settings['simple_grid'] : [];

        if (isset($settings['simple_grid']['rows'])) {
            $columns = $settings['simple_grid']['rows'];
            $newColumns = [];
            $maxColumnId = $this->findMaxColumnId($columns);

            foreach ($columns as $id => $column) {
                if (substr($id,  0, 8) === 'new_row_') {
                    $maxColumnId++;
                    $numericId = $maxColumnId;
                } else {
                    $numericId = (int) str_replace('row_id_', '', $id);
                }

                $newColumns[$numericId] = $column;
            }

            $saveSettings['columns'] = $newColumns;
        }

        // The MiniGrid service wants to save the rows key, but we're using it to define columns.
        unset($saveSettings['rows']);

        $saveSettings['field_fmt'] = 'none';
        $saveSettings['field_show_fmt'] = 'n';
        $saveSettings['field_wide'] = true;

        return $saveSettings;
    }

    /**
     * @inheritdoc
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        $prefix = isset($params['prefix']) ? $params['prefix'] : '';
        $dataAsArray = (array) $data;
        $totalRows = count($dataAsArray);
        $columns = $this->settings['columns'];
        $totalColumns = count($columns);
        $rowCount = 1;
        $rowIndex = 0;
        $tagdataOutput = '';

        foreach ($data as $rowId => $rowData) {
            $tagdataRow = $tagdata;
            // Native parser isn't taking care of {switch="odd|even"}
            $tagdataRow = ee()->TMPL->parse_switch($tagdataRow, $rowIndex, $prefix);

            foreach ($rowData as $columnName => $colValue) {
                $colId = str_replace('col_id_', '', $columnName);

                if (!isset($columns[$colId])) {
                    continue;
                }

                $colName = $columns[$colId]['col_name'];
                $colType = $columns[$colId]['col_type'];

                if ($colType === 'file') {
                    ee()->load->library('file_field');
                    $colValue = ee()->file_field->parse_string($colValue);
                }

                $row = [
                    $prefix.$colName => $colValue,
                    $prefix.'row_id' => intval($rowId),
                    $prefix.'total_rows' => $totalRows,
                    $prefix.'total_columns' => $totalColumns,
                    $prefix.'count' => $rowCount,
                    $prefix.'row_count' => $rowCount,
                    $prefix.'index' => $rowIndex,
                    $prefix.'row_index' => $rowIndex,
                    $prefix.'is_first_row' => (intval($rowId) === 1),
                    $prefix.'is_last_row' => (intval($rowId) === count($dataAsArray))
                ];

                $tagdataRow = ee()->TMPL->parse_variables($tagdataRow, [$row]);
                $tagdataRow = ee()->functions->prep_conditionals($tagdataRow, [$row]);
            }

            $tagdataOutput .= $tagdataRow;

            $rowCount++;
            $rowIndex++;
        }

        // Backspace parameter
        if (isset($params['backspace']) && $params['backspace'] > 0) {
            $tagdataOutput = substr($tagdataOutput, 0, -$params['backspace']);
        }

        return $tagdataOutput;
    }

    /**
     * @todo Need to handle field validation. Add validate() method and save the data to cache
     *       then reference it here. See how ft.bloqs.php
     *
     * @inheritdoc
     */
    protected function renderField($data, $fieldName = '')
    {
        $data = !is_array($data) ? json_decode($data ,true) : $data;
        $contentType = $this->content_type();
        $columns = $this->settings['columns'];

        /** @var GridInput $grid */
        $grid = ee('CP/GridInput', [
            'field_name' => $fieldName,
            'lang_cols' => false,
            'grid_min_rows' => 0,
            'grid_max_rows' => 99,
            'reorder' => true
        ]);
        $grid->loadAssets();
        $grid->setNoResultsText('no_rows_created', 'add_new_row');

        $columnHeadings = [];
        $blankColumn = [];
        $gridData = [];
        $rows = [];

        // If validation data is set, we're likely coming back to the form on a validation error
        if (isset($this->_validated[$fieldName])) {
            $rows = $this->_validated[$fieldName];
        }
        elseif (is_array($data)) {
            $rows = $data;
        }

        foreach ($columns as $columnId => $column) {
            $columnHeadings[] = [
                'label' => $column['col_label'],
                //'desc' => $column['col_instructions'],
                //'required' => ($column['col_required'] == 'y')
            ];

            $attrs = [
                'class' => $this->getClassForColumnType($column['col_type']),
                'data-fieldtype' => $column['col_type'],
                'data-column-id' => $columnId,
            ];

            //if (!empty($column['col_width'])) {
            //    $attrs['style'] = 'min-width: '.$column['col_width'].'px';
            //}

            $blankColumn[] = [
                'html' => $this->getColumnView($column['col_type'], $columnId),
                'attrs' => $attrs
            ];
        }

        $grid->setColumns($columnHeadings);
        $grid->setBlankRow($blankColumn);

        foreach ($rows as $index => $row) {
            if (!is_numeric($index)) {
                $row['row_id'] = $index;
                // We want to reserve the row-id data attribute for real row IDs, not
                // the string placeholders, in case folks are relying on having a real
                // number there or are using it to determine if a row is new or not
                $dataRowAttrId = 'data-new-row-id';
            } else {
                $dataRowAttrId = 'data-row-id';
            }

            $fieldColumns = [];

            foreach ($columns as $columnId => $column) {
                $attrs = [
                    'data-fieldtype' => $column['col_type'],
                    'data-column-id' => $columnId,
                    $dataRowAttrId => $index,
                ];

                if ( ! empty($column['col_width'])) {
                    $attrs['style'] = 'min-width: '.$column['col_width'].'px';
                }

                $colData = isset($row['col_id_'.$columnId]) ? $row['col_id_'.$columnId] : '';

                $col = [
                    'html' => $this->getColumnView($column['col_type'], $columnId, $colData),
                    'attrs' => $attrs,
                    //'error' => isset($row['col_id_'.$column['col_id'].'_error']) ? $row['col_id_'.$column['col_id'].'_error'] : NULL,
                ];

                //if ($column['col_required'] == 'y') {
                //    $col['attrs']['class'] = 'required';
                //}

                $fieldColumns[] = $col;
            }

            $gridData[] = [
                'attrs' => ['row_id' => $index],
                'columns' => $fieldColumns
            ];
        }

        $grid->setData($gridData);
        $vars = $grid->viewData();

        $vars['table_attrs'] = [
            'data-grid-settings' => json_encode([
                'grid_min_rows' => $grid->config['grid_min_rows'],
                'grid_max_rows' => $grid->config['grid_max_rows']
            ])
        ];

        $field = ee('View')->make('ee:_shared/table')->render($vars);
        $tag = $contentType === 'channel' ? 'div' : 'template';

        return '<'. $tag .' class="fieldset-faux simple-gt simple-grid" data-content-type="'. $contentType .'">'. $field .'</'. $tag .'>';
    }

    /**
     * @param null $settings
     * @return array
     */
    protected function getFieldSettings($settings = null)
    {
        $columnTypes = $this->getColumnTypes();
        $isGridOrBloqs = (in_array($this->content_type(), ['grid', 'blocks', 'blocks/1']));

        /** @var \EllisLab\ExpressionEngine\Library\CP\MiniGridInput $grid */
        $grid = ee('CP/MiniGridInput', [
            'field_name' => 'simple_grid'
        ]);
        $grid->loadAssets();
        $grid->setColumns([
            'Type',
            'Short Name',
            'Label',
        ]);
        $grid->setNoResultsText('No columns exist', 'Add A Column');
        $grid->setBlankRow([
            ['html' => form_dropdown('col_type', $columnTypes)],
            ['html' => form_input('col_name', '')],
            ['html' => form_input('col_label', '')],
        ]);

        $pairs = [];
        if (isset($settings['columns'])) {
            foreach ($settings['columns'] as $columnId => $rowData) {
                $pairs[] = [
                    'attrs' => ['row_id' => $columnId],
                    'columns' => [
                        ['html' => form_dropdown('col_type', $columnTypes, $rowData['col_type'])],
                        ['html' => form_input('col_name', $rowData['col_name'])],
                        ['html' => form_input('col_label', $rowData['col_label'])],
                    ]
                ];
            }
        }

        $grid->setData($pairs);
        $miniGrid = ee('View')->make('ee:_shared/form/mini_grid')->render($grid->viewData());

        if ($isGridOrBloqs) {
            ee()->javascript->output("
                var miniGridInit = function(context) {
                    $('.fields-keyvalue', context).miniGrid({grid_min_rows:0,grid_max_rows:''});
                }
                Grid.bind('simple_grid', 'displaySettings', function(column) {
                    miniGridInit(column);
                });
                FieldManager.on('fieldModalDisplay', function(modal) {
                    miniGridInit(modal);
                });
            ");
        }

        $sections = [
            [
                'title' => 'Columns',
                'fields' => [
                    'columns' => [
                        'type' => 'html',
                        'content' => $miniGrid
                    ]
                ]
            ],
        ];

        if ($isGridOrBloqs) {
            return ['field_options' => $sections];
        }

        return ['field_options_simple_grid' => [
            'label' => 'field_options',
            'group' => 'simple_grid',
            'settings' => $sections
        ]];
    }

    /**
     * @return array
     */
    private function getColumnTypes()
    {
        return [
            'text' => 'Text',
            'textarea' => 'Textarea',
            //'rte' => 'Rich Text Area',
            'toggle' => 'Toggle',
            'file' => 'File',
        ];
    }

    /**
     * @param string $colId
     * @param string $data
     * @return array
     */
    private function getColumnView($type, $colId = '', $data = '')
    {
        $fieldName = 'col_id_'.$colId;

        //ee()->load->add_package_path(SYSPATH.'ee/EllisLab/Addons/rte/');
        //ee()->load->library('rte_lib');

        $types = [
            //'rte' => ee()->rte_lib->display_field($data, $fieldName, ['field_ta_rows' => 6, 'field_text_direction' => 'ltr'], 'grid'),
            'text' => form_input($fieldName, $data),
            'textarea' =>  form_textarea($fieldName, $data),
            'toggle' => ee('View')->make('ee:_shared/form/fields/toggle')->render([
                'field_name' => $fieldName,
                'value'      => $data,
                'disabled'   => false,
                'yes_no'     => false,
            ]),
            'file' => (new FileField($fieldName, $data, []))->render()
        ];

        if (!isset($types[$type])) {
            show_error('Column type '. $type .' not found.');
        }

        return $types[$type];
    }

    private function getClassForColumnType($type)
    {
        switch ($type) {
            case 'rte':
                $class = 'grid-rte';
                break;
            case 'textarea':
                $class = 'grid-textarea';
                break;
            case 'toggle':
                $class = 'grid-toggle';
                break;
            case 'file':
                $class = 'grid-file-upload';
                break;
            default:
                $class = '';
                break;
        }

        return $class;
    }

    /**
     * Since we aren't using an auto-incrementing table to save our columns we need to mimic such behavior.
     *
     * @param array $columns
     * @return int
     */
    private function findMaxColumnId($columns)
    {
        $max = 0;
        foreach ($columns as $id => $column) {
            if (substr($id,  0, 8) === 'new_row_') {
                continue;
            }
            $numericId = (int) str_replace('row_id_', '', $id);
            if ($numericId > $max) {
                $max = $numericId;
            }
        }

        return $max;
    }
}
