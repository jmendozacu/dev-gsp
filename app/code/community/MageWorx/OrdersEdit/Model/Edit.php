<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersEdit_Model_Edit extends Mage_Core_Model_Abstract
{
    /**
     * Order items which have already been saved
     *
     * @var array
     */
    protected $_savedOrderItems = array();

    /**
     * The flag shows whether missed quote items have been checked
     *
     * @var bool
     */
    protected $_quoteItemsAlreadyChecked = false;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $changes = array();

    /**
     * Apply all the changes to order and save it
     *
     * @return $this
     * @throws Exception
     */
    public function saveOrder()
    {
        // Validate important parameters. Throws exception on error.
        $this->validateBeforeSave();

        // Start saving process. Save addresses, payment, items etc. (if has changes)
        $this->updateAddresses();
        $this->updatePayment();
        $this->addUpdateItems();
        $this->prepareBeforeSaving();

        // Finally save the order and the quote with updated data
        $this->getQuote()->save();
        $this->getOrder()->save();

        return $this;
    }

    /**
     * Validate important parameters before process saving the order
     *
     * @throws Exception
     */
    protected function validateBeforeSave()
    {
        $error = null;
        $helper = Mage::helper('mageworx_ordersedit');

        $order = $this->getOrder();
        if (!$order instanceof Mage_Sales_Model_Order) {
            $class = get_class($order);
            $error = $helper->__('Got %s , Mage_Sales_Model_Order expected.', $class);
        }

        $quote = $this->getQuote();
        if (!$quote instanceof Mage_Sales_Model_Quote) {
            $class = get_class($quote);
            $error = $helper->__('Got %s , Mage_Sales_Model_Quote expected.', $class);
        }

        if ($error) {
            throw new Exception($error);
        }
    }

    /**
     * Updates and save order billing & shipping addresses
     * only when the address has been changed
     *
     * @return $this
     */
    protected function updateAddresses()
    {
        if (isset($this->changes['billing_address'])) {
            $this->saveAddress($this->getQuote()->getBillingAddress(), $this->getOrder()->getBillingAddress());
            unset($this->changes['billing_address']);
        }

        if (isset($this->changes['shipping_address'])) {
            $this->saveAddress($this->getQuote()->getShippingAddress(), $this->getOrder()->getShippingAddress());
            unset($this->changes['shipping_address']);
        }

        $address = $this->getQuote()->getIsVirtual() ?
            $this->getQuote()->getBillingAddress() :
            $this->getQuote()->getShippingAddress();
        Mage::helper('core')->copyFieldset('sales_convert_quote_address', 'to_order', $address, $this->getOrder());
        $address->save();

        return $this;
    }

    /**
     * Update and save result items with childes
     *
     * @return $this
     */
    protected function addUpdateItems()
    {
        $this->_savedOrderItems = array();

        $this->saveNewOrderItems();
        $this->saveOldOrderItems();
        $this->postProcessItems();

        return $this;
    }

    /**
     * Add data from the changes to the order before saving
     *
     * @return $this
     */
    protected function prepareBeforeSaving()
    {
        $this->collectItemsQty();

        $this->changes['customer_id'] = empty($this->changes['customer_id']) ?
            $this->getOrder()->getCustomerId() :
            $this->changes['customer_id'];

        if (isset($this->changes['status'])) {
            $this->changes['state'] = $this->changes['status'];
        }

        $this->getOrder()->addData($this->changes);
        $this->getOrder()->setData('is_edited', 1);

        $this->getLogModel()->commitOrderChanges($this->getOrder());

        return $this;
    }

    /**
     * Collect total qty ordered of the updated order
     *
     * @return $this
     */
    protected function collectItemsQty()
    {
        $totalQtyOrdered = 0;
        foreach ($this->getOrder()->getAllItems() as $orderItem) {
            $totalQtyOrdered += $orderItem['qty_ordered'] - $orderItem['qty_canceled'];
        }
        $this->changes['total_qty_ordered'] = $totalQtyOrdered;

        return $this;
    }

    protected function postProcessItems()
    {
        $quote = $this->getQuote();
        $order = $this->getOrder();

        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {

            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = $order->getItemByQuoteItemId($quoteItem->getItemId());

            if (isset($orderItem) && (in_array($orderItem->getItemId(), $this->_savedOrderItems))) {
                continue;
            }

            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = $this->getConverter()->itemToOrderItem($quoteItem, $orderItem);
            $orderItem->setOrderId($order->getId());
            $orderItem->save();

            $quoteChildrens = $quoteItem->getChildren();
            $orderChildrens = array();
            foreach ($quoteChildrens as $childQuoteItem) {

                /** @var Mage_Sales_Model_Order_Item $childOrderItem */
                $childOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getItemId());

                if (isset($childOrderItem) && in_array($childOrderItem->getItemId(), $this->_savedOrderItems)) {
                    continue;
                }

                /** @var Mage_Sales_Model_Order_Item $childOrderItem */
                $childOrderItem = $this->getConverter()->itemToOrderItem($childQuoteItem, $childOrderItem);
                $childOrderItem->setOrderId($order->getId());
                $childOrderItem->setParentItem($orderItem);
                $childOrderItem->setParentItemId($orderItem->getId());
                $childOrderItem->save();
                $orderChildrens[] = $childOrderItem;
            }

            if (!empty($orderChildrens)) {
                foreach ($orderChildrens as $child) {
                    $orderItem->addChildItem($child);
                }
                $orderItem->save();
            }

            /** @var Mage_Sales_Model_Resource_Order_Item_Collection $orderItemsCollection */
            $orderItemsCollection = $order->getItemsCollection();
            if ($orderItemsCollection->getItemById($orderItem->getId())) {
                $orderItemsCollection->removeItemByKey($orderItem->getId());
            }
            $orderItemsCollection->addItem($orderItem);

            /*** Add new items to log ***/
            $changedItem = $quoteItem;
            $itemChange = array(
                'name'       => $changedItem->getName(),
                'qty_before' => 0,
                'qty_after'  => $changedItem->getQty()
            );
            $this->getLogModel()->addItemChange($changedItem->getId(), $itemChange);
        }
    }

    /**
     * Check whether the order's quote exists. If no then create it from the order
     *
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Exception
     */
    protected function checkOrderQuote(Mage_Sales_Model_Order $order)
    {
        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->setStoreId($order->getStoreId())->load($quoteId);
        if ($quote && $quote->getId()) {
            return $this;
        }

        $converter = Mage::getSingleton('sales/convert_order');

        // Copy quote data
        $quote = $converter->toQuote($order);

        // Copy shipping address data
        if ($order->getShippingAddress()) {
            $shippingAddress = $quote->getShippingAddress();
            Mage::helper('core')->copyFieldset('sales_convert_order_address', 'to_quote_address', $order->getShippingAddress(), $shippingAddress);
            Mage::helper('core')->copyFieldset('sales_convert_order', 'to_quote_address_shipping', $order, $shippingAddress);
        }

        // Copy billing address
        if ($order->getBillingAddress()) {
            $billingAddress = $quote->getBillingAddress();
            Mage::helper('core')->copyFieldset('sales_convert_order_address', 'to_quote_address', $order->getBillingAddress(), $billingAddress);
        }

        // Copy payment
        if ($order->getPayment()) {
            $payment = $quote->getPayment();
            $converter->paymentToQuotePayment($order->getPayment(), $payment);
        }

        // Recreate shipping rates
        if ($quote->getShippingAddress() && !$quote->getShippingAddress()->getGroupedAllShippingRates()) {
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
        }

        $quote->save();

        $order->setQuoteId($quote->getId())->save();

        return $this;
    }

    /**
     * Check and restore quote items which have been deleted from database
     *
     * @param                        $quoteId
     * @param Mage_Sales_Model_Order $order
     * @internal param \Mage_Sales_Model_Quote $quote
     * @return $this
     */
    protected function checkQuoteItems($quoteId, Mage_Sales_Model_Order $order)
    {
        if ($this->_quoteItemsAlreadyChecked) {
            return $this;
        }

        /** @var MageWorx_OrdersEdit_Helper_Edit $editHelper */
        $editHelper = Mage::helper('mageworx_ordersedit/edit');

        /** @var Mage_Sales_Model_Order_item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            $quoteItemId = $orderItem->getQuoteItemId();
            $quoteItem = Mage::getModel('sales/quote_item')->load($quoteItemId);

            if ($quoteItem && $editHelper->isQuoteItemAvailable($quoteItem, $orderItem)) {
                /** TODO: temporary disabled. Bug with not existing quote. Tracker: 10727 
                if ($quoteItem->getQuoteId() != $quoteId && $quoteItem->getId()) {
                    // @var Mage_Sales_Model_Quote $quote
                    $quote = Mage::getModel('sales/quote')->setStoreId($order->getStoreId())->load($quoteId);
                    if($quote && $quote->getId()) {
                        $quoteItem->setQuote($quote);
                        $quoteItem->save();
                    }
                } */
                continue;
            }

            $product = Mage::getModel('catalog/product')
                ->setStoreId($order->getStoreId())
                ->load($orderItem->getProductId());

            $newQuoteItem = Mage::getModel('sales/convert_order')->itemToQuoteItem($orderItem);
            $newQuoteItem->unsetData('custom_price');
            $newQuoteItem->unsetData('original_custom_price');
            $qty = $this->getQtyRest($orderItem, true);
            $newQuoteItem->setQuoteId($quoteId)
                ->setProduct($product)
                ->setQty($qty)
                ->save();

            $orderItem->setQuoteItemId($newQuoteItem->getItemId())->save();
        }

        $this->_quoteItemsAlreadyChecked = true;

        return $this;
    }

    /**
     * Save billing/shipping address
     *
     * @param Mage_Sales_Model_Quote_Address $quoteAddress
     * @param Mage_Sales_Model_Order_Address $orderAddress
     * @return $this
     * @throws Exception
     */
    protected function saveAddress(
        Mage_Sales_Model_Quote_Address $quoteAddress,
        Mage_Sales_Model_Order_Address $orderAddress
    ) {
        $quote = $quoteAddress->getQuote();
        $order = $orderAddress->getOrder();

        $this->getConverter()->addressToOrderAddress($quoteAddress, $orderAddress);

        if (($quote->getIsVirtual() && $orderAddress->getAddressType() == 'billing')
            || (!$quote->getIsVirtual() && $orderAddress->getAddressType() == 'shipping')
        ) {
            Mage::helper('core')->copyFieldset('sales_convert_quote_address', 'to_order', $quoteAddress, $order);
        }

        $orderAddress->save();
        $quoteAddress->save();

        return $this;
    }

    /**
     * Save payment method
     *
     * @return $this
     * @throws Exception
     */
    protected function updatePayment() {

        if (!isset($this->changes['payment'])) {
            return $this;
        }

        $quotePayment = $this->quote->getPayment();
        $orderPayment = $this->order->getPayment();

        $orderPayment = $this->getConverter()->paymentToOrderPayment($quotePayment, $orderPayment);
        $orderPayment->save();
        $quotePayment->save();

        unset($this->changes['payment']);

        return $this;
    }

    /**
     * Save changed order products
     *
     * @return $this
     */
    protected function saveOldOrderItems()
    {
        $quote = $this->getQuote();
        $order = $this->getOrder();
        $log = $this->getLogModel();
        $helper = Mage::helper('mageworx_ordersedit/edit');
        $quoteItemsChanges = $this->getChanges('quote_items');

        if (empty($quoteItemsChanges)) {
            return $this;
        }

        foreach ($quoteItemsChanges as $itemId => $params) {

            /**
             * @var Mage_Sales_Model_Quote_Item $quoteItem
             * @var Mage_Sales_Model_Order_Item $orderItem
             * @var MageWorx_OrdersEdit_Model_Edit_Quote_Convert $converter
             * @var MageWorx_OrdersEdit_Model_Edit_Log $log
             * @var MageWorx_OrdersEdit_Helper_Edit $helper
             * @var Mage_Sales_Model_Quote_Item $childQuoteItem
             * @var Mage_Sales_Model_Order_Item $childOrderItem
             */
            $quoteItem  = $quote->getItemById($itemId);
            $orderItem  = $order->getItemByQuoteItemId($itemId);
            $converter  = $this->getConverter();

            if (!$orderItem || !$helper->checkOrderItemForCancelRefund($orderItem)) {
                continue;
            }

            $orderItemQty = $this->getQtyRest($orderItem);

            if (isset($params['qty']) && $params['qty'] < 0.001) {
                $params['action'] = 'remove';
            }

            if ((isset($params['action']) && $params['action'] == 'remove')) {
                $this->removeOrderItem($order, $orderItem);

                $price = $this->getItemPricesConsideringTax($orderItem);
                $itemChange = array(
                    'name'         => $orderItem->getName(),
                    'qty_before'   => $orderItemQty,
                    'qty_after'    => '',
                    'price_before' => $price['before'],
                    'price_after'  => $price['after']
                );
                $log->addItemChange($orderItem->getId(), $itemChange);

                continue;
            }

            if (isset($params['qty']) && $params['qty'] != $orderItemQty) {

                $origQtyOrdered = $orderItem->getQtyOrdered();
                $orderItem = $converter->itemToOrderItem($quoteItem, $orderItem);

                /* Change main item qty */
                $this->changeItemQty($orderItem, $params['qty'], $origQtyOrdered);

                /* Change qty of child products if exists */
                if ($orderItem->getProductType() == 'bundle' || $orderItem->getProductType() == 'configurable') {
                    foreach ($quote->getAllItems() as $childQuoteItem) {
                        if ($childQuoteItem->getParentItemId() == $quoteItem->getId()) {

                            /* Recalculate totals of new order item */
                            $childQuoteItem->save();
                            $childOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getId());
                            $origChildQtyOrdered = $childOrderItem->getQtyOrdered();
                            $childOrderItem = $converter->itemToOrderItem($childQuoteItem, $childOrderItem);

                            /* Change child item qty and save */
                            $this->changeItemQty($childOrderItem, $params['qty'] * $childQuoteItem->getQty(), $origChildQtyOrdered);
                            $childOrderItem->save();
                            $this->_savedOrderItems[] = $childOrderItem->getItemId();

                        }
                    }
                }
            } elseif ($quoteItem->getPrice() != $orderItem->getPrice()) {
                $orderItem = $converter->itemToOrderItem($quoteItem, $orderItem);
            }

            if (isset($params['instruction'])) {
                $orderItem->setData('instruction', trim($params['instruction']));
            }

            $price = $this->getItemPricesConsideringTax($orderItem);
            $itemChange = array(
                'name'         => $orderItem->getName(),
                'qty_before'   => $orderItemQty,
                'qty_after'    => $quoteItem->getQty(),
                'price_before' => $price['before'],
                'price_after'  => $price['after']
            );

            /* Check Discount changes */
            if (isset($params['use_discount'])
                && $params['use_discount'] == 1
                && $quoteItem->getOrigData('discount_amount') == 0
                && $quoteItem->getData('discount_amount') > 0
            ) {
                $itemChange['discount'] = 1;
            } elseif ($quoteItem->getData('discount_amount') < 0.001 && $quoteItem->getOrigData('discount_amount') > 0) {
                $itemChange['discount'] = -1;
            }

            /* Add item changes to log */
            if ($itemChange['qty_before'] != $itemChange['qty_after']
                || $itemChange['price_before'] != $itemChange['price_after']
                || isset($itemChange['discount'])
            ) {
                $log->addItemChange($orderItem->getId(), $itemChange);
            }

            $quoteItem->save();
            $orderItem->save();

            $this->_savedOrderItems[] = $orderItem->getItemId();
        }

        return $this;
    }

    /**
     * Add new products to order
     *
     * @return $this
     */
    protected function saveNewOrderItems()
    {
        if (empty($this->changes['product_to_add'])) {
            return $this;
        }

        $quote = $this->getQuote();
        $order = $this->getOrder();

        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = $order->getItemByQuoteItemId($quoteItem->getItemId());
            if ($orderItem && $orderItem->getItemId()) {
                continue;
            }

            $quoteItem->save();

            $orderItem = $this->getConverter()->itemToOrderItem($quoteItem, $orderItem);
            $order->addItem($orderItem);
            if ($orderItem->save()) {
                $this->removeQtyFromStock($orderItem->getProductId(), $orderItem->getQtyOrdered());
            }

            /*** Add new items to log ***/
            $changedItem = $quoteItem;
            $itemChange = array(
                'name'       => $changedItem->getName(),
                'qty_before' => 0,
                'qty_after'  => $changedItem->getQty()
            );
            $this->getLogModel()->addItemChange($changedItem->getId(), $itemChange);

            $this->_savedOrderItems[] = $orderItem->getItemId();
        }

        /** @var Mage_Sales_Model_Quote_Item $childQuoteItem */
        foreach ($quote->getAllItems() as $childQuoteItem) {
            /** @var Mage_Sales_Model_Order_Item $childOrderItem */
            $childOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getItemId());

            /*** Add items relations for configurable and bundle products ***/
            if ($childQuoteItem->getParentItemId()) {
                /** @var Mage_Sales_Model_Order_Item $parentOrderItem */
                $parentOrderItem = $order->getItemByQuoteItemId($childQuoteItem->getParentItemId());

                $childOrderItem->setParentItemId($parentOrderItem->getItemId());
                $childOrderItem->save();
            }
        }

        return $this;
    }

    /**
     * Remove specific qty of order item from order
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param null $qtyToReturn
     * @return $this
     * @internal param Mage_Sales_Model_Order $order
     */
    protected function returnOrderItem(Mage_Sales_Model_Order_Item $orderItem, $qtyToReturn = null)
    {
        $delete = false;

        if (is_null($qtyToReturn)) {
            $qtyToReturn = $orderItem->getQtyToRefund() + $orderItem->getQtyToCancel();
        }

        if ($qtyToReturn > 0) {

            if ($orderItem->getParentItem()) {
                $qtyToCancel = $qtyToReturn;
                $qtyToReturn -= $qtyToCancel;
            } else {
                $qtyToCancel = min($qtyToReturn, $orderItem->getQtyToCancel());
                $qtyToReturn -= $qtyToCancel;
            }

            $this->cancelOrderItem($orderItem, $qtyToCancel);
            if ($orderItem->getQtyOrdered() && $orderItem->getQtyOrdered() == $orderItem->getQtyCanceled()) {
                $delete = true;
            }
        }

        if ($qtyToReturn > 0 && $orderItem->getQtyToRefund() > 0) {
            $this->refundOrderItem($orderItem, $qtyToReturn);
        } elseif ($delete) {
            if ($orderItem->getChildrenItems()) {
                /** @var Mage_Sales_Model_Order_Item $childOrderItem */
                foreach ($orderItem->getChildrenItems() as $childOrderItem) {
                    Mage::getModel('sales/quote_item')->load($childOrderItem->getQuoteItemId())->delete();
                    $childOrderItem->delete();
                }
            }
            Mage::getModel('sales/quote_item')->load($orderItem->getQuoteItemId())->delete();
            $orderItem->delete();
        }

        return $this;
    }

    /**
     * Refund specific qty of order item
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param $qtyToRefund
     * @return $this
     * @internal param Mage_Sales_Model_Order $order
     */
    protected function refundOrderItem(Mage_Sales_Model_Order_Item $orderItem, $qtyToRefund)
    {
        $cmModel = Mage::getSingleton('mageworx_ordersedit/edit_creditmemo');
        $cmModel->addItemToRefund($orderItem->getId(), $qtyToRefund);

        if ($orderItem->getProductType() == 'bundle') {
            $orderItem->setQtyRefunded($qtyToRefund);
        }

        return $this;
    }

    /**
     * Cancel specific qty of order item
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param null                        $qtyToCancel
     * @return Mage_Sales_Model_Order_Item
     */
    protected function cancelOrderItem(Mage_Sales_Model_Order_Item $orderItem, $qtyToCancel = null)
    {
        if ($orderItem->getStatusId() !== Mage_Sales_Model_Order_Item::STATUS_CANCELED) {
            if (!$qtyToCancel) {
                $qtyToCancel = $orderItem->getQtyToCancel();
            }

            if (!$qtyToCancel) {
                return $orderItem;
            }

            $origQtyCancelled = $orderItem->getQtyCanceled();
            $orderItem->setQtyCanceled($origQtyCancelled + $qtyToCancel);
            Mage::dispatchEvent('sales_order_item_cancel', array('item' => $orderItem));

            $orderItem->setTaxCanceled(
                $orderItem->getTaxCanceled() +
                $orderItem->getBaseTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
            );
            $orderItem->setHiddenTaxCanceled(
                $orderItem->getHiddenTaxCanceled() +
                $orderItem->getHiddenTaxAmount() * $orderItem->getQtyCanceled() / $orderItem->getQtyOrdered()
            );
        }

        return $orderItem;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param int|float $newQty
     * @param int|float $origQtyOrdered
     * @return Mage_Sales_Model_Order_Item
     */
    protected function changeItemQty($orderItem, $newQty, $origQtyOrdered)
    {
        $orderItemQty = $this->getQtyRest($orderItem);

        if ($newQty < $orderItemQty) {
            $qtyToRemove = $orderItemQty - $newQty;
            $orderItem->setQtyOrdered($origQtyOrdered);
            $this->returnOrderItem($orderItem, $qtyToRemove);
        } else {
            $qtyDiff = $newQty - $orderItemQty;
            $this->removeQtyFromStock($orderItem->getProductId(), $qtyDiff);
            $orderItem->setQtyOrdered($origQtyOrdered + $qtyDiff);
        }

        return $this;
    }

    /**
     * Completely remove item (full qty)
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return $this
     */
    protected function removeOrderItem(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Item $orderItem)
    {
        $this->returnOrderItem($orderItem);
        if ($orderItem->getProductType() == 'bundle' || $orderItem->getProductType() == 'configurable') {
            /** @var Mage_Sales_Model_Order_Item $childOrderItem */
            foreach ($orderItem->getChildrenItems() as $childOrderItem) {
                $this->cancelOrderItem($childOrderItem, $this->getQtyRest($childOrderItem, false));
            }
        }

        $orderItem->setSubtotal(0)
            ->setBaseSubtotal(0)
            ->setRowTotalInclTax(0)
            ->setBaseRowTotalInclTax(0)
            ->setTaxAmount(0)
            ->setBaseTaxAmount(0)
            ->setTaxPercent(0)
            ->setDiscountAmount(0)
            ->setBaseDiscountAmount(0)
            ->setRowTotal(0)
            ->setBaseRowTotal(0);

        return $this;
    }

    /**
     * @param $productId
     * @param $qty
     */
    protected function removeQtyFromStock($productId, $qty)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        $qtyAfter = $stockItem->getQty() - $qty;
        if ($qtyAfter <= 0) {
            $stockItem->setIsInStock(0);
            $stockItem->setQty(0);
        } else {
            $stockItem->setIsInStock(1);
            $stockItem->setQty($qtyAfter);
        }
        $stockItem->save();
    }

    /**
     * Get order item original and modified prices incl/excl tax
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return array
     */
    protected function getItemPricesConsideringTax($orderItem)
    {
        if (Mage::getModel('tax/config')->displaySalesSubtotalInclTax() ||
            Mage::getModel('tax/config')->displaySalesSubtotalBoth()) {
            $price['before']  = $orderItem->getOrigData('price_incl_tax');
            $price['after']   = $orderItem->getData('price_incl_tax');
        } else {
            $price['before']  = $orderItem->getOrigData('price');
            $price['after']   = $orderItem->getData('price');
        }

        return $price;
    }

    /**
     * Get rest of available item qty.
     * qty = qty - canceled [ - refunded ] (optional)
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param bool|false $excludeRefunded
     * @return float|int
     */
    protected function getQtyRest(Mage_Sales_Model_Order_Item $item, $excludeRefunded = false)
    {
        return Mage::helper('mageworx_ordersedit/edit')->getOrderItemQtyRest($item, $excludeRefunded);
    }

    /**
     * Get quote model by order
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean|Mage_Sales_Model_Quote
     */
    public function getQuoteByOrder(Mage_Sales_Model_Order $order)
    {
        /** @var MageWorx_OrdersEdit_Helper_Edit $editHelper */
        $editHelper = Mage::helper('mageworx_ordersedit/edit');

        if (!$this->getQuote()) {
            $quoteId = $order->getQuoteId();
            $storeId = $order->getStoreId();
            $this->checkOrderQuote($order);
            $this->checkQuoteItems($quoteId, $order);

            /** @var Mage_Sales_Model_Quote $quote */
            $quote = Mage::getModel('sales/quote')->setStoreId($storeId)->load($quoteId);
            $editHelper->removeRefundedItemsFromQuote($quote, $order);
            $this->setQuote($quote);
        } else {
            $quote = $this->getQuote();
        }

        return $quote;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     * @return $this
     *
     */
    public function setQuote(Mage_Sales_Model_Quote $quote = null)
    {
        $this->quote = $quote;
        return $this;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Mage_Sales_model_Order|null $order
     * @return $this
     */
    public function setOrder(Mage_Sales_model_Order $order = null)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Get array of the all changes or part of the changes by the $name key (if exists)
     *
     * @param null $name
     * @return array|string|bool
     */
    public function getChanges($name = null)
    {
        $result = $this->changes;

        if ($name) {
            $result = isset($this->changes[$name]) ? $this->changes[$name] : false;
        }

        return $result;
    }

    /**
     * @param array|null $changes
     * @return $this
     */
    public function setChanges(array $changes = null)
    {
        $this->changes = is_array($changes) ? $changes : array();
        return $this;
    }

    /**
     * Get model for converting quote parts to order
     *
     * @return MageWorx_OrdersEdit_Model_Edit_Quote_Convert
     */
    public function getConverter()
    {
        return Mage::getSingleton('mageworx_ordersedit/edit_quote_convert');
    }

    /**
     * Get model for logging order changes
     *
     * @return MageWorx_OrdersEdit_Model_Edit_Log
     */
    public function getLogModel()
    {
        return Mage::getSingleton('mageworx_ordersedit/edit_log');
    }
}
