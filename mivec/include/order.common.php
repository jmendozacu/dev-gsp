<?php
function getOrderDetail(Mage_Sales_Model_Order $order)
{
	$shippingAddress = $order->getShippingAddress();
	$customer = Mage::getModel('customer/customer')
		->load($order->getCustomerId());
	//print_r($order->getData());exit;
	
	$data['general'] = array(
		'order_id'	=> $order->getId(),
		'increment_id'	=> $order->getIncrementId(),
		'customer_id' => $order->getData('customer_id'),
		'email'	=> $order->getCustomerEmail(),
		'order_date' => $order->getData('created_at'),
		'status'	=> $order->getStatus(),
		'remote_ip'	=> $order->getData('remote_ip'),
	);
	
	$data['customer'] = array(
		'group'		=> $customer->getGroupName(),
		'firstname'	=> $order->getData('customer_firstname'),
		'lastname'	=> $order->getData('customer_lastname'),
		'email'		=> $order->getData('customer_email'),
	);
	
	$country = Mage::getModel('directory/country')->loadByCode($shippingAddress->getCountryId());
	$data['shipping_address'] = array(
		'firstname'	=> $shippingAddress->getData('firstname'),
		'lastname'	=> $shippingAddress->getData('lastname'),
		'company'	=> $shippingAddress->getCompany(),
		'city'		=> $shippingAddress->getCity(),
		'region'	=> $shippingAddress->getRegion(),
		'street'	=> $shippingAddress->getData('street'),
		'telephone'	=> $shippingAddress->getTelephone(),
		'zip'	=> $shippingAddress->getData('postcode'),
		'country'	=> $country->getName(),
	);
	
	$data['carrier'] = array(
		'carrier'	=> $order->getData('shipping_description'),
		'amount'	=> $order->getData('base_shipping_amount')
	);
	
	$data['amount'] = array(
		'weight'	=> $order->getData('weight') . '/KG',
		'subtotal'	=> $order->getData('order_currency_code') . ' ' 
				. number_format($order->getData('grand_total') , 2),
		'grand_total'	=> $order->getData('base_currency_code]') .' ' 
				. number_format($order->getData('base_grand_total') , 2),
		'shipping'	=> $order->getData('base_shipping_amount'),
	);
	return $data;
}

function getOredrItems(Mage_Sales_Model_Order $order)
{
	//$itemCollection = $order->getAllItems();
	$items = $order->getAllItems();
	$itemcount = count($items);
	$name = array();
	$unitPrice = array();
	$sku = array();
	$ids = array();
	$qty = array();
	
	foreach ($items as $itemId => $item)
	{
		//print_r($item->getData());
		
		$qty = $item->getData('qty_ordered');
		$price = $item->getData('original_price');
		$discount = $item->getData('discount_amount');
		
		//$options = unserialize($item->getData('product_options'));
		$_options = getItemOptions($item);//$item->getProductOptions();
		//print_r($_options);
		
		$arr[] = array(
			'id'	=> $item->getId(),
			'name'	=> $item->getName(),
			'options'	=> $_options,
			'sku'	=> $item->getSku(),
			'qty'	=> $qty,
			'price'	=> $price,
			'discount'	=> $discount,
			'row'	=> (($price - $discount) * $qty)
		);
	}
	return $arr;
}

function getItemOptions($_item)
{
	$result = array();
	if ($options = $_item->getProductOptions()) {
		if (isset($options['options'])) {
			$result = array_merge($result, $options['options']);
		}
		if (isset($options['additional_options'])) {
			$result = array_merge($result, $options['additional_options']);
		}
		if (isset($options['attributes_info'])) {
			$result = array_merge($result, $options['attributes_info']);
		}
	}
	return $result;
}

function getFormatedOptionValue($optionValue)
{
	$optionInfo = array();

	// define input data format
	if (is_array($optionValue)) {
		if (isset($optionValue['option_id'])) {
			$optionInfo = $optionValue;
			if (isset($optionInfo['value'])) {
				$optionValue = $optionInfo['value'];
			}
		} elseif (isset($optionValue['value'])) {
			$optionValue = $optionValue['value'];
		}
	}

	// render customized option view
	if (isset($optionInfo['custom_view']) && $optionInfo['custom_view']) {
		$_default = array('value' => $optionValue);
		if (isset($optionInfo['option_type'])) {
			try {
				$group = Mage::getModel('catalog/product_option')->groupFactory($optionInfo['option_type']);
				return array('value' => $group->getCustomizedView($optionInfo));
			} catch (Exception $e) {
				return $_default;
			}
		}
		return $_default;
	}

	// truncate standard view
	$result = array();
	if (is_array($optionValue)) {
		$_truncatedValue = implode("\n", $optionValue);
		$_truncatedValue = nl2br($_truncatedValue);
		return array('value' => $_truncatedValue);
	} else {
		//$_truncatedValue = Mage::helper('core/string')->truncate($optionValue, 55, '');
		$_truncatedValue = nl2br($_truncatedValue);
	}

	$result = array('value' => $_truncatedValue);

/*	if (Mage::helper('core/string')->strlen($optionValue) > 55) {
		$result['value'] = $result['value'] . ' <a href="#" class="dots" onclick="return false">...</a>';
		$optionValue = nl2br($optionValue);
		$result = array_merge($result, array('full_view' => $optionValue));
	}*/

	return $result;
}

function getErpOrder($_order_id)
{
	global $db;
	$sql = "
		SELECT * FROM mivec_erp_order
			WHERE increment_id = '$_order_id'
	";
	return $db->fetchRow($sql);
}

function getOrderTracking($_erp_order_id) 
{
	global $db;
	$sql = "
		SELECT * FROM mivec_erp_order_tracking
			WHERE erp_order_id = '$_erp_order_id'
	";
	return $db->fetchAll($sql);
}

function isInPurchase($_order_id)
{
	global $db;
	$sql = "
		SELECT * FROM mivec_erp_purchase
			WHERE `order` LIKE '%$_order_id%';
	";
	$r = $db->fetchRow($sql);
	return $r['is_confirm'];
}

