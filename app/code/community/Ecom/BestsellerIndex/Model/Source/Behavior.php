<?php

class Ecom_BestsellerIndex_Model_Source_Behavior
{
    /**
     * Prepare and return array of behavior.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Ecom_BestsellerIndex_Model_Process::BEHAVIOR_PRICE,
                'label' => Mage::helper('bestsellerindex')->__('Base on sale value')
            ),
            array(
                'value' => Ecom_BestsellerIndex_Model_Process::BEHAVIOR_QTY,
                'label' => Mage::helper('bestsellerindex')->__('Base on Qty sold')
            )
        );
    }
}
