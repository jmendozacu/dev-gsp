<?php

class Ecom_AdminLogger_Block_Adminhtml_Details_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('id');
        $this->setDefaultSort('id');
		$this->setDefaultDir('desc');
        $this->setUseAjax(false);
		$this->setFilterVisibility(false);
		$this->setPagerVisibility(false);
    }

    protected function _prepareCollection()
    {
		$collection = Mage::getModel('adminlogger/details')->getCollection()
			->addFieldToFilter('event_id', $this->getParam('event_id'));
		
        $this->setCollection($collection);
        parent::_prepareCollection();
		
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => $this->__('ID'),
            'sortable' => true,
            'index' => 'id',
        ));
		
        $this->addColumn('event_id', array(
            'header' => $this->__('Event ID'),
            'sortable' => true,
            'index' => 'event_id',
        ));

        $this->addColumn('source_name', array(
            'header' => $this->__('Source name'),
            'sortable' => true,
            'index' => 'source_name',
        ));
		
        $this->addColumn('source_id', array(
            'header' => $this->__('Source ID'),
            'sortable' => true,
            'index' => 'source_id',
        ));

        $this->addColumn('property_name', array(
            'header' => $this->__('Property name'),
            'sortable' => true,
            'index' => 'property_name',
        ));
		
		$this->addColumn('original_data', array(
            'header' => $this->__('Original data'),
            'sortable' => true,
            'index' => 'original_data',
        ));

        $this->addColumn('result_data', array(
            'header' => $this->__('Result data'),
            'sortable' => true,
            'index' => 'result_data',
        ));

		
        return parent::_prepareColumns();
    }

}
