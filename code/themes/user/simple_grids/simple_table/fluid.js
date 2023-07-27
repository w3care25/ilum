(function($) {

    var onDisplay = function(cell) {
        var $gridField = cell.find('.grid-input-form');
        var gridSettings = $gridField.data('gridSettings');

        // Instantiate this cell's contents as a Grid field
        EE.grid($gridField, gridSettings);

        // Now make it a SimpleTable, which binds more events, so we can add columns.
        new SimpleTable($gridField.closest('.simple-table'));
    };

    FluidField.on('simple_table', 'add', onDisplay);

})(jQuery);
