<?php
/**
 * Paygate data helper
 */
class AAIT_Payex2_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_PAYEX_METHODS = 'payex2';

    public function getMethodInstance($code)
    {
        $key = self::XML_PATH_PAYEX_METHODS . '/' . $code . '/model';
        $class = Mage::getStoreConfig($key);
        if (!$class) {
            Mage::throwException($this->__('Can not configuration for payment method with code: %s', $code));
        }
        return Mage::getModel($class);
    }
}
