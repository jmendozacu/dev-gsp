<?php

/**
 * PayEx Invoice Payment Model
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment method code
     */
    public $_code = 'payexinvoice';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canFetchTransactionInfo = true;

    /**
     * Payment method blocks
     */
    protected $_infoBlockType = 'payexinvoice/info';
    protected $_formBlockType = 'payexinvoice/form';

    /**
     * Init Class
     */
    public function __construct()
    {
        $accountnumber = $this->getConfigData('accountnumber');
        $encryptionkey = $this->getConfigData('encryptionkey');
        $debug = (bool)$this->getConfigData('debug');

        Mage::helper('payexinvoice/api')->getPx()->setEnvironment($accountnumber, $encryptionkey, $debug);
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
        Mage::helper('payexinvoice/tools')->addToDebug('Action: Validate');

        // Get Total Amount
        $amount = $this->getQuote()->getGrandTotal();            

        // Get Invoice Type
        $type = Mage::app()->getRequest()->getParam('pxinvoice_method');
        switch ($type) {
            case 'private':
                $ssn = Mage::app()->getRequest()->getParam('socialSecurityNumber');
                $firstName = Mage::app()->getRequest()->getParam('firstName');
                $lastName = Mage::app()->getRequest()->getParam('lastName');

                // Call PxVerification.CreditCheckPrivate
                $params = array(
                    'accountNumber' => '',
                    'countryCode' => $this->getQuote()->getBillingAddress()->getCountry(),
                    'socialSecurityNumber' => $ssn,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'amount' => round($amount * 100),
                    'clientIPAddress' => Mage::helper('core/http')->getRemoteAddr()
                );
                $status = Mage::helper('payexinvoice/api')->getPx()->CreditCheckPrivate2($params);
                Mage::helper('payexinvoice/tools')->addToDebug('PxVerification.CreditCheckPrivate2:' . $status['description']);
                break;
            case 'corporate':
                $orgnum = Mage::app()->getRequest()->getParam('organizationNumber');

                // Call PxVerification.CreditCheckCorporate
                //$code = () ?: $this->getInfoInstance()->getQuote()->getBillingAddress()->getCountry();
             //   $amount = ($amount) ?: $this->getInfoInstance()->getQuote()->getGrandTotal();
                
                $params = array(
                    'accountNumber' => '',
                    'countryCode' => $this->getQuote()->getBillingAddress()->getCountry(),
                    'organizationNumber' => $orgnum,
                    'amount' => round($amount * 100)
                );              
                
                //Mage::helper('payexinvoice/tools')->addToDebug('PxVerification.CreditCheckCorporate22:' .  $this->getInfoInstance()->getQuote()->getGrandTotal());
                $status = Mage::helper('payexinvoice/api')->getPx()->CreditCheckCorporate2($params);
                Mage::helper('payexinvoice/tools')->addToDebug('PxVerification.CreditCheckCorporate:' . $status['description']);
                break;
            default:
                $status = array();
                break;
        }

        if ($status['code'] == 'OK' && $status['description'] == 'OK' && $status['errorCode'] == 'OK') {
            // Check if credit check went ok
            if ($status['creditStatus'] === 'True') {
                $this->getCheckout()->setCreditData($status);
                Mage::helper('payexinvoice/tools')->addToDebug('Credit status: ok');
                return true;
            } elseif ($this->getConfigData('unapproved')) {
                // Allow unapproved
                $this->getCheckout()->setCreditData($status);
                Mage::helper('payexinvoice/tools')->addToDebug('Credit status: not approved. Ignore this.');
                return true;
            } else {
                // Declining payment
                Mage::helper('payexinvoice/tools')->addToDebug('Credit status: not approved. Abort payment.');
                Mage::throwException('Unfortunately PayEx did not grant you Invoice credit. Please try other means of payment');
            }
        }
        // Show Error Message
        Mage::helper('payexinvoice/tools')->throwPayExException($status, 'PxVerification.CreditCheck');
        return false;
    }

    /**
     * Get the redirect url
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        Mage::helper('payexinvoice/tools')->addToDebug('Action: getOrderPlaceRedirectUrl');

        // Save Data
        $method = Mage::app()->getRequest()->getParam('pxinvoice_method');
        $this->getCheckout()->setMethod($method);

        $ssn = ($method === 'private') ? Mage::app()->getRequest()->getParam('socialSecurityNumber') : Mage::app()->getRequest()->getParam('organizationNumber');
        $this->getCheckout()->setSocialSecurtyNumber($ssn);

        return Mage::getUrl('payexinvoice/payexinvoice/redirect', array('_secure' => true));
    }

    /**
     * Capture payment
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::helper('payexinvoice/tools')->addToDebug('Action: Capture');

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
            Mage::throwException(Mage::helper('payexinvoice')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Not to execute for Sale transactions
        if ((int)$details['transactionStatus'] !== 3) {
            Mage::throwException(Mage::helper('payexinvoice')->__('Can\'t capture captured order.'));
            //return $this;
        }

        $transactionNumber = $details['transactionNumber'];
        $order_id = $details['orderId'];
        if (!$order_id) {
            $order_id = $payment->getOrder()->getIncrementId();
        }

        // Call PxOrder.Capture5
        $params = array(
            'accountNumber' => '',
            'transactionNumber' => $transactionNumber,
            'amount' => round(100 * $amount),
            'orderId' => $order_id,
            'vatAmount' => 0,
            'additionalValues' => ''
        );
        $result = Mage::helper('payexinvoice/api')->getPx()->Capture5($params);
        Mage::helper('payexinvoice/tools')->addToDebug('PXOrder.Capture5:' . $result['description'], $order_id);

        // Check Results
        if ($result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            // Note: Order Status will be changed in Observer

            // Add Capture Transaction
            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($result['transactionNumber'])
                ->setIsTransactionClosed(0);

            // Add Transaction fields
            $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
            return $this;
        }

        // Show Error
        Mage::helper('payexinvoice/tools')->throwPayExException($result, 'PxOrder.Capture5');
        return $this;
    }

    /**
     * Cancel payment
     * @param   Varien_Object $payment
     * @return  $this
     */
    public function cancel(Varien_Object $payment)
    {
        Mage::helper('payexinvoice/tools')->addToDebug('Action: Cancel');

        if (!$payment->getLastTransId()) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        // Load transaction Data
        $transactionId = $payment->getLastTransId();
        $transaction = $payment->getTransaction($transactionId);
        if (!$transaction) {
            Mage::throwException(Mage::helper('payexinvoice')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Not to execute for Sale transactions
        if ((int)$details['transactionStatus'] !== 3) {
            Mage::throwException(Mage::helper('payexinvoice')->__('Unable to execute cancel.'));
        }

        $transactionNumber = $details['transactionNumber'];
        $order_id = $details['orderId'];
        if (!$order_id) {
            $order_id = $payment->getOrder()->getId();
        }

        // Call PXOrder.Cancel2
        $params = array(
            'accountNumber' => '',
            'transactionNumber' => $transactionNumber
        );
        $result = Mage::helper('payexinvoice/api')->getPx()->Cancel2($params);
        Mage::helper('payexinvoice/tools')->addToDebug('PxOrder.Cancel2:' . $result['description'], $order_id);

        // Check Results
        if ($result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
            // Add Cancel Transaction
            $payment->setStatus(self::STATUS_DECLINED)
                ->setTransactionId($result['transactionNumber'])
                ->setIsTransactionClosed(1); // Closed

            // Add Transaction fields
            $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
            return $this;
        }

        // Show Error
        Mage::helper('payexinvoice/tools')->throwPayExException($result, 'PxOrder.Cancel2');
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
        Mage::helper('payexinvoice/tools')->addToDebug('Action: Refund');

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
            Mage::throwException(Mage::helper('payexinvoice')->__('Can\'t load last transaction.'));
        }

        // Get Transaction Details
        $details = $this->fetchTransactionInfo($payment, $transactionId);

        // Check for Capture and Authorize transaction only
        if ((int)$details['transactionStatus'] !== 6 && (int)$details['transactionStatus'] !== 0) {
            Mage::throwException(Mage::helper('payexinvoice')->__('This payment has not yet captured.'));
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
            'amount' => round(100 * $amount),
            'orderId' => $order_id,
            'vatAmount' => 0,
            'additionalValues' => ''
        );
        $result = Mage::helper('payexinvoice/api')->getPx()->Credit5($params);
        Mage::helper('payexinvoice/tools')->debugApi($result, 'PxOrder.Credit');

        // Check Results
        if ($result['code'] === 'OK' && $result['errorCode'] === 'OK' && $result['description'] === 'OK') {
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
        Mage::helper('payexinvoice/tools')->throwPayExException($result, 'PxOrder.Credit5');
        return $this;
    }

    /**
     * Void payment
     * @param Varien_Object $payment
     * @return $this
     */
    public function void(Varien_Object $payment)
    {
        Mage::helper('payexinvoice/tools')->addToDebug('Action: Void');
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
        Mage::helper('payexinvoice/tools')->addToDebug('Action: fetchTransactionInfo. ID ' . $transactionId);

        // Get Transaction Details
        $params = array(
            'accountNumber' => '',
            'transactionNumber' => $transactionId,
        );
        $details = Mage::helper('payexinvoice/api')->getPx()->GetTransactionDetails2($params);
        Mage::helper('payexinvoice/tools')->debugApi($details, 'PxOrder.GetTransactionDetails2');

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
        Mage::helper('payexinvoice/tools')->throwPayExException($details, 'GetTransactionDetails2');
    }

    /**
     * Create Payment Block
     * @param $name
     * @return mixed
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('payexinvoice/form', $name)
            ->setMethod('payexinvoice')
            ->setPayment($this->getPayment())
            ->setTemplate('payexinvoice/form.phtml');
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
        return Mage::getSingleton('payexinvoice/session');
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
     * Get Product Tax Classes
     * @return array
     */
    public function getTaxClasses()
    {
        $collection = Mage::getModel('tax/class')
            ->getCollection()
            ->setClassTypeFilter('PRODUCT')
            ->toOptionArray();
        return $collection;
    }
}