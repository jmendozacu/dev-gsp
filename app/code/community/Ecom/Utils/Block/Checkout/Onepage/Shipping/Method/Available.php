<?php

class Ecom_Utils_Block_Checkout_Onepage_Shipping_Method_Available extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    public function getCarrierName($carrierCode)
    {
        $name = parent::getCarrierName($carrierCode);

        if($name == $carrierCode) return "";
        else return $name;
    }
}
