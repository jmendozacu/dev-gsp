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
if ($version == '1.0.9') {
    return;
} elseif ($version != '1.0.8') {
    die('Please, run migration Rma 1.0.8');
}
$installer->startSetup();
$sql = "
ALTER TABLE `{$this->getTable('rma/item')}` ADD COLUMN `to_stock` TINYINT(1) NOT NULL DEFAULT 0;
";
$helper = Mage::helper('rma/migration');
$helper->trySql($installer, $sql);

$sql = "
update `{$this->getTable('rma/status')}` set code='pending' where status_id = 1 and code='';
update `{$this->getTable('rma/status')}` set code='approved' where status_id = 2 and code='';
update `{$this->getTable('rma/status')}` set code='rejected' where status_id = 3 and code='';
update `{$this->getTable('rma/status')}` set code='closed' where status_id = 4 and code='';
";
$installer->run($sql);
/*                                    **/

$installer->endSetup();
