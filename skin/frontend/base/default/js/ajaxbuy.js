var ajaxBuy = Class.create({
	initialize:function(ajaxUrl){
	
		this.ajaxUrl = ajaxUrl;
		var thisClass = this;

		$(document.body).select("button.btn-cart").each(function(elm){

			if(!elm.hasClassName('btn-checkout') && !elm.hasClassName('ajax-buy-initialized')) {

				if(elm.up('#bundleProduct')) return; // dont do anything for bundle's in lists

				var path = String(elm.getAttribute('onclick')); // get the current onclick

				if(path.match(/setLocation/)){ // handle normal buttons

					if(path.match(/\?options/)) return; // dont do anything for bundle's in lists

					var productId = Number(String(path.match(/\/product\/[0-9]+\//)).match(/[0-9]+/)); // get the products ID

					if(productId == 0) return; // cant find the ID, ignore this one

					if(document.body.hasClassName("onestepcheckout-index-index") == false) {
						elm.setAttribute('onclick','') // remove default onclick
						elm.observe('click',thisClass.add.bindAsEventListener(thisClass,productId,elm)) // add a new one
					}
				}


				if(path == 'productAddToCartForm.submit(this)'){ // handle forms

					// check for file inputs, and if found, disable ajaxbuy
					if($("product-options-wrapper") && $("product-options-wrapper").select("input[type=file]").length >=1) return;

					var productId = $('product_addtocart_form').getInputs('hidden','product').first().value;

					elm.setAttribute('onclick','return false;') // remove default onclick
					elm.observe('click',thisClass.add.bindAsEventListener(thisClass,productId,elm, $('qty'),true)) // add a new one
					
					// reset the form functions, to prevent them to fire.
					if(productAddToCartForm){
						productAddToCartForm.submit = function(button, url) {}.bind(productAddToCartForm);
						//productAddToCartForm.submitLight = function(button, url){}.bind(productAddToCartForm);
					}
					
        
				}

				elm.addClassName('ajax-buy-initialized');

			}
			//j
		})

	},

	add:function(event, productId, elm, qtyElm, isForm){

		Event.stop(event);
		
		var qty = 1;
		if(qtyElm) {
            qty = qtyElm.value;
            /*
            * User can add to cart by choose different qty at category page.Edit by jerry.
            * */
        }else if(elm.parentNode.getElementsByClassName("act-input").length>0){
			qty = elm.parentNode.getElementsByClassName("act-input")[0].value;
		}

		var params = {};
		params['product'] = productId;
		params['qty'] = qty;
		if($('qty_square')) params['qty_square'] = $('qty_square').value; // compatibility with Ecom_ProductSquare

		if(isForm) {

			if(productAddToCartForm){
				if(!productAddToCartForm.validator.validate()) return;
			}

			if($("product_addtocart_form") && $('product_addtocart_form').getInputs('hidden','product').first().value == productId){
				params = $("product_addtocart_form").serialize(true);
			}

		}

		var loader = new Element('div').addClassName('ajaxbuy-loader');
		if(qtyElm) loader.addClassName('loader-form-version')
		elm.insert({top:loader});
		elm.addClassName('ajaxbuy-button-loader');

		var thisClass = this;

		new Ajax.Request(this.ajaxUrl,{
			method:'get',
			parameters: params,
			onSuccess:function(transport){
				var response = transport.responseJSON;

				if(response.result == 'redirect'){
					window.location=response.url;
				}
				else {

					if(response.msg){
						thisClass.showMessage(response.success,response.msg)
					}

					loader.addClassName('ajaxbuy-done');
					elm.addClassName('ajaxbuy-button-done');
					window.setTimeout(function(){
						loader.remove()
						elm.removeClassName('ajaxbuy-button-loader');
						elm.removeClassName('ajaxbuy-button-done');
					},2000);

					if($('messages_product_view')) $('messages_product_view').update();

					if(qtyElm) qtyElm.value = 1;

					//console.log(typeof supercart)
					//console.log(typeof ultracart)
					if(typeof supercart == 'object') supercart.reloadCart();
					if(typeof ultracart == 'object') ultracart.reload(true);
					if(typeof KCOController == 'object') KCOController.reload();
					if(typeof UpsellOverlay == 'object') UpsellOverlay.processResponse(response);

				}
			}
		})
	},
	showMessage:function(isSuccess,msg){
		var elm = new Element('div').addClassName('ajaxbuy-message');

		if(isSuccess) elm.addClassName('ajaxbuy-message-success');
		else elm.addClassName('ajaxbuy-message-failiure');

		$(document.body).insert({top:elm});

		elm.update(msg);

		window.setTimeout(function(){
			elm.addClassName('open');
		},1);

		window.setTimeout(function(){
			elm.removeClassName('open');
		},6000);

		window.setTimeout(function(){
			elm.remove();
		},6500);
	}

});


