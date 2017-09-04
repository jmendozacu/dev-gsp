<?php
/**
 * PayEx Invoice Helper: Paygate data helper
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_PATH_PAYEX_METHODS = 'payexinvoice';

    public function getMethodInstance($code) {
        $key = self::XML_PATH_PAYEX_METHODS . '/' . $code . '/model';
        $class = Mage::getStoreConfig($key);
        if (!$class) {
            Mage::throwException(Mage::helper('payexinvoice')->__('Can not configuration for payment method with code: %s', $code));
        }
        return Mage::getModel($class);
    }

}
