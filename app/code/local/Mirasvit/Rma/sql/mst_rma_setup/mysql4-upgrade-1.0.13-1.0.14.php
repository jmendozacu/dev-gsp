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
if ($version == '1.0.14') {
    return;
} elseif ($version != '1.0.13') {
    die('Please, run migration Rma 1.0.13');
}
$installer->startSetup();
if (Mage::registry('mst_allow_drop_tables')) {
    $sql = "
       DROP TABLE IF EXISTS `{$this->getTable('rma/template')}`;
       DROP TABLE IF EXISTS `{$this->getTable('rma/template_store')}`;
    ";
    $installer->run($sql);
}
$sql = "
CREATE TABLE IF NOT EXISTS `{$this->getTable('rma/template')}` (
    `template_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `template` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`template_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$this->getTable('rma/template_store')}` (
    `template_store_id` int(11) NOT NULL AUTO_INCREMENT,
    `ts_template_id` INT(11) NOT NULL,
    `ts_store_id` SMALLINT(5) unsigned NOT NULL,
    KEY `fk_rma_template_store_template_id` (`ts_template_id`),
    CONSTRAINT `mst_9fffbac352a78f98b60c7abaa9b0c1cd` FOREIGN KEY (`ts_template_id`) REFERENCES `{$this->getTable('rma/template')}` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `fk_rma_template_store_store_id` (`ts_store_id`),
    CONSTRAINT `mst_bd84e1780b43c1b6319f0a070c383add` FOREIGN KEY (`ts_store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY (`template_store_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `{$this->getTable('rma/rma')}` ADD COLUMN `is_admin_read` TINYINT(1) NOT NULL DEFAULT 0;
UPDATE  `{$this->getTable('rma/rma')}` SET is_admin_read = 1;
";
$helper = Mage::helper('rma/migration');
$helper->trySql($installer, $sql);

/*                                    **/

$installer->endSetup();
