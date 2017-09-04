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



class Mirasvit_Rma_Block_Rma_Guest_New extends Mirasvit_Rma_Block_Rma_Guest_Abstract
{
    public function getStep1PostUrl()
    {
        return Mage::getUrl('rma/guest/new');
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getOrderIncrementId()
    {
        return Mage::app()->getRequest()->getParam('order_increment_id');
    }

    public function getEmail()
    {
        return Mage::app()->getRequest()->getParam('email');
    }
}
