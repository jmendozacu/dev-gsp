<?php

# trigger ultimo to create new css files
try {
  Mage::getSingleton('ultimo/cssgen_generator')->generateCss('grid', NULL, NULL);
  Mage::getSingleton('ultimo/cssgen_generator')->generateCss('layout', NULL, NULL);
  Mage::getSingleton('ultimo/cssgen_generator')->generateCss('design', NULL, NULL);
} catch (Exception $ex) {
  Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup: Ultimo css-generation failed', null);
}

//Add attribute color
$attribute_set_name = 'Default';
$group_name = 'General';
$attribute_code = 'color';

# add color attribute to set and group
$attribute_set_id=$this->getAttributeSetId('catalog_product', $attribute_set_name);
$attribute_group_id=$this->getAttributeGroupId('catalog_product', $attribute_set_id, $group_name);
$attribute_id=$this->getAttributeId('catalog_product', $attribute_code);
$this->addAttributeToSet('catalog_product',$attribute_set_id, $attribute_group_id, $attribute_id);

# change color to be included in product listning
$model = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(4, $attribute_code);
if ($model) $model->setUsedInProductListing(1)->save();