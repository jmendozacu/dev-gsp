<?php

class AAIT_Payexinvoice_Block_Checkout_Fee extends Mage_Checkout_Block_Total_Default
{
    protected $_template = 'payexinvoice/checkout/fee.phtml';

    /**
     * Get Payment fee
     * @return float
     */
    public function getPaymentFee()
    {
        return Mage::getSingleton('payexinvoice/fee')->getPaymentFee();
    }

}
