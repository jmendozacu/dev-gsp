<?php
 
$installer = $this;
$installer->startSetup();

$iProductEntityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
$attr_id = $installer->getAttributeId($iProductEntityTypeId, 'color');

$colorOption = array (
	'attribute_id' => $attr_id,
  	'value' => array (
  		'Black' => array (0 => 'Black',1 => 'Svart',),
     	'Blue' => array (0 => 'Blue', 1 => 'Blå'),
     	'Gray' => array (0 => 'Gray', 1 => 'Grå')
  		) 
      
);

$installer->addAttributeOption($colorOption);
$installer->endSetup();
