<?php

class Ecom_KlarnaCheckout_Model_Service_Customer extends Mage_Core_Model_Abstract {
	
	const PASSWORD_LENGTH = 10;
	
	public function createOrLoadCustomer($first_name, $last_name, $email) {

        // try to load the customer

        // first attempt
		$customer = Mage::getModel('customer/customer');
		$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
		$customer->setStore(Mage::app()->getStore());
		$customer->loadByEmail($email);

        // second attempt
        if ($customer->getId()) {
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
            $customer->loadByEmail($email);
        }

        if ($customer->getId()) {
			return $customer;
		} else {
			$customer->setEmail($email);
			$customer->setFirstname($first_name);
			$customer->setLastname($last_name);
			$customer->setPassword($customer->generatePassword(self::PASSWORD_LENGTH));

			try {
				$customer->save();
				$customer->setConfirmation(null); // Set confirmation to prevent additional emails being sent
				$customer->save();
			
				// Notify customer
				$customer->sendNewAccountEmail();
				
				return $customer;
			} catch (Exception $e) {
				Mage::logException($e);
				return null;
			}
		}		
		
	}
	
}