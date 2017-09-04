<?php

class Ecom_KlarnaCheckout_Block_Shipping extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    protected $_rates;
    protected $_address;

    public function getShippingRates()
    {
        $quote = $this->getQuote();
		
        if($quote->getShippingAddress()->getCountryId() === null){
            //var_dump($quote->getShippingAddress()->getCountryId());
            $quote->getShippingAddress()
                    ->setCountryId(Mage::getStoreConfig('general/country/default'))
                    //->setPostCode('27136')
                    ->setCollectShippingRates(true);
        }

        return parent::getShippingRates();
    }

    public function getCarrierName($code){
        $name = parent::getCarrierName($code);
        if($name == $code) return null;
        else return $name;
    }

}
