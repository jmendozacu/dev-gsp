<?php
class Ecom_UltraCart_Block_System_Config_Form_Field_Links extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('href', array(
            'label' => Mage::helper('ultracart')->__('Link'),
            'style' => 'width:250px',
        ));
        $this->addColumn('label', array(
            'label' => Mage::helper('ultracart')->__('Label'),
            'style' => 'width:250px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('ultracart')->__('Add link');
        parent::__construct();
    }
}