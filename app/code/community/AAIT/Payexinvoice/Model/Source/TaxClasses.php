<?php
/**
 * PayEx Invoice Payment
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Model_Source_TaxClasses
{
    public function toOptionArray()
    {
        $model = Mage::getSingleton('payexinvoice/payment');
        return $model->getTaxClasses();
    }
}