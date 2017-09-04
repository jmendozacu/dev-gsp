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
 * @method Varien_Object getFilterData()
 */
class Mirasvit_Rma_Block_Adminhtml_Report_Grid_Abstract
    extends Mage_Adminhtml_Block_Report_Grid_Abstract
{
    protected function _prepareCollection()
    {
        $filterData = $this->getFilterData();
        if ($filterData->getData('from') == null || $filterData->getData('to') == null) {
            $this->setCountTotals(false);
            $this->setCountSubTotals(false);

            return parent::_prepareCollection();
        }

        $resourceCollection = Mage::getResourceModel($this->getResourceCollectionName())
            ->setPeriod($filterData->getData('period_type'))
            ->setDateRange($filterData->getData('from', null), $filterData->getData('to', null))
            ->addStoreFilter(explode(',', $filterData->getData('store_ids')))
            ->addOrderStatusFilter($filterData->getData('order_statuses'))
            ->setAggregatedColumns($this->_getAggregatedColumns());
        /* @noinspection PhpUndefinedMethodInspection */
        $resourceCollection->setFilterData($filterData->getData());

        if ($this->_isExport) {
            $this->setCollection($resourceCollection);

            return $this;
        }

        if ($filterData->getData('show_empty_rows', false)) {
            Mage::helper('reports')->prepareIntervalsCollection(
                $this->getCollection(),
                $filterData->getData('from', null),
                $filterData->getData('to', null),
                $filterData->getData('period_type')
            );
        }

        if ($this->getCountSubTotals()) {
            $this->getSubTotals();
        }

        if ($this->getCountTotals()) {
            $totalsCollection = Mage::getResourceModel($this->getResourceCollectionName())
                ->setPeriod($filterData->getData('period_type'))
                ->setDateRange($filterData->getData('from', null), $filterData->getData('to', null))
                ->addStoreFilter(explode(',', $filterData->getData('store_ids')))
                ->addOrderStatusFilter($filterData->getData('order_statuses'))
                ->setAggregatedColumns($this->_getAggregatedColumns())
                ->isTotals(true);
            foreach ($totalsCollection as $item) {
                $this->setTotals($item);
                break;
            }
        }
        /* @noinspection PhpUndefinedMethodInspection */
        $this->getCollection()->setColumnGroupBy($this->_columnGroupBy);
        /* @noinspection PhpUndefinedMethodInspection */
        $this->getCollection()->setResourceCollection($resourceCollection);
    }

    /************************/
}
