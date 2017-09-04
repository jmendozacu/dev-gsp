

/**
 * Update ultracart before closing the loader
 */
IWD.OPC.Checkout.hideLoader = function(){
	if(typeof ultracart == 'object') ultracart.reload(false,false); // update ultracart

	setTimeout(function(){
		$j('.opc-ajax-loader').hide();
		//$j('.opc-btn-checkout').removeClass('button-disabled');
	},600);
};

/**
 *
 * Set qty & update stuff
 * See IWD.OPC.Checkout.pullReview() for details this is copied from there
 *
 **/
IWD.OPC.Checkout.setQty =  function(itemId, newQty){

    IWD.OPC.Checkout.showLoader();

    /*

        $_POST['qty'] = 3
        http://demo.magentocommerce.com/checkout/cart/ajaxUpdate/id/26141/uenc/formkey,,/
        response: (json)
             content: "html---"
    Note->   message: "Item was updated successfully."
             qty: 3
             success: 1


        $_POST['qty'] = 3000000000
        http://demo.magentocommerce.com/checkout/cart/ajaxUpdate/id/26141/uenc/formkey,,/?___SID=U
        response: (json)
             content: "html----"
    Note->   notice: "The maximum quantity allowed for purchase is 10000."
             qty: 3000000000
             success: 1

     */


    IWD.OPC.Checkout.reload(); // will hide loader when done

};


/**
 * Method to reload the checkout (cart, payment & shipping)
 */
IWD.OPC.Checkout.reload =  function(showLoader){

    if(showLoader) IWD.OPC.Checkout.showLoader(); // might not be needed..

    IWD.OPC.Checkout.reloadShippingsPayments();
    IWD.OPC.Checkout.pullReview();

};



$j( document ).ready(function() {
	$j("#opc-review-block").removeClass("hidden");
});