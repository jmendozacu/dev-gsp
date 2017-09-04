/* global jQuery, FmGui, FmCtrl */

(function(context, $, FmGui, FmCtrl) {
    'use strict';

    // Setup page
    $(document).ready(function() {
        FmGui.show_load_screen(function(){
            FmCtrl.bind_event_handlers();

            // load all parent categories
            FmCtrl.load_categories(0, $('.fm-category-tree-container'), function() {
                // load products from first category
                var $firstCategory = $('.fm-category-tree a').eq(0);
                var category_id = $firstCategory.parent().attr('data-category_id');

                FmCtrl.updateCategoryName($firstCategory.text());
                FmCtrl.load_products(category_id, 1, function() {
                    FmGui.hide_load_screen();
                });
            });
        });
    });
})(window, jQuery, FmGui, FmCtrl, Handlebars);
