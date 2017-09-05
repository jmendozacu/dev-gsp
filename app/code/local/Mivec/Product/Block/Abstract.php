<?php
abstract class Mivec_Product_Block_Abstract extends Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        return $this;
    }
}