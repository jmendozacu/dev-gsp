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
if ($version == '1.0.8') {
    return;
} elseif ($version != '1.0.7') {
    die('Please, run migration Rma 1.0.7');
}
$installer->startSetup();
$sql = "
ALTER TABLE `{$this->getTable('rma/rma')}` ADD COLUMN `exchange_order_id` INT(11) ;
ALTER TABLE `{$this->getTable('rma/rma')}` ADD COLUMN `credit_memo_id` INT(11) ;
ALTER TABLE `{$this->getTable('rma/resolution')}` ADD COLUMN `code` VARCHAR(255) NOT NULL DEFAULT '';
";
$helper = Mage::helper('rma/migration');
$helper->trySql($installer, $sql);

/*                                    **/

$installer->endSetup();
