<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_Items extends Mage_Adminhtml_Block_Sales_Order_Create_Items
{
    /**
     * Preapre layout to show "edit order items" form
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $grid = $this->getLayout()->createBlock('mageworx_ordersedit/adminhtml_sales_order_edit_form_items_itemsgrid')->setTemplate('mageworx/ordersedit/edit/items/grid.phtml');
        $this->append($grid);

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

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $html = $this->getChildHtml();
        $html .= '<div id="ordersedit_product_grid" style="display: none;"></div>';

        //Configure existing order items
        $html .= <<<SCRIPT
        <script type="text/javascript">
            orderEditItems = new OrdersEditEditItems(
                    '{$this->getCurrencySymbol()}',
                    {$this->jsonEncode($this->getQuoteItemIds())}
                );
        </script>
SCRIPT;
        return $html;
    }

    /**
     * Get quote items ids as array for orderEditItems init. (JS)
     *
     * @return array
     */
    public function getQuoteItemIds()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrder() ? $this->getOrder() : Mage::registry('ordersedit_order');
        $itemsIds = array();

        if (!$order) {
            return $itemsIds;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getQuote();
        /** @var Mage_Sales_Model_Resource_Quote_Item_Collection $quoteItems */
        $quoteItems = $quote->getItemsCollection();
        $orderItems = $order->getAllItems();

        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($orderItems as $orderItem) {
            $quoteItemId = $orderItem->getQuoteItemId();
            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            $quoteItem = $quoteItems->getItemById($quoteItemId);
            /** @var int|float $orderItemQtyRest */
            $orderItemQtyRest = Mage::helper('mageworx_ordersedit/edit')->getOrderItemQtyRest($orderItem, true);

            if ($quoteItem && $quoteItem->getId() && $orderItemQtyRest) {
                $id = $quoteItem->getId();
                $itemsIds[$id] = $quoteItem->getBuyRequest()->toArray();
            }
        }

        return $itemsIds;
    }

    /**
     * Get all quote items by order
     *
     * @return array
     */
    public function getItems()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrder() ? $this->getOrder() : Mage::registry('ordersedit_order');
        $items = array();

        if (!$order) {
            return $items;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getQuote();
        /** @var Mage_Sales_Model_Resource_Quote_Item_Collection $quoteItems */
        $quoteItems = $quote->getItemsCollection();
        /** @var array $orderItems */
        $orderItems = $order->getAllItems();

        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($orderItems as $orderItem) {
            $quoteItemId = $orderItem->getQuoteItemId();
            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            $quoteItem = $quoteItems->getItemById($quoteItemId);
            /** @var int|float $orderItemQtyRest */
            $orderItemQtyRest = Mage::helper('mageworx_ordersedit/edit')->getOrderItemQtyRest($orderItem, true);

            if (!$quoteItem) {
                continue;
            }

            if (!$quoteItem->getId() || !$orderItemQtyRest) {
                $quoteItems->removeItemByKey($quoteItem->getId());
            }
        }

        $items = $quoteItems->getItems();

        return $items;
    }

    /**
     * Get currency symbol for order
     *
     * @return string
     * @throws Exception
     */
    protected function getCurrencySymbol()
    {
        $order = $this->getOrder() ? $this->getOrder() : Mage::registry('ordersedit_order');
        $currency = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode());
        $symbol = $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();

        return $symbol;
    }

    /**
     * @param $data
     * @return string
     */
    protected function jsonEncode($data)
    {
        return Zend_Json::encode($data);
    }
}