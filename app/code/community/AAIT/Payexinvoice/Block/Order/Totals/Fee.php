<?php

class AAIT_Payexinvoice_Block_Order_Totals_Fee extends Mage_Core_Block_Abstract
{
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        if ($parent->getOrder()->getPayment()->getMethodInstance()->getCode() !== 'payexinvoice') {
            return $this;
        }

        if ($parent->getOrder()->getBasePayexinvoicePaymentFee()) {
            $total = new Varien_Object();
            $total->setLabel($this->__('Payment fee'));
            $total->setValue($parent->getOrder()->getPayexinvoicePaymentFee());
            $total->setBaseValue($parent->getOrder()->getPayexinvoiceBasePaymentFee());
            $total->setCode('payexinvoice_payment_fee');
            $parent->addTotalBefore($total, 'tax');
        }

        return $this;
    }
}