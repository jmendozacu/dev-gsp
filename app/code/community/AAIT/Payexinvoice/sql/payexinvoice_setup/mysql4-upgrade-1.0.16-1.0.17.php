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

// Clean up unnecessary data
try {
    $this->_conn->dropColumn($this->getTable('sales_flat_order'), 'px_invoice_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_order'), 'base_px_invoice_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_invoice'), 'px_invoice_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_invoice'), 'base_px_invoice_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_quote'), 'px_invoice_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_quote'), 'base_px_invoice_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_quote_address'), 'payexinvoice_payment_fee');
    $this->_conn->dropColumn($this->getTable('sales_flat_quote_address'), 'base_payexinvoice_payment_fee');
} catch (Exception $e) {
    Mage::logException($e);
}

try {
    $eav = new Mage_Sales_Model_Resource_Setup('sales_setup');
    $eav->removeAttribute('order', 'px_invoice_fee');
    $eav->removeAttribute('order', 'base_px_invoice_fee');
    $eav->removeAttribute('invoice', 'px_invoice_fee');
    $eav->removeAttribute('invoice', 'base_px_invoice_fee');
    $eav->removeAttribute('quote', 'px_invoice_fee');
    $eav->removeAttribute('quote', 'base_px_invoice_fee');
    $eav->removeAttribute('quote_address', 'px_invoice_fee');
    $eav->removeAttribute('quote_address', 'base_px_invoice_fee');
} catch (Exception $e) {
    Mage::logException($e);
}

$this->endSetup();
