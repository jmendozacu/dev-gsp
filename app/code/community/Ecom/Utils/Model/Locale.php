<?php

class Ecom_Utils_Model_Locale extends Mage_Core_Model_Locale {
    public function getJsPriceFormat(){

        $result = parent::getJsPriceFormat();
        $result['precision'] = $result['requiredPrecision'] = Mage::getStoreConfig("currency/options/frontend_precision");
        return $result;

    }
}