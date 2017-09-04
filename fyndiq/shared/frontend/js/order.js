/* global jQuery, FmCtrl, FmGui*/

(function(context, $, FmGui, FmCtrl) {
    'use strict';

    $(document).ready(function () {
        FmGui.show_load_screen(function () {
            FmCtrl.bind_order_event_handlers();

            var page = $('div.pages > ol > li.current').html();
            if (page === 'undefined') {
                page = 1;
            }
            // load all orders
            FmCtrl.load_orders(page, function () {
                FmGui.hide_load_screen();
            });
        });
    });
})(window, jQuery, FmGui, FmCtrl);
