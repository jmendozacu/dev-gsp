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
if ($version == '1.0.4') {
    return;
} elseif ($version != '1.0.3') {
    die('Please, run migration Rma 1.0.3');
}
$installer->startSetup();
$sql = "
ALTER TABLE `{$this->getTable('rma/rma')}` ADD COLUMN `ticket_id` INT(11) ;
ALTER TABLE `{$this->getTable('rma/rma')}` ADD COLUMN `user_id` INT(11) ;
ALTER TABLE `{$this->getTable('rma/comment')}` ADD COLUMN `email_id` INT(11) ;
update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[rma_guest_print_url]', '{{var rma.guest_print_url}}'),
	admin_message = REPLACE(admin_message, '[rma_guest_print_url]', '{{var rma.guest_print_url}}'),
	history_message = REPLACE(history_message, '[rma_guest_print_url]', '{{var rma.guest_print_url}}');

update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[rma_guest_url]', '{{var rma.guest_url}}'),
	admin_message = REPLACE(admin_message, '[rma_guest_url]', '{{var rma.guest_url}}'),
	history_message = REPLACE(history_message, '[rma_guest_url]', '{{var rma.guest_url}}');

update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[rma_increment_id]', '{{var rma.increment_id}}'),
	admin_message = REPLACE(admin_message, '[rma_increment_id]', '{{var rma.increment_id}}'),
	history_message = REPLACE(history_message, '[rma_increment_id]', '{{var rma.increment_id}}');

update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[customer_name]', '{{var customer.name}}'),
	admin_message = REPLACE(admin_message, '[customer_name]', '{{var customer.name}}'),
	history_message = REPLACE(history_message, '[customer_name]', '{{var customer.name}}');

update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[order_increment_id]', '{{var order.increment_id}}'),
	admin_message = REPLACE(admin_message, '[order_increment_id]', '{{var order.increment_id}}'),
	history_message = REPLACE(history_message, '[order_increment_id]', '{{var order.increment_id}}');

update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[rma_return_address]', '{{var rma.return_address_html}}'),
	admin_message = REPLACE(admin_message, '[rma_return_address]', '{{var rma.return_address_html}}'),
	history_message = REPLACE(history_message, '[rma_return_address]', '{{var rma.return_address_html}}');

update `{$this->getTable('rma/status')}` SET
	customer_message = REPLACE(customer_message, '[rma_items]', ''),
	admin_message = REPLACE(admin_message, '[rma_items]', ''),
	history_message = REPLACE(history_message, '[rma_items]', '');

";
$helper = Mage::helper('rma/migration');
$helper->trySql($installer, $sql);

/*                                    **/

$installer->endSetup();
