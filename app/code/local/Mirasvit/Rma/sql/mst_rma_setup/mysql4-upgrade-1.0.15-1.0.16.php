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
if ($version == '1.0.16') {
    return;
} elseif ($version != '1.0.15') {
    die('Please, run migration Rma 1.0.15');
}
$installer->startSetup();
if (Mage::registry('mst_allow_drop_tables')) {
    $sql = "
       DROP TABLE IF EXISTS `{$this->getTable('rma/rma_order')}`;
       DROP TABLE IF EXISTS `{$this->getTable('rma/rma_creditmemo')}`;
    ";
    $installer->run($sql);
}
$sql = "
CREATE TABLE IF NOT EXISTS `{$this->getTable('rma/rma_order')}` (
    `rma_order_id` int(11) NOT NULL AUTO_INCREMENT,
    `re_rma_id` INT(11) NOT NULL,
    `re_exchange_order_id` INT(11) ,
    KEY `fk_rma_rma_order_rma_id` (`re_rma_id`),
    CONSTRAINT `mst_0634dda093533cb5972030cadeb3a4ca` FOREIGN KEY (`re_rma_id`) REFERENCES `{$this->getTable('rma/rma')}` (`rma_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY (`rma_order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$this->getTable('rma/rma_creditmemo')}` (
    `rma_creditmemo_id` int(11) NOT NULL AUTO_INCREMENT,
    `rc_rma_id` INT(11) NOT NULL,
    `rc_credit_memo_id` INT(11) ,
    KEY `fk_rma_rma_creditmemo_rma_id` (`rc_rma_id`),
    CONSTRAINT `mst_c05dff0fd72e8951b8521815dbdd8564` FOREIGN KEY (`rc_rma_id`) REFERENCES `{$this->getTable('rma/rma')}` (`rma_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY (`rma_creditmemo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

";
$installer->run($sql);

/*                                    **/

$installer->endSetup();
