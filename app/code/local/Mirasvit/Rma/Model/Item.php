<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   RMA
 * @version   2.0.1
 * @build     982
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */



/**
 * @method Mirasvit_Rma_Model_Resource_Item_Collection|Mirasvit_Rma_Model_Item[] getCollection()
 * @method Mirasvit_Rma_Model_Item load(int $id)
 * @method bool getIsMassDelete()
 * @method Mirasvit_Rma_Model_Item setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method Mirasvit_Rma_Model_Item setIsMassStatus(bool $flag)
 * @method Mirasvit_Rma_Model_Resource_Item getResource()
 * @method int getProductId()
 * @method Mirasvit_Rma_Model_Item setProductId(int $entityId)
 * @method int getReasonId()
 * @method Mirasvit_Rma_Model_Item setReasonId(int $reasonId)
 * @method int getResolutionId()
 * @method Mirasvit_Rma_Model_Item setResolutionId(int $resolutionId)
 * @method int getConditionId()
 * @method Mirasvit_Rma_Model_Item setConditionId(int $conditionId)
 * @method int getRmaId()
 * @method Mirasvit_Rma_Model_Item setRmaId(int $rmaId)
 * @method Mirasvit_Rma_Model_Item setExchangeProductId(int $id)
 * @method int getExchangeProductId()
 * @method Mirasvit_Rma_Model_Item setQtyRequested(int $qty)
 * @method int getQtyRequested()
 * @method Mirasvit_Rma_Model_Item setToStock(bool $flag)
 * @method bool getToStock()
 */
