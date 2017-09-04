<?php

class Ecom_UltraCart_Helper_Shipping extends Ecom_UltraCart_Helper_Data {

	/*
	 * get the shippingprices
	 * in local currency, as numbers.
	 */
	public function _collectShippingInfo(& $quote){
		if(!empty($this->_shippingPriceCache)) return $this->_shippingPriceCache;
		
		$address = $quote->getShippingAddress();
		
		# find the weight
		$weight = 0;
		foreach($quote->getAllItems() as $item) {
			$weight += $item->getRowWeight();
		}
		$address->setWeight($weight);
		
		
		// preset country
		if($address->getCountryId() === null) $address->setCountryId(Mage::getStoreConfig('general/country/default'));
		if($address->getPostcode() === null && Mage::getStoreConfig('tax/defaults/postcode') != '*') $address->setPostcode(Mage::getStoreConfig('tax/defaults/postcode'));
		
		// set free method rate (can me 0 sometimes??)
		if($address->getFreeMethodWeight() < $weight) $quote->getShippingAddress()->setFreeMethodWeight($weight);
		$address->setCollectShippingRates(true);
		
		//Mage::log($quote->getShippingAddress()->getShippingMethod());
		
		// get the current method, note: can be empty if none is set
		$currentMethod = $quote->getShippingAddress()->getShippingMethod();
		
		// get available methods
		$availableRates = $this->getAvailableRates($quote);
		
		// set a method, only if needed!
		// the current method might not be available anymore
		if(!$currentMethod || !array_key_exists($currentMethod,$availableRates)){
			
			$newRate = $this->chooseNewRate($availableRates);

			if($newRate !== null){
				# convert the object to the correct kind
				$rate = Mage::getModel('sales/quote_address_rate')->importShippingRate($newRate);


				# find out if the shipping address allready have a rate set
				if(!count($quote->getShippingAddress()->getShippingRatesCollection())){

					# set the rate to the quote, and set the method. Then collect the totals for the shipping address
					$quote->getShippingAddress()
							->addShippingRate($rate)
							->setShippingMethod($rate->getCarrier().'_'.$rate->getMethod())
							->collectTotals();
				}

				Mage::getSingleton('checkout/type_onepage')->saveShippingMethod($rate->getCarrier().'_'.$rate->getMethod());
			}
		}

		# collect the totals for the quote
		//$quote->collectTotals();
		$quote->setTotalsCollectedFlag(false);
		$quote->getShippingAddress()->unsetData('cached_items_all');
		$quote->getShippingAddress()->unsetData('cached_items_nominal');
		$quote->getShippingAddress()->unsetData('cached_items_nonnominal');
		$quote->collectTotals();
		
		return $quote;
		
	}
	
	public function getAvailableRates(& $quote){
		$return = array();
		
		// what's the default carrier & method?
		list($shippingCarrier, $shippingService) = explode('_',$this->getShippingMethodCode());
		
		$availableRates = $this->collectRatesByAddress($quote->getShippingAddress());
		foreach($availableRates->getResult()->getAllRates() as $rateLine){
			
			/* support for matrixrate by webshopsapps */
			if($shippingCarrier == 'matrixrate' && $rateLine->getCarrier() == $shippingCarrier){
				$rateLine->setIsDefault(true);
			}

			if($rateLine->getCarrier() == $shippingCarrier && $rateLine->getMethod() == $shippingService){
				$rateLine->setIsDefault(true);
			}
			
			$return[$rateLine->getCarrier().'_'.$rateLine->getMethod()] = $rateLine;
		}
		
		return $return;
	}
	
	// choose which rate to apply
	// first choice the default (from config)
	// second choice the cheapest
    public function chooseNewRate($availableRates){
        $cheapest = array(null,1000000000000);

        // find the default and make stats
        foreach($availableRates as $code => $rate){
            if($rate->getIsDefault()) return $rate;

            if($rate->getPrice() < $cheapest[1]){
                $cheapest[0] = $rate;
                $cheapest[1] = $rate->getPrice();
            }
        }

        return $cheapest[0];
    }


	/**
	 * COPY FROM Mage_Shipping_Model_Shipping::collectRatesByAddress
	 * Only 2 changes: added "setFreeShipping" & changed $this to Mage::getModel... (in return)
	 */
	public function collectRatesByAddress(Varien_Object $address, $limitCarrier=null)
    {
        /** @var $request Mage_Shipping_Model_Rate_Request */
        $request = Mage::getModel('shipping/rate_request');
        $request->setAllItems($address->getAllItems());
        $request->setDestCountryId($address->getCountryId());
        $request->setDestRegionId($address->getRegionId());
        $request->setDestPostcode($address->getPostcode());
        $request->setPackageValue($address->getBaseSubtotal());
        $request->setPackageValueWithDiscount($address->getBaseSubtotalWithDiscount());
        $request->setPackageWeight($address->getWeight());
        $request->setFreeMethodWeight($address->getFreeMethodWeight());
        $request->setPackageQty($address->getItemQty());
        $request->setStoreId(Mage::app()->getStore()->getId());
        $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $request->setBaseCurrency(Mage::app()->getStore()->getBaseCurrency());
        $request->setPackageCurrency(Mage::app()->getStore()->getCurrentCurrency());
        $request->setLimitCarrier($limitCarrier);
        $request->setFreeShipping($address->getFreeShipping() ? true : false); // Denna behövs för att "gratis frakt" infon ska gå över till fraktsättet!

        $request->setBaseSubtotalInclTax($address->getBaseSubtotalInclTax()
            + $address->getBaseExtraTaxAmount());

        return Mage::getModel('shipping/shipping')->collectRates($request);
    }

}