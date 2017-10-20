/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2015 MageWorx (http://www.mageworx.com/)
 */

var OrdersEditEdit = Class.create();
OrdersEditEdit.prototype = {

    initialize: function (editUrl, productGridUrl, customersGridUrl, submitCustomerUrl, saveOrderUrl, blocksConfig, beforeTabCloseText, saveChangesUrl, cancelChangesUrl) {

        this.editLinkTmp = '<div class="tools"><a href="#" onclick="orderEdit.loadEditForm(\'%block_id%\', this); return false;">Edit</a></div>';
        this.editUrl = editUrl;
        this.productGridUrl = productGridUrl;
        this.customersGridUrl = customersGridUrl;
        this.submitCustomerUrl = submitCustomerUrl;
        this.saveOrderUrl = saveOrderUrl;
        this.blocksConfig = blocksConfig;
        this.saveOrderChangesUrl = saveChangesUrl;
        this.cancelOrderChangesUrl = cancelChangesUrl;
        this.currentBlock = false;
        this.addEditLinks();
        this.hasChanges = false;
        this.surcharge = 0;

        Event.observe(window, 'beforeunload', function (event) {
            if (orderEdit.hasChanges) {
                event.returnValue = beforeTabCloseText;
            }
        });
    },

    addEditLinks: function () {

        var parent = this;
        ////// BugFix for magento wrong class name for block order_info
        var infoEl = $$('#sales_order_view_tabs_order_info_content .box-left .entry-edit-head h4.head-account')[0];
        infoEl.removeClassName('head-account');
        infoEl.addClassName('head-general');
        //// end of BugFix

        var linkTemplate = this.editLinkTmp;

        $$('#sales_order_view_tabs_order_info_content .entry-edit-head div.tools').each(function (oldEditLink) {
            if (!parent.skipBlock(oldEditLink.innerHTML)) {
                oldEditLink.remove();
            }
        });

        this.blocksConfig.each(function (block) {
            $$('#sales_order_view_tabs_order_info_content .entry-edit-head h4.' + block.className).each(function (el) {
                if (!parent.skipBlock(el.innerHTML)) {
                    var editLink = linkTemplate.replace('%block_id%', block.blockId);
                    el.insert({after: editLink});
                }
            });
        });
    },

    skipBlock: function (str) {
        var blocks = ['amorderattrorder', 'head-attributes', 'Attributes'];
        var skip = false;

        blocks.forEach(function (blockString) {
            if (typeof blockString != 'undefined') {
                var expr = new RegExp(blockString, 'ig');
                var check = expr.test(str);
                if (check === true) {
                    skip = true;
                    return true;
                }
            }
        });

        return skip;
    },

    loadEditForm: function (blockId, element) {

        if (blockId != 'order_items') {
            this.cancel();
        }

        var url = this.editUrl.replace('%block_id%', blockId);
        if (element) {
            var oldFieldset = element.up('div.entry-edit').down('div.fieldset');
            if (!oldFieldset) {
                oldFieldset = element.up('div.entry-edit').down('fieldset');
            }
        }

        if (blockId == 'order_items') {
            oldFieldset = document.querySelector('.grid.np');
        }

        var parent = this;
        new Ajax.Request(url, {
            onComplete: function (transport) {

                oldFieldset.addClassName('hidden-fieldset');
                if (blockId == 'order_items') {
                    $$('#ordersedit-fieldset').each(function (el) {
                        el.remove();
                    });
                }
                oldFieldset.insert({after: '<div class="fieldset" id="ordersedit-fieldset">' + transport.responseText + '</div>'});
                document.ordersEditForm = new VarienForm('ordersedit_edit_form');
                parent.currentBlock = blockId;
                parent.addEventListenerToQtyInputs();
                if (blockId == 'order_items') {
                    document.getElementById('ordersedit-fieldset')
                        .querySelector('.ordersedit-buttons button.mw-cancel-button').hide();
                    document.getElementById('mw-add-new-product').show();
                }
            }
        });
    },

    cancel: function () {

        var currentFieldset = this.getCurrentFieldset();
        if (currentFieldset &&
            currentFieldset instanceof Element &&
            !currentFieldset.querySelector('#order-items_grid')
        ) {
            var hiddenFieldset = currentFieldset.up().querySelector('.hidden-fieldset');
            if (hiddenFieldset) {
                hiddenFieldset.removeClassName('hidden-fieldset');
            }

            currentFieldset.remove();
        }

        this.currentBlock = false;
    },

    getCurrentFieldset : function () {
        var currentFieldset = null;
        var allFieldset = document.querySelectorAll('#ordersedit-fieldset');
        if (allFieldset.length > 1) {
            for (var key in allFieldset) {
                if (allFieldset.hasOwnProperty(key) &&
                    allFieldset[key] instanceof Element &&
                    !allFieldset[key].querySelector('#order-items_grid')
                ) {
                    currentFieldset = allFieldset[key];
                    break;
                }
            }
        } else {
            currentFieldset = document.getElementById('ordersedit-fieldset');
        }

        return currentFieldset;
    },

    getOrigFieldset : function() {
        var origFieldset = null;
        var allFieldset = document.getElementById('sales_order_view_tabs_order_info_content').querySelectorAll('.hidden-fieldset');

        if (allFieldset.length > 1) {
            for (var key in allFieldset) {
                if (allFieldset.hasOwnProperty(key) &&
                    allFieldset[key] instanceof Element &&
                    !allFieldset[key].hasClassName('np')
                ) {
                    origFieldset = allFieldset[key];
                    break;
                }
            }
        } else {
            origFieldset = document.querySelector('#sales_order_view_tabs_order_info_content .hidden-fieldset');
        }

        return origFieldset;
    },

    getFormParams: function () {

        var params = {};
        var fieldset = this.getCurrentFieldset();

        if (this.currentBlock == 'payment_method') {
            $$('input[name^="payment[method]"]').each(function (el) {
                if (el.checked) {
                    params[el.name] = el.value;
                    var paymentForm = $('payment_form_' + el.value);
                    if (paymentForm) {
                        paymentForm.select('input', 'select', 'textarea').each(function (el) {
                            if (el.checked || (el.type != 'checkbox' && el.type != 'radio')) {
                                params[el.name] = el.value;
                            }
                        });
                    }
                }
            });

        } else if (this.currentBlock == 'shipping_method') {
            fieldset.select('input', 'select', 'textarea').each(function (el) {
                if (el.checked || (el.type != 'checkbox' && el.type != 'radio')) {
                    params[el.name] = el.value;
                }
            });

        } else if (this.currentBlock == 'shipping_address' || this.currentBlock == 'billing_address') {
            $('ordersedit_edit_form').select('input', 'select', 'textarea').each(function (el) {
                if (el.checked || (el.type != 'checkbox' && el.type != 'radio')) {
                    var valName = orderEdit.currentBlock + '[' + el.name + ']';
                    params[valName] = el.value;
                }
            });

        } else if (this.currentBlock == 'order_items') {
            params = orderEditItems.getFormParams();
            orderEditItems.gridProducts = $H({});

        } else if (this.currentBlock == 'sales_order_coupons') {
            fieldset.select('input', 'select', 'textarea').each(function (el) {
                if (el.checked || (el.type != 'checkbox' && el.type != 'radio')) {
                    params[el.name] = el.value;
                }
            });

        } else {
            var form = $('ordersedit_edit_form');
            if (form) {
                params = form.serialize();
            }
        }

        params.surcharge = this.surcharge;

        return params;
    },

    saveOrder: function () {

        if (document.ordersEditForm.form == null || document.ordersEditForm.validator.validate()) {

            var url = this.saveOrderUrl;
            var params = this.getFormParams();
            var parent = this;

            url = url.replace('%edited_block%', this.currentBlock);

            new Ajax.Request(url, {
                parameters: params,
                onComplete: function (transport) {

                    var result = transport.responseText.evalJSON();
                    var element = '';

                    if (typeof result.exception != 'undefined') {
                        try {
                            if (parent.currentBlock == 'order_items') {
                                element = $$('#sales_order_view_tabs_order_info_content').first().querySelector('.head-products').up().querySelector('.tools > a');
                            } else {
                                element = $$('#sales_order_view_tabs_order_info_content .hidden-fieldset').first().up().querySelector('.tools > a');
                            }
                            parent.showException(result.exception, parent.currentBlock, element);
                        } catch (e) {
                            console.log(e);
                        }
                    } else {
                        var changedBlockHtml = result[parent.currentBlock];
                        var updateItems = false;
                        if (parent.currentBlock == 'sales_order_coupons') {
                            updateItems = true;
                        }

                        if (parent.currentBlock == 'order_items' || !parent.currentBlock) {
                            parent.loadEditForm('order_items', null);
                        } else {
                            var origFieldset = parent.getOrigFieldset();
                            if (origFieldset) {
                                origFieldset.update(changedBlockHtml);
                            }
                            parent.cancel();
                            parent.hasChanges = true;
                            if (updateItems) {
                                parent.loadEditForm('order_items', null);
                            }
                        }

                        if (typeof result.temp_totals != 'undefined') {
                            parent.showTempTotals(result.temp_totals);
                        }

                        parent.decorateChangedBlocks();
                    }
                }
            });
        }
    },

    changeCustomer: function () {
        this.showOverlay();
        var parent = this;
        new Ajax.Request(this.customersGridUrl, {
            onComplete: function (transport) {
                parent.showPopup(transport.responseText);

                $$('#customerGrid tr[title^="submit_customer_"]').each(function (el) {
                    el.observe('click', function (event) {
                        customerId = el.title.replace('submit_customer_', '');
                        parent.submitCustomer(customerId);
                        $(el).stopObserving('click');
                    });
                });
            }
        });
    },

    searchProducts: function () {
        new Ajax.Request(this.productGridUrl, {
            onComplete: function (transport) {
                var gridEl = $('ordersedit_product_grid');
                if (gridEl) {
                    gridEl.update(transport.responseText);
                    console.log(document.getElementById('ordersedit_product_grid'));
                    document.getElementById('ordersedit_product_grid').show();
                    var buttons = document.getElementById('ordersedit-fieldset')
                        .querySelector('.ordersedit-buttons button.mw-submit-button');
                    var buttonsClone = buttons.cloneNode(true);
                    document.getElementById("productGrid").querySelector('.filter-actions').appendChild(buttonsClone);
                    document.getElementById('mw-add-new-product').hide();
                }
            }
        });
    },

    showOverlay: function () {
        this.hidePopup();

        var overlay = new Element('div');
        overlay.id = 'ordersedit-overlay';
        $$('body')[0].insert(overlay);
    },

    submitCustomer: function (customerId) {
        var url = this.submitCustomerUrl.replace('%customer_id%', customerId);
        new Ajax.Request(url, {
            onComplete: function (transport) {
                customerData = transport.responseText.evalJSON();

                $('customer_id').value = customerData.entity_id;
                $('current_customer_id').innerHTML = customerData.entity_id;
                $('customer_firstname').value = customerData.firstname;
                $('customer_lastname').value = customerData.lastname;
                $('customer_email').value = customerData.email;
                $$('#customer_group_id option[value="' + customerData.group_id + '"]')[0].selected = true;
            }
        });

        this.hidePopup();
    },

    showPopup: function (content) {
        var overlay = $('ordersedit-overlay');
        var popup = new Element('div');
        popup.id = 'ordersedit-popup';

        popup.insert('<a href="#" class="cancel" onclick="orderEdit.hidePopup()">[x] Cancel</a>');
        popup.insert(content);
        overlay.insert({after: popup});

        var width = window.innerWidth || document.documentElement.clientWidth;
        var posLeft = (width - $('ordersedit-popup').getWidth()) / 2;
        popup.style.left = posLeft + 'px';

        var height = window.innerHeight || document.documentElement.clientHeight;
        var posTop = (height - $('ordersedit-popup').getHeight()) / 2;
        popup.style.top = posTop + 'px';
    },

    hidePopup: function () {
        $$('#ordersedit-overlay').each(function (el) {
            el.remove();
        });

        $$('#ordersedit-popup').each(function (el) {
            el.remove();
        });
    },

    editComment: function (commentId) {
        this.cancelCommentEdit(commentId);
        $('order_comment_' + commentId).hide();
        $('edit_comment_' + commentId).show();
    },

    cancelCommentEdit: function (commentId) {
        $$('#order_history_block form[id^="edit_comment_"]').each(function (el) {
            el.hide();
            $('order_comment_' + commentId).show();
        });
    },

    saveCommentEdit: function (commentId) {
        var commentForm = $('edit_comment_' + commentId);
        new Ajax.Request(commentForm.action, {
            parameters: commentForm.serialize(),
            onComplete: function (transport) {
                $('order_history_block').update(transport.responseText);
            }
        });
    },

    switchPaymentMethod: function (code) {

        $$('#order-billing_method_form ul[id^="payment_form_"]').each(function (formEl) {
            formEl.select('input, select, textarea').each(function (el) {
                el.disabled = true;
            });
            formEl.hide();
        });

        var pMethodForm = $('payment_form_' + code);
        if (pMethodForm) {
            pMethodForm.select('input, select, textarea').each(function (el) {
                el.disabled = false;
            });
            pMethodForm.show();
        }
    },

    removeCoupon: function () {
        $('coupons:code').setValue('').up().up().hide();
        this.saveOrder();
    },

    showTempTotals: function (html) {
        if ($$('.new-totals').first()) {
            $$('.new-totals').first().up().replace(html);
        } else {
            $$('.box-left.mw_coupons').first().insert({after: html});
        }
    },

    decorateChangedBlocks: function () {
        $$('.changed-block').each(function (element) {
            if (element.up().hasClassName('grid') && element.up().hasClassName('np')) {
                document.querySelector('.head-products').up('.entry-edit-head').addClassName('changed-block-head').removeClassName('error-block-head');
            } else {
                element.up('.entry-edit').down('.entry-edit-head').addClassName('changed-block-head').removeClassName('error-block-head');
            }
        });
    },

    applyChangedOrder: function () { // save changes
        var message = Translator.translate('Are you sure you want to apply the changes the order?');
        if (confirm(message)) {
            orderEdit.hasChanges = false; // Skip validation
            window.setLocation(this.saveOrderChangesUrl);
        }
    },

    cancelChangedOrder: function () {
        orderEdit.hasChanges = false;
        window.setLocation(this.cancelOrderChangesUrl);
    },

    showException: function (eMsg, currentBlock, element) {
        orderEdit.loadEditForm(currentBlock, element);
    },

    setSurcharge: function (value) {
        this.surcharge = value;
    },

    saveOrderWithSurcharge: function () {
        var message = Translator.translate('Are you sure you want to apply the changes to the order and send the payment link to the customer?');
        if (confirm(message)) {
            this.surcharge = 1;
            orderEdit.hasChanges = false; // Skip validation
            var param = 'surcharge=' + this.surcharge;
            var urlWithSurcharge = this.saveOrderChangesUrl + (this.saveOrderChangesUrl.split('?')[1] ? '&' : '?') + param;
            window.setLocation(urlWithSurcharge);
        }
    },

    addEventListenerToQtyInputs: function () {
        var parent = this;
        var qtyInputs = document.querySelectorAll("" +
            "#order-items_grid .item-qty, " +
            "#order-items_grid .item-price, " +
            "#order-items_grid .items-grid-action," +
            "#order-items_grid .item_use_discount" +
            "");

        for (var key in qtyInputs) {
            if (!qtyInputs.hasOwnProperty(key)) {
                continue;
            }

            var el = qtyInputs[key];
            if (el instanceof Element) {
                if (el.type == 'checkbox') {
                    el.addEventListener("click", function (e) {
                        if (e.target.value != '') {
                            parent.currentBlock = 'order_items';
                            parent.saveOrder();
                        }
                    }, false);
                } else {
                    el.addEventListener("change", function (e) {
                        if (e.target.value != '') {
                            parent.currentBlock = 'order_items';
                            parent.saveOrder();
                        }
                    }, false);
                }
            }
        }
    }
};

var OrdersEditEditItems = Class.create();
OrdersEditEditItems.prototype = {

    initialize: function (currencySymbol, quoteItems) {

        this.gridProducts = $H({});
        this.quoteItems = quoteItems;
        this.productPriceBase = {};
        this.currencySymbol = currencySymbol;

    },

    showQuoteItemConfiguration: function (quoteItemId) {
        productConfigure.showItemConfiguration('quote_items', quoteItemId);
    },

    getFormParams: function () {
        var params = {};

        $$('#order-items_grid tr[id^="order_item_"]').each(function (itemRow) {

            itemId = itemRow.id.replace('order_item_', '');

            var qtyField = $$('input[name="item[' + itemId + '][qty]"]')[0];
            if (typeof qtyField != 'undefined') {
                params['quote_items[' + itemId + '][qty]'] = qtyField.value;
            }

            var instructionField = $$('textarea[name="item[' + itemId + '][instruction]"]')[0];
            if (typeof instructionField != 'undefined') {
                params['quote_items[' + itemId + '][instruction]'] = instructionField.value;
            }

            var useCustomPriceField = $$('input[id="item_use_custom_price_' + itemId + '"]')[0];
            if (typeof useCustomPriceField != 'undefined' && useCustomPriceField.checked) {
                params['quote_items[' + itemId + '][use_custom_price]'] = "1";
            }

            var customPriceField = $$('input[name="item[' + itemId + '][custom_price]"]')[0];
            if (typeof customPriceField != 'undefined') {
                params['quote_items[' + itemId + '][custom_price]'] = customPriceField.value;
            }

            var discountField = $$('input[name="item[' + itemId + '][use_discount]"]')[0];
            if (typeof discountField != 'undefined' && discountField.checked) {
                params['quote_items[' + itemId + '][use_discount]'] = discountField.value;
            }

            var actionField = $$('select[name="item[' + itemId + '][action]"]')[0];
            if (typeof actionField != 'undefined') {
                params['quote_items[' + itemId + '][action]'] = actionField.value;
            }

        });

        this.gridProducts.each(function (prod) {

            var product_id = prod.key;
            var qty = prod.value.qty;
            if (qty <= 0) {
                qty = 1;
            }

            params['product_to_add[' + product_id + '][qty]'] = qty;

            var optionsBlock = $('product_composite_configure_confirmed[product_to_add][' + product_id + ']');
            if (optionsBlock) {
                optionsBlock.select('input', 'select', 'textarea').each(function (el) {
                    if (el.checked || (el.type != 'checkbox' && el.type != 'radio')) {

                        var valName = el.name;
                        if (valName.indexOf('[')) {
                            valName = valName.split(']').join('');
                            valName = valName.split('[').join('][');
                        }
                        params["product_to_add[" + product_id + "][" + valName + "]"] = el.value;

                    }
                });
            }
        });

        for (var key in this.quoteItems) {
            if (this.quoteItems.hasOwnProperty(key)) {
                var product_id = key;
                var qty = this.quoteItems[key].qty;
                if (qty <= 0) {
                    continue;
                }

                var optionsBlock = $('product_composite_configure_confirmed[quote_items][' + product_id + ']');
                if (optionsBlock) {
                    optionsBlock.select('input', 'select', 'textarea').each(function (el) {
                        if (el.checked || (el.type != 'checkbox' && el.type != 'radio')) {
                            var valName = el.name;
                            if (valName.indexOf('[')) {
                                valName = valName.split(']').join('');
                                valName = valName.split('[').join('][');
                            }
                            params["quote_items[" + product_id + "][" + valName + "]"] = el.value;
                        }
                    });
                    params['quote_items[' + product_id + '][configured]'] = 1;
                    params['quote_items[' + product_id + '][action]'] = 'remove';
                    console.log(params);
                }
            } else {
                console.log('no property: ' + key);
            }
        }

        console.log(params);
        return params;
    },

    productGridRowClick: function (grid, event) {
        var trElement = Event.findElement(event, 'tr');
        var qtyElement = trElement.select('input[name="qty"]')[0];
        var eventElement = Event.element(event);
        var isInputCheckbox = eventElement.tagName == 'INPUT' && eventElement.type == 'checkbox';
        var isInputQty = eventElement.tagName == 'INPUT' && eventElement.name == 'qty';
        if (trElement && !isInputQty) {
            var checkbox = Element.select(trElement, 'input[type="checkbox"]')[0];
            var confLink = Element.select(trElement, 'a')[0];
            var priceColl = Element.select(trElement, '.price')[0];
            if (checkbox) {
                // processing non composite product
                if (confLink.readAttribute('disabled')) {
                    var checked = isInputCheckbox ? checkbox.checked : !checkbox.checked;
                    grid.setCheckboxChecked(checkbox, checked);
                    // processing composite product
                } else if (isInputCheckbox && !checkbox.checked) {
                    grid.setCheckboxChecked(checkbox, false);
                    // processing composite product
                } else if (!isInputCheckbox || (isInputCheckbox && checkbox.checked)) {
                    var listType = confLink.readAttribute('list_type');
                    var productId = confLink.readAttribute('product_id');
                    if (typeof this.productPriceBase[productId] == 'undefined') {
                        var priceBase = priceColl.innerHTML.match(/.*?([\d,]+\.?\d*)/);
                        if (!priceBase) {
                            this.productPriceBase[productId] = 0;
                        } else {
                            this.productPriceBase[productId] = parseFloat(priceBase[1].replace(/,/g, ''));
                        }
                    }
                    productConfigure.setConfirmCallback(listType, function () {
                        // sync qty of popup and qty of grid
                        var confirmedCurrentQty = productConfigure.getCurrentConfirmedQtyElement();
                        if (qtyElement && confirmedCurrentQty && !isNaN(confirmedCurrentQty.value)) {
                            qtyElement.value = confirmedCurrentQty.value;
                        }
                        // calc and set product price
                        var productPrice = parseFloat(this._calcProductPrice() + this.productPriceBase[productId]);
                        priceColl.innerHTML = this.currencySymbol + productPrice.toFixed(2);
                        // and set checkbox checked
                        grid.setCheckboxChecked(checkbox, true);
                    }.bind(this));
                    productConfigure.setCancelCallback(listType, function () {
                        if (!$(productConfigure.confirmedCurrentId) || !$(productConfigure.confirmedCurrentId).innerHTML) {
                            grid.setCheckboxChecked(checkbox, false);
                        }
                    });
                    productConfigure.setShowWindowCallback(listType, function () {
                        // sync qty of grid and qty of popup
                        var formCurrentQty = productConfigure.getCurrentFormQtyElement();
                        if (formCurrentQty && qtyElement && !isNaN(qtyElement.value)) {
                            formCurrentQty.value = qtyElement.value;
                        }
                    }.bind(this));
                    productConfigure.showItemConfiguration(listType, productId);
                }
            }
        }
    },

    productGridCheckboxCheck: function (grid, element, checked) {
        if (checked) {
            if (element.inputElements) {
                this.gridProducts.set(element.value, {});
                var product = this.gridProducts.get(element.value);
                for (var i = 0; i < element.inputElements.length; i++) {
                    var input = element.inputElements[i];
                    if (!input.hasClassName('input-inactive')) {
                        input.disabled = false;
                        if (input.name == 'qty' && !input.value) {
                            input.value = 1;
                        }
                    }

                    if (input.checked || input.name != 'giftmessage') {
                        product[input.name] = input.value;
                    } else if (product[input.name]) {
                        delete(product[input.name]);
                    }
                }
            }
        } else {
            if (element.inputElements) {
                for (var i = 0; i < element.inputElements.length; i++) {
                    element.inputElements[i].disabled = true;
                }
            }
            this.gridProducts.unset(element.value);
        }

        grid.reloadParams = {'products[]': this.gridProducts.keys()};
    },

    productGridRowInit: function (grid, row) {
        var checkbox = $(row).select('.checkbox')[0];
        var inputs = $(row).select('.input-text');
        if (checkbox && inputs.length > 0) {
            checkbox.inputElements = inputs;
            for (var i = 0; i < inputs.length; i++) {
                var input = inputs[i];
                input.checkboxElement = checkbox;

                var product = this.gridProducts.get(checkbox.value);
                if (product) {
                    var defaultValue = product[input.name];
                    if (defaultValue) {
                        if (input.name == 'giftmessage') {
                            input.checked = true;
                        } else {
                            input.value = defaultValue;
                        }
                    }
                }

                input.disabled = !checkbox.checked || input.hasClassName('input-inactive');

                Event.observe(input, 'keyup', this.productGridRowInputChange.bind(this));
                Event.observe(input, 'change', this.productGridRowInputChange.bind(this));
            }
        }
    },

    productGridRowInputChange: function (event) {
        var element = Event.element(event);
        if (element && element.checkboxElement && element.checkboxElement.checked) {
            if (element.name != 'giftmessage' || element.checked) {
                this.gridProducts.get(element.checkboxElement.value)[element.name] = element.value;
            } else if (element.name == 'giftmessage' && this.gridProducts.get(element.checkboxElement.value)[element.name]) {
                delete(this.gridProducts.get(element.checkboxElement.value)[element.name]);
            }
        }
    },

    _calcProductPrice: function () {
        var productPrice = 0;
        var getPriceFields = function (elms) {
            var productPrice = 0;
            var getPrice = function (elm) {
                var optQty = 1;
                if (elm.hasAttribute('qtyId')) {
                    if (!$(elm.getAttribute('qtyId')).value) {
                        return 0;
                    } else {
                        optQty = parseFloat($(elm.getAttribute('qtyId')).value);
                    }
                }
                if (elm.hasAttribute('price') && !elm.disabled) {
                    return parseFloat(elm.readAttribute('price')) * optQty;
                }
                return 0;
            };
            for (var i = 0; i < elms.length; i++) {
                if (elms[i].type == 'select-one' || elms[i].type == 'select-multiple') {
                    for (var ii = 0; ii < elms[i].options.length; ii++) {
                        if (elms[i].options[ii].selected) {
                            productPrice += getPrice(elms[i].options[ii]);
                        }
                    }
                }
                else if (((elms[i].type == 'checkbox' || elms[i].type == 'radio') && elms[i].checked)
                    || ((elms[i].type == 'file' || elms[i].type == 'text' || elms[i].type == 'textarea' || elms[i].type == 'hidden')
                    && Form.Element.getValue(elms[i]))
                ) {
                    productPrice += getPrice(elms[i]);
                }
            }
            return productPrice;
        }.bind(this);
        productPrice += getPriceFields($(productConfigure.confirmedCurrentId).getElementsByTagName('input'));
        productPrice += getPriceFields($(productConfigure.confirmedCurrentId).getElementsByTagName('select'));
        productPrice += getPriceFields($(productConfigure.confirmedCurrentId).getElementsByTagName('textarea'));
        return productPrice;
    },

    toggleCustomPrice: function (checkbox, elemId, tierBlock) {
        if (checkbox.checked) {
            $(elemId).disabled = false;
            $(elemId).show();
            if ($(tierBlock)) {
                $(tierBlock).hide();
            }
        }
        else {
            $(elemId).disabled = true;
            $(elemId).hide();
            if ($(tierBlock)) {
                $(tierBlock).show();
            }
        }
    }
};

var OrderAddress = new Class.create();
OrderAddress.prototype = {
    initialize : function()
    {
        this.addresses = $H({});
    },

    setAddresses : function(addresses){
        this.addresses = addresses;
    },

    selectAddress : function(el, container){
        id = el.value;
        if (id.length == 0) {
            id = '0';
        }
        if(this.addresses[id]){
            this.fillAddressFields(container, this.addresses[id]);
        }
        else{
            this.fillAddressFields(container, {});
        }
    },

    fillAddressFields : function(container, data){
        var regionIdElem = false;
        var regionIdElemValue = false;

        var fields = $(container).select('input', 'select', 'textarea');

        for(var i=0;i<fields.length;i++){
            // skip input type file @Security error code: 1000
            if (fields[i].tagName.toLowerCase() == 'input' && fields[i].type.toLowerCase() == 'file') {
                continue;
            }

            if (typeof data[fields[i].name] !== 'undefined') {
                fields[i].setValue(data[fields[i].name] ? data[fields[i].name] : '');
            }
            if (fields[i].name == 'street[0]') {
                fields[i].setValue(data['street']);
            }

            if (fields[i].changeUpdater) fields[i].changeUpdater();
            if (name == 'region' && data['region_id'] && !data['region']){
                fields[i].value = data['region_id'];
            }
        }
    }
};