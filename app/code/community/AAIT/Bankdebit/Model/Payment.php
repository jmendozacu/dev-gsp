<?php

/**
 * PayEx Bank Debit Payment Model
 * Created by AAIT Team.
 */
class AAIT_Bankdebit_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment method code
     */
    public $_code = 'bankdebit';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canFetchTransactionInfo = true;
    //protected $_canCancelInvoice        = true;

    /**
     * Payment method blocks
     */
    protected $_infoBlockType = 'bankdebit/info';
    protected $_formBlockType = 'bankdebit/form';

    /**
     * Init Class
     */
    public function __construct()
    {
        $accountnumber = $this->getConfigData('accountnumber');
        $encryptionkey = $this->getConfigData('encryptionkey');
        $debug = (bool)$this->getConfigData('debug');

        Mage::helper('bankdebit/api')->getPx()->setEnvironment($accountnumber, $encryptionkey, $debug);
    }

    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Instantiate state and set it to state object
     * @param  $paymentAction
     * @param  $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        // Set Initial Order Status
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get config action to process initialization
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }

    /**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (parent::isAvailable($quote) === false) {
            return false;
        }

        // Check currency
        $allowedCurrency = array('DKK', 'EUR', 'GBP', 'NOK', 'SEK', 'USD');
        return in_array($quote->getQuoteCurrencyCode(), $allowedCurrency);
    }

    /**
     * Validate
     * @return bool
     */
    public function validate()
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: Validate');
        return parent::validate();
    }

    /**
     * Get the redirect url
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: getOrderPlaceRedirectUrl');

        // Save Bank Id
        $bank_id = Mage::app()->getRequest()->getParam('payexbank');
        $this->getCheckout()->setBankId($bank_id);

        return Mage::getUrl('bankdebit/bankdebit/redirect', array('_secure' => true));
    }

    /**
     * Capture payment
     * @note In BankDebit used auto-capture
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: Capture');

        parent::capture($payment, $amount);

        $transactionId = $payment->getLastTransId();
        //$transactionId = $transactionId . '-capture';

        // Add Capture Transaction
        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(0);

        // Do nothing

        return $this;
    }

    /**
     * Cancel payment
     * @param   Varien_Object $payment
     * @return  $this
     */
    public function cancel(Varien_Object $payment)
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: Cancel');

        $transactionId = $payment->getLastTransId();

        // Add Cancel Transaction
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($transactionId)
            ->setIsTransactionClosed(1); // Closed

        // Do nothing

        return $this;
    }

    /**
     * Refund capture
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: Refund');

        parent::refund($payment, $amount);

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for refund.'));
        }

        if (!$payment->getLastTransId()) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        // Load transaction Data
        $transactionId = $payment->getLastTransId();
        $transaction = $payment->getTransaction($transactionId);
        if (!$transaction) {
            Mage::throwException(Mage::helper('bankdebit')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Check for Capture and Authorize transaction only
        if ((int)$details['transactionStatus'] !== 6 && (int)$details['transactionStatus'] !== 0) {
            Mage::throwException(Mage::helper('bankdebit')->__('This payment has not yet captured.'));
        }

        $transactionNumber = $details['transactionNumber'];
        $order_id = $details['orderId'];
        if (!$order_id) {
            $order_id = $payment->getOrder()->getId();
        }

        // Call PxOrder.Credit5
        $params = array(
            'accountNumber' => '',
            'transactionNumber' => $transactionNumber,
            'amount' => round($amount * 100),
            'orderId' => $order_id,
            'vatAmount' => 0,
            'additionalValues' => ''
        );
        $result = Mage::helper('bankdebit/api')->getPx()->Credit5($params);
        Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.Credit');

        // Check Results
        if ($result['code'] == 'OK' && $result['errorCode'] == 'OK' && $result['description'] == 'OK') {
            // Add Credit Transaction
            $payment->setAnetTransType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
            $payment->setAmount($amount);

            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($result['transactionNumber'])
                ->setIsTransactionClosed(0); // No-Closed

            // Add Transaction fields
            $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
            return $this;
        }

        // Show Error
        Mage::helper('bankdebit/tools')->throwPayExException($result, 'PxOrder.Credit5');
    }

    /**
     * Void payment
     * @param Varien_Object $payment
     * @return $this
     */
    public function void(Varien_Object $payment)
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: Void');
        return $this->cancel($payment);
    }

    /**
     * Fetch transaction details info
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        Mage::helper('bankdebit/tools')->addToDebug('Action: fetchTransactionInfo. ID ' . $transactionId);

        // Get Transaction Details
        $params = array(
            'accountNumber' => '',
            'transactionNumber' => $transactionId,
        );
        $details = Mage::helper('bankdebit/api')->getPx()->GetTransactionDetails2($params);
        Mage::helper('bankdebit/tools')->debugApi($details, 'PxOrder.GetTransactionDetails2');

        // Check Results
        if ($details['code'] === 'OK' && $details['errorCode'] === 'OK' && $details['description'] === 'OK') {
            // Filter details
            foreach ($details as $key => $value) {
                if (empty($value)) {
                    unset($details[$key]);
                }
            }
            return $details;
        }

        // Show Error
        Mage::helper('bankdebit/tools')->throwPayExException($details, 'GetTransactionDetails2');
    }

    /**
     * Create Payment Block
     * @param $name
     * @return mixed
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('bankdebit/form', $name)
            ->setMethod('bankdebit')
            ->setPayment($this->getPayment())
            ->setTemplate('bankdebit/form.phtml');
        return $block;
    }

    public function getStandardCheckoutFormFields()
    {
        return array();
    }

    /**
     * Check void availability
     * @param   Varien_Object $payment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($payment instanceof Mage_Sales_Model_Order_Invoice
            || $payment instanceof Mage_Sales_Model_Order_Creditmemo
        ) {
            return false;
        }
        return $this->_canVoid;
    }

    public function canEdit()
    {
        return false;
    }

    public function canUseInternal()
    {
        return $this->_canUseInternal;
    }

    public function canUseForMultishipping()
    {
        return $this->_canUseForMultishipping;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
        return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Order_Invoice $payment)
    {
    }

    public function canCapture()
    {
        return $this->_canCapture;
    }

    public function getSession()
    {
        return Mage::getSingleton('bankdebit/session');
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function canFetchTransactionInfo()
    {
        return $this->_canFetchTransactionInfo;
    }

    /**
     * Complete Transaction and Save Transaction Id
     * @param  $order_id
     * @param  $result
     * @return bool|string
     */
    public function savePaymentTransaction($order_id, $result)
    {
        Mage::helper('bankdebit/tools')->addToDebug('Save Payment Transaction...', $order_id);

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

            Mage::helper('bankdebit/tools')->addToDebug('Order has been uncanceled.', $order_id);
        }

        /* Transaction statuses:
        0=Sale, 1=Initialize, 2=Credit, 3=Authorize, 4=Cancel, 5=Failure, 6=Capture */
        switch ((int)$result['transactionStatus']) {
            case 1:
                // From PayEx PIM:
                // "If PxOrder.Complete returns transactionStatus = 1, then check pendingReason for status."
                // See http://www.payexpim.com/payment-methods/paypal/
                if ($result['pending'] === 'true') {
                    Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, 0, $result);
                    $payment->save();

                    // Set Order State
                    $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Payment is pending'));
                    $order->save();
                } else {
                    Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                    $payment->save();

                    // Get Error Message
                    $error = Mage::helper('bankdebit')->__('Detected an abnormal payment process (Transaction Status: %s. Transaction Id: %s).', $result['transactionStatus'], $result['transactionNumber']);
                    Mage::helper('bankdebit/tools')->addToDebug($error, $order_id);

                    // Cancel
                    $order->cancel();
                    $order->addStatusHistoryComment($error);
                    $order->save();

                    return $error;
                }

                return $result;
            case 3:
                Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, 0, $result);
                $payment->save();

                Mage::helper('bankdebit/tools')->addToDebug('Payment is accepted', $order_id);

                // Set Order State
                $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Transaction Status: %s. Transaction Id: %s', $result['transactionStatus'], $result['transactionNumber']));
                $order->save();

                return $result;
            case 0;
            case 6:
                Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, 1, $result);
                $payment->save();

                // Set Order State
                $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Transaction Status: %s. Transaction Id: %s', $result['transactionStatus'], $result['transactionNumber']));
                $order->save();

                // Create Invoice for Sale Transaction
                if (isset($result['transactionNumber'])) {
                    $invoice = Mage::helper('bankdebit/order')->makeInvoice($order, false);

                    // Add transaction Id
                    $invoice->setTransactionId($result['transactionNumber']);
                    $invoice->save();
                }

                return $result;
            case 2:
                Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                $payment->save();

                // Get Error Message
                $error = Mage::helper('bankdebit')->__('Detected an abnormal payment process (Transaction Status: %s. Transaction Id: %s).', $result['transactionStatus'], $result['transactionNumber']);
                Mage::helper('bankdebit/tools')->addToDebug($error, $order_id);

                // Cancel Order
                $order->cancel();
                $order->addStatusHistoryComment($error);
                $order->save();

                return $error;
            case 4;
                Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                $payment->save();

                $order->cancel();
                $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Order automatically canceled. Transaction is canceled.'));
                $order->save();

                Mage::helper('bankdebit/tools')->addToDebug('Transaction is canceled.', $order_id);
                return Mage::helper('bankdebit')->__('Order is canceled.');
            case 5;
                Mage::helper('bankdebit/order')->createTransaction($payment, null, $result['transactionNumber'], Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, 0, $result);
                $payment->save();

                // Cancel
                if ($order->getId()) {
                    $order->cancel();
                    $order->addStatusHistoryComment(Mage::helper('bankdebit')->__('Order automatically canceled. Transaction is failed.'));
                    $order->save();
                }

                $error = Mage::helper('bankdebit/tools')->getVerboseErrorMessage($result);
                Mage::helper('bankdebit/tools')->addToDebug('Transaction is failed: ' . $error, $order_id);
                return $error;
            default:
                return Mage::helper('bankdebit')->__('Unknown transaction status.');
        }
    }
    /**
     * Add PayEx Single Order Line
     * @param string $orderRef
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function addOrderLine($orderRef, $order)
    {
        // add Order Items
        $items = $order->getAllVisibleItems();
        $i = 1;
        /** @var $item Mage_Sales_Model_Order_Item */
        foreach ($items as $item) {
            // @todo Calculate prices using Discount Rules
            // @todo Get children products from bundle
            if (!$item->getNoDiscount()) {
                Mage::helper('bankdebit/tools')->addToDebug('Warning: The product has a discount. There might be problems.', $order->getIncrementId());
            }

            $itemQty = (int)$item->getQtyOrdered();
            //$taxPrice = $item->getTaxAmount();
            $taxPrice = Mage::app()->getStore()->roundPrice($itemQty * $item->getPriceInclTax() - $itemQty * $item->getPrice());
            $taxPercent = $item->getTaxPercent();
            $priceWithTax = Mage::app()->getStore()->roundPrice($itemQty * $item->getPriceInclTax());

            // Calculate tax percent for Bundle products
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $taxPercent = ($taxPrice > 0) ? round(100 / (($priceWithTax - $taxPrice) / $taxPrice)) : 0;
            }

            $params = array(
                'accountNumber' => '',
                'orderRef' => $orderRef,
                'itemNumber' => $i,
                'itemDescription1' => $item->getName(),
                'itemDescription2' => '',
                'itemDescription3' => '',
                'itemDescription4' => '',
                'itemDescription5' => '',
                'quantity' => $itemQty,
                'amount' => round(100 * $priceWithTax), //must include tax
                'vatPrice' => round(100 * $taxPrice),
                'vatPercent' => round(100 * $taxPercent)
            );

            $result = Mage::helper('bankdebit/api')->getPx()->AddSingleOrderLine2($params);
            Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.AddSingleOrderLine2');
            $i++;
        }

        // add Discount
        $discount = $order->getDiscountAmount();

        // exclude shipping discount
        // discount is negative value
        $discount += $order->getShippingDiscountAmount();

        if (abs($discount) > 0) {
            $params = array(
                'accountNumber' => '',
                'orderRef' => $orderRef,
                'itemNumber' => $i,
                'itemDescription1' => ($order->getDiscountDescription() !== null) ? Mage::helper('sales')->__('Discount (%s)', $order->getDiscountDescription()) : Mage::helper('sales')->__('Discount'),
                'itemDescription2' => '',
                'itemDescription3' => '',
                'itemDescription4' => '',
                'itemDescription5' => '',
                'quantity' => 1,
                'amount' => round(100 * $discount),
                'vatPrice' => 0,
                'vatPercent' => 0
            );

            $result = Mage::helper('bankdebit/api')->getPx()->AddSingleOrderLine2($params);
            Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.AddSingleOrderLine2');
            $i++;
        }

        // add Shipping
        if (!$order->getIsVirtual()) {
            $shipping = $order->getShippingAmount();
            $shippingIncTax = $order->getShippingInclTax();
            $shippingTax = $order->getShippingTaxAmount();

            $params = array(
                'accountNumber' => '',
                'orderRef' => $orderRef,
                'itemNumber' => $i,
                'itemDescription1' => $order->getShippingDescription(),
                'itemDescription2' => '',
                'itemDescription3' => '',
                'itemDescription4' => '',
                'itemDescription5' => '',
                'quantity' => 1,
                'amount' => round(100 * $shippingIncTax), //must include tax
                'vatPrice' => round(100 * $shippingTax),
                'vatPercent' => $shipping != 0 ? round((100 * 100 * ($shippingTax) / $shipping)) : 0
            );

            $result = Mage::helper('bankdebit/api')->getPx()->AddSingleOrderLine2($params);
            Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.AddSingleOrderLine2');
            $i++;
        }
        return true;
    }

    /**
     * Add Payex Order Address
     * @param $orderRef
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function addOrderAddress($orderRef, $order)
    {
        $billingAddress = $order->getBillingAddress()->getStreet();
        $billingCountryCode = $order->getBillingAddress()->getCountry();
        $billingCountry = Mage::getModel('directory/country')->load($billingCountryCode)->getName();

        $params = array(
            'accountNumber' => '',
            'orderRef' => $orderRef,
            'billingFirstName' => $order->getBillingAddress()->getFirstname(),
            'billingLastName' => $order->getBillingAddress()->getLastname(),
            'billingAddress1' => $billingAddress[0],
            'billingAddress2' => (isset($billingAddress[1])) ? $billingAddress[1] : '',
            'billingAddress3' => '',
            'billingPostNumber' => (string)$order->getBillingAddress()->getPostcode(),
            'billingCity' => (string)$order->getBillingAddress()->getCity(),
            'billingState' => (string)$order->getBillingAddress()->getRegion(),
            'billingCountry' => $billingCountry,
            'billingCountryCode' => $billingCountryCode,
            'billingEmail' => (string)$order->getBillingAddress()->getEmail(),
            'billingPhone' => (string)$order->getBillingAddress()->getTelephone(),
            'billingGsm' => '',
        );

        // add Shipping
        $shipping_params = array(
            'deliveryFirstName' => '',
            'deliveryLastName' => '',
            'deliveryAddress1' => '',
            'deliveryAddress2' => '',
            'deliveryAddress3' => '',
            'deliveryPostNumber' => '',
            'deliveryCity' => '',
            'deliveryState' => '',
            'deliveryCountry' => '',
            'deliveryCountryCode' => '',
            'deliveryEmail' => '',
            'deliveryPhone' => '',
            'deliveryGsm' => '',
        );

        if (!$order->getIsVirtual()) {
            $deliveryAddress = $order->getShippingAddress()->getStreet();
            $deliveryCountryCode = $order->getShippingAddress()->getCountry();
            $deliveryCountry = Mage::getModel('directory/country')->load($deliveryCountryCode)->getName();

            $shipping_params = array(
                'deliveryFirstName' => $order->getShippingAddress()->getFirstname(),
                'deliveryLastName' => $order->getShippingAddress()->getLastname(),
                'deliveryAddress1' => $deliveryAddress[0],
                'deliveryAddress2' => (isset($deliveryAddress[1])) ? $deliveryAddress[1] : '',
                'deliveryAddress3' => '',
                'deliveryPostNumber' => (string)$order->getShippingAddress()->getPostcode(),
                'deliveryCity' => (string)$order->getShippingAddress()->getCity(),
                'deliveryState' => (string)$order->getShippingAddress()->getRegion(),
                'deliveryCountry' => $deliveryCountry,
                'deliveryCountryCode' => $deliveryCountryCode,
                'deliveryEmail' => (string)$order->getShippingAddress()->getEmail(),
                'deliveryPhone' => (string)$order->getShippingAddress()->getTelephone(),
                'deliveryGsm' => '',
            );
        }
        $params += $shipping_params;

        $result = Mage::helper('bankdebit/api')->getPx()->AddOrderAddress2($params);
        Mage::helper('bankdebit/tools')->debugApi($result, 'PxOrder.AddOrderAddress2');
        return true;
    }
}
