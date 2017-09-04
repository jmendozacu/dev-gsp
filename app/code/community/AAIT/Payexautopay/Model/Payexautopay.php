<?php
/**
 * PayEx Autopay Payment Model
 * Created by AAIT Team.
 */

class AAIT_Payexautopay_Model_Payexautopay extends AAIT_Payexautopay_Model_Abstract
{
    /**
     * Payment method code
     */
    public $_code = 'payexautopay';

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

    /**
     * Payment method blocks
     */
    protected $_infoBlockType = 'payexautopay/info';
    protected $_formBlockType = 'payexautopay/form';

    /**
     * Supported currencies
     * See http://pim.payex.com/Section3/currencycodes.htm
     */
    static protected $_allowCurrencyCode = array('DKK', 'EUR', 'GBP', 'NOK', 'SEK', 'USD');

    /**
     * Init Class
     */
    public function __construct()
    {
        $accountnumber = $this->getConfigData('accountnumber');
        $encryptionkey = $this->getConfigData('encryptionkey');
        $debug = (bool)$this->getConfigData('debug');

        Mage::helper('payexautopay/api')->getPx()->setEnvironment($accountnumber, $encryptionkey, $debug);
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
     * Instantiate state and set it to state onject
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
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        // is Disabled
        if (parent::isAvailable($quote) === false) {
            return false;
        }
        if (Mage::app()->getStore()->isAdmin() == true) {
            return true; // Available for Admin only
        }
        // Available only for Logged
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * Validate
     * @return bool
     */
    public function validate()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Action: Validate');
        parent::validate();

        // Get current currency
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $currency_code = $paymentInfo->getOrder()->getBaseCurrencyCode();
        } else {
            $currency_code = $paymentInfo->getQuote()->getBaseCurrencyCode();
        }

        // Check supported currency
        if (!in_array($currency_code, self::$_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('payexautopay')->__('Selected currency code (%s) is not compatible with PayEx', $currency_code));
        }

