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



class Mirasvit_Rma_Model_Resource_Rma extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('rma/rma', 'rma_id');
    }

    protected function loadStoreIds(Mage_Core_Model_Abstract $object)
    {
        /* @var  Mirasvit_Rma_Model_Rma $object */
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('rma/rma_store'))
            ->where('rs_rma_id = ?', $object->getId());
        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $array = array();
            foreach ($data as $row) {
                $array[] = $row['rs_store_id'];
            }
            $object->setData('store_ids', $array);
        }

        return $object;
    }

    protected function saveStoreIds($object)
    {
        /* @var  Mirasvit_Rma_Model_Rma $object */
        $condition = $this->_getWriteAdapter()->quoteInto('rs_rma_id = ?', $object->getId());
        $this->_getWriteAdapter()->delete($this->getTable('rma/rma_store'), $condition);
        foreach ((array) $object->getData('store_ids') as $id) {
            $objArray = array(
                'rs_rma_id' => $object->getId(),
                'rs_store_id' => $id,
            );
            $this->_getWriteAdapter()->insert(
                $this->getTable('rma/rma_store'), $objArray);
        }
    }

    protected function loadExchangeOrderIds(Mage_Core_Model_Abstract $object)
    {
        /* @var  Mirasvit_Rma_Model_Rma $object */
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('rma/rma_order'))
            ->where('re_rma_id = ?', $object->getId());
        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $array = array();
            foreach ($data as $row) {
                $array[] = $row['re_exchange_order_id'];
            }
            $object->setData('exchange_order_ids', $array);
        }
        //for backward compatibility
        if (count($object->getExchangeOrderIds()) == 0 && $object->getExchangeOrderId()) {
            $object->setData('exchange_order_ids', array($object->getExchangeOrderId()));
        }

        return $object;
    }

    protected function saveExchangeOrderIds($object)
    {
        /* @var  Mirasvit_Rma_Model_Rma $object */
        $condition = $this->_getWriteAdapter()->quoteInto('re_rma_id = ?', $object->getId());
        $this->_getWriteAdapter()->delete($this->getTable('rma/rma_order'), $condition);
        foreach ((array) $object->getData('exchange_order_ids') as $id) {
            $objArray = array(
                're_rma_id' => $object->getId(),
                're_exchange_order_id' => $id,
            );
            $this->_getWriteAdapter()->insert(
                $this->getTable('rma/rma_order'), $objArray);
        }
    }

    protected function loadCreditMemoIds(Mage_Core_Model_Abstract $object)
    {
        /* @var  Mirasvit_Rma_Model_Rma $object */
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('rma/rma_creditmemo'))
            ->where('rc_rma_id = ?', $object->getId());
        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $array = array();
            foreach ($data as $row) {
                $array[] = $row['rc_credit_memo_id'];
            }
            $object->setData('credit_memo_ids', $array);
        }
        //for backward compatibility
        if (count($object->getCreditMemoIds()) == 0 && $object->getCreditMemoId()) {
            $object->setData('credit_memo_ids', array($object->getCreditMemoId()));
        }

        return $object;
    }

    protected function saveCreditMemoIds($object)
    {
        /* @var  Mirasvit_Rma_Model_Rma $object */
        $condition = $this->_getWriteAdapter()->quoteInto('rc_rma_id = ?', $object->getId());
        $this->_getWriteAdapter()->delete($this->getTable('rma/rma_creditmemo'), $condition);
        foreach ((array) $object->getData('credit_memo_ids') as $id) {
            $objArray = array(
                'rc_rma_id' => $object->getId(),
                'rc_credit_memo_id' => $id,
            );
            $this->_getWriteAdapter()->insert(
                $this->getTable('rma/rma_creditmemo'), $objArray);
        }
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        /** @var  Mirasvit_Rma_Model_Rma $object */
        if (!$object->getIsMassDelete()) {
            $this->loadStoreIds($object);
            $this->loadExchangeOrderIds($object);
            $this->loadCreditMemoIds($object);
        }

        return parent::_afterLoad($object);
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        /** @var  Mirasvit_Rma_Model_Rma $object */
        if (!$object->getId()) {
            $object->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
            $object->setCode($this->normalize($object->getCode()));
        }
        $object->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());

        return parent::_beforeSave($object);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        /** @var  Mirasvit_Rma_Model_Rma $object */
        if (!$object->getIsMassStatus()) {
            $this->saveStoreIds($object);
            $this->saveExchangeOrderIds($object);
            $this->saveCreditMemoIds($object);
        }

        return parent::_afterSave($object);
    }

    /************************/

    public function normalize($string)
    {
        $string = Mage::getSingleton('catalog/product_url')->formatUrlKey($string);
        $string = str_replace('-', '_', $string);

        return 'f_'.$string;
    }
}
