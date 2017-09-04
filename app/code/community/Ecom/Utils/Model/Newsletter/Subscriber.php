<?php

class Ecom_Utils_Model_Newsletter_Subscriber extends Mage_Newsletter_Model_Subscriber
{

    /**
     * Sends out confirmation success email
     *
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function sendConfirmationSuccessEmail()
    {
        if(!Mage::getStoreConfig('newsletter/subscription/send_success_email')) return $this;
		else return parent::sendConfirmationSuccessEmail();
    }
}
