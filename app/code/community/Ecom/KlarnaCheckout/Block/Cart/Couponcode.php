<?php

class Ecom_KlarnaCheckout_Block_Cart_Couponcode extends Ecom_KlarnaCheckout_Block_Cart {

    protected function _toHtml(){
        if(Mage::getStoreConfig("klarnacheckout/checkout/couponcode_below_checkout") && $this->getPosition() == 'above') return '';
        elseif(!Mage::getStoreConfig("klarnacheckout/checkout/couponcode_below_checkout") && $this->getPosition() == 'below') return '';
        else return parent::_toHtml();
    }

}
