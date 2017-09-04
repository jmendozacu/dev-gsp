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



class Mirasvit_Rma_Block_Adminhtml_Rule_Edit_Tab_Action extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        /** @var Mirasvit_Rma_Model_Rule $rule */
        $rule = Mage::registry('current_rule');

        $fieldset = $form->addFieldset('action_fieldset', array('legend' => Mage::helper('rma')->__('Actions')));
        if ($rule->getId()) {
            $fieldset->addField('rule_id', 'hidden', array(
                'name' => 'rule_id',
                'value' => $rule->getId(),
            ));
        }
        $fieldset->addField('status_id', 'select', array(
            'label' => Mage::helper('rma')->__('Set Status'),
            'name' => 'status_id',
            'value' => $rule->getStatusId(),
            'values' => Mage::getModel('rma/status')->getCollection()->toOptionArray(true),
        ));
        $fieldset->addField('user_id', 'select', array(
            'label' => Mage::helper('rma')->__('Set Owner'),
            'name' => 'user_id',
            'value' => $rule->getUserId(),
            'values' => Mage::helper('rma')->toAdminUserOptionArray(true),
        ));
        $fieldset->addField('is_resolved', 'select', array(
            'label' => Mage::helper('rma')->__('Resolved'),
            'name' => 'is_resolved',
            'value' => $rule->getIsResolved(),
            'values' => Mage::getSingleton('rma/config_source_is_resolved')->toOptionArray(),
        ));

        return parent::_prepareForm();
    }

    /************************/
}
