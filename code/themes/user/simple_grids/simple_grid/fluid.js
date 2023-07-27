(function($) {

    var onDisplay = function(cell) {
        var $gridField = cell.find('.grid-input-form');
        var gridSettings = $gridField.data('gridSettings');

        // Instantiate this cell's contents as a Grid field
        EE.grid($gridField, gridSettings);
    };

    FluidField.on('simple_grid', 'add', onDisplay);

})(jQuery);
