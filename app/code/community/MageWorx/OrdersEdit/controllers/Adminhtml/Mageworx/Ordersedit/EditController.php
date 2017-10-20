<?php

/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersEdit_Adminhtml_Mageworx_Ordersedit_EditController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $origOrder;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * @var array
     */
    protected $pendingChanges = array();

    /**
     * Id of the currently edited block
     *
     * @var string
     */
    protected $blockId;

    /**
     * @var bool
     */
    protected $surchargeFlag = false;

    /**
     * Initialize main models and store important data from the request
     *
     * @param bool $applyNewChanges
     * @return $this
     */
    protected function init($applyNewChanges = false)
    {
        // Get base order id and load order and quote
        $orderId = $this->getRequest()->getParam('order_id');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        $this->order = $order;
        $this->origOrder = clone $order;
        Mage::register('ordersedit_order', $order);

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('mageworx_ordersedit/edit')->getQuoteByOrder($order);

        // Get id of the currently edited block
        $blockId = $this->getRequest()->getParam('block_id');
        $editedBlock = $this->getRequest()->getParam('edited_block');
        $this->blockId = $blockId ? $blockId : $editedBlock;
        Mage::register('ordersedit_block_id', $blockId);

        // Get pending changes
        $pendingChanges = $this->getMwEditHelper()->getPendingChanges($orderId);
        if ($applyNewChanges) {
            $data = $this->getRequest()->getPost();
            $pendingChanges = $this->getMwEditHelper()->addPendingChanges($orderId, $data);
            $this->order->addData($data);
        }

        $surcharge = $this->getRequest()->getParam('surcharge');
        if ($surcharge) {
            $pendingChanges['surcharge'] = $surcharge;
            $this->surchargeFlag = true;
        }

        $this->pendingChanges = $pendingChanges;
        Mage::register('ordersedit_pending_changes', $pendingChanges);

        // Update quote if pending changes exists
        if (!empty($pendingChanges)) {
            $quote = Mage::getSingleton('mageworx_ordersedit/edit_quote')->applyDataToQuote($quote, $pendingChanges);
        }
        $this->quote = $quote;
        Mage::register('ordersedit_quote', $quote);

        return $this;
    }

    /**
     * Load form to edit specific block of order
     *
     * @return $this
     */
    public function loadEditFormAction()
    {
        $this->init();
        $block = $this->getBlockDataById();
        if (!$block || !$this->order) {
            return $this;
        }

        if (empty($this->pendingChanges)) {
            if (Mage::getModel('tax/config')->shippingPriceIncludesTax()) {
                Mage::getSingleton('adminhtml/session_quote')
                    ->setData('base_shipping_custom_price', $this->order->getBaseShippingInclTax());
            } else {
                Mage::getSingleton('adminhtml/session_quote')
                    ->setData('base_shipping_custom_price', $this->order->getBaseShippingAmount());
            }
            $this->removeTempQuoteItems();
        }

        /** @var Mage_Core_Block_Abstract $form */
        $form = $this->getLayout()->createBlock($block['block']);
        $form->addData(array('quote' => $this->quote, 'order' => $this->order));

        $buttons = $this->getLayout()->createBlock('core/template')
            ->setTemplate('mageworx/ordersedit/edit/buttons.phtml');

        // Render messages block
        $errors = $this->getLayout()->createBlock('adminhtml/messages')
            ->setMessages(Mage::getSingleton('adminhtml/session')->getMessages(true))
            ->getGroupedHtml();
        $html = $errors . $form->toHtml() . $buttons->toHtml();
        $html = str_replace('var VatParameters', 'VatParameters', $html);
        $this->getResponse()->setBody($html);

        return $this;
    }

    /**
     * Apply changes to the order & quote
     */
    public function applyChangesAction()
    {
        try {
            $this->init(true);
            if ($this->blockId != 'false') {
                $blockData = $this->getBlockDataById();
                /** @var Mage_Core_Block_Abstract $block */
                $block = $this->getLayout()->createBlock($blockData['changedBlock']);

                $data = array(
                    'quote' => $this->quote,
                    'order' => $this->order
                );

                if ($this->blockId == 'shipping_address') {
                    $data['address_type'] = 'shipping';
                } elseif ($this->blockId == 'billing_address') {
                    $data['address_type'] = 'billing';
                }

                $block->addData($data);

                $noticeHtml = $this->getLayout()->createBlock('core/template')
                    ->setTemplate('mageworx/ordersedit/changed/notice.phtml')
                    ->toHtml();
                $result[$this->blockId] = $noticeHtml . $block->toHtml();
            }

            $result['temp_totals'] = $this->getTempTotalsBlockHtml();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

        } catch (Exception $e) {
            $result = array('exception' => '1');
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        return $this;
    }

    /**
     * Save order with changes from the quote
     * Final step
     */
    public function saveOrderAction()
    {
        $this->init();
        /** @var MageWorx_OrdersEdit_Model_Edit $editModel */
        $editModel = Mage::getSingleton('mageworx_ordersedit/edit');
        /** @var MageWorx_OrdersEdit_Model_Edit_Quote $editQuoteModel */
        $editQuoteModel = Mage::getSingleton('mageworx_ordersedit/edit_quote');

        try {

            // We can not save the order with the grand total smaller than 0
            if ($this->quote->getBaseGrandTotal() < 0) {
                throw new Exception('GT < 0');
            }
            // Applies the pending changes to the quote and save the order
            $editModel->setQuote($this->quote);
            $editModel->setOrder($this->order);
            $editModel->setChanges($this->pendingChanges);
            $editModel->saveOrder();

            // Removes "is_temporary" flag from the items
            $editQuoteModel->saveTemporaryItems($this->quote, 0, false);

            Mage::dispatchEvent('mwoe_save_order_after', array(
                'quote' => $this->quote,
                'order' => $this->order,
                'orig_order' => $this->origOrder
            ));

            // Create an invoice or credit memo for the saved order if needed
            $this->afterSaveOrder();

            // Remove pending changes from the session
            $this->resetPendingChanges();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order changes have been saved'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')
                ->addError($this->__('An error occurred while saving the order ' . $e->getMessage()));
        }

        $this->_redirectReferer();
    }

    /**
     * Unset all temporary quote data
     */
    public function cancelChangesAction()
    {
        $this->init();
        $this->removeTempQuoteItems();
        $this->resetPendingChanges();
        Mage::getSingleton('adminhtml/session_quote')->unsetData();
        Mage::getSingleton('adminhtml/session_quote')
            ->setData('base_shipping_custom_price', $this->order->getBaseShippingAmount());
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order changes have been canceled'));

        $this->_redirectReferer();
    }

    /**
     * Load customer grid
     */
    public function customersGridAction()
    {
        $grid = $this->getLayout()->createBlock('mageworx_ordersedit/adminhtml_sales_order_edit_form_customer_grid');
        $this->getResponse()->setBody($grid->toHtml());
    }

    /**
     * load product grid
     */
    public function productGridAction()
    {
        $this->init();
        $grid = $this->getLayout()->createBlock('mageworx_ordersedit/adminhtml_sales_order_edit_form_items_grid');
        $grid->setData('order', $this->order);
        $this->getResponse()->setBody($grid->toHtml());
    }

    /**
     * Apply new customer to order (only imports data to form)
     */
    public function submitCustomerAction()
    {
        $customerId = $this->getRequest()->getParam('id');
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $this->getResponse()->setBody(Zend_Json::encode($customer->getData()));
    }

    /**
     * Creating invoice or credit memo for the saved order
     * (based on the module configuration and surcharge module used)
     *
     * @return $this
     */
    protected function afterSaveOrder()
    {
        /** @var countable $invoices */
        $invoices = $this->order->getInvoiceCollection();
        $invoicesCount = count($invoices);
        if (!$invoicesCount) {
            // Do not process order without invoices
            return $this;
        }

        $alreadyPaidBase = $this->origOrder->getBaseTotalPaid() - $this->origOrder->getBaseTotalRefunded();
        $ordersBaseGT = $this->order->getBaseGrandTotal();
        $ordersBaseCanceled = $this->origOrder->getBaseTotalCanceled();

        if ($ordersBaseGT >= $alreadyPaidBase) {

            // Do not invoice surcharge order
            if ($this->surchargeFlag) {
                return $this;
            }

            // Create invoice if needed
            Mage::getSingleton('mageworx_ordersedit/edit_creditmemo')->refundChanges(
                $this->origOrder,
                $this->order,
                true
            );
            Mage::getSingleton('mageworx_ordersedit/edit_invoice')->invoiceChanges(
                $this->origOrder,
                $this->order,
                Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE
            );

        } elseif ($ordersBaseGT < ($alreadyPaidBase - $ordersBaseCanceled)) {

            // Create refund if needed
            Mage::getSingleton('mageworx_ordersedit/edit_invoice')->invoiceChanges(
                $this->origOrder,
                $this->order,
                Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE,
                true
            );
            Mage::getSingleton('mageworx_ordersedit/edit_creditmemo')->refundChanges(
                $this->origOrder,
                $this->order
            );
        }

        return $this;
    }

    /**
     * Get blocks data from the edit helper by block id
     *
     * @see MageWorx_OrdersEdit_Helper_Edit::getAvailableBlocks()
     * @param null $blockId
     * @return array
     */
    protected function getBlockDataById($blockId = null)
    {
        $editHelper = $this->getMwEditHelper();
        $blockId = $blockId ? $blockId : $this->blockId;
        $data = $editHelper->getBlockDataById($blockId);

        return $data;
    }

    /**
     * Remove all pending changes from the session
     *
     * @param $orderId
     * @return $this
     */
    protected function resetPendingChanges($orderId = null)
    {
        $orderId = $orderId ? $orderId : $this->order->getId();
        $this->getMwEditHelper()->resetPendingChanges($orderId);
    }

    /**
     * Removes the temporary quote items
     *
     * @param Mage_Sales_Model_Order|null $order
     * @throws Exception
     */
    protected function removeTempQuoteItems(Mage_Sales_Model_Order $order = null)
    {
        $editHelper = $this->getMwEditHelper();
        $order = $order ? $order : $this->order;
        if (!$order instanceof Mage_sales_Model_Order) {
            throw new Exception('Unknown object ' . get_class($order) . ' was given. Mage_Sales_Model_Order expected.');
        }

        $editHelper->removeTempQuoteItems($order);
    }

    /**
     * Render temp totals (preview)
     *
     * @return string
     */
    protected function getTempTotalsBlockHtml()
    {
        /** @var array $totals */
        $totals = $this->quote->getTotals();
        $tempTotalsBlock = Mage::getSingleton('core/layout')->createBlock(
            'mageworx_ordersedit/adminhtml_sales_order_totals',
            'temp_totals',
            array(
                'totals' => $totals,
                'order' => $this->order,
                'quote' => $this->quote
            )
        );
        $tempTotalsHtml = $tempTotalsBlock->toHtml();

        return $tempTotalsHtml;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/mageworx_ordersedit');
    }

    /**
     * @return MageWorx_OrdersEdit_Helper_Edit
     */
    protected function getMwEditHelper()
    {
        return Mage::helper('mageworx_ordersedit/edit');
    }

    /**
     * @return MageWorx_OrdersEdit_Helper_Data
     */
    protected function getMwHelper()
    {
        return Mage::helper('mageworx_ordersedit');
    }
}