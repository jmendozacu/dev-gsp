<?php
/**
 * Magecom
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magecom.net so we can send you a copy immediately.
 *
 * @category Magecom
 * @package Magecom_CustomerApproval
 * @author Magecom
 * @copyright Copyright 2017 Magecom, Inc. (http://www.magecom.net)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Magecom_CustomerApproval_Helper_Data class
 *
 * @category Magecom
 * @author Magecom
 */

class Magecom_CustomerApproval_Helper_Data extends Mage_Core_Helper_Data
{
    const ACCESSIBLE_URLS_CONFIG                     = '';
    const BLOCKS_TO_DELETE_CONFIG                    = '';
    const CUSTOMER_GROUP                             = 'new_customer';
    const DEFAULT_STORE_ID_CONFIG                    = 'customer/create_account/default_group';
    const MODULE_STATUS_CONFIG                       = 'customerApproval/general/enabled';
    const CUSTOMER_APPROVAL_ADMIN_EMAIL              = 'customerApproval/general/admin_email';
    const CUSTOMER_APPROVAL_REGISTRATION_TEMPLATE    = 'magecom_customerapproval_registration';
    const CUSTOMER_APPROVAL_ADMIN_CONFIRM            = 'magecom_customerapproval_admin_confirm';

    /**
     * @var array
     */
    protected $_blocksToDelete = array(
        'top.menu',
        'ultracart.block',
        'search.block',
        'top.search',
        'ultimo.list',
        'homepage.category',
        'homepage.customerapproval.new'
    );

    /**
     *
     * @var array
     */
    protected $_accessibleUrls = array(
        'cms',
        'customer'
    );

    /**
     * Send Email
     *
     * @param $emailTemplate
     * @param $emailSubject
     * @param null $emailSend
     */
    public function sendEmail($emailTemplate, $emailSubject, $emailSend=null)
    {
        $emailTemplate = Mage::getModel('core/email_template')->loadDefault($emailTemplate);
        $emailTemplate->setTemplateSubject($emailSubject);
        $emailAdmin = Mage::getStoreConfig('trans_email/ident_general/email');
        $emailAdminName = Mage::getStoreConfig('trans_email/ident_general/name');

        if ($emailSend) {
            $emailTemplate->setSenderEmail($emailAdmin);
            $emailTemplate->setSenderName($emailAdminName);
            $emailTemplate->send($emailSend, 'admin');
        } else {
            $emailTemplate->setSenderEmail($emailAdmin);
            $emailTemplate->setSenderName($emailAdminName);
            $emailTemplate->send($emailSend, 'admin');
        }
    }

    /**
     * Get accesible urls
     *
     * @return array|mixed
     */
    public function getAccessibleUrls()
    {
        $config = Mage::getStoreConfig(self::ACCESSIBLE_URLS_CONFIG, $this->getStoreId());
        if (empty($config)) return $this->_accessibleUrls;

        return unserialize($config);
    }

    /**
     * Get blocks to delete
     *
     * @return array|mixed
     */
    public function getBlocksToDelete()
    {
        $config = Mage::getStoreConfig(self::BLOCKS_TO_DELETE_CONFIG, $this->getStoreId());
        if (empty($config)) return $this->_blocksToDelete;

        return unserialize($config);
    }

    /**
     * Get customer group
     *
     * @return mixed
     */
    public function getNewCustomerGroupId()
    {
        $customerGroupId = Mage::getStoreConfig(self::DEFAULT_STORE_ID_CONFIG, $this->getStoreId());

        if (is_int($customerGroupId)) return $customerGroupId;

        foreach (Mage::getModel('customer/group')->getCollection() as $group){
            if ($group->getCode() == self::CUSTOMER_GROUP)
                return $group->getId();
        }
    }

    /**
     * Get module status
     *
     * @return bool|mixed
     */
    public function getModuleStatus()
    {
        $config = Mage::getStoreConfig(self::MODULE_STATUS_CONFIG, $this->getStoreId());
        if (!isset($config) || empty($config)) return false;

        return $config;
    }

    /**
     * Get customerapproval admin email
     *
     * @return mixed
     */
    public function getCustomerApprovalAdminEmail()
    {
        $config = Mage::getStoreConfig(self::CUSTOMER_APPROVAL_ADMIN_EMAIL, $this->getStoreId());
        return $config;
    }

    /**
     * Get customer registration template
     *
     * @return mixed
     */
    public function getCustomerRegistrationEmailTemplate()
    {
        $template = self::CUSTOMER_APPROVAL_REGISTRATION_TEMPLATE;
        return $template;
    }

    /**
     *Get customer admin confirm customer template
     *
     * @return string
     */
    public function getCustomerApprovalAdminConfirmTemplate()
    {
        $template = self::CUSTOMER_APPROVAL_ADMIN_CONFIRM;
        return $template;
    }
}