<?php

/**
 * Cleanbutton Widget for Admin
 */
class AAIT_Payexautopay_Block_Cleanbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        /* Pending Clean URL */
        $url = Mage::getUrl('payexautopay/payexautopay/pendingclean', array('_secure' => true));
        $label = Mage::helper('payexautopay')->__('Clean');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel($label)
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
}