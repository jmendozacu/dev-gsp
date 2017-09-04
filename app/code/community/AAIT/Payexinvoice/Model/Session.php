<?php
/**
 * PayEx Invoice: Transaction session namespace
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Model_Session extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        $this->init('payexinvoice');
    }
}
