<?php

class AAIT_Wywallet_WywalletController extends Mage_Core_Controller_Front_Action
{
    public function _construct()
    {
        Mage::getSingleton('wywallet/payment');
    }

    public function redirectAction()
    {
        Mage::helper('wywallet/tools')->addToDebug('Controller: redirect');

        // Load Order
        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);
        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
        }

        // Set quote to inactive
        Mage::getSingleton('checkout/session')->setPayexQuoteId(Mage::getSingleton('checkout/session')->getQuoteId());
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        Mage::getSingleton('checkout/session')->clear();

        // Get Currency code
        $currency_code = $order->getOrderCurrency()->getCurrencyCode();

        // Get CustomerId
        $customer_id = (Mage::getSingleton('customer/session')->isLoggedIn() == true) ? Mage::getSingleton('customer/session')->getCustomer()->getId() : '0';

        // Get Amount
        //$amount = $order->getGrandTotal();
        $amount = Mage::helper('wywallet/order')->getOrderAmount($order);

        // Call PxOrder.Initialize8
        $params = array(
            'accountNumber' => '',
            'purchaseOperation' => (Mage::getSingleton('wywallet/payment')->getConfigData('transactiontype') == 0) ? 'AUTHORIZATION' : 'SALE',
            'price' => 0,
            'priceArgList' => 'WYWALLET=' . round($amount * 100),
            'currency' => $currency_code,
            'vat' => 0,
            'orderID' => $order_id,
            'productNumber' => $customer_id,
            'description' => Mage::app()->getStore()->getName(),
            'clientIPAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'clientIdentifier' => '',
            'additionalValues' => Mage::getSingleton('wywallet/payment')->getConfigData('responsive') === '1' ? 'USECSS=RESPONSIVEDESIGN' : '',
            'externalID' => '',
            'returnUrl' => Mage::getUrl('wywallet/wywallet/success', array('_secure' => true)),
            'view' => 'MICROACCOUNT',
            'agreementRef' => '',
            'cancelUrl' => Mage::getUrl('wywallet/wywallet/cancel', array('_secure' => true)),
            'clientLanguage' => Mage::getSingleton('wywallet/payment')->getConfigData('clientlanguage')
        );
        $result = Mage::helper('wywallet/api')->getPx()->Initialize8($params);
        Mage::helper('wywallet/tools')->addToDebug('PxOrder.Initialize8:' . $result['description']);

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK' || $result['errorCode'] !== 'OK') {
            $message = Mage::helper('wywallet/tools')->getVerboseErrorMessage($result);

            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment($message, Mage_Sales_Model_Order::STATE_CANCELED);
            $order->save();

            // Set quote to active
            if ($quoteId = Mage::getSingleton('checkout/session')->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
                }
            }

            Mage::getSingleton('checkout/session')->addError($message);
            $this->_redirect('checkout/cart');
            return;
        }
        $order_ref = $result['orderRef'];
        $redirectUrl = $result['redirectUrl'];

        // Add Order Lines and Orders Address
        $order->getPayment()->getMethodInstance()->addOrderLine($order_ref, $order);
        $order->getPayment()->getMethodInstance()->addOrderAddress($order_ref, $order);

        // Set Pending Payment status
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('wywallet')->__('The customer was redirected to PayEx.'));
        $order->save();

        // Redirect to Bank
        header('Location: ' . $redirectUrl);
        exit();
    }

    public function successAction()
    {
        Mage::helper('wywallet/tools')->addToDebug('Controller: success');

        // Check OrderRef
        if (empty($_GET['orderRef'])) {
            $this->_redirect('checkout/cart');
        }

        // Load Order
        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);
        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
        }

        // Call PxOrder.Complete
        $params = array(
            'accountNumber' => '',
            'orderRef' => $_GET['orderRef']
        );
        $result = Mage::helper('wywallet/api')->getPx()->Complete($params);
        Mage::helper('wywallet/tools')->debugApi($result, 'PxOrder.Complete');
        if ($result['errorCodeSimple'] !== 'OK') {
            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment(Mage::helper('wywallet')->__('Order automatically canceled. Failed to complete payment.'));
            $order->save();

            // Set quote to active
            if ($quoteId = Mage::getSingleton('checkout/session')->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
                }
            }

            $message = Mage::helper('wywallet/tools')->getVerboseErrorMessage($result);
            Mage::getSingleton('checkout/session')->addError($message);
            $this->_redirect('checkout/cart');
            return;
        }

        // Save Transaction
        $result = Mage::getSingleton('wywallet/payment')->savePaymentTransaction($order_id, $result);

        // Check Transaction Result
        if (is_array($result) && $result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            $transaction_status = (int)$result['transactionStatus'];

            // Set Order State
            if (in_array($transaction_status, array(0, 6))) {
                $new_status = Mage::getSingleton('wywallet/payment')->getConfigData('order_status_capture');
            }
            if ($transaction_status === 3) {
                $new_status = Mage::getSingleton('wywallet/payment')->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            // Change order status
            $order->setData('state', $new_status);
            $order->setStatus($new_status);
            $order->addStatusHistoryComment(Mage::helper('wywallet')->__('Order has been paid'), $new_status);

            // Update Order Totals: "Total Due" on Sale Transactions bugfix
            if ($transaction_status === 0) {
                $order->setTotalPaid($order->getTotalDue());
                $order->setBaseTotalPaid($order->getBaseTotalDue());
                $order->setTotalDue($order->getTotalDue() - $order->getTotalPaid());
                $order->getBaseTotalDue($order->getBaseTotalDue() - $order->getBaseTotalPaid());

                // Update Order Totals because API V2 don't update order totals
                /** @var $invoice Mage_Sales_Model_Order_Invoice */
                $invoice = Mage::getResourceModel('sales/order_invoice_collection')
                    ->setOrderFilter($order->getId())->getFirstItem();

                $order->setTotalInvoiced($order->getTotalInvoiced() + $invoice->getGrandTotal());
                $order->setBaseTotalInvoiced($order->getBaseTotalInvoiced() + $invoice->getBaseGrandTotal());
                $order->setSubtotalInvoiced($order->getSubtotalInvoiced() + $invoice->getSubtotal());
                $order->setBaseSubtotalInvoiced($order->getBaseSubtotalInvoiced() + $invoice->getBaseSubtotal());
                $order->setTaxInvoiced($order->getTaxInvoiced() + $invoice->getTaxAmount());
                $order->setBaseTaxInvoiced($order->getBaseTaxInvoiced() + $invoice->getBaseTaxAmount());
                $order->setHiddenTaxInvoiced($order->getHiddenTaxInvoiced() + $invoice->getHiddenTaxAmount());
                $order->setBaseHiddenTaxInvoiced($order->getBaseHiddenTaxInvoiced() + $invoice->getBaseHiddenTaxAmount());
                $order->setShippingTaxInvoiced($order->getShippingTaxInvoiced() + $invoice->getShippingTaxAmount());
                $order->setBaseShippingTaxInvoiced($order->getBaseShippingTaxInvoiced() + $invoice->getBaseShippingTaxAmount());
                $order->setShippingInvoiced($order->getShippingInvoiced() + $invoice->getShippingAmount());
                $order->setBaseShippingInvoiced($order->getBaseShippingInvoiced() + $invoice->getBaseShippingAmount());
                $order->setDiscountInvoiced($order->getDiscountInvoiced() + $invoice->getDiscountAmount());
                $order->setBaseDiscountInvoiced($order->getBaseDiscountInvoiced() + $invoice->getBaseDiscountAmount());
                $order->setBaseTotalInvoicedCost($order->getBaseTotalInvoicedCost() + $invoice->getBaseCost());
            }

            $order->save();
            $order->sendNewOrderEmail();

            // Redirect to Success Page
            Mage::getSingleton('checkout/session')->setLastSuccessQuoteId(Mage::getSingleton('checkout/session')->getPayexQuoteId());
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            // Set quote to active
            if ($quoteId = Mage::getSingleton('checkout/session')->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
                }
            }

            Mage::getSingleton('checkout/session')->addError($result);
            $this->_redirect('checkout/cart');
        }
    }

    public function cancelAction()
    {
        Mage::helper('wywallet/tools')->addToDebug('Controller: cancel');

        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order');

        $message = Mage::helper('wywallet')->__('Order canceled by user');

        $order_id = $session->getLastRealOrderId();
        $order->loadByIncrementId($order_id);

        // Note: Cancel only non-captured orders!
        if (!$order->isCanceled() && !$order->hasInvoices()) {
            // Set Canceled State
            $order->cancel();
            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $message);
            $order->save();
        }

        // Set quote to active
        if ($quoteId = Mage::getSingleton('checkout/session')->getPayexQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
            }
        }

        $session->addError($message);
        $this->_redirect('checkout/cart');
    }

}

