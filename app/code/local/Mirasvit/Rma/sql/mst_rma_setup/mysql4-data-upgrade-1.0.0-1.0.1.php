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
$connection = $installer->getConnection();
$count = $connection->fetchOne('SELECT count(*) FROM '.$this->getTable('rma/reason'));
if ($count == 0) {
    $sql = "
   INSERT INTO `{$this->getTable('rma/reason')}` (name,is_active,sort_order) VALUES ('Out of Service','1','10');
   INSERT INTO `{$this->getTable('rma/reason')}` (name,is_active,sort_order) VALUES ('Don\'t like','1','20');
   INSERT INTO `{$this->getTable('rma/reason')}` (name,is_active,sort_order) VALUES ('Wrong color','1','30');
   INSERT INTO `{$this->getTable('rma/reason')}` (name,is_active,sort_order) VALUES ('Wrong size','1','40');
   INSERT INTO `{$this->getTable('rma/reason')}` (name,is_active,sort_order) VALUES ('Other','1','50');

   INSERT INTO `{$this->getTable('rma/condition')}` (name,is_active,sort_order) VALUES ('Unopened','1','10');
   INSERT INTO `{$this->getTable('rma/condition')}` (name,is_active,sort_order) VALUES ('Opened','1','20');
   INSERT INTO `{$this->getTable('rma/condition')}` (name,is_active,sort_order) VALUES ('Damaged','1','30');

   INSERT INTO `{$this->getTable('rma/resolution')}` (name,is_active,sort_order) VALUES ('Exchange','1','10');
   INSERT INTO `{$this->getTable('rma/resolution')}` (name,is_active,sort_order) VALUES ('Refund','1','20');
   INSERT INTO `{$this->getTable('rma/resolution')}` (name,is_active,sort_order) VALUES ('Store Credit','1','30');

   INSERT INTO `{$this->getTable('rma/status')}` (name,is_active,sort_order,is_rma_resolved,customer_message,history_message,admin_message) VALUES ('Pending Approval','1','10','0',
'Dear {{var customer.name}},<br><br>

Your Return request has been received. You will be notified when your request is reviewed.',
'Return request has been received. You will be notified when your request is reviewed.',
'RMA #{{var rma.increment_id}} has been created.');
   INSERT INTO `{$this->getTable('rma/status')}` (name,is_active,sort_order,is_rma_resolved,customer_message,history_message) VALUES ('Approved','1','20','1',
'Dear {{var customer.name}},<br><br>

Your Return request has been approved.
<br>
Please, print <a href=\'{{var rma.guest_print_url}}\'>RMA Packing Slip</a>{{depend rma.guest_print_label_url}}, <a href=\'{{var rma.guest_print_label_url}}\'>RMA Shipping Label</a>
{{/depend}} and send package to:<br>
{{var rma.return_address_html}}',
'Your Return request has been approved.
<br>
Please, print <a href=\'{{var rma.guest_print_url}}\'>RMA Packing Slip</a>{{depend rma.guest_print_label_url}}, <a href=\'{{var rma.guest_print_label_url}}\'>RMA Shipping Label</a>
{{/depend}} and send package to:<br>
{{var rma.return_address_html}}'
);
   INSERT INTO `{$this->getTable('rma/status')}` (name,is_active,sort_order,is_rma_resolved,customer_message,history_message) VALUES ('Rejected','1','30','0',
'Dear {{var customer.name}},<br><br>

Return request has been rejected.',
'Return request has been rejected.'
);
   INSERT INTO `{$this->getTable('rma/status')}` (name,is_active,sort_order,is_rma_resolved,customer_message,history_message) VALUES ('Closed','1','40','0',
'Dear {{var customer.name}},<br><br>

Your Return request has been closed.',
'Return request has been closed.'
);

   INSERT INTO `{$this->getTable('rma/status')}` (name,is_active,sort_order,code,is_rma_resolved,customer_message,history_message,admin_message) VALUES ('Package Sent','1','25','package_sent','0','','','Package is sent.')
";
    $installer->run($sql);
}