class Mirasvit_Rma_Model_Item extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('rma/item');
    }

    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    protected $_product = null;

    /**
     * @return bool|Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->getProductId()) {
            return false;
        }
        if ($this->_product === null) {
            $this->_product = Mage::getModel('catalog/product')->load($this->getProductId());
        }

        return $this->_product;
    }

    protected $_reason = null;

    /**
     * @return bool|Mirasvit_Rma_Model_Reason
     */
    public function getReason()
    {
        if (!$this->getReasonId()) {
            return false;
        }
        if ($this->_reason === null) {
            $this->_reason = Mage::getModel('rma/reason')->load($this->getReasonId());
        }

        return $this->_reason;
    }

    protected $_resolution = null;

    /**
     * @return bool|Mirasvit_Rma_Model_Resolution
     */
    public function getResolution()
    {
        if (!$this->getResolutionId()) {
            return false;
        }
        if ($this->_resolution === null) {
            $this->_resolution = Mage::getModel('rma/resolution')->load($this->getResolutionId());
        }

        return $this->_resolution;
    }

    protected $_condition = null;

    /**
     * @return bool|Mirasvit_Rma_Model_Condition
     */
    public function getCondition()
    {
        if (!$this->getConditionId()) {
            return false;
        }
        if ($this->_condition === null) {
            $this->_condition = Mage::getModel('rma/condition')->load($this->getConditionId());
        }

        return $this->_condition;
    }

    protected $_rma = null;

    /**
     * @return bool|Mirasvit_Rma_Model_Rma
     */
    public function getRma()
    {
        if (!$this->getRmaId()) {
            return false;
        }
        if ($this->_rma === null) {
            $this->_rma = Mage::getModel('rma/rma')->load($this->getRmaId());
        }

        return $this->_rma;
    }

    /************************/
    protected $_stockQty;
    public function getQtyStock()
    {
        if (!$this->_stockQty) {
            $product = $this->getProduct();
            $this->_stockQty = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
        }

        return $this->_stockQty;
    }

    protected $_orderItem;

    /**
     * @return Mage_Sales_Model_Order_Item
     */
    public function getOrderItem()
    {
        if (!$this->_orderItem) {
            $this->_orderItem = Mage::getModel('sales/order_item')->load($this->getOrderItemId());
        }

        return $this->_orderItem;
    }

    public function getQtyOrdered()
    {
        return (int) $this->getOrderItem()->getQtyOrdered();
    }

    /**
     * Returns quantity, available for return.
     *
     * @return int
     */
    public function getQtyAvailable()
    {
        $others = Mage::helper('rma')->getRmaByOrder($this->getOrderItem()->getOrder());
        $qtyReturned = 0;
        foreach ($others as $rma) {
            $items = $rma->getItemCollection();
            foreach ($items as $item) {
                if ($item->getProductId() == $this->getProductId()) {
                    $qtyReturned = $qtyReturned + $item->getQtyRequested();
                }
            }
        }

        return $this->getQtyOrdered() - $qtyReturned;
    }

    public function getProductSku()
    {
        return $this->getOrderItem()->getSku();
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     *
     * @return $this
     */
    public function initFromOrderItem($orderItem)
    {
        $this->_orderItem = $orderItem;
        $this->setOrderItemId($orderItem->getId());
        $this->setProductId($orderItem->getProductId());
        $this->setName($orderItem->getName());
        $this->setProductOptions($orderItem->getProductOptions());
        $this->setProductType($orderItem->getProductType());
        $qtyShipped = $orderItem->getQtyShipped();
        if (!$product = $orderItem->getProduct()) { //magento 1.6 does not have this method
            if ($productId = $orderItem->getProductId()) {
                $product = Mage::getModel('catalog/product')->load($productId);
            }
        }
        $status = $product->getRmaStatus();

        $this->setIsRmaAllowed((string) $status !== '0');

        // we have option to allow rma when status is processing (for example). so products are not shipped yet.
        if ($qtyShipped == 0) {
            $qtyShipped = $orderItem->getQtyOrdered();
        }
        $qty = $qtyShipped - $this->getQtyInRma($orderItem);
        if ($qty < 0) {
            $qty = 0;
        }
        $this->setQtyAvailable($qty);

        //we need this to avoid error of mysql foreign key
        if (!$this->getReasonId()) {
            $this->setReasonId(null);
        }
        if (!$this->getResolutionId()) {
            $this->setResolutionId(null);
        }
        if (!$this->getConditionId()) {
            $this->setConditionId(null);
        }

        return $this;
    }

    protected function getQtyInRma($orderItem)
    {
        $collection = Mage::getModel('rma/item')->getCollection();
        $collection->addFieldToFilter('order_item_id', $orderItem->getId());
        // echo $collection->getSelect();die;
        $sum = 0;
        foreach ($collection as $item) {
            $sum += $item->getQtyRequested();
        }

        return $sum;
    }

    public function getProductOptions()
    {
        $options = $this->getData('product_options');
        if (is_string($options)) {
            $options = @unserialize($options);
            $this->setData('product_options', $options);
        }

        return $options;
    }

    public function getReasonName()
    {
        return Mage::helper('rma/locale')->getLocaleValue($this, 'reason_name');
    }

    public function getConditionName()
    {
        return Mage::helper('rma/locale')->getLocaleValue($this, 'condition_name');
    }

    public function getResolutionName()
    {
        return Mage::helper('rma/locale')->getLocaleValue($this, 'resolution_name');
    }

    protected $_exchangeProduct;

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getExchangeProduct()
    {
        if (!$this->_exchangeProduct) {
            $this->_exchangeProduct = Mage::getModel('catalog/product')->load($this->getExchangeProductId());
        }

        return $this->_exchangeProduct;
    }

    /**
     * @return bool
     */
    public function isRefund()
    {
        return $this->getResolutionId() == Mage::helper('rma')->getResolutionByCode('refund')->getId();
    }

    /**
     * @return bool
     */
    public function isExchange()
    {
        return $this->getResolutionId() == Mage::helper('rma')->getResolutionByCode('exchange')->getId();
    }

    /**
     * @return bool
     */
    public function isCredit()
    {
        if (!Mage::helper('rma')->getResolutionByCode('credit')) {
            return false;
        }
        return $this->getResolutionId() == Mage::helper('rma')->getResolutionByCode('credit')->getId();
    }
}
