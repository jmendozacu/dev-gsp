/* global jQuery, FmGui, FmPaths, extra_data */

var FmCtrl = (function(context, $) {
    'use strict';

    function do_export_products(products, callback, hideSuccess) {
        FmCtrl.call_service('export_products', {products: products}, function (status, data) {
            if (status === 'success') {
                if (!hideSuccess) {
                    FmGui.show_message('success', context.messages['products-exported-title'],
                        context.messages['products-exported-message']);
                }
                // reload category to ensure that everything is reset properly
                var category = $('.fm-category-tree li.active').attr('data-category_id');
                var page = parseInt($('div.pages > ol > li.current').html(), 10) || 1;
                FmCtrl.load_products(category, page, function () {
                    if (callback) {
                        callback(status);
                    }
                });
            } else {
                if (callback) {
                    callback(status);
                }
            }
        });
    }

    return {
        $categoryName: null,

        debounce: function (func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) {
                        func.apply(context, args);
                    }
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) {
                    func.apply(context, args);
                }
            };
        },


        call_service: function (action, args, callback) {
            var data = {action: action, args: args};
            var extra_data = context.extra_data || false;
            var error = '';
            if (extra_data) {
                data = $.extend({action: action, args: args}, extra_data);
            }
            $.ajax({
                type: 'POST',
                url: FmPaths.service,
                data: data,
                dataType: 'json'
            })
            .fail(function(jqXHR){
                if (jqXHR.status === 401) {
                    // Expired session, refresh the page
                    location.reload();
                }
                error = jqXHR.status + ': ' + jqXHR.statusText;
            })
            .always(function (data) {
                var status = 'error';
                var result = null;
                if ($.isPlainObject(data) && ('fm-service-status' in data)) {
                    if (data['fm-service-status'] === 'error') {
                        FmGui.show_message('error', data.title, data.message);
                    }
                    if (data['fm-service-status'] === 'success') {
                        status = 'success';
                        result = data.data;
                    }
                    if (data['fm-service-status'] === 'redirect') {
                        status = 'success';
                        window.location.href = data.data;
                        return;
                    }
                } else {
                    FmGui.show_message('error', context.messages['unhandled-error-title'],
                        context.messages['unhandled-error-message'] + 'JS:(' + error + '`' + JSON.stringify(data) + '`)');
                }
                if (callback) {
                    callback(status, result);
                }
            });
        },

        load_categories: function (category_id, $container, callback) {
            FmCtrl.call_service('get_categories', {category_id: category_id}, function (status, categories) {
                if (status === 'success') {
                    $(context.tpl['category-tree']({
                        categories: categories,
                        messages: context.messages
                    })).appendTo($container);
                }

                if ($.isFunction(callback)) {
                    callback();
                }
            });
        },

        load_products: function (category_id, page, callback) {
            // unset active class on previously selected category
            $('.fm-category-tree li').removeClass('active');

            FmCtrl.call_service('get_products', {category: category_id, page: page}, function (status, products) {
                if (status === 'success') {
                    $('.fm-product-list-container').html(context.tpl['product-list']({
                        paths: FmPaths,
                        products: products.products,
                        pagination: products.pagination
                    }));

                    // set active class on selected category
                    $('.fm-category-tree li[data-category_id=' + category_id + ']').addClass('active');

                    // http://stackoverflow.com/questions/5943994/jquery-slidedown-snap-back-issue
                    // set correct height on combinations to fix jquery slideDown jump issue
                    $('.fm-product-list .combinations').each(function (k, v) {
                        $(v).css('height', $(v).height());
                        $(v).hide();
                    });
                }

                if (callback) {
                    callback(status);
                }
            });
        },

        update_product: function (product, percentage, callback) {
            var data = [{
                    product: {
                        id: product,
                        fyndiq_percentage: percentage
                    }
                }];
            do_export_products(data, callback, true);
        },

        load_orders: function (page, callback) {
            FmCtrl.call_service('load_orders', {page: page}, function (status, orders) {
                if (status === 'success') {
                    $('.fm-order-list-container').html(context.tpl['orders-list']({
                        paths: FmPaths,
                        orders: orders.orders,
                        pagination: orders.pagination
                    }));
                }

                if (callback) {
                    callback();
                }
            });
        },

        import_orders: function (callback) {
            FmCtrl.call_service('import_orders', {}, function (status, date) {
                if (status === 'success') {
                    FmGui.show_message('success', context.messages['orders-imported-title'],
                        context.messages['orders-imported-message']);
                }
                if (callback) {
                    callback(date);
                }
            });
        },

        export_products: do_export_products,

        products_delete: function (products, callback) {
            FmCtrl.call_service('delete_exported_products', {products: products}, function (status) {
                if (status === 'success') {
                    FmGui.show_message('success', context.messages['products-deleted-title'],
                        context.messages['products-deleted-message']);
                }
                if (callback) {
                    callback();
                }
            });
        },

        updateCategoryName: function (name) {
            if (FmCtrl.$categoryName === null) {
                FmCtrl.$categoryName = $('#categoryname');
            }
            FmCtrl.$categoryName.html(name);
        },

        update_order_status: function (orders, callback) {
            FmCtrl.call_service('update_order_status', {orders: orders}, function (status, data) {
                if (status === 'success') {
                    if (callback) {
                        callback(data);
                    }
                }
            });
        },


        update_product_status: function(callback) {
            FmCtrl.call_service('update_product_status', {}, function (status, data) {
                if (status === 'success') {
                    if (callback) {
                        callback(data);
                    }
                }
            });
        },

        bind_event_handlers: function () {
            // import orders submit button
            $(document).on('submit', '.fm-form.orders', function (e) {
                e.preventDefault();
                FmGui.show_load_screen();
                FmCtrl.import_orders(function () {
                    FmGui.hide_load_screen();
                });
            });

            // When clicking category in tree, load its products
            $(document).on('click', '.fm-category-tree a', function (e) {
                var $li = $(this).parent();
                var categoryName = $(this).text();
                e.preventDefault();
                var category_id = parseInt($li.attr('data-category_id'), 10);
                if ($li.parents('.fm-category-tree').length === 1) {
                    $('div>.fm-category-tree>li').each(function() {
                        var $el = $(this);
                        if (parseInt($el.data('category_id'), 10) !== category_id) {
                            $el.find('ul').addClass('hidden');
                        } else {
                            $el.find('ul').removeClass('hidden');
                        }
                    });
                }
                FmGui.show_load_screen(function () {
                    if (!$li.data('expanded')) {
                        FmCtrl.load_categories(category_id, $li, function () {
                            $li.data('expanded', true);
                            FmCtrl.load_products(category_id, function () {
                                FmCtrl.updateCategoryName(categoryName);
                                FmGui.hide_load_screen();
                            });
                        });
                    } else {
                        FmCtrl.load_products(category_id, function () {
                            FmCtrl.updateCategoryName(categoryName);
                            FmGui.hide_load_screen();
                        });
                    }
                });
            });

            $(document).on('click', 'div.pages > ol > li > a', function (e) {
                e.preventDefault();

                var category = $('.fm-category-tree li.active').attr('data-category_id');
                FmGui.show_load_screen(function () {
                    var page = $(e.target).attr('data-page');
                    FmCtrl.load_products(category, page, function () {
                        FmGui.hide_load_screen();
                    });
                });
            });

            $(document).on('click', '.fm-product-list input:checkbox', function() {
                var checked = $(this).prop('checked');
                var $discount = $(this).closest('.product').find('.fyndiq_dicsount');
                var isUnPublished = $(this).closest('.product').find('.icon').hasClass('noton');
                if (checked) {
                    $discount.removeAttr('disabled');
                    return;
                }
                if (!isUnPublished) {
                    $discount.attr('disabled', 'disabled');
                }
            });


            // when clicking select all products checkbox, set checked on all product's checkboxes
            $(document).on('click', '#select-all', function () {
                var checked = $(this).is(':checked');
                $('.fm-product-list tr .select input').each(function () {
                    var $this = $(this);
                    if (checked) {
                        $this.prop('checked', true);
                        $('.fm-delete-products').removeClass('disabled').addClass('red');
                        $this.closest('.product').find('.fyndiq_dicsount').removeAttr('disabled');
                    } else {
                        var $product = $this.closest('.product');
                        $this.prop('checked', false);
                        $('.fm-delete-products').removeClass('red').addClass('disabled');
                        if ($product.find('.icon').hasClass('noton')) {
                            $product.find('.fyndiq_dicsount').attr('disabled', 'disabled');
                        }
                    }
                });
            });

            // When clicking select on one product, check if any other is select and make delete button red.
            $(document).on('click', '.fm-product-list > tr', function () {
                var red = false;
                $('.fm-product-list .select input').each(function () {
                    var active = $(this).prop('checked');
                    if (active) {
                        red = true;
                    }
                });
                if (red) {
                    $('.fm-delete-products').removeClass('disabled').addClass('red');
                }
                else {
                    $('.fm-delete-products').removeClass('red').addClass('disabled');
                }
            });

            var saveTimeout;
            $(document).on('keyup', '.fyndiq_dicsount', FmCtrl.debounce(function () {
                var isPublished = !($(this).closest('.product').find('.icon').hasClass('noton'));
                var discount = parseFloat($(this).val());
                var $product = $(this).closest('.product');
                var product_id = $product.attr('data-id');

                if (discount > 100) {
                    discount = 100;
                }
                else if (discount < 0) {
                    discount = 0;
                }

                var price = $product.attr('data-price');
                var field = $(this).closest('.prices').find('.price_preview_price');

                var counted = price - ((discount / 100) * price);
                if (isNaN(counted)) {
                    counted = price;
                }

                field.text(counted.toFixed(2));
                if (isPublished) {
                    clearTimeout(saveTimeout);
                    var ajaxdiv = $(this).parent().parent().find('#ajaxFired');
                    ajaxdiv.html('... ').show();
                    saveTimeout = setTimeout(function () {
                        FmCtrl.update_product(product_id, discount, function (status) {
                            if (status === 'success') {
                                ajaxdiv.html('+').delay(1000).fadeOut();
                                return;
                            }
                            ajaxdiv.html('-').delay(1000).fadeOut();
                        });
                    }, 1000);
                }
            }, 250));

            // when clicking the export products submit buttons, export products
            $(document).on('click', '.fm-export-products', function (e) {
                e.preventDefault();

                var products = [];

                // find all products
                $('.fm-product-list > tr').each(function () {

                    // check if product is selected
                    var active = $(this).find('.select input').prop('checked');
                    if (active) {


                        // store product id and combinations
                        var price = $(this).find('td.prices > div.price > input').val();
                        var fyndiq_percentage = $(this).find('.fyndiq_dicsount').val();
                        products.push({
                            product: {
                                id: $(this).data('id'),
                                fyndiq_percentage: fyndiq_percentage
                            }
                        });
                    }
                });

                // if no products selected, show info message
                if (products.length === 0) {
                    FmGui.show_message('info', context.messages['products-not-selected-title'],
                        context.messages['products-not-selected-message']);

                } else {

                    // helper function that does the actual product export
                    var export_products = function (products) {
                        FmGui.show_load_screen(function () {
                            FmCtrl.export_products(products, function () {
                                FmGui.hide_load_screen();
                            });
                        });
                    };

                    // export the products
                    export_products(products);
                }
            });

            //Deleting selected products from export table
            $(document).on('click', '.fm-delete-products', function (e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) {
                    return;
                }
                FmGui.show_load_screen(function () {
                    var products = [];

                    // find all products
                    $('.fm-product-list .select input:checked').each(function () {
                        products.push({
                            product: {
                                id: $(this).parent().parent().data('id')
                            }
                        });
                    });

                    // if no products selected, show info message
                    if (products.length === 0) {
                        FmGui.show_message('info', context.messages['products-not-selected-title'],
                            context.messages['products-not-selected-message']);
                        FmGui.hide_load_screen();

                    } else {
                        // delete selected products
                        FmCtrl.products_delete(products, function () {
                            // reload category to ensure that everything is reset properly
                            var category = $('.fm-category-tree li.active').attr('data-category_id');
                            var page = parseInt($('div.pages > ol > li.current').html(), 10) || 1;
                            FmCtrl.load_products(category, page, function () {
                                FmGui.hide_load_screen();
                            });

                        });

                    }
                });
            });

            $(document).on('click', '.fm-update-product-status', function(e) {
                e.preventDefault();
                FmCtrl.update_product_status(function () {
                    FmGui.show_load_screen(function () {
                        var category = $('.fm-category-tree li.active').attr('data-category_id');
                        var page = $(e.target).attr('data-page');
                        FmCtrl.load_products(category, page, function () {
                            FmGui.hide_load_screen();
                        });
                    });
                });
            });
        },
        bind_order_event_handlers: function () {
            // import orders submit button
            $(document).on('click', '#fm-import-orders', function (e) {
                e.preventDefault();
                FmGui.show_load_screen();
                FmCtrl.import_orders(function (time) {
                    $('#fm-order-import-date').html(
                        context.tpl['order-import-date-content']({
                            paths: FmPaths,
                            import_time: time
                        }));
                    var page = parseInt($('div.pages > ol > li.current').html(), 10) || 1;
                    FmCtrl.load_orders(page, function () {
                        FmGui.hide_load_screen();
                    });
                });
            });

            // when clicking select all orders checkbox, set checked on all order's checkboxes
            $(document).on('click', '#select-all', function () {
                if ($(this).is(':checked')) {
                    $('.fm-orders-list tr .select input').each(function () {
                        $(this).prop('checked', true);
                    });

                } else {
                    $('.fm-orders-list tr .select input').each(function () {
                        $(this).prop('checked', false);
                    });
                }
            });

            $(document).on('click', '.getdeliverynote', function (e) {
                if ($('.fm-orders-list > tr .select input:checked').length === 0) {
                    e.preventDefault();
                    return FmGui.show_message('info', context.messages['orders-not-selected-title'],
                        context.messages['orders-not-selected-message']);
                }
                $('#download').off('load').on('load', function() {
                    var $iframe = $(this);
                    var errorMessage = $iframe.contents().find('body').text();
                    if (errorMessage !== '') {
                        FmGui.show_message('error', messages['unhandled-error-title'], errorMessage);
                    }
                });
            });

            // pagination trigger
            $(document).on('click', 'div.pages > ol > li > a', function (e) {
                e.preventDefault();

                FmGui.show_load_screen(function () {
                    var page = $(e.target).attr('data-page');
                    FmCtrl.load_orders(page, function () {
                        FmGui.hide_load_screen();
                    });
                });
            });

            $(document).on('click', '.markasdone', function (e) {
                e.preventDefault();
                var $orderRows = $('.fm-orders-list > tr .select input:checked');
                if ($orderRows.length > 0) {
                    var orders = [];
                    $orderRows.each(function () {
                        orders.push(
                            $(this).closest('tr').data('id')
                        );
                    });
                    FmCtrl.update_order_status(orders, function (newStatus) {
                        $orderRows.each(function () {
                            $(this).closest('tr').children('.state').text(newStatus);
                        });
                        FmGui.show_message('success', context.messages['orders-state-updated-title'],
                            context.messages['orders-state-updated-message']);
                    });
                }
                else {
                    FmGui.show_message('info', context.messages['orders-not-selected-title'],
                        context.messages['orders-not-selected-message']);
                }
            });
        }
    };
})(window, jQuery);
