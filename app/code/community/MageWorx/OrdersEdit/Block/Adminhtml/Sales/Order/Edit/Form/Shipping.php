<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_Shipping extends Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form
{
    /**
     * Prepare layout for shipping method form
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('mageworx/ordersedit/edit/shipping_method.phtml');
        return $this;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->getData('quote')) {
            return $this->getData('quote');
        }

        /** @var Mage_Sales_model_Order $order */
        $order = $this->getOrder() ? $this->getOrder() : Mage::registry('ordersedit_order');
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('mageworx_ordersedit/edit')->getQuoteByOrder($order);

        return $quote;
    }

    public function getShippingRates()
    {
        if (Mage::helper('mageworx_ordersedit')->isShippingCostRecalculationEnabled()) {
            $address = $this->getQuote()->getShippingAddress();

            $address->setCollectShippingRates(true);
            $this->_rates = $address->collectShippingRates()->getGroupedAllShippingRates();
            $address->save();

            return $this->_rates;
        }
        return parent::getShippingRates();
    }
}