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



class Mirasvit_Rma_Model_Config_Source_Rule_Event
{
    public function toArray()
    {
        return array(
            Mirasvit_Rma_Model_Config::RULE_EVENT_RMA_CREATED => Mage::helper('rma')->__('New RMA has been created'),
            Mirasvit_Rma_Model_Config::RULE_EVENT_RMA_UPDATED => Mage::helper('rma')->__('RMA has been changed'),
            Mirasvit_Rma_Model_Config::RULE_EVENT_NEW_CUSTOMER_REPLY => Mage::helper('rma')->__('New reply from customer'),
            Mirasvit_Rma_Model_Config::RULE_EVENT_NEW_STAFF_REPLY => Mage::helper('rma')->__('New reply from staff'),
        );
    }
    public function toOptionArray()
    {
        $result = array();
        foreach ($this->toArray() as $k => $v) {
            $result[] = array('value' => $k, 'label' => $v);
        }

        return $result;
    }

    /************************/
}
