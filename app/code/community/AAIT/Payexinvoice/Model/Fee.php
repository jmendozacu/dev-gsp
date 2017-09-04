<?php

class AAIT_Payexinvoice_Model_Fee extends Mage_Core_Model_Abstract
{
    public function getPaymentFee()
    {
        $fee = (float) Mage::getSingleton('payexinvoice/payment')->getConfigData('paymentfee');
        return $fee;
    }
}
