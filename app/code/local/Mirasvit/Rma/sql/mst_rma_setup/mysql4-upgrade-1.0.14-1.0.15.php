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
if ($version == '1.0.15') {
    return;
} elseif ($version != '1.0.14') {
    die('Please, run migration Rma 1.0.14');
}
$installer->startSetup();
if (Mage::registry('mst_allow_drop_tables')) {
    $sql = "
       DROP TABLE IF EXISTS `{$this->getTable('rma/rule')}`;
    ";
    $installer->run($sql);
}
$sql = "
CREATE TABLE IF NOT EXISTS `{$this->getTable('rma/rule')}` (
    `rule_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `event` VARCHAR(255) NOT NULL DEFAULT '',
    `email_subject` VARCHAR(255) NOT NULL DEFAULT '',
    `email_body` TEXT,
    `is_active` INT(11) ,
    `conditions_serialized` TEXT,
    `is_send_owner` TINYINT(1) NOT NULL DEFAULT 0,
    `is_send_department` TINYINT(1) NOT NULL DEFAULT 0,
    `is_send_user` TINYINT(1) NOT NULL DEFAULT 0,
    `other_email` VARCHAR(255) NOT NULL DEFAULT '',
    `sort_order` SMALLINT(5) NOT NULL DEFAULT '0',
    `is_stop_processing` TINYINT(1) NOT NULL DEFAULT 0,
    `status_id` INT(11) ,
    `user_id` ".(Mage::getVersion() >= '1.6.0.0' ? 'int(10)' : 'mediumint(11)')." unsigned,
    `is_send_attachment` TINYINT(1) NOT NULL DEFAULT 0,
    `is_resolved` SMALLINT(5) NOT NULL DEFAULT '0',
    KEY `fk_rma_rule_status_id` (`status_id`),
    CONSTRAINT `mst_9e6ac3ae5bab043d53a4940b65683295` FOREIGN KEY (`status_id`) REFERENCES `{$this->getTable('rma/status')}` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    KEY `fk_rma_rule_user_id` (`user_id`),
    CONSTRAINT `mst_bff2a325ea2c3b6d8b1565bfe5252d06` FOREIGN KEY (`user_id`) REFERENCES `{$this->getTable('admin/user')}` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

";
$installer->run($sql);

/*                                    **/

$installer->endSetup();
