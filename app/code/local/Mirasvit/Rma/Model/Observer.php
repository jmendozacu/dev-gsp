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



class Mirasvit_Rma_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Add comment to rma from helpdesk email.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onHelpdeskProcessEmail($observer)
    {
        $event = $observer->getEvent();
        $ticket = $event->getTicket();
        $customer = $event->getCustomer();
        $user = $event->getUser();

        $text = $event->getBody();
        if (!$rmaId = $ticket->getRmaId()) {
            return;
        }
        $rma = Mage::getModel('rma/rma')->load($rmaId);
        if (!$rma->getId()) {
            return;
        }

        $rma->addComment($text, false, $customer, $user, true, true, true, true);
    }

    /**
     * Save rma id to session when create exchange order in the backend.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCreateOrderSessionQuoteInitialized($observer)
    {
        $session = $observer->getSessionQuote();
        if ($rmaId = Mage::app()->getRequest()->getParam('rma_id')) {
            $session->setRmaId($rmaId);
        }
    }

    /**
     * Save exchange order id to rma.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCheckoutSubmitAllAfter($observer)
    {
        $order = $observer->getOrder();
        $session = Mage::getSingleton('adminhtml/session_quote');
        if ($rmaId = $session->getRmaId()) {
            $rma = Mage::getModel('rma/rma')->load($rmaId);
            $ids = $rma->getExchangeOrderIds();
            $ids[] = $order->getId();
            $rma->setExchangeOrderIds($ids);
            $rma->save();
            $session->unsetRmaId();
        }
    }

    /**
     * Save rma id to session when create credit memo in the backend.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onSalesOrderCreditmemoRegisterBefore($observer)
    {
        if ($rmaId = Mage::app()->getRequest()->getParam('rma_id')) {
            Mage::getSingleton('adminhtml/session')->setRmaId($rmaId);
        }
    }

    /**
     * Save credit memo id to rma.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onSalesOrderCreditmemoSaveAfter($observer)
    {
        $creditmemo = $observer->getDataObject();
        $session = Mage::getSingleton('adminhtml/session');
        if ($rmaId = $session->getRmaId()) {
            $rma = Mage::getModel('rma/rma')->load($rmaId);
            $ids = $rma->getCreditMemoIds();
            $ids[] = $creditmemo->getId();
            $rma->setCreditMemoIds($ids);
            $rma->save();
            $session->unsetRmaId();
        }
    }
}
