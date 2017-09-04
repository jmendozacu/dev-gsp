<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Magecom
 * @package     Magecom_CustomerApproval
 * @copyright  Copyright (c) 2017 Magecom, Inc. (http://www.magecom.net)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$newGroupName = Magecom_CustomerApproval_Helper_Data::CUSTOMER_GROUP;

$collection = Mage::getModel('customer/group')->getCollection()
                                                        ->addFieldToFilter('customer_group_code', $newGroupName);
if (!count($collection)){
    $newGroup = Mage::getModel('customer/group');
    $newGroup->setCode($newGroupName);
    $newGroup->setTaxClassId(3);
    $newGroup->save();

    $configPath = Magecom_CustomerApproval_Helper_Data::DEFAULT_STORE_ID_CONFIG;
    Mage::getConfig()->saveConfig($configPath, $newGroup->getId(), 'default', 0);
    Mage::getConfig()->saveConfig($configPath, $newGroup->getId(), 'websites', 2);
}

$installer->endSetup();