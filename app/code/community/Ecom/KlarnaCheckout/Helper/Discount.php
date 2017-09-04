<?php
class Ecom_KlarnaCheckout_Helper_Discount extends Ecom_KlarnaCheckout_Helper_Data {
	
	/**
	 * Return total discount incl OR excl tax, depending on settings
	 * @param int
	 */
	public function getDiscount($quote){
		if(Mage::getStoreConfig('tax/cart_display/subtotal')>1) return $this->getDiscountData($quote)->getDiscountInclTax();
		else return $this->getDiscountData($quote)->getDiscountExclTax();
	}
	
	public function getDiscountData($quote) {

		$CatPriceIncl = Mage::getStoreConfig('tax/calculation/price_includes_tax');
		
		$shippingAddress = $quote->getShippingAddress();

		$discountIncl = 0;
		$discountExcl = 0;

		// hitta discount på produkterna
		foreach ($quote->getItemsCollection() as $item) {
			if (!$CatPriceIncl) {
				$discountExcl += $item->getDiscountAmount();
				$discountIncl += $item->getDiscountAmount() * (($item->getTaxPercent() / 100) + 1);
			} else {
				$discountExcl += $item->getDiscountAmount() / (($item->getTaxPercent() / 100) + 1);
				$discountIncl += $item->getDiscountAmount();
			}
		}

		// ta reda på moms-sats
		if((float)$shippingAddress->getShippingInclTax() && (float)$shippingAddress->getShippingAmount())
			$shippingTaxRate = $shippingAddress->getShippingInclTax() / $shippingAddress->getShippingAmount();
		else
			$shippingTaxRate = 1;

		// hur mycket diffar $discountExcl mot total discount?
		if(!$CatPriceIncl) $shippingDiscount = abs($quote->getShippingAddress()->getDiscountAmount()) - $discountExcl;
		else $shippingDiscount = abs($quote->getShippingAddress()->getDiscountAmount()) - $discountIncl;

		// ta den siffran gånger momssats från frakten
		if(!$CatPriceIncl){
			$discountIncl += $shippingDiscount * $shippingTaxRate;
			$discountExcl += $shippingDiscount;
		} else {
			$discountIncl += $shippingDiscount;
			$discountExcl += $shippingDiscount / $shippingTaxRate;
		}
		

		$return = new Varien_Object();
		return $return->setDiscountInclTax($discountIncl)->setDiscountExclTax($discountExcl);
	}
}