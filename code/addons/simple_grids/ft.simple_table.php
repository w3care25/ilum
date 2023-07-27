<?php

use Basee\Updater;
use EllisLab\ExpressionEngine\Library\CP\GridInput;
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

require_once 'abstract.simple_grids.php';

class Simple_table_ft extends SimpleGrids
{
    const MIN_COLUMNS = 1;
    const MAX_COLUMNS = 10;

    public function __construct()
    {
        parent::__construct('simpleTable');
    }

    /**
     * @inheritdoc
     */
    public function save($data)
    {
        $cleanData = [];
        $rowId = 1;

        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                // Re-index starting at 1
                $cleanData[$rowId] = array_combine(range(1, count($row)), array_values($row));

                $rowId++;
            }
        }

        return json_encode($cleanData);
    }

    /**
     * @param array $settings
     * @return array
     */
    public function save_settings($settings = [])
    {
        $saveSettings = isset($settings['simple_table']) ? $settings['simple_table'] : [];

        if (isset($settings['simple_table']['rows'])) {
            $columns = $settings['simple_table']['rows'];
            $newColumns = [];
            $i = 1;

            foreach ($columns as $rowId => $column) {
                $newColumns[$i] = $column['col_label'];
                $i++;
            }

            $saveSettings['columns'] = $newColumns;
        }

        // The MiniGrid service wants to save the rows key, but we're using it to define columns.
        unset($saveSettings['rows']);

        $saveSettings['field_fmt'] = 'none';
        $saveSettings['field_show_fmt'] = 'n';
        $saveSettings['field_wide'] = true;
        $saveSettings['min_columns'] = $this->getMinColumns($settings);
        $saveSettings['max_columns'] = $this->getMaxColumns($settings);

        return $saveSettings;
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return string
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        $rows = [];
        $prefix = isset($params['prefix']) ? $params['prefix'] : '';
        $dataAsArray = (array) $data;
        $totalRows = count($dataAsArray);
        $rowCount = 1;
        $rowIndex = 0;

        foreach ($data as $rowId => $rowData) {
            $columns = [];

            foreach ($rowData as $colId => $colValue) {
                $columns[] = [
                    $prefix.'column_id' => $colId,
                    $prefix.'value' => $colValue,
                ];
            }

            $colCount = 1;
            $colKeys = array_keys($columns);

            foreach ($columns as $colIndex => $column) {
                $columns[$colIndex][$prefix.'col_count'] = $colCount;
                $columns[$colIndex][$prefix.'col_index'] = $colCount - 1;
                $columns[$colIndex][$prefix.'is_first_col'] = ($colIndex === 0);
                $columns[$colIndex][$prefix.'is_last_col'] = ((end($colKeys) == $colIndex));

                $colCount++;
            }

            $rows[] = [
                $prefix.'row_id' => intval($rowId),
                $prefix.'columns' => $columns,
                $prefix.'total_rows' => $totalRows,
                $prefix.'total_columns' => count($columns),
                $prefix.'count' => $rowCount,
                $prefix.'row_count' => $rowCount,
                $prefix.'index' => $rowIndex,
                $prefix.'row_index' => $rowIndex,
                $prefix.'is_first_row' => (intval($rowId) === 1),
                $prefix.'is_last_row' => (intval($rowId) === $totalRows)
            ];

            $rowCount++;
            $rowIndex++;
        }

        $tagdata = ee()->TMPL->parse_variables($tagdata, $rows);
        $tagdata = ee()->functions->prep_conditionals($tagdata, $rows);

        // Backspace parameter
        if (isset($params['backspace']) && $params['backspace'] > 0) {
            $tagdata = substr($tagdata, 0, -$params['backspace']);
        }

        return $tagdata;
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

        /** @var GridInput $grid */
        $grid = ee('CP/GridInput', [
            'field_name' => $fieldName,
            'lang_cols' => false,
            'grid_min_rows' => 0,
            'grid_max_rows' => 100,
            'reorder' => true
        ]);
        $grid->loadAssets();
        $grid->setNoResultsText('no_rows_created', 'add_new_row');

        $columnSettings = isset($this->settings['columns']) ? $this->settings['columns'] : [];
        $columnHeadings = [];
        $blankColumn = [];
        $gridData = [];
        $rows = [];
        $columnSettingsCount = count($columnSettings);
        $minColumns = isset($this->settings['min_columns']) ? (int) $this->settings['min_columns'] : 1;

        // If validation data is set, we're likely coming back to the form on a validation error
        if (isset($this->_validated[$fieldName])) {
            $rows = $this->_validated[$fieldName];
        } elseif (is_array($data)) {
            $rows = $data;
        } elseif (count($columnSettings) > 0) {
            $rows[1] = $columnSettings;
        }

        if (isset($rows[1]) && !empty($rows[1])) {
            foreach ($rows[1] as $columnId => $columnValue) {
                $columns[$columnId] = ['col_label' => $columnId];
            }

            // Fill in additional blank columns based on settings
            $count = count($columns);
            if ($count < $minColumns) {
                while ($count <= $minColumns) {
                    $columns[$count] = ['col_label' => $count];
                    $count++;
                }
            }
        } else {
            // Did the user define some default headings? These are not actual table headings, instead
            // they are the values in the first row of the table to act as a default heading.
            if ($columnSettingsCount > 0) {
                $i = 1;
                foreach ($columnSettings as $colId => $colLabel) {
                    $columns[$i] = ['col_label' => $i];
                    $i++;
                }
            } else {
                $columns = [
                    1 => ['col_label' => 1],
                ];
            }
        }

        foreach ($columns as $columnId => $column) {
            $columnHeadings[] = [
                'label' => $column['col_label'],
            ];

            $attrs = [
                'class' => '',
                'data-column-id' => $columnId,
            ];

            $blankColumn[] = [
                'html' => $this->cellField($columnId, ''),
                'attrs' => $attrs
            ];
        }

        $grid->setColumns($columnHeadings);
        $grid->setBlankRow($blankColumn);

        foreach ($rows as $rowId => $row) {
            if (!is_numeric($rowId)) {
                $row['row_id'] = $rowId;
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
                    'data-column-id' => $columnId,
                    $dataRowAttrId => $rowId,
                ];

                if ( ! empty($column['col_width'])) {
                    $attrs['style'] = 'min-width: '.$column['col_width'].'px';
                }

                $colData = isset($row[$columnId]) ? $row[$columnId] : '';

                $col = [
                    'html' => $this->cellField($columnId, $colData),
                    'attrs' => $attrs,
                ];

                $fieldColumns[] = $col;
            }

            $gridData[] = [
                'attrs' => ['row_id' => $rowId],
                'columns' => $fieldColumns
            ];
        }

        $grid->setData($gridData);
        $vars = $grid->viewData();

        $vars['table_attrs'] = [
            'data-grid-settings' => json_encode([
                'grid_min_rows' => $grid->config['grid_min_rows'],
                'grid_max_rows' => $grid->config['grid_max_rows'],
                'grid_min_columns' => $this->getMinColumns($this->settings),
                'grid_max_columns' => $this->getMaxColumns($this->settings),
            ])
        ];

        $field = ee('View')->make('ee:_shared/table')->render($vars);
        $tag = $contentType === 'channel' ? 'div' : 'template';

        return '<'. $tag .' class="fieldset-faux simple-gt simple-table" data-content-type="'. $contentType .'">'. $field .'</'. $tag .'>';
    }

    /**
     * @param $id
     * @param $data
     * @return string
     */
    private function cellField($id, $data)
    {
        return form_textarea(['name' => 'col_id_'. $id, 'rows' => 0], $data, 'class="simple-table--input"');
    }

    /**
     * @param $settings
     * @return int
     */
    private function getMinColumns($settings)
    {
        return (isset($settings['min_columns']) && $settings['min_columns'] > 1) ? $settings['min_columns'] : self::MIN_COLUMNS;
    }

    /**
     * @param $settings
     * @return int
     */
    private function getMaxColumns($settings)
    {
        return (isset($settings['max_columns']) && $settings['max_columns']) ? $settings['max_columns'] : self::MAX_COLUMNS;
    }

    /**
     * @inheritdoc
     */
    protected function getFieldSettings($settings = null)
    {
        $isGridOrBloqs = (in_array($this->content_type(), ['grid', 'blocks', 'blocks/1']));

        /** @var \EllisLab\ExpressionEngine\Library\CP\MiniGridInput $grid */
        $grid = ee('CP/MiniGridInput', [
            'field_name' => 'simple_table'
        ]);
        $grid->loadAssets();
        $grid->setColumns([
            '',
        ]);
        $grid->setNoResultsText('No first row column values exist', 'Add a value');
        $grid->setBlankRow([
            ['html' => form_input('col_label', '')],
        ]);

        $pairs = [];
        if (isset($settings['columns'])) {
            foreach ($settings['columns'] as $columnId => $colData) {
                $pairs[] = [
                    'attrs' => ['row_id' => $columnId],
                    'columns' => [
                        ['html' => form_input('col_label', $colData)],
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
                Grid.bind('simple_table', 'displaySettings', function(column) {
                    miniGridInit(column);
                });
                FieldManager.on('fieldModalDisplay', function(modal) {
                    miniGridInit(modal);
                });
            ");
        }

        $sections = [
            [
                'title' => 'Minimum columns',
                'fields' => [
                    'min_columns' => [
                        'type' => 'text',
                        'value' => isset($settings['min_columns']) ? $settings['min_columns'] : 1,
                    ]
                ]
            ],
            [
                'title' => 'Maximum columns',
                'fields' => [
                    'max_columns' => [
                        'type' => 'text',
                        'value' => isset($settings['max_columns']) ? $settings['max_columns'] : 10,
                    ]
                ]
            ],
            [
                'title' => 'First row column starting values',
                'fields' => [
                    'columns' => [
                        'type' => 'html',
                        'content' => $miniGrid,
                    ]
                ]
            ],
        ];

        if ($isGridOrBloqs) {
            return ['field_options' => $sections];
        }

        return ['field_options_simple_table' => [
            'label' => 'field_options',
            'group' => 'simple_table',
            'settings' => $sections
        ]];
    }
}
