<?php

class Ecom_Utils_Block_Customer_Form_Register extends Mage_Customer_Block_Form_Register
{
    function getShowAddressFields(){
		if(Mage::getStoreConfig('customer/create_account/show_address_fields')) return true;
		else return false;
	}
}
