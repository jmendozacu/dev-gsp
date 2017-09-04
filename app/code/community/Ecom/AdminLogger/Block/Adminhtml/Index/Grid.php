<?php

class Ecom_AdminLogger_Block_Adminhtml_Index_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {
		parent::__construct();

		$this->setId('entity_id');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('desc');
		$this->setUseAjax(false);
	}

	protected function _prepareCollection() {
		$this->setCollection(Mage::getModel('adminlogger/log')->getCollection());
		parent::_prepareCollection();

		return $this;
	}

	protected function _prepareColumns() {
		$this->addColumn('entity_id', array(
			'header' => $this->__('ID'),
			'sortable' => true,
			'index' => 'entity_id',
		));

		$this->addColumn('created_at', array(
			'header' => $this->__('Time'),
			'index' => 'created_at',
			'type' => 'text',
			'width' => '170px',
		));

		$this->addColumn('username', array(
			'header' => $this->__('Username'),
			'index' => 'username',
            'type'  => 'options',
            'options' => Mage::getSingleton('adminlogger/source_username')->toOptionArray(),
		));

		$this->addColumn('controller', array(
			'header' => $this->__('Controller'),
			'index' => 'controller',
            'type'  => 'options',
            'options' => Mage::getSingleton('adminlogger/source_controller')->toOptionArray(),
		));

		$this->addColumn('action_type', array(
			'header' => $this->__('Action Type'),
			'index' => 'action_type',
            'type'  => 'options',
            'options' => Mage::getSingleton('adminlogger/source_action')->toOptionArray(),
		));

		$this->addColumn('store', array(
			'header' => $this->__('Store Code'),
			'index' => 'store',
			'type' => 'text',
		));

		$this->addColumn('item', array(
			'header' => $this->__('Item'),
			'index' => 'item',
			'type' => 'text',
		));

		$this->addColumn('view', array(
			'header' => $this->__('Details'),
			'width' => 50,
			'type' => 'action',
			'getter' => 'getId',
			'actions' => array(array(
					'caption' => $this->__('View'),
					'url' => array(
						'base' => '*/*/details',
					),
					'field' => 'event_id'
				)),
			'filter' => false,
			'sortable' => false,
		));

		$this->addExportType('*/*/exportCsv', $this->__('CSV'));
		$this->addExportType('*/*/exportExcel', $this->__('Excel XML'));

		return parent::_prepareColumns();
	}

	public function getRowUrl($row) {
		return $this->getUrl('*/*/details', array('event_id' => $row->getId()));
	}

}
