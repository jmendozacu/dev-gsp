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



class Mirasvit_Rma_Block_Adminhtml_Rule_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('rule_tabs');
        $this->setDestElementId('edit_form');
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general_section', array(
            'label' => Mage::helper('rma')->__('General Information'),
            'title' => Mage::helper('rma')->__('General Information'),
            'content' => $this->getLayout()->createBlock('rma/adminhtml_rule_edit_tab_general')->toHtml(),
        ));
        $this->addTab('condition_section', array(
            'label' => Mage::helper('rma')->__('Conditions'),
            'title' => Mage::helper('rma')->__('Conditions'),
            'content' => $this->getLayout()->createBlock('rma/adminhtml_rule_edit_tab_condition')->toHtml(),
        ));
        $this->addTab('action_section', array(
            'label' => Mage::helper('rma')->__('Actions'),
            'title' => Mage::helper('rma')->__('Actions'),
            'content' => $this->getLayout()->createBlock('rma/adminhtml_rule_edit_tab_action')->toHtml(),
        ));
        $this->addTab('notification_section', array(
            'label' => Mage::helper('rma')->__('Notifications'),
            'title' => Mage::helper('rma')->__('Notifications'),
            'content' => $this->getLayout()->createBlock('rma/adminhtml_rule_edit_tab_notification')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }

    /************************/
}
