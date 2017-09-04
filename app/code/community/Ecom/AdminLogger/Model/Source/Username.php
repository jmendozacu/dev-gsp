<?php
/**
 * Created by PhpStorm.
 * User: klang
 * Date: 2015-03-31
 * Time: 12:15
 */ 
class Ecom_AdminLogger_Model_Source_Username extends Mage_Core_Model_Abstract {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {

        $return = array();

        foreach(Mage::getModel("Mage_Admin_Model_User")->getCollection() as $user){

            $return[$user->getUsername()]= $user->getUsername();

        }
        return $return;
    }
}