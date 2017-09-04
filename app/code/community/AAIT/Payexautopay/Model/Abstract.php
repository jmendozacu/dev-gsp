<?php
abstract class AAIT_Payexautopay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Set PayEx Environment
     * @deprecated
     */
    public function setEnvironment()
    {
        $accountnumber = $this->getConfigData('accountnumber');
        $encryptionkey = $this->getConfigData('encryptionkey');
        $debug = (bool)$this->getConfigData('debug');

        Mage::helper('payexautopay/api')->getPx()->setEnvironment($accountnumber, $encryptionkey, $debug);
    }

    /**
     * Complete Transaction and Save Transaction Id
     * @param  int|string $order_id Order Id
     * @param  array $result Transaction Data
     * @return bool|string
     */
    public function savePaymentTransaction($order_id, $result)
    {
        Mage::helper('payexautopay/tools')->addToDebug('Save Payment Transaction...', $order_id);

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);
        $payment = $order->getPayment();

        // Prevent Order cancellation when used TC
        if (in_array((int)$result['transactionStatus'], array(0, 3, 6)) && $order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
            if ($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
                $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
                $order->save();

                foreach ($order->getAllItems() as $item) {
                    $item->setQtyCanceled(0);
                    $item->save();
                }
            }

            Mage::helper('payexautopay/tools')->addToDebug('Order has been uncanceled.', $order_id);
        }

        /* Transaction statuses:
        0=Sale, 1=Initialize, 2=Credit, 3=Authorize, 4=Cancel, 5=Failure, 6=Capture */
        switch ((int)$result['transactionStatus']) {
            case 1:
                // From PayEx PIM:
                // "If PxOrder.Complete returns transactionStatus = 1, then check pendingReason for status."
                // See http://www.payexpim.com/payment-methods/paypal/
                if ($result['pending'] === 'true') {
                    Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, 0, $result);
                    $payment->save();

                    // Set Order State
                    $order->addStatusHistoryComment(Mage::helper('payexautopay')->__('Payment is pending'));
                    $order->save();
                } else {
                    Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                    $payment->save();

                    // Get Error Message
                    $error = Mage::helper('payexautopay')->__('Detected an abnormal payment process (Transaction Status: %s. Transaction Id: %s).', $result['transactionStatus'], $result['transactionNumber']);
                    Mage::helper('payexautopay/tools')->addToDebug($error, $order_id);

                    // Cancel
                    $order->cancel();
                    $order->addStatusHistoryComment($error);
                    $order->save();

                    return $error;
                }

                return $result;
            case 3:
                Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, 0, $result);
                $payment->save();

                Mage::helper('payexautopay/tools')->addToDebug('Payment is accepted', $order_id);

                // Set Order State
                $order->addStatusHistoryComment(Mage::helper('payexautopay')->__('Transaction Status: %s. Transaction Id: %s', $result['transactionStatus'], $result['transactionNumber']));
                $order->save();

                return $result;
            case 0;
            case 6:
                Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, 1, $result);
                $payment->save();

                // Set Order State
                $order->addStatusHistoryComment(Mage::helper('payexautopay')->__('Transaction Status: %s. Transaction Id: %s', $result['transactionStatus'], $result['transactionNumber']));
                $order->save();

                // Create Invoice for Sale Transaction
                if (isset($result['transactionNumber'])) {
                    $invoice = Mage::helper('payexautopay/order')->makeInvoice($order, false);

                    // Add transaction Id
                    $invoice->setTransactionId($result['transactionNumber']);
                    $invoice->save();
                }

                return $result;
            case 2:
                Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                $payment->save();

                // Get Error Message
                $error = Mage::helper('payexautopay')->__('Detected an abnormal payment process (Transaction Status: %s. Transaction Id: %s).', $result['transactionStatus'], $result['transactionNumber']);
                Mage::helper('payexautopay/tools')->addToDebug($error, $order_id);

                // Cancel Order
                $order->cancel();
                $order->addStatusHistoryComment($error);
                $order->save();

                return $error;
            case 4;
                Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                $payment->save();

                $order->cancel();
                $order->addStatusHistoryComment(Mage::helper('payexautopay')->__('Order automatically canceled. Transaction is canceled.'));
                $order->save();

                Mage::helper('payexautopay/tools')->addToDebug('Transaction is canceled.', $order_id);
                return Mage::helper('payexautopay')->__('Order is canceled.');
            case 5;
                Mage::helper('payexautopay/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                $payment->save();

                // Cancel
                if ($order->getId()) {
                    $order->cancel();
                    $order->addStatusHistoryComment(Mage::helper('payexautopay')->__('Order automatically canceled. Transaction is failed.'));
                    $order->save();
                }

                $error = Mage::helper('payexautopay/tools')->getVerboseErrorMessage($result);
                Mage::helper('payexautopay/tools')->addToDebug('Transaction is failed: ' . $error, $order_id);
                return $error;
            default:
                return Mage::helper('payexautopay')->__('Unknown transaction status.');
        }
    }

    /**
     * Complete Order
     * @param $orderRef
     * @return mixed
     */
    public function complete($orderRef)
    {
        // Call PxOrder.Complete
        $params = array(
            'accountNumber' => $this->getConfigData('accountnumber'),
            'orderRef' => $orderRef
        );
        $result = Mage::helper('payexautopay/api')->getPx()->Complete($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.Complete');
        return $result;
    }
}
