var UltraCartClass = Class.create({
    initialize:function(wrapper, url){
        this.ajaxUrl = url;
        this.wrapper = wrapper; // should be the outer-most wrapper
        this.products = $(document.body).select('.ultracart-products');
        this.summary = $(document.body).select('.ultracart-summary');
        this.title = $(document.body).select('.ultracart-title');
        this.messages = $(document.body).select('.ultracart-msg');

        this.attachRegularEvents();
        this.attachAjaxEvents();
    },

    attachRegularEvents:function(){
        this.wrapper.observe('mouseenter',this.hover.bindAsEventListener(this,'enter'));
        this.wrapper.observe('mouseleave',this.hover.bindAsEventListener(this,'leave'));
    },

    attachAjaxEvents:function(){
        this.products.each(function(productElm){
            productElm.select('li.item').each(function(product){
                var itemId = product.down('input.itemId').value;
                var itemQty = Number(product.down('input.itemQty').value);
                product.down('.decrease').observe('click',this.decrease.bindAsEventListener(this,itemId,itemQty));
                product.down('.increase').observe('click',this.increase.bindAsEventListener(this,itemId,itemQty));
                product.down('.remove').observe('click',this.remove.bindAsEventListener(this,itemId));
            },this)
        },this)
    },

    increase:function(event, itemId,itemQty){
        var thisClass = this;
        this.startLoader(itemId);
        new Ajax.Request(this.ajaxUrl+'setqty', {
            method:'post',
            parameters:{item_id:itemId,item_qty:(itemQty+1)},
            onSuccess: thisClass.ajaxSuccessCallback.bind(thisClass),
            onFailure: thisClass.ajaxFaliureCallback.bind(thisClass)
        });
    },

    decrease:function(event, itemId,itemQty){
        var thisClass = this;
        this.startLoader(itemId);
        new Ajax.Request(this.ajaxUrl+'setqty', {
            method:'post',
            parameters:{item_id:itemId,item_qty:(itemQty-1)},
            onSuccess: thisClass.ajaxSuccessCallback.bind(thisClass),
            onFailure: thisClass.ajaxFaliureCallback.bind(thisClass)
        });
    },

    remove:function(event, itemId){
        var thisClass = this;
        this.startLoader(itemId);
        new Ajax.Request(this.ajaxUrl+'setqty', {
            method:'post',
            parameters:{item_id:itemId,item_qty:0},
            onSuccess: thisClass.ajaxSuccessCallback.bind(thisClass),
            onFailure: thisClass.ajaxFaliureCallback.bind(thisClass)
        });
    },

    reload:function(flash){
        var thisClass = this;
        new Ajax.Request(this.ajaxUrl+'reload', {
            method:'post',
            onSuccess: function(transport){
                thisClass.ajaxSuccessCallback(transport,flash,false)
            },
            onFailure: thisClass.ajaxFaliureCallback.bind(thisClass)
        });
    },

    doFlash:function(){
        this.title.each(function(titleElm){
            new Effect.Pulsate(titleElm,{pulses:2,duration:1})
        });

    },

    ajaxSuccessCallback:function(transport,flash,reloadExternal){
        var json = transport.responseJSON;

        // update all titles
        this.title.each(function(titleElm){
            if(json.qty > 0) titleElm.up("div.ultracart").addClassName("has-items");
            else titleElm.up("div.ultracart").removeClassName("has-items");
            titleElm.update(json.title);
        });

        // update all summarys
        this.summary.each(function(summaryElm){
            summaryElm.update(json.summary);
        });

        // update all product-lists
        this.products.each(function(productsElm){
            productsElm.update(json.products);
        },this);

        // reattach all events
        this.attachAjaxEvents();

        if(typeof json.msg !== 'undefined' && json.msg) this.openMsg(json.msg);
        else this.closeMsg();

        if(flash) this.doFlash();

        if(reloadExternal!== false) reloadExternal = true;
        if(reloadExternal && typeof KCOController == 'object') KCOController.reload();
    },

    openMsg:function(msg){

        this.messages.each(function(msgElm){
            msgElm.insert(new Element('div').insert(new Element('span').update(msg)));

            window.setTimeout(function(){
                if(msgElm.down().down()) new Effect.Highlight(msgElm.down().down());
            }, 300);
            window.setTimeout(function(){
                msgElm.update('');
            }, 4000);

        },this);

    },

    closeMsg:function(){
        this.messages.each(function(msgElm){
            msgElm.update();
        },this);
    },

    ajaxFaliureCallback:function(transport){
        alert("Error in cominication to the server, please try again or refresh the site.");
    },

    hover:function(event,state){
        if($(document.body).hasClassName('klarnacheckout-index-index')) return;
        if($(document.body).hasClassName('onestepcheckout-index-index')) return;
        if($(document.body).hasClassName('opc-index-index')) return;
        if($(document.body).hasClassName('checkout-cart-index')) return;

        if(state =='enter') this.wrapper.down('.dropdown').style.display = 'block';

        if(state =='leave') this.wrapper.down('.dropdown').style.display = 'none';
    },

    startLoader:function(itemId){
        this.products.each(function(productsElm){
            productsElm.select('li.item').each(function(item){
                if(item.down('input.itemId').value == itemId) item.down('div.qty').addClassName('loading-indicator');
            },this)
        },this)
    }
});