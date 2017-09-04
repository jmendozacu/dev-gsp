<?php

class Ecom_AdminLogger_Block_Adminhtml_Index extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'adminlogger';
        $this->_controller = 'adminhtml_index';
        $this->_headerText = Mage::helper('adminlogger')->__('Admin Logger');

        parent::__construct();

        $this->_removeButton('add');
    }
}