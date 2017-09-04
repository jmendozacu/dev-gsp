<?php
class AAIT_Payexautopay_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Get PayEx Singleton
     * @return Mage_Core_Model_Abstract
     */
    public function getPayex()
    {
        $payex = Mage::getSingleton('payexautopay/payexautopay');
        $payex->setEnvironment();
        return $payex;
    }

    /**
     * Clean Pending Orders via Cron
     * @return AAIT_Payexautopayy_Model_Observer
     */
    public function cleanPendingOrders()
    {
        // Check UTC TimeZone for Save
        // See http://www.magentocommerce.com/boards/viewthread/40981/
        if (date_default_timezone_get() != Mage_Core_Model_Locale::DEFAULT_TIMEZONE) {
            Mage::throwException('Magento TimeZone Configuration are broken. Use UTC TimeZone.');
        }

        $clean_time = -1 * (int)$this->getPayex()->getConfigData('cleantime');
        if ($clean_time !== 0) {
            // Force Cancel Pending orders
            $clean_time = date('Y-m-d H:i:s', strtotime($clean_time . ' minutes'));
            Mage::helper('payexautopay/cleaner')->forceCancel('pending_payment', $clean_time);
            //Mage::helper('payexautopay/cleaner')->forceCancel('pending', $clean_time);
        }

        return $this;
    }

    /**
     * Change Order Status on Invoice Generation
     * @param Varien_Event_Observer $observer
     * @return AAIT_Payexautopay_Model_Observer
     */
    public function sales_order_invoice_save_after(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $payment = $order->getPayment();

        $code = $payment->getMethodInstance()->getCode();
        if ($code !== 'payexautopay') {
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
            if (in_array($details['transactionStatus'], array('0', '6'))) {
                // For Capture
                $new_status = $this->getPayex()->getConfigData('order_status_capture');
            }
            if ($details['transactionStatus'] === '3') {
                // For Authorize
                $new_status = $this->getPayex()->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            // Change order status
            $message = Mage::helper('payexautopay')->__('Order has been paied');

            if ($order->isStateProtected($new_status) === true) {
                // Force change status
                Mage::helper('payexautopay/order')->changeOrderState($order->getId(), $new_status);
                $order->addStatusToHistory($new_status, $message, false);
            } else {
                $order->setState($new_status, $new_status, $message);
            }
            $order->save();
        }
    }

    /**
     * Force CreditMemo Feature
     * Mage_Payment_Model_Observer
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Payment_Model_Observer
     */
    public function sales_order_save_before(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethodInstance()->getCode() !== 'payexautopay') {
            return $this;
        }

        if ($order->canUnhold()) {
            return $this;
        }

        if ($order->isCanceled() ||
            $order->getState() === Mage_Sales_Model_Order::STATE_CLOSED
        ) {
            return $this;
        }
        /**
         * Allow forced creditmemo just in case if it wasn't defined before
         */
        if (!$order->hasForcedCanCreditmemo()) {
            $order->setForcedCanCreditmemo(true);
        }
        return $this;
    }
}