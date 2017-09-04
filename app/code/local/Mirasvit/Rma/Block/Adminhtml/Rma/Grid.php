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



class Mirasvit_Rma_Block_Adminhtml_Rma_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_customFilters = array();
    protected $_activeTab;

    public function __construct()
    {
        parent::__construct();
        $this->setId('rma_grid');
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    public function addCustomFilter($field, $filter)
    {
        $this->_customFilters[$field] = $filter;

        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('rma/rma')
            ->getCollection();
        foreach ($this->_customFilters as $key => $value) {
            $collection->addFieldToFilter($key, $value);
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $columns = Mage::getSingleton('rma/config')->getGeneralRmaGridColumns();

        if (in_array('increment_id', $columns)) {
            $this->addColumn('increment_id', array(
                    'header' => Mage::helper('rma')->__('RMA #'),
                    'index' => 'increment_id',
                    'filter_index' => 'main_table.increment_id',
                )
            );
        }
        if (in_array('order_increment_id', $columns)) {
            $this->addColumn('order_increment_id', array(
                'header' => Mage::helper('rma')->__('Order #'),
                'index' => 'order_increment_id',
                'filter_index' => 'order.increment_id',
                )
            );
        }
        if (in_array('customer_email', $columns)) {
            $this->addColumn('email', array(
                'header' => Mage::helper('rma')->__('Email'),
                'index' => 'email',
                )
            );
        }
        if (in_array('customer_name', $columns)) {
            $this->addColumn('name', array(
                'header' => Mage::helper('rma')->__('Customer Name'),
                'index' => array('firstname', 'lastname'),
                'type' => 'concat',
                'separator' => ' ',
                'filter_index' => "CONCAT(firstname, ' ', lastname)",
                )
            );
        }
        if (in_array('user_id', $columns)) {
            $this->addColumn('user_id', array(
                'header' => Mage::helper('rma')->__('Owner'),
                'index' => 'user_id',
                'filter_index' => 'main_table.user_id',
                'type' => 'options',
                'options' => Mage::helper('rma')->getAdminUserOptionArray(),
                )
            );
        }
        if (in_array('last_reply_name', $columns)) {
            $this->addColumn('last_reply_name', array(
                'header' => Mage::helper('rma')->__('Last Replier'),
                'index' => 'last_reply_name',
                'filter_index' => 'main_table.last_reply_name',
                'frame_callback' => array($this, '_lastReplyFormat'),
                )
            );
        }
        if (in_array('status_id', $columns)) {
            $this->addColumn('status_id', array(
                'header' => Mage::helper('rma')->__('Status'),
                'index' => 'status_id',
                'filter_index' => 'main_table.status_id',
                'type' => 'options',
                'options' => Mage::getModel('rma/status')->getOptionArray(),
                )
            );
        }
        if (in_array('increment_id', $columns)) {
            $this->addColumn('created_at', array(
                'header' => Mage::helper('rma')->__('Created Date'),
                'index' => 'created_at',
                'filter_index' => 'main_table.created_at',
                'type' => 'datetime',
                )
            );
        }
        if (in_array('updated_at', $columns)) {
            $this->addColumn('updated_at', array(
                'header' => Mage::helper('rma')->__('Last Activity'),
                'index' => 'updated_at',
                'filter_index' => 'main_table.updated_at',
                'type' => 'datetime',
                'frame_callback' => array($this, '_lastActivityFormat'),
                )
            );
        }
        if (in_array('store_id', $columns)) {
            $this->addColumn('store_id', array(
                    'header' => Mage::helper('rma')->__('Store'),
                    'index' => 'store_id',
                    'filter_index' => 'main_table.store_id',
                    'type' => 'options',
                    'options' => Mage::helper('rma')->getCoreStoreOptionArray(),
                )
            );
        }
        if (in_array('items', $columns)) {
            $this->addColumn('items', array(
                    'header' => Mage::helper('rma')->__('Items'),
                    'column_css_class' => 'nowrap',
                    'type' => 'text',
                    'frame_callback' => array($this, '_itemsFormat'),
                )
            );
        }
        if ($this->getTabMode() || in_array('action', $columns)) {
            $this->addColumn('action',
                array(
                    'header' => Mage::helper('rma')->__('Action'),
                    'width' => '50px',
                    'type' => 'action',
                    'getter' => 'getId',
                    'actions' => array(
                        array(
                            'caption' => Mage::helper('rma')->__('View'),
                            'url' => array(
                                'base' => 'rmaadmin/adminhtml_rma/edit',
                            ),
                            'field' => 'id',
                        ),
                    ),
                    'filter' => false,
                    'sortable' => false,
                ));
        }

        $collection = Mage::helper('rma/field')->getStaffCollection();
        foreach ($collection as $field) {
            if (in_array($field->getCode(), $columns)) {
                $this->addColumn($field->getCode(), array(
                    'header' => Mage::helper('rma')->__($field->getName()),
                    'index' => $field->getCode(),
                    'type' => $field->getGridType(),
                    'options' => $field->getGridOptions(),
                ));
            }
        }

        if ($this->getExportVisibility() !== false) {
            $this->addExportType('*/*/exportCsv', Mage::helper('rma')->__('CSV'));
            $this->addExportType('*/*/exportXml', Mage::helper('rma')->__('XML'));
        }

        return parent::_prepareColumns();
    }

    /**
     * @param Mirasvit_Rma_Block_Adminhtml_Rma_Grid   $renderedValue
     * @param Mirasvit_Rma_Model_Rma                  $rma
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool                                    $isExport
     *
     * @return string
     */
    public function _itemsFormat($renderedValue, $rma, $column, $isExport)
    {
        $html = array();
        foreach ($rma->getItemCollection() as $item) {
            $s = '<b>'.$item->getName().'</b>';
            $s .= ' / ';
            $s .= $item->getReasonName() ? $item->getReasonName() : '-';
            $s .= ' /  ';
            $s .= $item->getConditionName() ? $item->getConditionName() : '-';
            $s .= ' / ';
            $s .= $item->getResolutionName() ? $item->getResolutionName() : '-';

            $html[] = $s;
        }

        return implode('<br>', $html);
    }

    /**
     * @param Mirasvit_Rma_Block_Adminhtml_Rma_Grid   $renderedValue
     * @param Mirasvit_Rma_Model_Rma                  $rma
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool                                    $isExport
     *
     * @return string
     */
    public function _lastReplyFormat($renderedValue, $rma, $column, $isExport)
    {
        $name = $rma->getLastReplyName();
        // If last comment is automated, assign Last Reply Name value to owner, if such exists
        $lastComment = $rma->getLastComment();
        if ($lastComment && !$lastComment->getUserId() && !$lastComment->getCustomerId()) {
            $name = $rma->getUserName();
        }

        if (!$rma->getIsAdminRead()) {
            $name .= ' <img src="'.$this->getSkinUrl('images/fam_newspaper.gif').'">';
        }

        return $name;
    }

    /**
     * @param Mirasvit_Rma_Block_Adminhtml_Rma_Grid   $renderedValue
     * @param Mirasvit_Rma_Model_Rma                  $rma
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool                                    $isExport
     *
     * @return string
     */
    public function _lastActivityFormat($renderedValue, $rma, $column, $isExport)
    {
        return Mage::helper('rma/string')->nicetime(strtotime($rma->getUpdatedAt()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('rma_id');
        $this->getMassactionBlock()->setFormFieldName('rma_id');
        $statuses = array(
                array('label' => '', 'value' => ''),
                array('label' => $this->__('Disabled'), 'value' => 0),
                array('label' => $this->__('Enabled'), 'value' => 1),
        );
        /*
         * Proper redirect if mass action was conducted in Tab Mode.
         * If action in tab mode, current URL is:
         *  - ...mageadmin/sales_order/view/order_id/196/key/ff1e507218a2b78d0fa2c02bf0aa3ab5/?active_tab=RMA
         * But when mass action URL is opened, it changes to:
         *  - ...rmaadmin/adminhtml_rma/massDelete/
         * Thus, redirect after mass action will be to index page of RMA, so we need to pass back URL to controller.
        */
        $backUrl = strtr(base64_encode($this->getGridUrl()), '+/=', '-_,');
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('rma')->__('Delete'),
            'url' => $this->getTabMode() ? $this->getUrl('rmaadmin/adminhtml_rma/massDelete', array('back_url' => $backUrl))
                                         : $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('rma')->__('Are you sure?'),
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('rmaadmin/adminhtml_rma/edit', array('id' => $row->getId()));
    }

    /************************/

    public function setActiveTab($tabName)
    {
        $this->_activeTab = $tabName;
    }

    public function getGridUrl()
    {
        if ($this->_activeTab) {
            return parent::getGridUrl().'?active_tab='.$this->_activeTab;
        }

        return parent::getGridUrl();
    }
}
