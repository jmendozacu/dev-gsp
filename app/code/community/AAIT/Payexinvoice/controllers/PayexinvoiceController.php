<?php
/**
 * PayEx Invoice Controller
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_PayexinvoiceController extends Mage_Core_Controller_Front_Action
{
    public function _construct()
    {
        Mage::getSingleton('payexinvoice/payment');
    }

    public function redirectAction()
    {
        Mage::helper('payexinvoice/tools')->addToDebug('Controller: redirect');

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

        // Get Operation Type (AUTHORIZATION / SALE)
        $operation = (Mage::getSingleton('payexinvoice/payment')->getConfigData('transactiontype') == 0) ? 'AUTHORIZATION' : 'SALE';

        // Get CustomerId
        $customer_id = (Mage::getSingleton('customer/session')->isLoggedIn() == true) ? Mage::getSingleton('customer/session')->getCustomer()->getId() : '0';

        // Get Additional Values
        $additional = Mage::getSingleton('payexinvoice/payment')->getConfigData('additionalValues') . '&INVOICE_MEDIADISTRIBUTION=11';

        // Responsive Skinning
        if (Mage::getSingleton('payexinvoice/payment')->getConfigData('responsive') === '1') {
            $separator = (!empty($additional) && mb_substr($additional, -1) !== '&') ? '&' : '';
            $additional .= $separator . 'USECSS=RESPONSIVEDESIGN';
        }

        // Get Amount
        //$amount = $order->getGrandTotal();
        $amount = Mage::helper('payexinvoice/order')->getCalculatedOrderAmount($order)->amount;

        $method = Mage::getSingleton('checkout/session')->getMethod();
        $ssn = Mage::getSingleton('checkout/session')->getSocialSecurtyNumber();
        $credit_data = Mage::getSingleton('checkout/session')->getCreditData();

        // Call PxOrder.Initialize8
        $params = array(
            'accountNumber' => '',
            'purchaseOperation' => $operation,
            'price' => round($amount * 100),
            'priceArgList' => '',
            'currency' => $currency_code,
            'vat' => 0,
            'orderID' => $order_id,
            'productNumber' => $customer_id,
            'description' => Mage::app()->getStore()->getName(),
            'clientIPAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'clientIdentifier' => 'USERAGENT=' . Mage::helper('core/http')->getHttpUserAgent(),
            'additionalValues' => $additional,
            'externalID' => '',
            'returnUrl' => Mage::getUrl('payexinvoice/payexinvoice/success', array('_secure' => true)),
            'view' => 'INVOICE',
            'agreementRef' => '',
            'cancelUrl' => Mage::getUrl('payexinvoice/payexinvoice/cancel', array('_secure' => true)),
            'clientLanguage' => Mage::getSingleton('payexinvoice/payment')->getConfigData('clientlanguage')
        );
        $result = Mage::helper('payexinvoice/api')->getPx()->Initialize8($params);
        Mage::helper('payexinvoice/tools')->addToDebug('PxOrder.Initialize8:' . $result['description']);

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK' || $result['errorCode'] !== 'OK') {
            $message = Mage::helper('payexinvoice/tools')->getVerboseErrorMessage($result);

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

        // Add Order Lines and Orders Address
        Mage::helper('payexinvoice/order')->addOrderLine($order_ref, $order);
        Mage::helper('payexinvoice/order')->addOrderAddress($order_ref, $order);

        // Call Invoice Purchase
        $result = array();
        switch ($method)
        {
            case 'private':
                // Call PxOrder.PurchaseInvoicePrivate
                $params = array(
                    'accountNumber' => '',
                    'orderRef' => $order_ref,
                    'customerRef' => $customer_id,
                    'customerName' => $credit_data['firstName'] . ' ' . $credit_data['lastName'],
                    'streetAddress' => $credit_data['address'],
                    'coAddress' => $credit_data['address'],
                    'postalCode' => $credit_data['postCode'],
                    'city' => $credit_data['city'],
                    'country' => $order->getBillingAddress()->getCountry(),
                    'socialSecurityNumber' => $ssn,
                    'phoneNumber' => '',
                    'email' => $order->getBillingAddress()->getEmail(),
                    'productCode' => '0001',
                    'creditcheckRef' => Mage::getSingleton('payexinvoice/payment')->getConfigData('unapproved') ? '' : $credit_data['creditCheckRef'],
                    'mediaDistribution' => Mage::getSingleton('payexinvoice/payment')->getConfigData('distribution'),
                    'invoiceText' => Mage::getSingleton('payexinvoice/payment')->getConfigData('invoicetext'),
                    'invoiceDate' => date('Y-m-d'),
                    'invoiceDueDays' => Mage::getSingleton('payexinvoice/payment')->getConfigData('invoiceduedays'),
                    'invoiceNumber' => $order_id,
                    'invoiceLayout' => ''
                );
                $result = Mage::helper('payexinvoice/api')->getPx()->PurchaseInvoicePrivate($params);
                Mage::helper('payexinvoice/tools')->addToDebug('PxOrder.PurchaseInvoicePrivate:' . $result['description']);
                break;
            case 'corporate':
                // Call PxOrder.PurchaseInvoiceCorporate
                $params = array(
                    'accountNumber' => '',
                    'orderRef' => $order_ref,
                    'companyRef' => $customer_id,
                    'companyName' => $credit_data['name'], // Firm name
                    'streetAddress' => $credit_data['address'],
                    'coAddress' => $credit_data['address'],
                    'postalCode' => $credit_data['postCode'],
                    'city' => $credit_data['city'],
                    'country' => $order->getBillingAddress()->getCountry(),
                    'organizationNumber' => $ssn,
                    'phoneNumber' => '',
                    'email' => $order->getBillingAddress()->getEmail(),
                    'productCode' => '0001',
                    'creditcheckRef' => Mage::getSingleton('payexinvoice/payment')->getConfigData('unapproved') ? '' : $credit_data['creditCheckRef'],
                    'mediaDistribution' => Mage::getSingleton('payexinvoice/payment')->getConfigData('distribution'),
                    'invoiceText' => Mage::getSingleton('payexinvoice/payment')->getConfigData('invoicetext'),
                    'invoiceDate' => date('Y-m-d'),
                    'invoiceDueDays' => Mage::getSingleton('payexinvoice/payment')->getConfigData('invoiceduedays'),
                    'invoiceNumber' => $order_id,
                    'invoiceLayout' => ''
                );
                $result = Mage::helper('payexinvoice/api')->getPx()->PurchaseInvoiceCorporate($params);
                Mage::helper('payexinvoice/tools')->addToDebug('PxOrder.PurchaseInvoiceCorporate:' . $result['description']);
                break;
        }

        // Check Errors
        if ($result['code'] !== 'OK' || $result['description'] !== 'OK') {
            $message = Mage::helper('payexinvoice/tools')->getVerboseErrorMessage($result);

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

            Mage::helper('payexinvoice/tools')->addToDebug('Order has been uncanceled.', $order_id);
        }

        // Process Transaction
        Mage::helper('payexinvoice/tools')->addToDebug('Process Payment Transaction...', $order_id);
        $transaction = Mage::helper('payexinvoice/order')->processPaymentTransaction($order, $result);
        $transaction_status = isset($result['transactionStatus']) ? (int)$result['transactionStatus'] : null;

        // Check Order and Transaction Result
        /* Transaction statuses: 0=Sale, 1=Initialize, 2=Credit, 3=Authorize, 4=Cancel, 5=Failure, 6=Capture */
        switch ($transaction_status) {
            case 0;
            case 1;
            case 3;
            case 6:
                // Select Order Status
                if (in_array($transaction_status, array(0, 6))) {
                    $new_status = Mage::getSingleton('payexinvoice/payment')->getConfigData('order_status_capture');
                } elseif ($transaction_status === 3 || (isset($result['pending']) && $result['pending'] === 'true')) {
                    $new_status = Mage::getSingleton('payexinvoice/payment')->getConfigData('order_status_authorize');
                } else {
                    $new_status = $order->getStatus();
                }

                // Get Order State
                $status = Mage::getModel('sales/order_status')
                    ->getCollection()
                    ->joinStates()
                    ->addFieldToFilter('main_table.status', $new_status)
                    ->getFirstItem();

                // Change order status
                $order->setData('state', $status->getState());
                $order->setStatus($status->getStatus());
                $order->addStatusHistoryComment(Mage::helper('payexinvoice')->__('Order has been paid'), $new_status);

                // Create Invoice for Sale Transaction
                if (in_array($transaction_status, array(0, 6))) {
                    $invoice = Mage::helper('payexinvoice/order')->makeInvoice($order, false);
                    $invoice->setTransactionId($result['transactionNumber']);
                    $invoice->save();

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
                }

                $order->save();
                $order->sendNewOrderEmail();

                // Redirect to Success Page
                Mage::getSingleton('checkout/session')->setLastSuccessQuoteId(Mage::getSingleton('checkout/session')->getPayexQuoteId());
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
                break;
            default:
                // Cancel order
                if ($transaction->getIsCancel()) {
                    Mage::helper('payexinvoice/tools')->addToDebug('Cancel: ' . $transaction->getMessage(), $order->getIncrementId());

                    $order->cancel();
                    $order->addStatusHistoryComment($transaction->getMessage());
                    $order->save();
                    $order->sendOrderUpdateEmail(true, $transaction->getMessage());
                }

                // Set quote to active
                if ($quoteId = Mage::getSingleton('checkout/session')->getPayexQuoteId()) {
                    $quote = Mage::getModel('sales/quote')->load($quoteId);
                    if ($quote->getId()) {
                        $quote->setIsActive(true)->save();
                        Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
                    }
                }

                Mage::getSingleton('checkout/session')->addError($transaction->getMessage());
                $this->_redirect('checkout/cart');
        }
    }
}

