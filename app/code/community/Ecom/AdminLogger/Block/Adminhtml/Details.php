<?php

class Ecom_AdminLogger_Block_Adminhtml_Details extends Mage_Adminhtml_Block_Widget_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'adminlogger';
        $this->_controller = 'adminhtml_details';

        parent::__construct();
		
		$this->_addButton('back', array(
               'label'     => Mage::helper('adminlogger')->__('Back'),
               'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/index') . '\')',
               'class' => 'back'
            ), -80);
	  
        $this->_removeButton('add');
    }
	
	public function getHeaderText() {
        if ($this->getCurrentEvent()) {
            return Mage::helper('adminlogger')->__('Admin Logger - Event #%d', $this->getCurrentEvent()->getId());
        }
		
        return Mage::helper('adminlogger')->__('Log Entry Details');
    }

    public function getCurrentEvent()
    {
        if ($this->_currentEvent === null) {
            $this->_currentEvent = Mage::registry('current_event');
        }
		
        return $this->_currentEvent;
    }}