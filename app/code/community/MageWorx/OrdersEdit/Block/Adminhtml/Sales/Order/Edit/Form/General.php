<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_General extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Preapre form to edit general info of order
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $helper = Mage::helper('mageworx_ordersedit');
        $form = new Varien_Data_Form();

        $statuses = Mage::getSingleton('adminhtml/system_config_source_order_status')->toOptionArray();
        $form->addField('status', 'select', array(
                'name'  => 'status',
                'label' => Mage::helper('adminhtml')->__('Order Status'),
                'title' => Mage::helper('adminhtml')->__('Order Status'),
                'required' => true,
                'values' => $statuses
            )
        );

        $form->addField('increment_id', 'text', array(
                'name'  => 'increment_id',
                'label' => Mage::helper('adminhtml')->__('Order Number'),
                'title' => Mage::helper('adminhtml')->__('Order Number'),
                'required' => true,
                'class' => 'mageworx-increment-id-field validate-length maximum-length-50 minimum-length-1',
                'after_element_html' =>
                    '<p class="sub-note"><sup>'. $helper->__('Must be less than 51 characters') .'</sup></p>'
            )
        );

        $data = $this->getOrder()->getData();

        $form->setValues($data);

        $form->setUseContainer(true);
        $form->setId('ordersedit_edit_form');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}