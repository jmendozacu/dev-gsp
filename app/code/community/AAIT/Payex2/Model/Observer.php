<?php

class AAIT_Payex2_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Clean Pending Orders via Cron
     * @return $this
     */
    public function cleanPendingOrders()
    {
        // Check UTC TimeZone for Save
        // See http://www.magentocommerce.com/boards/viewthread/40981/
        if (date_default_timezone_get() !== Mage_Core_Model_Locale::DEFAULT_TIMEZONE) {
            Mage::throwException('Magento TimeZone Configuration are broken. Use UTC TimeZone.');
        }

        $clean_time = -1 * (int)Mage::getSingleton('payex2/payment')->getConfigData('cleantime');
        if ($clean_time !== 0) {
            // Force Cancel Pending orders
            $clean_time = date('Y-m-d H:i:s', strtotime($clean_time . ' minutes'));
            Mage::helper('payex2/cleaner')->forceCancel('pending_payment', $clean_time);
            //Mage::helper('payex2/cleaner')->forceCancel('pending', $clean_time);
        }

        return $this;
    }

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
        if ($code !== 'payex2') {
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
                $new_status = Mage::getSingleton('payex2/payment')->getConfigData('order_status_capture');
            }
            if ((int)$details['transactionStatus'] === 3) {
                // For Authorize
                $new_status = Mage::getSingleton('payex2/payment')->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            // Change order status
            $order->setData('state', $new_status);
            $order->setStatus($new_status);
            $order->addStatusHistoryComment(Mage::helper('payex2')->__('Order has been paid'), $new_status);
            $order->save();
        }
        return $this;
    }
}