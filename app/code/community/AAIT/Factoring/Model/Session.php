<?php

/**
 *
 * Payex transaction session namespace
 *
 */
class AAIT_Factoring_Model_Session extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        $this->init('factoring');
    }
}
