<?php
/**
 * Model template
 *
 */
class Gsp_Repair_Model_Observer extends Mage_Core_Model_Abstract
{
	function appendCustomColumn(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (!isset($block)) {
            return $this;
        }
 
        if ($block->getType() == 'adminhtml/sales_order_grid') {
            /* @var $block Mage_Adminhtml_Block_Customer_Grid */
            $block->addColumn('repair_isrepair', array(
                'header'    => 'Reparation',
                'type'    => 'options',
                'options' => array('1' => 'Ja', '0' => 'Nej'),
                'index'     => 'repair_isrepair',
                'width'     => '50px'
            ));
        }
	}
    
    //     /**
    //  * Catalog Product After Save
    //  *
    //  * @param Varien_Event_Observer $observer
    //  * @return Mage_Catalog_Model_Product_Flat_Observer
    //  */
    // public function catalogProductSaveAfter(Varien_Event_Observer $observer) {      
    //     $product   = $observer->getEvent()->getProduct();
    //     
    //     $groupPrices = $product->getData('group_price');
    //     
    //     foreach ($groupPrices as $group)
    //     {
    //         
    //     }
    //     
    // }
}
