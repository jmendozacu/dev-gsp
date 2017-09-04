<?php

class AAIT_Payexinvoice_Model_Invoice_Total extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
        if ($order->getPayment()->getMethodInstance()->getCode() !== 'payexinvoice') {
            return $this;
        }

        if ($order->getBasePayexinvoicePaymentFee()) {
            $baseInvoiceTotal = $invoice->getBaseGrandTotal();
            $invoiceTotal = $invoice->getGrandTotal();

            $baseInvoiceTotal = $baseInvoiceTotal + $order->getBasePayexinvoicePaymentFee();
            $invoiceTotal = $invoiceTotal + $order->getPayexinvoicePaymentFee();

            $invoice->setBaseGrandTotal($baseInvoiceTotal);
            $invoice->setGrandTotal($invoiceTotal);
        }
    }
}