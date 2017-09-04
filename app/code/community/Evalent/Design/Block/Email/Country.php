<?php
// $countryCode looks like "US"

class Evalent_Design_Block_Email_Country extends Mage_Core_Block_Text {
    public function _toHtml () {

        $merchantCountry = Mage::getStoreConfig('general/store_information/merchant_country');
        if(isset($merchantCountry) && !empty($merchantCountry)){
            $country = Mage::getModel('directory/country')->loadByCode($merchantCountry);
            if ($country && $country->getId()){
                return $country->getName();
            }
        }

        return '';
    }
}
