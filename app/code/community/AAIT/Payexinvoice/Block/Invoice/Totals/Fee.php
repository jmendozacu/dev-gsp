<?php

class AAIT_Payexinvoice_Block_Invoice_Totals_Fee extends Mage_Core_Block_Abstract
{
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $invoice = $parent->getInvoice();
        if ($invoice->getOrder()->getPayment()->getMethodInstance()->getCode() !== 'payexinvoice') {
            return $this;
        }

        if ($invoice->getOrder()->getBasePayexinvoicePaymentFee()) {
            $total = new Varien_Object();
            $total->setLabel($this->__('Payment fee'));
            $total->setValue($invoice->getOrder()->getPayexinvoicePaymentFee());
            $total->setBaseValue($invoice->getOrder()->getPayexinvoiceBasePaymentFee());
            $total->setCode('payexinvoice_payment_fee');
            $parent->addTotalBefore($total, 'tax');
        }

        return $this;
    }
}