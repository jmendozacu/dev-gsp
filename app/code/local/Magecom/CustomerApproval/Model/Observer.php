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
 * Magecom_CustomerApproval_Model_Observer class
 *
 * @category Magecom
 * @author Magecom
 */

class Magecom_CustomerApproval_Model_Observer
{
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('customerApproval');
    }

    /**
     *Checked credentional user
     *
     * @param $observer
     */
    public function restrictAccess($observer)
    {
        if (!$this->_helper->getModuleStatus()) return;

        $action = $observer->getControllerAction();
        $routeName = $action->getRequest()->getRouteName();
        $customer = Mage::getModel('customer/session')->getCustomer();

        if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), "googlebot")) {
            return;
        }

        if ($routeName == 'api' || $_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        if (isset($customer) && $customer->getGroupId() != $this->_helper->getNewCustomerGroupId()) {
            return;
        }

        $this->updateLayout();

        if ($this->_helper->getAccessibleUrls()) {
            foreach ($this->_helper->getAccessibleUrls() as $route){
                if (strpos($routeName, $route) !== false) return;
            }
        }

        $action->getResponse()->setRedirect('/');
    }

    /**
     *Delete blocks if user not logged or not approval
     */
    public function updateLayout()
    {
        $update = Mage::app()->getLayout()->getUpdate();

        if ($this->_helper->getBlocksToDelete()) {
            foreach ($this->_helper->getBlocksToDelete() as $blockId){
                $update->addUpdate('<remove name="' . $blockId . '"/>');
            }
        }
    }

    /**
     * Send email for admin
     */
    public function sendAdminEmail()
    {
        $emailTemplate = $this->_helper->getCustomerRegistrationEmailTemplate();
        $customerApprovalAdminEmail = $this->_helper->getCustomerApprovalAdminEmail();

        if ($customerApprovalAdminEmail) {
            $this->_helper->sendEmail($emailTemplate, 'New customer registration', $customerApprovalAdminEmail);
        } else {
            $this->_helper->sendEmail($emailTemplate, 'New customer registration');
        }
    }

    /**
     * Send user confirm email
     *
     * @param $obs
     */
    public function sendUserApprovalEmail($obs)
    {
        $customer = $obs->getEvent()->getCustomer();
        $customerPrevGroup = $customer->getOrigData('group_id');
        $customerCurrentGroup = $customer->getData('group_id');
        $newCustomerGroupId = $this->_helper->getNewCustomerGroupId();
        $emailSend = $customer->getEmail();

        if ($customerPrevGroup == $newCustomerGroupId && $customerCurrentGroup != $newCustomerGroupId) {
            $emailTemplate = $this->_helper->getCustomerApprovalAdminConfirmTemplate();
            $this->_helper->sendEmail($emailTemplate, 'Your account has been activated on G-SP', $emailSend);
        }
    }
}