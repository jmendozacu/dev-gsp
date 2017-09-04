<?php
/**
 * PayEx AutoPay Helper: Agreement Tools
 * Created by AAIT Team.
 */

class AAIT_Payexautopay_Helper_Agreement extends Mage_Core_Helper_Abstract
{
    protected static $session_token = 'PAYEX_GUEST_AGREEMENT_ID';

    /**
     * Get Customer Agreement ID from Database
     * @return bool
     */
    public function getCustomerAgreement()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            $db = Mage::getSingleton('core/resource')->getConnection('core_read');
            $results = $db->fetchAll("SELECT agreement_id FROM payex_autopay WHERE customer_id = $customer_id;");
            if (count($results) > 0) {
                $agreement_id = $results[0]['agreement_id'];
            } else {
                return false;
            }
        } elseif (isset($_SESSION[self::$session_token])) {
            $agreement_id = $_SESSION[self::$session_token];
        } else {
            return false;
        }
        return $agreement_id;
    }

    /**
     * Set Customer Agreement ID in Database
     * @param  $agreement_id
     * @return void
     */
    public function setCustomerAgreement($agreement_id)
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            $db = Mage::getSingleton('core/resource')->getConnection('core_write');
            if (self::getCustomerAgreement() === false) {
                $query = "INSERT INTO `payex_autopay` (customer_id, agreement_id) VALUES ('$customer_id', '$agreement_id');";
            } else {
                $query = "UPDATE `payex_autopay` SET agreement_id='$agreement_id' WHERE customer_id='$agreement_id';";
            }
            $db->query($query);
        } else {
            $_SESSION[self::$session_token] = $agreement_id;
        }
    }

    /**
     * Remove Customer Agreement ID form Database
     * @param int $customer_id
     * @return void
     */
    public function removeCustomerAgreement($customer_id = 0)
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            if ($customer_id == 0) {
                $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            }
            $db = Mage::getSingleton('core/resource')->getConnection('core_write');
            if ($this->getCustomerAgreement() !== false) {
                $query = "DELETE FROM `payex_autopay` WHERE customer_id='$customer_id';";
                $db->query($query);
            }
        } else {
            unset($_SESSION[self::$session_token]);
        }
    }

    /**
     * Remove Customer Agreement
     * @param $agreement_id
     * @return bool
     */
    public function removePxAgreement($agreement_id)
    {
        if ($agreement_id !== false) {
            // Call PxAgreement.DeleteAgreement
            $params = array(
                'accountNumber' => '',
                'agreementRef' => $agreement_id,
            );

            $result = Mage::helper('payexautopay/api')->getPx()->DeleteAgreement($params);
            Mage::helper('payexautopay/tools')->debugApi($result, 'PxAgreement.DeleteAgreement');

            if ($result['code'] == 'OK' && $result['description'] == 'OK' && $result['errorCode'] == 'OK') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check status Customer Agreement
     * @param  $agreement_id
     * @return bool | int
     */
    public function getPxAgreementStatus($agreement_id)
    {
        if ($agreement_id === false) {
            return false;
        }

        // Call PxAgreement.AgreementCheck
        $params = array(
            'accountNumber' => '',
            'agreementRef' => $agreement_id,
        );

        $result = Mage::helper('payexautopay/api')->getPx()->AgreementCheck($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxAgreement.AgreementCheck');

        if ($result['code'] == 'OK' && $result['description'] == 'OK' && $result['errorCode'] == 'OK') {
            /** NotVerified = 0, Verified = 1, Deleted = 2 */
            $status = (int)$result['agreementStatus'];
            Mage::helper('payexautopay/tools')->addToDebug('PxAgreement.AgreementCheck Status is ' . $status . ' (NotVerified = 0, Verified = 1, Deleted = 2)');
            //if ( $status != 1 ) { return false; } // Not Verified
            return $status;
        }
        return false;
    }

    /**
     * Create Agreement
     * @param $params
     * @return array
     */
    public function createPxAgreement($params)
    {
        // Call PxAgreement.CreateAgreement3
        $result = Mage::helper('payexautopay/api')->getPx()->CreateAgreement3($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxAgreement.CreateAgreement3');

        if ($result['code'] == 'OK' && $result['description'] == 'OK' && $result['errorCode'] == 'OK') {
            return $result['agreementRef'];
        }
        return false;
    }

    /**
     * Do PxAgreement.AutoPay2
     * @param int $price
     * @param string $order_id
     * @param string $operation
     * @param string $description
     * @param int $agreement_id
     * @return array
     */
    public function callAutoPay($price, $order_id, $operation, $description, $agreement_id)
    {
        // Call PxAgreement.AutoPay2
        $params = array(
            'accountNumber' => '',
            'agreementRef' => $agreement_id,
            'price' => round($price * 100),
            'productNumber' => (Mage::getSingleton('customer/session')->isLoggedIn() == true) ? Mage::getSingleton('customer/session')->getCustomer()->getId() : '0',
            'description' => $description,
            'orderId' => $order_id,
            'purchaseOperation' => $operation
        );

        $result = Mage::helper('payexautopay/api')->getPx()->AutoPay2($params);
        Mage::helper('payexautopay/tools')->debugApi($result, 'PxAgreement.AutoPay2');
        return $result;
    }
}