        return true;
    }

    /**
     * Get the redirect url
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        Mage::helper('payexautopay/tools')->addToDebug('Action: getOrderPlaceRedirectUrl');

        // Get Current Quote
        $_quote = $this->getQuote();

        // Get Amount
        //$price = $_quote->getBaseGrandTotal();
        $price = $this->getOrderAmount($_quote);

        // Get Currency code
        $currency_code = $_quote->getBaseCurrencyCode();

        // Get the iso2 Country Code from the billing section ('SE')
        $countrycode = $_quote->getBillingAddress()->getCountry();

        // Get Order ID
        $order_id = $_quote->getReservedOrderId();

        // Get Store name
        $description = Mage::app()->getStore()->getName();

        // Get Operation Type (AUTHORIZATION / SALE)
        $operation = ($this->getConfigData('transactiontype') == 0) ? 'AUTHORIZATION' : 'SALE';

        // Get Customer Id
        $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();

        // Get Additional Values
        $additional = '';

        // Responsive Skinning
        if ($this->getConfigData('responsive') === '1') {
            $separator = (!empty($additional) && mb_substr($additional, -1) !== '&') ? '&' : '';
            $additional .= $separator . 'USECSS=RESPONSIVEDESIGN';
        }

        // Get Current Customer AgreementId
        $agreement_id = Mage::helper('payexautopay/agreement')->getCustomerAgreement();

        // Get AgreementId Status
        /** NotVerified = 0, Verified = 1, Deleted = 2, Not Exists = false */
        $agreement_status = Mage::helper('payexautopay/agreement')->getPxAgreementStatus($agreement_id);
        Mage::helper('payexautopay/tools')->addToDebug('Reserved Order for CustomerId #' . $customer_id, $order_id);
        Mage::helper('payexautopay/tools')->addToDebug('Current Agreement Status for CustomerId #' . $customer_id . ' is ' . var_export($agreement_status, true));

        // Is Agreement ID Verified: Pay using AutoPay
        if ($agreement_status === 1) {
            // Call PxAgreement.AutoPay2
            $result = Mage::helper('payexautopay/agreement')->callAutoPay($price, $order_id, $operation, $description, $agreement_id);

            if ($result['code'] == 'OK' && $result['description'] == 'OK' && $result['errorCode'] == 'OK') {
                // Redirect to AutoPay Controller
                $this->getCheckout()->setAutopayTransaction($result);

                $this->getCheckout()->setPayexQuoteId($this->getCheckout()->getQuoteId());
                $this->getCheckout()->getQuote()->setIsActive(false)->save();
                return Mage::getUrl('payexautopay/payexautopay/autopay', array('_secure' => true));
            }

            // AutoPay: NOT successful
            // Reset Customer Agreement
            Mage::helper('payexautopay/tools')->addToDebug('Warning: AgreementId ' . $agreement_id . ' of CustomerId ' . $customer_id . ' is removed!');
            Mage::helper('payexautopay/agreement')->removePxAgreement($agreement_id);
            Mage::helper('payexautopay/agreement')->removeCustomerAgreement($customer_id);
            $agreement_id = 0;

            /**
             * PayEx "LastTransactionNotCompleted" bugfix
             * If this error occurred, then the AutoPay data is cleaned and used Redirect Method.
             * Other PayEx Error Codes:
             * @see http://www.payexpim.com/technical-reference/return-codes/
             */
            $other_error_codes = array('PaymentRefusedByFinancialInstitution', 'CreditCard_Error', 'REJECTED_BY_ACQUIRER', 'Error_Generic', '3DSecureDirectoryServerError', 'AcquirerComunicationError', 'AmountNotEqualOrderLinesTotal', 'CardNotEligible');
            if (in_array($result['errorCode'], $other_error_codes)) {
                Mage::helper('payexautopay/tools')->addToDebug('Warning: ErrorCode ' . $result['errorCode'] . ' Detected! Tryning the Redirect Method.');
                Mage::helper('payexautopay/agreement')->removeCustomerAgreement($customer_id);
                $agreement_status = false;
                // Try Redirect method... Skip.
            } else {
                // Show Fatal AutoPay Error
                Mage::helper('payexautopay/tools')->throwPayExException($result, 'PxAgreement.AutoPay2');
            }
        }

        // Create Agreement ID
        if ($agreement_id === false || $agreement_status === false || $agreement_status === 2) {
            // Remove Deleted Agreement ID
            if ($agreement_status === 2) {
                Mage::helper('payexautopay/agreement')->removeCustomerAgreement($customer_id);
            }

            // Clean Current Agreement ID
            Mage::helper('payexautopay/agreement')->removeCustomerAgreement($customer_id);

            // Call PxAgreement.createAgreement
            $params = array(
                'accountNumber' => '',
                'merchantRef' => $this->getConfigData('agreementurl'),
                'description' => $description,
                'purchaseOperation' => $operation,
                'maxAmount' => round($this->getConfigData('maxamount') * 100),
                'notifyUrl' => '', // Deprecated
                'startDate' => '',
                'stopDate' => ''
            );

            $agreement_id = Mage::helper('payexautopay/agreement')->createPxAgreement($params);
            if ($agreement_id === false) {
                // Show Fatal Error
                Mage::helper('payexautopay/tools')->throwPayExException($result, 'PxAgreement.CreateAgreement3');
            }

            // Save Customer Agreement ID
            Mage::helper('payexautopay/agreement')->setCustomerAgreement($agreement_id);

            Mage::helper('payexautopay/tools')->addToDebug('Agreement for CustomerId #' . $customer_id . ' created');
        }

        // Use Redirect Method...
        // Call PxOrder.Initialize7
        $params = array(
            'accountNumber' => '',
            'purchaseOperation' => $operation,
            'price' => round($price * 100),
            'priceArgList' => '',
            'currency' => $currency_code,
            'vat' => 0,
            'orderID' => $order_id,
            'productNumber' => (Mage::getSingleton('customer/session')->isLoggedIn() == true) ? Mage::getSingleton('customer/session')->getCustomer()->getId() : '0',
            'description' => $description,
            'clientIPAddress' => Mage::helper('core/http')->getRemoteAddr(),
            'clientIdentifier' => 'USERAGENT=' . Mage::helper('core/http')->getHttpUserAgent(),
            'additionalValues' => $additional,
            'externalID' => '',
            'returnUrl' => Mage::getUrl('payexautopay/payexautopay/success', array('_secure' => true)),
            'view' => 'CREDITCARD',
            'agreementRef' => $agreement_id, /* Agreement ID */
            'cancelUrl' => Mage::getUrl('payexautopay/payexautopay/cancel', array('_secure' => true)),
            'clientLanguage' => $this->getConfigData('clientlanguage')
        );

        $result = Mage::helper('payexautopay/api')->getPx()->Initialize7($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.Initialize7');

        if ($result['code'] == 'OK' && $result['description'] == 'OK' && $result['errorCode'] == 'OK') {
            $orderRef = $result['orderRef'];
            $redirectUrl = $result['redirectUrl'];

            // Add Order Lines and Orders Address
            $this->addOrderLine($orderRef, $_quote);
            $this->addOrderAddress($orderRef, $_quote);

            // Save Transaction Data
            $this->getCheckout()->setTransaction($result);
            // Redirect to Redirect Controller
            return Mage::getUrl('payexautopay/payexautopay/redirect', array('_secure' => true));
        }

        // Show Error Message
        Mage::helper('payexautopay/tools')->throwPayExException($result, 'PxOrder.Initialize7');
    }

    /**
     * Capture payment
     * @param Varien_Object $payment
     * @param $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::helper('payexautopay/tools')->addToDebug('Action: Capture');

        parent::capture($payment, $amount);

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for capture.'));
        }

        if (!$payment->getLastTransId()) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        $payment->setAmount($amount);

        // Load transaction Data
        $transactionId = $payment->getLastTransId();
        $transaction = $payment->getTransaction($transactionId);
        if (!$transaction) {
            Mage::throwException(Mage::helper('payexautopay')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Not to execute for Sale transactions
        if ($details['transactionStatus'] != 3) {
            Mage::throwException(Mage::helper('payexautopay')->__('Can\'t capture captured order.'));
        }

        $transactionStatus = $details['transactionStatus'];
        $transactionNumber = $details['transactionNumber'];
        $order_id = !empty($details['orderId']) ? $details['orderId'] : $payment->getOrder()->getId();

        // Call PXOrder.Capture4
        $params = array(
            'accountNumber' => $this->getConfigData('accountnumber'),
            'transactionNumber' => $transactionNumber,
            'amount' => round($amount * 100),
            'orderId' => $order_id,
            'vatAmount' => 0,
            'additionalValues' => ''
        );
        $result = Mage::helper('payexautopay/api')->getPx()->Capture4($params);
        Mage::helper('payexautopay/tools')->addToDebug('PXOrder.Capture4:' . $result['description'], $order_id);

        // Check Results
        if ($result['code'] == 'OK' && $result['errorCode'] == 'OK' && $result['description'] == 'OK') {
            // Force Change order status
            $newOrderStatus = $this->getConfigData('order_status');
            if (empty($newOrderStatus)) {
                $newOrderStatus = $payment->getOrder()->getStatus();
            }
            Mage::helper('payexautopay/order')->changeOrderState($order_id, $newOrderStatus);

            // Add Capture Transaction
            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($result['transactionNumber'])
                ->setIsTransactionClosed(0);
            /* @todo Review (1) */

            // Add Transaction fields
            $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
            return $this;
        }

        // Show Error
        Mage::helper('payexautopay/tools')->throwPayExException($result, 'PxOrder.Capture4');
    }

    /**
     * Cancel payment
     * @param   Varien_Object $payment
     * @return  Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        Mage::helper('payexautopay/tools')->addToDebug('Action: Cancel');

        if (!$payment->getLastTransId()) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        // Load transaction Data
        $transactionId = $payment->getLastTransId();
        $transaction = $payment->getTransaction($transactionId);
        if (!$transaction) {
            Mage::throwException(Mage::helper('payexautopay')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Not to execute for Sale transactions
        if ($details['transactionStatus'] != 3) {
            Mage::throwException(Mage::helper('payexautopay')->__('Unable to execute cancel.'));
        }

        $transactionStatus = $details['transactionStatus'];
        $transactionNumber = $details['transactionNumber'];
        $order_id = !empty($details['orderId']) ? $details['orderId'] : $payment->getOrder()->getId();

        // Call PXOrder.Cancel2
        $params = array(
            'accountNumber' => $this->getConfigData('accountnumber'),
            'transactionNumber' => $transactionNumber
        );
        $result = Mage::helper('payexautopay/api')->getPx()->Cancel2($params);
        Mage::helper('payexautopay/tools')->addToDebug('PxOrder.Cancel2:' . $result['description'], $order_id);

        // Check Results
        if ($result['code'] == 'OK' && $result['errorCode'] == 'OK' && $result['description'] == 'OK') {
            // Add Cancel Transaction
            $payment->setStatus(self::STATUS_DECLINED)
                ->setTransactionId($result['transactionNumber'])
                ->setIsTransactionClosed(1); // Closed

            // Add Transaction fields
            $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
            return $this;
        }

        // Show Error
        Mage::helper('payexautopay/tools')->throwPayExException($result, 'PxOrder.Cancel2');
    }

    /**
     * Refund capture
     * @param Varien_Object $payment
     * @param $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        Mage::helper('payexautopay/tools')->addToDebug('Action: Refund');

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
            Mage::throwException(Mage::helper('payexautopay')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Check for Capture and Authorize transaction only
        if (isset($details['transactionStatus']) && $details['transactionStatus'] != 6 && $details['transactionStatus'] != 0) {
            Mage::throwException(Mage::helper('payexautopay')->__('This payment has not yet captured.'));
        }

        $transactionStatus = $details['transactionStatus'];
        $transactionNumber = $details['transactionNumber'];
        $order_id = !empty($details['orderId']) ? $details['orderId'] : $payment->getOrder()->getId();

        // Call PXOrder.Credit4
        $params = array(
            'accountNumber' => $this->getConfigData('accountnumber'),
            'transactionNumber' => $transactionNumber,
            'amount' => round($amount * 100),
            'orderId' => $order_id,
            'vatAmount' => 0,
            'additionalValues' => ''
        );
        $result = Mage::helper('payexautopay/api')->getPx()->Credit4($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.Credit');

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
        Mage::helper('payexautopay/tools')->throwPayExException($result, 'PxOrder.Credit4');

    }

    /**
     * Void payment
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        Mage::helper('payexautopay/tools')->addToDebug('Action: Void');
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
        Mage::helper('payexautopay/tools')->addToDebug('Action: fetchTransactionInfo. ID ' . $transactionId);

        // Get Transaction Details
        $params = array(
            'accountNumber' => $this->getConfigData('accountnumber'),
            'transactionNumber' => $transactionId,
        );
        $details = Mage::helper('payexautopay/api')->getPx()->GetTransactionDetails2($params);
        Mage::helper('payexautopay/tools')->debugApi($details, 'PxOrder.GetTransactionDetails2');

        // Check Results
        if ($details['code'] == 'OK' && $details['errorCode'] == 'OK' && $details['description'] == 'OK') {
            // Filter details
            foreach ($details as $key => $value) {
                if (empty($value)) {
                    unset($details[$key]);
                }
            }
            return $details;
        }

        // Show Error
        Mage::helper('payexautopay/tools')->throwPayExException($details, 'GetTransactionDetails2');
    }

    /**
     * Create Payment Block
     * @param $name
     * @return mixed
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('payexautopay/form', $name)
            ->setMethod('payexautopay')
            ->setPayment($this->getPayment())
            ->setTemplate('payexautopay/form.phtml');
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
        return Mage::getSingleton('payexautopay/session');
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
     * Get Order Amount
     * With Using Rounding Issue Fix
     * @param $quote
     * @return float
     */
    public function getOrderAmount(&$quote)
    {
        $amount = 0;
        // add Order Items
        $items = $quote->getAllItems();
        /** @var $item Mage_Sales_Model_Quote_Item */
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $itemQty = $item->getQty();
            $price = $item->getTaxableAmount() + $item->getExtraTaxableAmount();
            $taxPrice = $item->getTaxAmount() + $item->getDiscountTaxCompensation();
            $taxPercent = $item->getTaxPercent();
            $priceWithTax = $item->getIsPriceInclTax() ? $price : $price + $taxPrice;

            $amount += round(100 *$priceWithTax);
        }

        // add Discount
        $shippingDiscount = (float)$quote->getShippingAddress()->getBaseDiscountAmount();
        $billingDiscount = (float)$quote->getBillingAddress()->getBaseDiscountAmount();
        $discount = $billingDiscount + $shippingDiscount;

        // exclude shipping discount
        // discount is negative value
        $discount += $quote->getShippingAddress()->getBaseShippingDiscountAmount();

        $amount += round(100 * $discount);

        // add Shipping
        if (!$quote->getIsVirtual()) {
            $shipping = $quote->getShippingAddress()->getBaseShippingAmount();
            $shippingTax = $quote->getShippingAddress()->getBaseShippingTaxAmount();

            $amount += round(100 * ($shipping + $shippingTax));
        }

        $grand_total = $quote->getBaseGrandTotal();
        $amount = $amount / 100;

        $abs = abs(Mage::app()->getStore()->roundPrice($amount) - Mage::app()->getStore()->roundPrice($grand_total));
        // Is 0.010000000002037
        if ($abs > 0 && $abs < 0.011) {
            Mage::helper('payexautopay/tools')->addToDebug('Warning: Price rounding issue. ' . $grand_total . ' vs ' . $amount);
            return $amount;
        } else {
            return $grand_total;
        }
    }

    /**
     * Add PayEx Single Order Line
     * @param $orderRef
     * @param $quote
     * @return bool
     */
    public function addOrderLine($orderRef, &$quote)
    {
        $order_id = $quote->getReservedOrderId();

        // add Order Items
        $items = $quote->getAllItems();
        $i = 1;
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $itemQty = $item->getQty();
            $price = $item->getTaxableAmount() + $item->getExtraTaxableAmount();
            $taxPrice = $item->getTaxAmount() + $item->getDiscountTaxCompensation();
            $taxPercent = $item->getTaxPercent();
            $priceWithTax = $item->getIsPriceInclTax() ? $price : $price + $taxPrice;

            $params = array(
                'accountNumber' => $this->getConfigData('accountnumber'),
                'orderRef' => $orderRef,
                'itemNumber' => $i,
                'itemDescription1' => $item->getName(),
                'itemDescription2' => '[Magento Shop] Payment for Order ID ' . $order_id,
                'itemDescription3' => '',
                'itemDescription4' => '',
                'itemDescription5' => '',
                'quantity' => $itemQty,
                'amount' => round(100 * $priceWithTax), //must include tax
                'vatPrice' => round(100 * $taxPrice),
                'vatPercent' => round(100 * $taxPercent)
            );

            $result = Mage::helper('payexautopay/api')->getPx()->AddSingleOrderLine($params);
            Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.AddSingleOrderLine');
            $i++;
        }

        // add Discount
        $shippingDiscount = (float)$quote->getShippingAddress()->getBaseDiscountAmount();
        $billingDiscount = (float)$quote->getBillingAddress()->getBaseDiscountAmount();
        $discount = $billingDiscount + $shippingDiscount;

        // exclude shipping discount
        // discount is negative value
        $discount += $quote->getShippingAddress()->getBaseShippingDiscountAmount();

        if (!empty($discount)) {
            $params = array(
                'accountNumber' => $this->getConfigData('accountnumber'),
                'orderRef' => $orderRef,
                'itemNumber' => $i,
                'itemDescription1' => Mage::helper('payexautopay')->__('Discount'),
                'itemDescription2' => '[Magento Shop] Payment for Order ID ' . $order_id,
                'itemDescription3' => '',
                'itemDescription4' => '',
                'itemDescription5' => '',
                'quantity' => 1,
                'amount' => round($discount * 100),
                'vatPrice' => 0,
                'vatPercent' => 0
            );

            $result = Mage::helper('payexautopay/api')->getPx()->AddSingleOrderLine($params);
            Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.AddSingleOrderLine');
            $i++;
        }

        // add Shipping
        if (!$quote->getIsVirtual()) {
            $shipping = $quote->getShippingAddress()->getBaseShippingAmount();
            $shippingTax = $quote->getShippingAddress()->getBaseShippingTaxAmount();

            $params = array(
                'accountNumber' => $this->getConfigData('accountnumber'),
                'orderRef' => $orderRef,
                'itemNumber' => $i,
                'itemDescription1' => Mage::helper('payexautopay')->__('Shipping'),
                'itemDescription2' => '[Magento Shop] Payment for Order ID ' . $order_id,
                'itemDescription3' => '',
                'itemDescription4' => '',
                'itemDescription5' => '',
                'quantity' => 1,
                'amount' => round(($shipping + $shippingTax) * 100), //must include tax
                'vatPrice' => round(100 * $shippingTax),
                'vatPercent' => $shipping != 0 ? round((100 * 100 * ($shippingTax) / $shipping)) : 0
            );

            $result = Mage::helper('payexautopay/api')->getPx()->AddSingleOrderLine($params);
            Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.AddSingleOrderLine');
            $i++;
        }
        return true;
    }

    /**
     * Add Payex Order Address
     * @param $orderRef
     * @param $quote
     * @return bool
     */
    public function addOrderAddress($orderRef, &$quote)
    {
        $billingAddress = $quote->getBillingAddress()->getStreet();
        $deliveryAddress = $quote->getShippingAddress()->getStreet();
        $billingCountryCode = $quote->getBillingAddress()->getCountry();
        $billingCountry = Mage::getModel('directory/country')->load($billingCountryCode)->getName();
        $deliveryCountryCode = $quote->getShippingAddress()->getCountry();
        $deliveryCountry = Mage::getModel('directory/country')->load($deliveryCountryCode)->getName();

        $params = array(
            'accountNumber' => $this->getConfigData('accountnumber'),
            'orderRef' => $orderRef,
            'billingFirstName' => $quote->getBillingAddress()->getFirstname(),
            'billingLastName' => $quote->getBillingAddress()->getLastname(),
            'billingAddress1' => $billingAddress[0],
            'billingAddress2' => (isset($billingAddress[1])) ? $billingAddress[1] : '',
            'billingAddress3' => '',
            'billingPostNumber' => (string)$quote->getBillingAddress()->getPostcode(),
            'billingCity' => (string)$quote->getBillingAddress()->getCity(),
            'billingState' => (string)$quote->getBillingAddress()->getRegion(),
            'billingCountry' => $billingCountry,
            'billingCountryCode' => $billingCountryCode,
            'billingEmail' => (string)$quote->getBillingAddress()->getEmail(),
            'billingPhone' => (string)$quote->getBillingAddress()->getTelephone(),
            'billingGsm' => '',
        );

        // add Shipping
        if (!$quote->getIsVirtual()) {
            $shipping_params = array(
                'deliveryFirstName' => $quote->getShippingAddress()->getFirstname(),
                'deliveryLastName' => $quote->getShippingAddress()->getLastname(),
                'deliveryAddress1' => $deliveryAddress[0],
                'deliveryAddress2' => (isset($deliveryAddress[1])) ? $deliveryAddress[1] : '',
                'deliveryAddress3' => '',
                'deliveryPostNumber' => (string)$quote->getShippingAddress()->getPostcode(),
                'deliveryCity' => (string)$quote->getShippingAddress()->getCity(),
                'deliveryState' => (string)$quote->getShippingAddress()->getRegion(),
                'deliveryCountry' => $deliveryCountry,
                'deliveryCountryCode' => $deliveryCountryCode,
                'deliveryEmail' => (string)$quote->getShippingAddress()->getEmail(),
                'deliveryPhone' => (string)$quote->getShippingAddress()->getTelephone(),
                'deliveryGsm' => '',
            );
        } else {
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
        }
        $params += $shipping_params;

        $result = Mage::helper('payexautopay/api')->getPx()->AddOrderAddress2($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxOrder.AddOrderAddress2');
        return true;
    }
    /**
     * Whether can manage recurring profiles
     *
     * @return bool
     */
    public function canManageRecurringProfiles()
    {
        return $this->_canManageRecurringProfiles;
    }

}