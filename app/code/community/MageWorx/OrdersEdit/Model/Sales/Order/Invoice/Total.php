<?php

/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersEdit_Model_Sales_Order_Invoice_Total extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $payment = $invoice->getOrder()->getPayment();
        $paymentData = Mage::app()->getRequest()->getPost('payment');
        if ($paymentData && isset($paymentData['cc_number'])) {
            return $this;
        }

        $specialPayments = array('authorizenet', 'authorizenet_directpost', 'paypal_direct');

        if (in_array($payment->getMethod(), $specialPayments) &&
            $payment->getBaseAmountOrdered() > 0 &&
            $invoice->getBaseGrandTotal() != $payment->getBaseAmountOrdered()
        ) {
            // TODO: Check invoices with paypal or authorize payments
            $invoice->setGrandTotal($payment->getAmountOrdered());
            $invoice->setBaseGrandTotal($payment->getBaseAmountOrdered());
            $invoice->setOrdersEditFlag(true);
        }
        return $this;
    }
}