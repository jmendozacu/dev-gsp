<?php

/**
 * Class Ecom_UltraCart_Model_Ultimo
 * Adds css to ultimos CSS-file "design_[storecode].css"
 * Loads css from ultracart/ultimo/css.phtml
 */
class Ecom_UltraCart_Model_Ultimo extends Mage_Core_Model_Abstract {
    public function addCss($observer){

        $event = $observer->getEvent()->getObject();
        if($event->getType() != 'design') return;

        $css = $event->getCss();
        $css .= Mage::app()->getLayout()->createBlock("core/template")->setData('area', 'frontend')->setTemplate("ultracart/ultimo/css.phtml")->toHtml();
        $event->setCss($css);
    }
}