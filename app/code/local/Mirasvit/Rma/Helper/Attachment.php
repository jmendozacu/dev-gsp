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



class Mirasvit_Rma_Helper_Attachment extends Mage_Core_Helper_Abstract
{
    public function getConfig()
    {
        return Mage::getSingleton('rma/config');
    }

    public function getAllowedSize()
    {
        return $this->getConfig()->getGeneralFileSizeLimit();
    }

    public function getAllowedExtensions()
    {
        return $this->getConfig()->getGeneralFileAllowedExtensions();
    }

    public function getAttachmentLimits()
    {
        $message = array();
        $allowedExtensions = $this->getAllowedExtensions();
        if (count($allowedExtensions)) {
            $message[] = $this->__('Allowed extensions:').' '.implode(', ', $allowedExtensions);
        }
        if ($allowedSize = $this->getAllowedSize()) {
            $message[] = $this->__('Maximum size:').' '.$allowedSize.'Mb';
        }

        return implode('<br>', $message);
    }
}
