<?php
/**
 * PayEx Invoice Payment
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Model_Source_MediaDistribution
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 1,
                'label' => Mage::helper('payexinvoice')->__('Paper by mail')
            ),
            array(
                'value' => 11,
                'label' => Mage::helper('payexinvoice')->__('PDF by e-mail')
            ),
        );
    }
}