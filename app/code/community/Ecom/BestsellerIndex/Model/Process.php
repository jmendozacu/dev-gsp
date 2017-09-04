<?php

class Ecom_BestsellerIndex_Model_Process extends Mage_Core_Model_Abstract {
	
	const XML_PATH_BESTSELLERINDEX_BEHAVIOR = "catalog/frontend/bestsellerbehavior";
	const BEHAVIOR_PRICE = "price";
	const BEHAVIOR_QTY = "qty";
	
	function processOneDay(){
		// define the breakpoint
		$breakpoint = date("Y-m-d H:i:s",strtotime('now -1 day'));
		$this->process($breakpoint);
	}
	
	function processAll(){
		$breakpoint = 0;
		$this->process($breakpoint, true);
	}
	
	function process($breakpoint, $reindex = false){
		
		// define the states to include
		$states = array(
				Mage_Sales_Model_Order::STATE_NEW,
				Mage_Sales_Model_Order::STATE_COMPLETE,
				Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
				Mage_Sales_Model_Order::STATE_PROCESSING,
				Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
				Mage_Sales_Model_Order::STATE_HOLDED
			);
		
		// select the orders
		$orders = Mage::getModel('sales/order')->getCollection()
				->addFieldToFilter('created_at',array('gteq'=>$breakpoint))
				->addFieldToFilter('state',array('in'=>$states));
		
		// find the individual products and merge the qty's
		$products = array();
		foreach($orders as $order){
			foreach($order->getAllItems() as $item){
				
				if(Mage::getStoreConfig(self::XML_PATH_BESTSELLERINDEX_BEHAVIOR) == self::BEHAVIOR_QTY)
					$value = $item->getQtyOrdered();
				else
					$value = $item->getBaseRowTotal();
				
				if(!isset($products[$item->getSku()]))
					$products[$item->getProductId()] = $value;
				else
					$products[$item->getProductId()] += $value;
			}
		}
		unset($orders, $order, $item);
		
		// update the products with the new index
		foreach($products as $productId => $qty){
			$product = Mage::getModel('catalog/product')->load($productId);
			
			if($product->getBestsellerIndex() && !$reindex) $product->setData('bestseller_index',(round($product->getBestsellerIndex() + $qty)));
			else $product->setData('bestseller_index',round($qty));
			
			$product->save();
		}
		unset($products, $product);
		
	}
	
}