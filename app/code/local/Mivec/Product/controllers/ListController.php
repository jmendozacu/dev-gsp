<?php
class Mivec_Product_ListController extends Mage_Core_Controller_Front_Action
{
    private function _init()
    {
        $this->loadLayout();
        return $this;
    }

    public function newAction()
    {
        $this->_init()
            ->renderLayout();
    }

}