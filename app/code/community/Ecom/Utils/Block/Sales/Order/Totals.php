<?php

class Ecom_Utils_Block_Sales_Order_Totals extends Mage_Sales_Block_Order_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
		 parent::_initTotals();
		 
		 if(Mage::getStoreConfig('tax/calculation/price_includes_tax')) return $this;
		 
		 if(isset($this->_totals['discount'])){
			 $this->_totals['discount']->setValue(Mage::helper('utils/discount')->getOrderDiscount($this->getOrder()));
		 }
		 
		 return $this;
    }

}
