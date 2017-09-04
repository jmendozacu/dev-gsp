function fireEvent(element, event) {
    if (document.createEventObject) {
        // dispatch for IE
        var evt = document.createEventObject();
        return element.fireEvent('on' + event, evt)
    }
    else {
        // dispatch for firefox + others
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent(event, true, true); // event type,bubbling,cancelable
        return !element.dispatchEvent(evt);
    }
}

Event.observe(document, 'dom:loaded', function () {
    // if ($('billing-address-select')) {
    //     document.getElementById('billing-address-select').value = '';
    //     fireEvent(document.getElementById('billing-address-select'), 'change');
    // }
    var ssn = $('ssn_click');
    if (ssn) {
        ssn.observe('click', function (event) {
            // Check button is disabled
            if ($(this).hasClassName('disabled')) {
                return;
            }

            var url = MAGENTO_BASE_URL + 'social_security_number/getaddr';
            var ssn = $$('[name="socialSecurityNumber"]')[0].value;

            $(this).addClassName('disabled');
            var self = this;
            var request = new Ajax.Request(
                url, {
                    method: 'post',
                    parameters: { ssn: ssn },
                    onSuccess: function (response) {
                        $(self).removeClassName('disabled');
                        var json = response.responseText.evalJSON();
                        if (!json.success) {
                            alert(json.message);
                            return;
                        }
                        // Set Form Fields
                        if ($('billing:firstname')) $('billing:firstname').setValue(json.first_name);
                        if ($('billing:lastname')) $('billing:lastname').setValue(json.last_name);
                        if ($('billing:company')) $('billing:company').setValue('');
                        if ($('billing:street1')) $('billing:street1').setValue(json.address_1);
                        if ($('billing:street2')) $('billing:street2').setValue(json.address_2);
                        if ($('billing:city')) $('billing:city').setValue(json.city);
                        if ($('billing:region')) $('billing:region').setValue('');
                        if ($('billing:postcode')) $('billing:postcode').setValue(json.postcode);
                        if ($('billing:country_id')) $('billing:country_id').setValue(json.country);
                    }
                }
                );
        });
    }
});
