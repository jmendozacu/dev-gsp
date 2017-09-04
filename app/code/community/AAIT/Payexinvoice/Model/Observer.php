<?php
/**
 * PayEx Invoice: Observer
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Model_Observer extends Mage_Core_Model_Abstract
{

    /**
     * Change Order Status on Invoice Generation
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function sales_order_invoice_save_after(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $payment = $order->getPayment();

        $code = $payment->getMethodInstance()->getCode();
        if ($code !== 'payexinvoice') {
            return $this;
        }

        // is Captured
        if (!$payment->getIsTransactionPending()) {
            // Load Invoice transaction Data
            if (!$invoice->getTransactionId()) {
                return $this;
            }

            $transactionId = $invoice->getTransactionId();
            $details = $payment->getMethodInstance()->fetchTransactionInfo($payment, $transactionId);

            if (!isset($details['transactionStatus'])) {
                return $this;
            }

            // Get Order Status
            if (in_array((int)$details['transactionStatus'], array(0, 6))) {
                // For Capture
                $new_status = Mage::getSingleton('payexinvoice/payment')->getConfigData('order_status_capture');
            }
            if ((int)$details['transactionStatus'] === 3) {
                // For Authorize
                $new_status = Mage::getSingleton('payexinvoice/payment')->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            // Change order status
            $order->setData('state', $new_status);
            $order->setStatus($new_status);
            $order->addStatusHistoryComment(Mage::helper('payexinvoice')->__('Order has been paid'), $new_status);
            $order->save();
        }
        return $this;
    }
    
    /**
     * Collects Payment Fee from quote/addresses to quote
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function sales_quote_collect_totals_after(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        $quote->setBasePayexinvoicePaymentFee(0);
        $quote->setPayexinvoicePaymentFee(0);

        foreach ($quote->getAllAddresses() as $address) {
            $quote->setBasePayexinvoicePaymentFee((float)($quote->getBasePayexinvoicePaymentFee() + $address->getBasePayexinvoicePaymentFee()));
            $quote->setPayexinvoicePaymentFee((float)($quote->getPayexinvoicePaymentFee() + $address->getPayexinvoicePaymentFee()));
        }
        return $this;
    }

    /**
     * Adds Payment Fee to order
     * @param Varien_Event_Observer $observer
     */
    public function sales_order_payment_place_end(Varien_Event_Observer $observer)
    {
        $payment = $observer->getPayment();
        if ($payment->getMethodInstance()->getCode() !== 'payexinvoice') {
            return;
        }

        $order = $payment->getOrder();
        $base_fee = $order->getQuote()->getBasePayexinvoicePaymentFee();
        $fee = $order->getQuote()->getPayexinvoicePaymentFee();

        $order->setBasePayexinvoicePaymentFee($base_fee);
        $order->setPayexinvoicePaymentFee($fee);
        $order->save();
    }

}
