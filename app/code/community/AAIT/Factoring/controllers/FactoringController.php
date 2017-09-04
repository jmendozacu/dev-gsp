<?php

class AAIT_Factoring_FactoringController extends Mage_Core_Controller_Front_Action
{
    public function _construct()
    {
        Mage::getSingleton('factoring/payment');
    }

    public function redirectAction()
    {
        Mage::helper('factoring/tools')->addToDebug('Controller: redirect');

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

        // Get Payment Mode
        $mode = Mage::getSingleton('factoring/payment')->getConfigData('mode');
        if ($mode === 'SELECT') {
            $mode = Mage::getSingleton('checkout/session')->getMode();
        }

        // Get Social Security Number from Session
        $ssn = Mage::getSingleton('checkout/session')->getSocialSecurityNumber();

        // Get Currency code
        $currency_code = $order->getOrderCurrency()->getCurrencyCode();

        // Get CustomerId
        $customer_id = (Mage::getSingleton('customer/session')->isLoggedIn() == true) ? Mage::getSingleton('customer/session')->getCustomer()->getId() : '0';

        // Get Amount
        $amount = $order->getGrandTotal();
        //$amount = Mage::helper('factoring/order')->getOrderAmount($order);

        // Call PxOrder.Initialize8
        $params = array(
            'accountNumber' => '',
            'purchaseOperation' => 'AUTHORIZATION',
            'price' => round($amount * 100),
            'priceArgList' => '',
            'currency' => $currency_code,
            'vat' => 0,
            'orderID' => $order_id,
            'productNumber' => $customer_id,
            'description' => Mage::app()->getStore()->getName(),
            'clientIPAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'clientIdentifier' => '',
            'additionalValues' => '',
            'externalID' => '',
            'returnUrl' => 'http://localhost.no/return',
            'view' => $mode,
            'agreementRef' => '',
            'cancelUrl' => 'http://localhost.no/cancel',
            'clientLanguage' => Mage::getSingleton('factoring/payment')->getConfigData('clientlanguage')
        );
        $result = Mage::helper('factoring/api')->getPx()->Initialize8($params);
        Mage::helper('factoring/tools')->addToDebug('PxOrder.Initialize8:' . $result['description']);

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK' || $result['errorCode'] !== 'OK') {
            $message = Mage::helper('factoring/tools')->getVerboseErrorMessage($result);

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

        // Call PxOrder.PurchaseInvoiceSale / PxOrder.PurchasePartPaymentSale
        $params = array(
            'accountNumber' => '',
            'orderRef' => $order_ref,
            'socialSecurityNumber' => $ssn,
            'legalFirstName' => $order->getBillingAddress()->getFirstname(),
            'legalLastName' => $order->getBillingAddress()->getLastname(),
            'legalStreetAddress' => $order->getBillingAddress()->getStreet(-1),
            'legalCoAddress' => '',
            'legalPostNumber' => $order->getBillingAddress()->getPostcode(),
            'legalCity' => $order->getBillingAddress()->getCity(),
            'legalCountryCode' => $order->getBillingAddress()->getCountry(),
            'email' => $order->getBillingAddress()->getEmail(),
            'msisdn' => (mb_substr($order->getBillingAddress()->getTelephone(), 0, 1) === '+') ? $order->getBillingAddress()->getTelephone() : '+' . $order->getBillingAddress()->getTelephone(),
            'ipAddress' => Mage::helper('core/http')->getRemoteAddr(),
        );

        if ($mode === 'FACTORING') {
            $result = Mage::helper('factoring/api')->getPx()->PurchaseInvoiceSale($params);
            Mage::helper('factoring/tools')->addToDebug('PxOrder.PurchaseInvoiceSale:' . $result['description']);
        } else {
            $result = Mage::helper('factoring/api')->getPx()->PurchasePartPaymentSale($params);
            Mage::helper('factoring/tools')->addToDebug('PxOrder.PurchasePartPaymentSale:' . $result['description']);
        }

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK') {
            $message = Mage::helper('factoring/tools')->getVerboseErrorMessage($result);

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

        // Save Transaction
        $result = Mage::getSingleton('factoring/payment')->savePaymentTransaction($order_id, $result);

        // Check Transaction Result
        if (is_array($result) && $result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            $transaction_status = (int)$result['transactionStatus'];

            // Set Order State
            if (in_array($transaction_status, array(0, 6))) {
                $new_status = Mage::getSingleton('factoring/payment')->getConfigData('order_status_capture');
            }
            if ($transaction_status === 3) {
                $new_status = Mage::getSingleton('factoring/payment')->getConfigData('order_status_authorize');
            }
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            // Change order status
            $order->setData('state', $new_status);
            $order->setStatus($new_status);
            $order->addStatusHistoryComment(Mage::helper('factoring')->__('Order has been paid'), $new_status);

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
            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment($result, Mage_Sales_Model_Order::STATE_CANCELED);
            $order->save();

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
}

