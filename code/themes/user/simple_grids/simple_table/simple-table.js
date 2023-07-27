$('div.simple-table[data-content-type="channel"]').each(function() {
    new SimpleTable($(this));
});

function SimpleTable($field) {
    var $table = $field.find('.grid-input-form');
    var gridSettings = $table.data('gridSettings');
    var $toolbar = $field.find('.tbl-wrap + .toolbar');
    var $removeColumnButton = $('<ul class="toolbar"><li class="remove"><a href="#" rel="remove_col"></a></li></ul>');
    var $addRowButton = $toolbar.find('.add:first-child a');
    $addRowButton.text(' Add Row');

    if ($toolbar.find('.add-column').length === 0) {
        $toolbar.append('<li class="add add-column"><a href="#"> Add Column</a></li>');
    }

    var $addColumnButton = $field.find('.add-column a');

    // Make sure all columns have a remove button.
    indexHeaders();

    // $table.on('grid:addRow', function () {
    // });

    $addColumnButton.click(function (event) {
        if (!canAddNewColumns()) {
            event.preventDefault();
            return;
        }

        addColumn();

        event.preventDefault();
    });

    function addColumn() {
        // Add the new column to the hidden row that EE uses as the template for adding new rows
        var nextColumnNum = addColumnElement($table.find('tbody tr.grid-blank-row'));

        addHeader(nextColumnNum);
        manageButtonState();

        // Now add the new column to all existing rows too
        $table.find('tbody tr:not(".hidden")').each(function () {
            addColumnElement($(this));
        });
    }

    function addColumnElement($row) {
        var totalColumns = $table.find('tbody tr:last-child td').length;
        var totalEditableColumn = totalColumns - 2; // minus first and last
        var nextColumnNum = totalEditableColumn + 1;
        var $lastColumn = $row.find('td:nth-child(' + (totalColumns - 1) + ')');
        var $colTemplate = $lastColumn.clone();

        $colTemplate.html(
            $colTemplate.html()
                .replace(RegExp('data-column-id-[0-9]{1,}', 'g'), 'data-column-id-'+ nextColumnNum)
                // Only update the 2nd col_id_N, the one at the end of the value. The first col_id_ is for the parent grid
                .replace(/\[col_id_(\d+)\]"/gm, '[col_id_'+ nextColumnNum +']"')
        ).find('textarea').val('');

        $colTemplate.insertAfter($lastColumn);

        $(document).trigger('entry:preview');

        return nextColumnNum;
    }

    function addHeader(num) {
        var $lastHeader = $table.find('thead tr th.last');
        var $newHeader = $('<th><span>' + num + '</span></th>');

        $newHeader.append(createColumnRemoveButton(num));
        $lastHeader.before($newHeader);
    }

    function createColumnRemoveButton(num) {
        var $button = $removeColumnButton.clone();

        $button.find('a').click(function (event) {
            var colIndex = num + 1;

            $table.find('tr th:nth-child(' + colIndex + ')').remove();
            $table.find('tr td:nth-child(' + colIndex + ')').remove();

            indexHeaders();
            event.preventDefault();
        });

        return $button;
    }

    function indexHeaders() {
        $table.find('thead th').not(':first').not(':last').each(function (index) {
            var colIndex = index + 1;
            var $header = $(this);

            $header.html('<span>' + colIndex + '</span>');

            if (colIndex > 1) {
                $header.append(createColumnRemoveButton(colIndex));
            }
        });

        manageButtonState();
    }

    function manageButtonState() {
        if (canAddNewColumns()) {
            $addColumnButton.closest('li').show();
        } else {
            $addColumnButton.closest('li').hide();
        }

        if (canRemoveColumns()) {
            $table.find('a[rel="remove_col"]').show();
        } else {
            $table.find('a[rel="remove_col"]').hide();
        }
    }

    function canAddNewColumns() {
        return getColumnCount() < gridSettings.grid_max_columns;
    }

    function getColumnCount() {
        return $table.find('thead th').not(':first').not(':last').length;
    }

    function canRemoveColumns() {
        return getColumnCount() > gridSettings.grid_min_columns;
    }
}

