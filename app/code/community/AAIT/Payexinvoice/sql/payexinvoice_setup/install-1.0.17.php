<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'payexinvoice_payment_fee', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'base_payexinvoice_payment_fee', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'payexinvoice_payment_fee', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'base_payexinvoice_payment_fee', 'decimal(12,4)');

$eav = new Mage_Sales_Model_Resource_Setup('sales_setup');
$eav->addAttribute('quote', 'payexinvoice_payment_fee', array('type' => 'decimal'));
$eav->addAttribute('quote', 'base_payexinvoice_payment_fee', array('type' => 'decimal'));
$eav->addAttribute('order', 'payexinvoice_payment_fee', array('type' => 'decimal'));
$eav->addAttribute('order', 'base_payexinvoice_payment_fee', array('type' => 'decimal'));

$this->endSetup();
