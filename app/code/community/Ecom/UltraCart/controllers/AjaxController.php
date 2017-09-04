<?php

class Ecom_UltraCart_AjaxController extends Mage_Core_Controller_Front_Action {

	protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }
	
	public function setqtyAction() {
		
		$itemId = $this->getRequest()->getPost('item_id'); // @todo: get from param
		$newQty = $this->getRequest()->getPost('item_qty'); // @todo: get from param
		
		
		$cart = $this->_getCart();
		
		# what to set
		$cartData = $cart->suggestItemsQty(array($itemId => array("qty"=>$newQty)));
		
		# check stock
		$quote = $this->_getOnepage()->getQuote();
		$orgQty = $quote->getItemById($itemId)->getQty();
		
		$cart->updateItems($cartData)->save();
		
		
		# find out how it went
		foreach($quote->getAllItems() as $item) Mage::dispatchEvent('sales_quote_item_qty_set_after',array('item'=>$item));
		
		$messages = $quote->getMessages();
		if(isset($messages['qty']) && $messages['qty']->getType() == 'error'){
			# it went wrong
			$msg = $messages['qty']->getCode();
			
			#reset qty
			$cartData = $cart->suggestItemsQty(array($itemId => array("qty"=>$orgQty)));
			$cart->updateItems($cartData)->save();
			foreach($quote->getAllItems() as $item) Mage::dispatchEvent('sales_quote_item_qty_set_after',array('item'=>$item));
			
		}
		
		
		
		$this->loadLayout(false);
		$block = $this->getLayout()->getBlock('ultracart.block');
		$response['summary'] = $block->getChildHtml('ultracart.summary');
        $response['qty'] = $block->getChild('ultracart.title')->getSummaryCount();
        $response['title'] = $block->getChildHtml('ultracart.title');
		$response['products'] = $block->getChildHtml('ultracart.products');
		if(isset($msg) && !empty($msg)) $response['msg'] = $msg;
		$this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
		
	}

	public function reloadAction() {
		$this->_getOnepage()->getQuote()->collectTotals()->save();
		
		$this->loadLayout(false);
		$block = $this->getLayout()->getBlock('ultracart.block');
		$response['summary'] = $block->getChildHtml('ultracart.summary');
        $response['qty'] = $block->getChild('ultracart.title')->getSummaryCount();
		$response['title'] = $block->getChildHtml('ultracart.title');
		$response['products'] = $block->getChildHtml('ultracart.products');
		$this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
	}

	protected function _getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}

}
