<?php


//$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

//Add attribute size_shoes
$attribute_set_name = 'Default';
$group_name = 'General';
$attribute_code = 'size_shoes';
$attribute_label = 'Size Shoes';

$this->addAttribute('catalog_product', $attribute_code, array(
  'type'              => 'varchar',
  'backend'           => '',
  'frontend'          => '',
  'label'             => $attribute_label,
  'input'             => 'select',
  'class'             => '',
  'source'            => 'eav/entity_attribute_source_table',
  'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
  'visible'           => true,
  'required'          => false,
  'user_defined'      => true,
  'default'           => '',
  'searchable'        => true,
  'filterable'        => true,
  'comparable'        => true,
  'visible_on_front'  => true,
  'unique'            => false,
  'is_configurable'   => true,
  'group'             => $group_name
));

# add size_shoes attribute to set and group
$attribute_set_id=$this->getAttributeSetId('catalog_product', $attribute_set_name);
$attribute_group_id=$this->getAttributeGroupId('catalog_product', $attribute_set_id, $group_name);
$attribute_id=$this->getAttributeId('catalog_product', $attribute_code);

# change size_shoes to be included in product listning
$model = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(4, $attribute_code);
if ($model) $model->setUsedInProductListing(1)->save();

$sizeShoeOption = array (
	'attribute_id' => $attribute_id,
  	'value' => array (
  		'size_35' => array (0 => '35',),
     	'size_36' => array (0 => '36',),
     	'size_37' => array (0 => '37',),
     	'size_38' => array (0 => '38',),
     	'size_39' => array (0 => '39',),
     	'size_40' => array (0 => '40',),
     	'size_41' => array (0 => '41',),
     	'size_42' => array (0 => '42',),
     	'size_43' => array (0 => '43',),
     	'size_44' => array (0 => '44',),
     	'size_45' => array (0 => '45',),
     	'size_46' => array (0 => '46',),
  		)
);

$this->addAttributeOption($sizeShoeOption);
$this->addAttributeToSet('catalog_product',$attribute_set_id, $attribute_group_id, $attribute_id);




//Add attribute size_clothes
$attribute_set_name = 'Default';
$group_name = 'General';
$attribute_code = 'size_clothes';
$attribute_label = 'Size Clothes';
//Create attribute size_clothes

$this->addAttribute('catalog_product', $attribute_code, array(
  'type'              => 'varchar',
  'backend'           => '',
  'frontend'          => '',
  'label'             => $attribute_label,
  'input'             => 'select',
  'class'             => '',
  'source'            => 'eav/entity_attribute_source_table',
  'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
  'visible'           => true,
  'required'          => false,
  'user_defined'      => true,
  'default'           => '',
  'searchable'        => true,
  'filterable'        => true,
  'comparable'        => true,
  'visible_on_front'  => true,
  'unique'            => false,
  'is_configurable'   => true,
  'group'             => $group_name
));

# add size_clothes attribute to set and group
$attribute_set_id=$this->getAttributeSetId('catalog_product', $attribute_set_name);
$attribute_group_id=$this->getAttributeGroupId('catalog_product', $attribute_set_id, $group_name);
$attribute_id=$this->getAttributeId('catalog_product', $attribute_code);
$this->addAttributeToSet('catalog_product',$attribute_set_id, $attribute_group_id, $attribute_id);

# change size_clothes to be included in product listning
$model = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(4, $attribute_code);
if ($model) $model->setUsedInProductListing(1)->save();

$sizeClothesOption = array (
	'attribute_id' => $attribute_id,
  	'value' => array (
  		'xxs' => array (0 => 'XXS',),
     	'xs' => array (0 => 'XS',),
     	's' => array (0 => 'S',),
     	'm' => array (0 => 'M',),
     	'l' => array (0 => 'L',),
     	'xl' => array (0 => 'XL',),
     	'xxl' => array (0 => 'XXL',),
  		)    
);

$this->addAttributeOption($sizeClothesOption);
