<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   RMA
 * @version   2.0.1
 * @build     982
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */



/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$version = Mage::helper('mstcore/version')->getModuleVersionFromDb('mst_rma');
if ($version == '1.0.13') {
    return;
} elseif ($version != '1.0.12') {
    die('Please, run migration Rma 1.0.12');
}
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
$sql = '
';
$installer->run($sql);

//PRODUCT
$setup->addAttribute('catalog_product', 'rma_status', array(
    'input' => 'select',
    'type' => 'int',
    'source' => 'rma/system_config_source_status',
    'label' => 'Allow RMA',
    'backend' => '',
    'visible' => 1,
    'required' => 0,
    'user_defined' => 1,
    'sort_order' => 100000,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
));

$attributeId = $setup->getAttributeId('catalog_product', 'rma_status');
$allAttributeSetIds = $setup->getAllAttributeSetIds('catalog_product');
foreach ($allAttributeSetIds as $attributeSetId) {
    try {
        $attributeGroupId = $setup->getAttributeGroupId('catalog_product', $attributeSetId, 'General');
        $setup->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupId, $attributeId);
    } catch (Exception $e) {
    }
}

$installer->endSetup();

/*                                    **/
