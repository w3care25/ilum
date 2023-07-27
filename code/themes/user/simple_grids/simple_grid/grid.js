(function($) {

    var onDisplay = function(cell) {
        var isBloqs = cell.hasClass('blocksft-atom');
        var isFluid = cell.closest('.fluid-wrap')[0];
        var rowId = 0;
        var newRowId = cell.data('newRowId');
        var existingRowId = cell.data('rowId');

        // When a Simple Grid is nested inside of a native Grid we can't render it normally. We hide the Simple Table
        // html in a <template> tag, otherwise parent native Grid binds all kinds of events to the child b/c it contains
        // the same markup. So when a new Grid row is added, this method gets called, and then we insert the Simple Table
        // markup and bind all the events to it separately.
        var $template = cell.find('template');
        var templateHtml = $($template[0]).html();
        var gridFieldHtml = '<div class="fieldset-faux simple-gt simple-grid" data-content-type="grid">' + templateHtml +'</div>';
        var $gridField = $(gridFieldHtml);
        var $table = $gridField.find('table[data-grid-settings]');
        var gridSettings = $table.data('gridSettings');

        // Instantiate this cell's contents as a Grid field
        $gridField.find('table[data-grid-settings]').addClass('grid-input-form');
        EE.grid($gridField.find('.grid-input-form')[0], gridSettings);

        cell.append($gridField);

        // Grid inside of Bloqs is good, don't need to do anything on the addRow event
        if (isBloqs) {
            return;
        }

        if (existingRowId) {
            rowId = existingRowId;
        } else {
            rowId = newRowId.replace('new_row_', '');
        }

        // Listen for the addRow event that is bound to our SimpleTable/Grid field. When a Simple Table is inside of
        // a normal Grid, the native JS events bound in the child Grid (Simple Table in this case) update the new_row_N
        // in the field name that relates to the parent Grid when adding a new row, thus screwing up the POST array.
        // |----------- parent Grid -----------||--- child Simple Table --|
        // field_id_8[rows][new_row_1][col_id_4][rows][new_row_3][col_id_1]
        // |------ parent Fluid ------||------------ parent Grid -------------||--- child Simple Table --|
        // field_id_11[fields][field_2][field_id_13][rows][new_row_2][col_id_4][rows][new_row_2][col_id_2]

        $(cell).on('grid:addRow', function(element) {
            var $simpleGridField = $(element.target);
            var $newRow = $simpleGridField.find('tr').last();

            $newRow.find('[name]').each(function(){
                var $field = $(this);
                var eleName = $field.attr('name');

                if (eleName) {
                    // Is it in a Fluid field?
                    if (isFluid) {
                        var gridRowId = cell.data('newRowId');
                        rowId = $newRow.find('td').first().data('newRowId');

                        if (rowId) {
                            eleName = eleName.replace(
                                /(field_id_\d+\[fields\]\[\S+\]\[field_id_\d+\]\[rows\])\[.*?\]/gm,
                                '$1['+ gridRowId +']'
                            );

                            eleName = eleName.replace(
                                /(field_id_\d+\[fields\]\[\S+\]\[field_id_\d+\]\[rows\]\[\S+\]\[col_id_\d+\]\[rows\]\[new_row_)\d+\]/gm,
                                '$1'+ rowId.replace('new_row_', '') +']'
                            );

                            $field.attr('name', eleName);
                        }
                    } else {
                        $field.attr('name', eleName
                            .replace(
                                /(field_id_\d+\[rows\]\[new_row_)\d+\]/gm,
                                '$1'+ rowId +']'
                            ));
                    }
                }
            });
        });
    };

    var beforeSort = function(cell) {};
    var afterSort = function(cell) {};

    Grid.bind('simple_grid', 'display', onDisplay);
    Grid.bind('simple_grid', 'beforeSort', beforeSort);
    Grid.bind('simple_grid', 'afterSort', afterSort);

})(jQuery);

