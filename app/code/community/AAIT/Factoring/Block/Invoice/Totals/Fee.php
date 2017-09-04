<?php

class AAIT_Factoring_Block_Invoice_Totals_Fee extends Mage_Core_Block_Abstract
{
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $invoice = $parent->getInvoice();
        if ($invoice->getOrder()->getPayment()->getMethodInstance()->getCode() !== 'factoring') {
            return $this;
        }

        if ($invoice->getOrder()->getBaseFactoringPaymentFee()) {
            $total = new Varien_Object();
            $total->setLabel($this->__('Payment fee'));
            $total->setValue($invoice->getOrder()->getFactoringPaymentFee());
            $total->setBaseValue($invoice->getOrder()->getFactoringBasePaymentFee());
            $total->setCode('factoring_payment_fee');
            $parent->addTotalBefore($total, 'tax');
        }

        return $this;
    }
}