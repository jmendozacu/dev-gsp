<?php
/**
 * PayEx Invoice Block: Info Block Renderer
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payexinvoice/info.phtml');
    }
}
