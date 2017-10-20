<?php

/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Widget//Mage_Adminhtml_Block_Sales_Order_Create_Abstract
{
    protected $_totals;
    protected $_buttons = array();
    protected $_afterTotalsHtml = '';

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('mageworx/ordersedit/totals.phtml');
    }

    /**
     * Format total value based on order currency
     *
     * @param   Varien_Object $total
     * @return  string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->helper('adminhtml/sales')->displayPrices(
                $this->getOrder(),
                $total->getBaseValue(),
                $total->getValue()
            );
        }
        return $total->getValue();
    }

    /**
     * Get order totals
     * @return array
     */
    public function getTotals()
    {
        $totals = $this->getData('totals');

        //for shipping incl. tax on "New Totals" block
        if ((Mage::helper('tax')->displayShippingPriceIncludingTax() || Mage::helper('tax')->displayShippingBothPrices()) &&
            isset($totals['shipping'])) {
            $totals['shipping']->setValue($this->getSource()->getShippingAddress()->getShippingInclTax());
        }

        $order = $this->getOrder();
        $rate = $order->getBaseToOrderRate();
        foreach ($totals as $total) {
            $base = $total->getValue() / $rate;
            $total->setData('base_value', $base);
        }

        return $totals;
    }

    /**
     * @return string
     */
    public function getAfterTotalsHtml()
    {
        Mage::dispatchEvent('mwoe_render_temp_totals_html_after', array(
            'block' => $this,
            'after_totals_html' => $this->_afterTotalsHtml
        ));

        return $this->_afterTotalsHtml;
    }

    /**
     * Html displayed after all totals (in totals table)
     *
     * @param string $html
     * @return $this
     */
    public function setAfterTotalsHtml($html)
    {
        $this->_afterTotalsHtml = $html;
        return $this;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function addAfterTotalsHtml($html)
    {
        $this->_afterTotalsHtml .= $html;
        return $this;
    }

    /**
     * Return buttons html
     *
     * @return string
     */
    public function getButtonsHtml()
    {
        $cancelButton = $this->getButtonHtml(
            $this->__('Cancel'),
            'orderEdit.cancelChangedOrder(\'' . Mage::helper('mageworx_ordersedit/edit')->getCancelChangesUrl() . '\');',
            'mw-totals-button mw_floater mw_br'
        );
        $this->addButton('cancel', $cancelButton);

        $applyButton = $this->getButtonHtml(
            $this->__('Apply'),
            'orderEdit.applyChangedOrder();',
            'mw-totals-button mw_floater-right mw_br'
        );
        $this->addButton('apply', $applyButton);

        Mage::dispatchEvent('mwoe_render_temp_totals_buttons_html_before', array(
            'block' => $this
        ));

        $buttonsHtml = implode('', $this->getButtons());

        return $buttonsHtml;
    }

    /**
     * Add button to temp totals block
     *
     * @param string $name
     * @param string $html
     * @return MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Totals
     */
    public function addButton($name, $html)
    {
        $this->_buttons[$name] = $html;

        return $this;
    }

    /**
     * @param $name
     * @return MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Totals
     */
    public function removeButton($name)
    {
        if (!isset($this->_buttons[$name])) {
            return $this;
        }
        unset($this->_buttons[$name]);

        return $this;
    }

    /**
     * Get array of temp totals block buttons
     *
     * @return array
     */
    public function getButtons()
    {
        return $this->_buttons;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getSource()
    {
        return $this->getQuote();
    }
}