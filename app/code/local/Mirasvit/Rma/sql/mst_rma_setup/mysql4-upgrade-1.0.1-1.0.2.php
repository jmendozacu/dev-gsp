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
if ($version == '1.0.2') {
    return;
} elseif ($version != '1.0.0') {
    die('Please, run migration Rma 1.0.0');
}
$installer->startSetup();
if (Mage::registry('mst_allow_drop_tables')) {
    $sql = "
       DROP TABLE IF EXISTS `{$this->getTable('rma/field')}`;
    ";
    $installer->run($sql);
}
$sql = "
CREATE TABLE IF NOT EXISTS `{$this->getTable('rma/field')}` (
    `field_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `code` VARCHAR(255) NOT NULL DEFAULT '',
    `type` VARCHAR(255) NOT NULL DEFAULT '',
    `values` TEXT,
    `description` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` SMALLINT(5) NOT NULL DEFAULT '0',
    `is_required_staff` TINYINT(1) NOT NULL DEFAULT 0,
    `is_required_customer` TINYINT(1) NOT NULL DEFAULT 0,
    `is_visible_customer` TINYINT(1) NOT NULL DEFAULT 0,
    `is_editable_customer` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`field_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

";
$installer->run($sql);

/*                                    **/

$installer->endSetup();
