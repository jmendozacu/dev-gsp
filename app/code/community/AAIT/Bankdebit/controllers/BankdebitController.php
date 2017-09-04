<?php

/**
 * PayEx Bank Debit Controller
 * Created by AAIT Team.
 */
class AAIT_Bankdebit_BankdebitController extends Mage_Core_Controller_Front_Action
{
    public function _construct()
    {
        Mage::getSingleton('bankdebit/payment');
    }

    public function redirectAction()
    {
        Mage::helper('bankdebit/tools')->addToDebug('Controller: redirect');

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

        // Get Additional Values
        $additional = '';

        // Responsive Skinning
        if (Mage::getSingleton('bankdebit/payment')->getConfigData('responsive') === '1') {
            $separator = (!empty($additional) && mb_substr($additional, -1) !== '&') ? '&' : '';
            $additional .= $separator . 'USECSS=RESPONSIVEDESIGN';
        }

        // Get Amount
        //$amount = $order->getGrandTotal();
        $amount = Mage::helper('bankdebit/order')->getOrderAmount($order);

        // Get Bank Id
        $bank_id = Mage::getSingleton('checkout/session')->getBankId();

        Mage::helper('bankdebit/tools')->addToDebug('Reserved Order for CustomerId #' . $customer_id, $order_id );
        Mage::helper('bankdebit/tools')->addToDebug('Selected bank: '.$bank_id);

        // Call PxOrder.Initialize8
        $params = array(
            'accountNumber' => '',
            'purchaseOperation' => 'SALE', // for BankDebit mode uses SALE method only
            'price' => 0,
            'priceArgList' => $bank_id . '=' . round($amount * 100),
            'currency' => $currency_code,
            'vat' => 0,
            'orderID' => $order_id,
            'productNumber' => $customer_id,
            'description' => Mage::app()->getStore()->getName(),
            'clientIPAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'clientIdentifier' => 'USERAGENT=' . Mage::helper('core/http')->getHttpUserAgent(),
            'additionalValues' => $additional,
            'externalID' => '',
            'returnUrl' => Mage::getUrl('bankdebit/bankdebit/success', array('_secure' => true)),
            'view' => 'DIRECTDEBIT',
            'agreementRef' => '',
            'cancelUrl' => Mage::getUrl('bankdebit/bankdebit/cancel', array('_secure' => true)),
            'clientLanguage' => Mage::getSingleton('bankdebit/payment')->getConfigData('clientlanguage')
        );
        $result = Mage::helper('bankdebit/api')->getPx()->Initialize8($params);
        Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.Initialize8');

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK' || $result['errorCode'] !== 'OK') {
            $message = Mage::helper('bankdebit/tools')->getVerboseErrorMessage($result);

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
        Mage::helper('bankdebit/tools')->addToDebug('Redirect URL: ' . $result['redirectUrl']);
        $order_ref = $result['orderRef'];

        // Add Order Lines and Orders Address
        $order->getPayment()->getMethodInstance()->addOrderLine($order_ref, $order);
        $order->getPayment()->getMethodInstance()->addOrderAddress($order_ref, $order);

        // Call PxOrder.PrepareSaleDD2
        $params = array(
            'accountNumber' => '',
            'orderRef' => $order_ref,
            'userType' => 0, // Anonymous purchase
            'userRef' => '',
            'bankName' => $bank_id
        );
        $result = Mage::helper('bankdebit/api')->getPx()->PrepareSaleDD2($params);
        Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.PrepareSaleDD2');

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK') {
            $message = Mage::helper('bankdebit/tools')->getVerboseErrorMessage($result);

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

        // Set Pending Payment status
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,  Mage::helper('bankdebit')->__('The customer was redirected to PayEx.'));
        $order->save();

        // Redirect to Bank
        header('Location: ' . $result['redirectUrl']);
        exit();
    }

    public function successAction()
    {
        Mage::helper('bankdebit/tools')->addToDebug('Controller: success');

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
        $result = Mage::helper('bankdebit/api')->getPx()->Complete($params);
        Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.Complete');
        if ($result['errorCodeSimple'] !== 'OK') {
            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Order automatically canceled. Failed to complete payment.'));
            $order->save();

            // Set quote to active
            if ($quoteId = Mage::getSingleton('checkout/session')->getPayexQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
                }
            }

            $message = Mage::helper('bankdebit/tools')->getVerboseErrorMessage($result);
            Mage::getSingleton('checkout/session')->addError($message);
            $this->_redirect('checkout/cart');
            return;
        }

        // Save Transaction
        $result = Mage::getSingleton('bankdebit/payment')->savePaymentTransaction($order_id, $result);

        // Check Transaction Result
        if (is_array($result) && $result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            $transaction_status = (int)$result['transactionStatus'];

            // Set Order State
            $new_status = Mage::getSingleton('bankdebit/payment')->getConfigData('order_status');
            if (empty($new_status)) {
                $new_status = $order->getStatus();
            }

            // Change order status
            $order->setData('state', $new_status);
            $order->setStatus($new_status);
            $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Order has been paid'), $new_status);

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


}

