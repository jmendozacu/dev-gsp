<?php
/**
 * PayEx Autopay Controller
 * Created by AAIT Team.
 */

class AAIT_Payexautopay_PayexautopayController extends Mage_Core_Controller_Front_Action
{
    public function _construct()
    {
        // Init PayEx Enviroment
        $this->getPayex();
    }

    /**
     * Get PayEx Autopay Singleton
     * @return Mage_Core_Model_Abstract
     */
    public function getPayex()
    {
        $payex = Mage::getSingleton('payexautopay/payexautopay');
        $payex->setEnvironment();
        return $payex;
    }

    public function redirectAction()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Controller: redirect');

        // Check session data
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getTransaction()) {
            Mage::helper('payexautopay/tools')->throwPayExException('Failed to complete action: Bad Session Data', 'PayEx');
        }
        $transaction_data = $session->getTransaction();

        // Load Order
        $order = Mage::getModel('sales/order');
        $order_id = $session->getLastRealOrderId();
        $order->loadByIncrementId($order_id);
        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
        }

        // Set Pending Payment status
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('payexautopay')->__('The customer was redirected to PayEx.'));
        $order->save();

        // Set quote to inactive
        $session->setPayexQuoteId($session->getQuoteId());
        $session->getQuote()->setIsActive(false)->save();
        //$session->clear();

        // Redirect to Bank
        header('Location: ' . $transaction_data['redirectUrl']);
        exit();
    }

    public function successAction()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Controller: success');

        // Check OrderRef
        if (empty($_GET['orderRef'])) {
            $this->_redirect('checkout/cart');
        }

        // Load Order
        $session = Mage::getSingleton('checkout/session');
        $order_id = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);

        // Complete Transaction
        $result = $this->getPayex()->complete($_GET['orderRef']);
        if ($result['errorCodeSimple'] !== 'OK') {
            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment(Mage::helper('payexautopay')->__('Order automatically canceled. Failed to complete payment.'));
            $order->save();

            // Set quote to active
            if ($quoteId = $session->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    $session->setQuoteId($quoteId);
                }
            }

            $message = Mage::helper('payex2/tools')->getVerboseErrorMessage($result);
            Mage::getSingleton('checkout/session')->addError($message);
            $this->_redirect('checkout/cart');
            return;
        }

        // Save Transaction
        $result = $this->getPayex()->savePaymentTransaction($order_id, $result);

        // Check Transaction Result
        if (is_array($result) && $result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            // Get Transaction status
            $transaction_status = $result['transactionStatus'];

            // Set Order State
            if (in_array($transaction_status, array('0', '6'))) {
                $new_status = $this->getPayex()->getConfigData('order_status_capture');
            }
            if ($transaction_status === '3') {
                $new_status = $this->getPayex()->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            $message = Mage::helper('payexautopay')->__('Order has been paied');
            if ($order->isStateProtected($new_status) === true) {
                // Force change status
                Mage::helper('payexautopay/order')->changeOrderState($order_id, $new_status);
                $order->addStatusToHistory($new_status, $message, false);
            } else {
                $order->setState($new_status, $new_status, $message);
            }

            // Update Order Totals: "Total Due" on Sale Transactions bugfix
            if ($result['transactionStatus'] == '0') {
                $order->setTotalPaid($order->getTotalDue());
                $order->setBaseTotalPaid($order->getBaseTotalDue());
                $order->setTotalDue($order->getTotalDue() - $order->getTotalPaid());
                $order->getBaseTotalDue($order->getBaseTotalDue() - $order->getBaseTotalPaid());
            }

            $order->sendNewOrderEmail();
            $order->save();

            Mage::helper('payexautopay/tools')->addToDebug('AutoPay success', $order_id);

            // Redirect to Success Page
            $session->setLastSuccessQuoteId($session->getPayexQuoteId());
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            // Set quote to active
            if ($quoteId = $session->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    $session->setQuoteId($quoteId);
                }
            }

            Mage::getSingleton('checkout/session')->addError($result);
            $this->_redirect('checkout/cart');
        }
    }

    public function cancelAction()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Controller: cancel');

        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order');

        $message = Mage::helper('payexautopay')->__('Order canceled by user');

        $order_id = $session->getLastRealOrderId();
        $order->loadByIncrementId($order_id);

        // Note: Cancel only non-captured orders!
        if (!$order->isCanceled() && !$order->hasInvoices()) {
            // Set Canceled State
            //$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $message);
            //$order->save();
            $order->cancel();
            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $message);
            $order->save();

            // Rollback stock
            Mage::helper('payexautopay/order')->rollbackStockItems($order);
        }

        // Set quote to active
        if ($quoteId = $session->getPayexQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }

        //Mage::getSingleton('checkout/session')->clear();
        $session->addError($message);
        $this->_redirect('checkout/cart');
    }


    public function cancel_agreementAction()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Controller: cancel_agreement');

        $agreement_id = Mage::helper('payexautopay/agreement')->getCustomerAgreement();
        if ($agreement_id !== false) {
            // Cancel Agreement
            Mage::helper('payexautopay/agreement')->removeCustomerAgreement(Mage::getSingleton('customer/session')->getCustomer()->getId());
            Mage::helper('payexautopay/agreement')->removePxAgreement($agreement_id);
        } else {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('payexautopay')->__('Failed to cancel agreement.'));
        }

        // Redirect to back
        if (!empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            $this->_redirect('/', array('_secure' => true));
        }
    }

    public function autopayAction()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Controller: autopay');

        // Check Transaction
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getAutopayTransaction()) {
            $message = Mage::helper('payexautopay')->__('Failed to complete action: Bad Autopay Session Data.');
            Mage::helper('payexautopay/tools')->addToDebug($message);
            Mage::getSingleton('checkout/session')->addError($message);
            return;
        }
        $transaction_data = $session->getAutopayTransaction();

        // Load Order
        $order_id = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);

        // Save Transaction
        $result = $this->getPayex()->savePaymentTransaction($order_id, $transaction_data);

        if (is_array($result) && $result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            // Get Transaction status
            $transaction_status = $result['transactionStatus'];

            // Set Order State
            if (in_array($transaction_status, array('0', '6'))) {
                $new_status = $this->getPayex()->getConfigData('order_status_capture');
            }
            if ($transaction_status === '3') {
                $new_status = $this->getPayex()->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }
            $message = Mage::helper('payexautopay')->__('Order has been paied');
            if ($order->isStateProtected($new_status) === true) {
                // Force change status
                Mage::helper('payexautopay/order')->changeOrderState($order_id, $new_status);
                $order->addStatusToHistory($new_status, $message, false);
            } else {
                $order->setState($new_status, $new_status, $message);
            }

            // Update Order Totals: "Total Due" on Sale Transactions bugfix
            if ($result['transactionStatus'] == '0') {
                $order->setTotalPaid($order->getTotalDue());
                $order->setBaseTotalPaid($order->getBaseTotalDue());
                $order->setTotalDue($order->getTotalDue() - $order->getTotalPaid());
                $order->getBaseTotalDue($order->getBaseTotalDue() - $order->getBaseTotalPaid());
            }

            $order->sendNewOrderEmail();
            $order->save();

            //$session->setAutopayTransaction(null);
            Mage::helper('payexautopay/tools')->addToDebug('AutoPay success', $order_id);

            // Redirect to Success Page
            $session->setLastSuccessQuoteId($session->getPayexQuoteId());
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            // Set quote to active
            if ($quoteId = $session->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    $session->setQuoteId($quoteId);
                }
            }

            Mage::getSingleton('checkout/session')->addError($result);
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Pending clean
     * @link http://localhost/magento/index.php/payexautopay/payexautopay/pendingclean
     * @return void
     */
    public function pendingcleanAction()
    {
        //@todo: Allow for Admins only
        Mage::helper('payexautopay/tools')->addToDebug('Controller: pending clean');

        // Check UTC TimeZone for Save
        // See http://www.magentocommerce.com/boards/viewthread/40981/
        if (date_default_timezone_get() != Mage_Core_Model_Locale::DEFAULT_TIMEZONE) {
            Mage::throwException('Magento TimeZone Configuration are broken. Use UTC TimeZone.');
        }

        // Force Cancel Pending orders
        $clean_time = date('Y-m-d H:i:s', strtotime('-20 minutes'));
        Mage::helper('payexautopay/cleaner')->forceCancel('pending_payment', $clean_time);
        Mage::helper('payexautopay/cleaner')->forceCancel('pending', $clean_time);

        // Redirect to back
        if (!empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            $this->_redirect('/', array('_secure' => true));
        }
    }
}
