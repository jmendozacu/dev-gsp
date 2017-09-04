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
   INSERT INTO `{$this->getTable('rma/status')}` (name,is_active,sort_order,code,is_rma_resolved,customer_message,history_message,admin_message) VALUES ('Package Sent','1','25','package_sent','0','','','Package is sent.')
";
    $installer->run($sql);
}
