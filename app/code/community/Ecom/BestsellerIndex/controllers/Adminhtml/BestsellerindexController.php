<?php
 
class Ecom_BestsellerIndex_Adminhtml_BestsellerindexController
    extends Mage_Adminhtml_Controller_Action
{
    public function runreindexAction()
    {
        Mage::getModel('bestsellerindex/process')->processAll();
		
		$result = 1;
        Mage::app()->getResponse()->setBody($result);
    }

    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('system/config/catalog');
    }
}