var kco = Class.create({
	initialize:function(ajaxUrl){
		this.ajaxUrl = ajaxUrl;
		
		/**
		 * Get the container used for ajax updates, the CONTENT of this will be updated by ajax
		 */
		this.summaryContainer = $('klarnacheckout-cart-wrapper');
        this.couponContainer = $('klarnacheckout-coupon-wrapper');
		this.snippetContainer = $('KCO-snippet'); // Note: this has to be replaced when updated
		
		this.attachModifacationEvents();
	},
	
	attachModifacationEvents:function(){
		this.summaryContainer.up().select('.kco-action').each(function(elm){
			elm.observe('click',this.modificationObserver.bindAsEventListener(this));
		},this);
		this.summaryContainer.up().select('.kco-action-dropdown').each(function(elm){
			elm.observe('change',this.modificationObserver.bindAsEventListener(this));
		},this);
	},
	
	resetObservers:function(){
		this.attachModifacationEvents();
	},
	
	modificationObserver:function(event,reloadExternal){
		var itemId = 'NULL';
		var targetQty = 'NULL';
		var couponCode = 'NULL';
		var shippingMethod = 'NULL';
		var postcode = 'NULL';
		var email = 'NULL';
		
		if(event == null){ // for reload
			var elm = null;
			var action = 'NULL';
		}
		else {
			var elm = event.element();
			
			if(!elm.hasClassName('kco-action') && !elm.hasClassName('kco-action-dropdown')) elm = elm.up();
			
			var action = elm.readAttribute("data-action");			
			switch(action) {
				case "changeQty":
                    var itemId = elm.readAttribute('data-itemid');
                    var targetQty = elm.readAttribute('data-target-qty');	
					break;
					
				case "couponCodeAdd":
					var couponCode = $('kco-coupon-code').getValue();
					if(action == "couponCodeAdd" && couponCode == "") {
						alert(Translator.translate("A valid coupon code must be entered"));
						return;
					}
					break;
					
				case "couponCodeRemove":
					var couponCode = '';
					break;
					
				case "setShippingMethod":
					var shippingMethod = elm.readAttribute("data-shipping-code");
					break;
					
				case "setShippingMethodDropdown":
					var shippingMethod = elm.value;
					break;
					
				case "saveStepOne":
					if($('kco-email')){
						if(!Validation.validate($('kco-email'))) return false;
						var email = $('kco-email').getValue();
					}
					
					if($('kco-postcode')){
						if(!Validation.validate($('kco-postcode'))) return false;
						var postcode = $('kco-postcode').getValue();
					}
					break;

                case "sendHelpForm":

                    var help_email = '';
                    var help_phone = '';
                    var help_message = '';

                    if($('kco-help-email')){
                        if(!Validation.validate($('kco-help-email'))) return false;
                        help_email = $('kco-help-email').getValue();
                    }

                    if($('kco-help-phone')){
                        if(!Validation.validate($('kco-help-phone'))) return false;
                        help_phone = $('kco-help-phone').getValue();
                    }

                    if($('kco-help-message')){
                        if(!Validation.validate($('kco-help-message'))) return false;
                        help_message = $('kco-help-message').getValue();
                    }

                    if(!help_phone && !help_email){
                        alert(Translator.translate("Email or phone has to be entered"));
                        return false;
                    }

                    elm.addClassName('loading');
                    this.sendHelpForm(help_email, help_phone, help_message);
                    return false; // this is a special case

                    break;
			}			
		}
		
		if(targetQty == 0 && !confirm(Translator.translate("Are you sure you want to delete this product from your cart?"))) return;
		
		// add loading indicators
		if(elm && elm.up('div.kco-action-wrapper')){
			var actionWrapper = elm.up('div.kco-action-wrapper');
			actionWrapper.down('.kco-qty').addClassName('loading-indicator');
		}
		
		// loading for step one
		if(action == 'saveStepOne'){
			elm.addClassName('loading');
		}
		
		// suspend the checkout
		if(window._klarnaCheckout) {
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}
		
		var thisClass = this;
		thisClass.action = action;
		new Ajax.Request(this.ajaxUrl, {
            method: 'post',
				parameters:{
					item_id:itemId,
					item_qty:targetQty,
					coupon_code:couponCode,
					shipping_method:shippingMethod,
					postcode:postcode,
					email:email
				},
            onSuccess: function(transport){
					var data = transport.responseText.evalJSON();

                    // update cart contents (summary)
					thisClass.summaryContainer.update(data.summary);

                    // update Coupon Code
                    thisClass.couponContainer.update(new Element('div').update(data.coupon).down(1));
					
					// resume the checkout
					if(window._klarnaCheckout) {
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
					
					// if kco isn't rendered
					if($('KCO-snippet') && $('KCO-snippet').innerHTML == '' && data.snippet){
						$('KCO-snippet').replace(data.snippet);
					}
					
					// loading for step one
					if(action == 'saveStepOne'){
						elm.removeClassName('loading');
					}
					
					thisClass.resetObservers();
					
					if(thisClass.action == 'couponCodeAdd') {
						$('kco-coupon-info').update(data.message);
						$('kco-coupon-info').show();
						
						if(data.success == true)
							$('kco-coupon-remove').show();
					}
					
					if(thisClass.action == 'couponCodeRemove') {
						$('kco-coupon-info').update(data.message);
						$('kco-coupon-info').show();
						
						if(data.success == true)
							$('kco-coupon-remove').hide();
					}
					
					if(typeof data.msg != 'undefined') alert(data.msg);
					
					if(typeof data.redirect != 'undefined') window.location = data.redirect;
					
					if(reloadExternal!== false) reloadExternal = true;
					if(reloadExternal){
						if(typeof supercart == 'object') supercart.reloadCart();
						if(typeof ultracart == 'object') ultracart.reload(false,false);	
					}
				}
		});
	},
	reload:function(){
		this.modificationObserver(null,false);
	},
    sendHelpForm:function(email,phone,message){
        new Ajax.Request(this.ajaxUrl, {
            method: 'post',
            parameters:{
                help_form:'true',
                help_phone:phone,
                help_email:email,
                help_message:message
            },
            onSuccess: function(transport){
                if(!$('cant-see-checkout-form')) return;

                $('cant-see-checkout-form').down('.hideable-inner').addClassName("hidden");
                $('cant-see-checkout-form').down('.success').removeClassName("hidden");
            }
        });
    }
});
