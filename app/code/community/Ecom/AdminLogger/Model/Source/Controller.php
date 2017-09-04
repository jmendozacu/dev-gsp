<?php
/**
 * Created by PhpStorm.
 * User: klang
 * Date: 2015-03-31
 * Time: 12:15
 */ 
class Ecom_AdminLogger_Model_Source_Controller extends Mage_Core_Model_Abstract {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {

        $return = array();

        foreach(Mage::getStoreConfig(Ecom_AdminLogger_Model_Observer_Action::XML_PATH_CONTROLLER_FILTER) as $controller => $data){
            $return[$controller]= $controller;
        }
        return $return;
    }
}