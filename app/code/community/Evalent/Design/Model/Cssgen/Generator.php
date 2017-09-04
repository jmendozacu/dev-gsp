<?php

class Evalent_Design_Model_Cssgen_Generator extends Infortis_Ultimo_Model_Cssgen_Generator {

    protected function _generateStoreCss($x0b, $x0d) {
        if (!Mage::app()->getStore($x0d)->getIsActive()) return;
        $x11 = '_' . $x0d;
        $x12 = $x0b . $x11 . '.css';
        $x13 = Mage::helper('ultimo/cssgen')->getGeneratedCssDir() . $x12;
        $x14 = Mage::helper('ultimo/cssgen')->getTemplatePath() . $x0b . '.phtml';
        Mage::register('cssgen_store', $x0d);
        try {
            $x15 = Mage::app()->getLayout()->createBlock("core/template")->setData('area', 'frontend')->setTemplate($x14)->toHtml();
            if (empty($x15)) {
                throw new Exception(Mage::helper('ultimo')->__("\x54\145\155p\x6ca\x74\145 \146\151l\x65\x20\151\x73 \145\x6d\160\x74y\x20or \144\x6f\145\163\x6e't\040\145\170\x69st\072 %\163", $x14));
            }

            $eventData = new Varien_Object(array('model' => $this, 'css' => $x15, 'type' => $x0b, 'store_code' => $x0d));
            Mage::dispatchEvent('ultimo_generate_css_save_before', array("object" => $eventData));
            $x15 = $eventData->getCss();

            $x16 = new Varien_Io_File();
            $x16->setAllowCreateFolders(true);
            $x16->open(array('path' => Mage::helper('ultimo/cssgen')->getGeneratedCssDir()));
            $x16->streamOpen($x13, 'w+', 0777);
            $x16->streamLock(true);
            $x16->streamWrite($x15);
            $x16->streamUnlock();
            $x16->streamClose();
        } catch (Exception $x17) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ultimo')->__('Failed generating CSS file: %s in %s', $x12, Mage::helper('ultimo/cssgen')->getGeneratedCssDir()) . '<br/>Message: ' . $x17->getMessage());
            Mage::logException($x17);
        }
        Mage::unregister('cssgen_store');
    }
}