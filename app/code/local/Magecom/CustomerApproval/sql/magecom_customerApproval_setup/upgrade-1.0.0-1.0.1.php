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
$content = "{{block type=\"catalog/product_new\" template=\"catalog/product/new.phtml\" products_count=\"10\" hide_button=\"0\" block_name=\"Nya
produkter\" pagination=\"1\" timeout=\"4000\" centered=\"1\" size=\"size-s\" loop=\"1\"}}";
$page = Mage::getModel('cms/page')->load('home', 'identifier');
$page->setData('content',$content);
$page->save();
$installer->endSetup();
