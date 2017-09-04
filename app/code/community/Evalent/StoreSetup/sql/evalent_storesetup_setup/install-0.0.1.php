<?php
$installer = $this;
$installer->startSetup();




$querybase = "DELETE FROM `core_config_data` WHERE path = ";

$querys = array();
$querys[] = "'general/country/default'";
$querys[] = "'general/locale/timezone'";
$querys[] = "'general/locale/code'";
$querys[] = "'general/locale/firstday'";
$querys[] = "'cms/wysiwyg/enabled'";
#$querys[] = "'catalog/frontend/flat_catalog_category'";
$querys[] = "'catalog/frontend/flat_catalog_product'";
$querys[] = "'catalog/seo/category_canonical_tag'";
$querys[] = "'catalog/seo/product_canonical_tag'";
$querys[] = "'shipping/origin/country_id'";
$querys[] = "'shipping/option/checkout_multiple'";
$querys[] = "'system/log/enabled'";
$querys[] = "'dev/log/active'";

$querys[] = "'tax/calculation/algorithm'";
$querys[] = "'tax/calculation/based_on'";
$querys[] = "'tax/calculation/price_includes_tax'";
$querys[] = "'tax/calculation/shipping_includes_tax'";
$querys[] = "'tax/calculation/apply_after_discount'";
$querys[] = "'tax/calculation/discount_tax'";
$querys[] = "'tax/calculation/apply_tax_on'";
$querys[] = "'tax/calculation/cross_border_trade_enabled'";

$querys[] = "'catalog/seo/product_url_suffix'";
$querys[] = "'catalog/seo/category_url_suffix'";
$querys[] = "'carriers/flatrate/active'";



$resource = Mage::getSingleton('core/resource');
$writeConnection = $resource->getConnection('core_write');

# unset some config
foreach ($querys AS $query) {

  try {
    $writeConnection->query($querybase.$query);
  }
  catch (Exception $ex) {
    Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup:'.$query.' failed', null);
  }
}

# set config in website level
try {
  $writeConnection->query("INSERT INTO  `core_config_data` (`config_id` ,`scope` ,`scope_id` ,`path` ,`value`) VALUES (NULL ,  'websites',  '1',  'general/locale/code',  'sv_SE');");
}
catch (Exception $ex) {
  Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup: Locale in website failed', null);
}

/*
# Create admin User
try {
  $user = Mage::getModel("admin/user")
    ->setUsername('')
    ->setFirstname('Dev')
    ->setLastname('Team')
    ->setEmail('sales@evalent.com')
    ->setPassword('')
    ->save();
  $role = Mage::getModel("admin/role");
  $role->setParent_id(1);
  $role->setTree_level(1);
  $role->setRole_type('U');
  $role->setUser_id($user->getId());
  $role->save();

  Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup:user creation success', null);
}
catch (Exception $ex) {
  Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup:user creation failed', null);
}
*/

//Change Main Website Name
$website = Mage::getModel('core/website')->load(1);
$website->setName('dinbutik.se')->save();
//Change Store Name
$group = Mage::getModel('core/store_group')->load(1, 'website_id');
$group->setName('Din Butik')->save();
//Change Store View Name
$store = Mage::getModel('core/store')->load(1);
$store->setName('Svenska')->save();


# TAX import CSV
Mage::helper('storesetup')->importRates('var/import/moms-25-12-6.csv');


# Change name on Taxable Goods and Shipping
$tax = Mage::getResourceModel('tax/class_collection');
foreach ($tax as $key) {

    switch ($key->getClassName()) {
        case 'Taxable Goods':
        $key->setClassName('Moms 25')->save();
            break;
        case 'Shipping':
        $key->setClassName('Moms 12')->save();
            break;
        case 'Retail Customer':
        $key->setClassName('B2C')->save();
            break;
        default:
            # code...
            break;
    }
}

# CUSTOMER TAX CLASS
$model = Mage::getModel('tax/class')->getCollection();

try {
  $M6 = clone $model->getFirstItem();
  $M6->setClassId(null);
  $M6->setClassName('Moms 6');
  $M6->setClassType('PRODUCT');

  $model->addItem($M6)->save();
}
catch (Exception $e) {
  Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup: Moms 6 Tax Rule: failed', null);
}


try {
  $B2B = clone $model->getFirstItem();
  $B2B->setClassId(null);
  $B2B->setClassName('B2B');
  $B2B->setClassType('CUSTOMER');

  $model->addItem($B2B)->save();
}
catch (Exception $e) {
  Mage::helper('storesetup')->log('Evalent_StoreSetup_Sql_Setup: B2B Tax Rule: failed', null);
}


# CUSTOMER GROUPS
Mage::getModel('customer/group')->load('0')->setCustomerGroupCode('GÄST')->save();
Mage::getModel('customer/group')->load('1')->setCustomerGroupCode('Privatkund')->save();
Mage::getModel('customer/group')->load('2')->setCustomerGroupCode('Grossist')->save();
Mage::getModel('customer/group')->load('3')->setCustomerGroupCode('Återförsäljare')->save();

# Get tax B2B class id 
$y = Mage::getModel('tax/class')->load('B2B','class_name');
$y = $y->getClassId();
# Load retailer
$x = Mage::getModel('customer/group')->load('3');
# Set tax class to B2B
$x->setTaxClassId($y)->save();
# Load wholesale
$x = Mage::getModel('customer/group')->load('2');
# Set tax class to B2B
$x->setTaxClassId($y)->save();




$installer->endSetup();
