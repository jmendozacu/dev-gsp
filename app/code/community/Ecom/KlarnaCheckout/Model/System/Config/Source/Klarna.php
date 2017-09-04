<?php

class Ecom_KlarnaCheckout_Model_System_Config_Source_Klarna {
	
	const KLARNA_SERVER_LIVE = 'LIVE';
	const KLARNA_SERVER_BETA = 'BETA';
	
	public function toOptionArray() {
                
        $options = array(
			   array('value' => self::KLARNA_SERVER_LIVE, 'label' => Mage::helper('adminhtml')->__('Live server')),
			   array('value' => self::KLARNA_SERVER_BETA, 'label' => Mage::helper('adminhtml')->__('Beta server (for testing)'))
            );
		
        return $options;
    }
	
}