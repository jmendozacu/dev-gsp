<?php
/**
 * PayEx Invoice Block: Payment Form Renderer
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Block_Form extends Mage_Payment_Block_Form
{
  protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payexinvoice/form.phtml');
    }
}

?>
