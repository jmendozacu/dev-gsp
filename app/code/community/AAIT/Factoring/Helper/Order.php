<?php

/**
 * PayEx Helper: Order
 * Created by AAIT Team.
 */
class AAIT_Factoring_Helper_Order extends Mage_Core_Helper_Abstract
{

    /**
     * Create transaction
     * @note: Use for only first transaction
     * @param $payment
     * @param $parentTransactionId
     * @param $transactionId
     * @param $type
     * @param int $IsTransactionClosed
     * @param array $fields
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function createTransaction(&$payment, $parentTransactionId, $transactionId, $type, $IsTransactionClosed = 0, $fields = array())
    {
        $failsafe = true;
        $ShouldCloseParentTransaction = true;

        // set transaction parameters
        $transaction = Mage::getModel('sales/order_payment_transaction')
            ->setOrderPaymentObject($payment)
            ->setTxnType($type)
            ->setTxnId($transactionId)
            ->isFailsafe($failsafe);

        $transaction->setIsClosed($IsTransactionClosed);

        // Set transaction addition information
        if (count($fields) > 0) {
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $fields);
        }

        // link with sales entities
        $payment->setLastTransId($transactionId);
        $payment->setCreatedTransaction($transaction);
        $payment->getOrder()->addRelatedObject($transaction);

        // link with parent transaction
        if ($parentTransactionId) {
            $transaction->setParentTxnId($parentTransactionId);
            // Close parent transaction
            if ($ShouldCloseParentTransaction) {
                $parentTransaction = $payment->getTransaction($parentTransactionId);
                if ($parentTransaction) {
                    $parentTransaction->isFailsafe($failsafe)->close(false);
                    $payment->getOrder()->addRelatedObject($parentTransaction);
                }
            }
        }

        return $transaction;
    }

    /**
     * Create Invoice
     * @param $order
     * @param bool $online
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function makeInvoice(&$order, $online = false)
    {

        if ($order->canInvoice() == false) {
            // when order cannot create invoice, need to have some logic to take care
            $order->addStatusToHistory(
                $order->getStatus(), // keep order status/state
                Mage::helper('paygate')->__('Error in creating an invoice', true),
                true /* notified */
            );
            return false;
        }

        // Prepare Invoice
        $magento_version = Mage::getVersion();
        if (version_compare($magento_version, '1.4.2', '>=')) {
            $invoice = Mage::getModel('sales/order_invoice_api_v2');
            $invoice_id = $invoice->create($order->getIncrementId(), $order->getAllItems(), Mage::helper('factoring')->__('Auto-generated from PayEx module'), false, false);
            $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoice_id);

            if ($online) {
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->capture()->save();
            } else {
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->pay()->save();
            }
        } else {
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
            $invoice->addComment(Mage::helper('factoring')->__('Auto-generated from PayEx module'), false, false);
            $invoice->setRequestedCaptureCase($online ? Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE : Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $invoice->getOrder()->setIsInProcess(true);

            try {
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
            } catch (Mage_Core_Exception $e) {
                // Save Error Message
                $order->addStatusToHistory(
                    $order->getStatus(),
                    'Failed to create invoice: ' . $e->getMessage(),
                    true
                );
                Mage::throwException($e->getMessage());
            }
        }

        $invoice->setIsPaid(true);

        // Assign Last Transaction Id with Invoice
        $transactionId = $invoice->getOrder()->getPayment()->getLastTransId();
        if ($transactionId) {
            $invoice->setTransactionId($transactionId);
            $invoice->save();
        }

        return $invoice;
    }

    /**
     * Change Order State, using Direct SQL
     * @param $order_id
     * @param $state
     * @return bool
     */
    public function changeOrderState($order_id, $state)
    {
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        try {
            $query = "UPDATE `" . Mage::getSingleton('core/resource')->getTableName('sales_flat_order') . "` SET state='$state', status='$state' WHERE increment_id = '$order_id';";
            $db->query($query);
            $query = "UPDATE `" . Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid') . "` SET status='$state' WHERE increment_id = '$order_id';";
            $db->query($query);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get First Transaction ID
     * @param  $order Mage_Sales_Model_Order
     * @return bool
     */
    static public function getFirstTransactionId(&$order)
    {
        $order_id = $order->getId();
        if (!$order_id) {
            return false;
        }
        $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addOrderIdFilter($order_id)
            ->setOrder('transaction_id', 'ASC')
            ->setPageSize(1)
            ->setCurPage(1);
        return $collection->getFirstItem()->getTxnId();
    }

    /**
     * Create CreditMemo
     * @param $order
     * @param $invoice
     * @param $amount
     * @param bool $online
     * @param null $transactionId
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function makeCreditMemo(&$order, &$invoice, $amount, $online = false, $transactionId = null)
    {
        $service = Mage::getModel('sales/service_order', $order);

        // Prepare CreditMemo
        if ($invoice) {
            $creditmemo = $service->prepareInvoiceCreditmemo($invoice);
        } else {
            $creditmemo = $service->prepareCreditmemo();
        }
        $creditmemo->addComment(Mage::helper('factoring')->__('Auto-generated from PayEx module'));

        // Refund
        if (!$online) {
            $creditmemo->setPaymentRefundDisallowed(true);
        }
        //$creditmemo->setRefundRequested(true);
        $invoice->getOrder()->setBaseTotalRefunded(0);
        $creditmemo->setBaseGrandTotal($amount);
        $creditmemo->register()->refund();
        $creditmemo->save();

        // Add transaction Id
        if ($transactionId) {
            $creditmemo->setTransactionId($transactionId);
        }
        // Save CreditMemo
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();

        return $creditmemo;
    }


    /**
     * Rollback stock
     * @param $order
     * @return void
     */
    public function rollbackStockItems(&$order)
    {
        $items = $order->getAllItems(); // Get all items from the order
        if ($items) {
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $quantity = $item->getQtyOrdered(); // get Qty ordered
                $product_id = $item->getProductId(); // get it's ID
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id); // Load the stock for this product
                $stock->setQty($stock->getQty() + $quantity); // Set to new Qty
                $stock->save(); // Save
            }
        }

    }

    /**
     * Get Order Amount
     * With Using Rounding Issue Fix
     * @param Mage_Sales_Model_Order $order
     * @return float
     */
    public function getOrderAmount($order)
    {
        // At moment this function don't support discounts
        if (abs($order->getDiscountAmount()) > 0) {
            return $order->getGrandTotal();
        }

        $amount = 0;
        // add Order Items
        $items = $order->getAllVisibleItems();
        /** @var $item Mage_Sales_Model_Order_Item */
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $itemQty = (int)$item->getQtyOrdered();
            $priceWithTax = $item->getPriceInclTax();
            $amount += round(100 * Mage::app()->getStore()->roundPrice($itemQty * $priceWithTax));
        }

        // add Fee
        $fee = Mage::getSingleton('factoring/fee')->getPaymentFee();
        if ($fee > 0) {
            $amount += (int)(100 * $fee);
        }

        // add Discount
        $discount = $order->getDiscountAmount();
        $discount += $order->getShippingDiscountAmount();
        $amount += round(100 * $discount);

        // add Shipping
        if (!$order->getIsVirtual()) {
            $shippingIncTax = $order->getShippingInclTax();
            $amount += round(100 * $shippingIncTax);
        }

        $grand_total = $order->getGrandTotal();
        $amount = $amount / 100;

        $abs = abs(Mage::app()->getStore()->roundPrice($amount) - Mage::app()->getStore()->roundPrice($grand_total));
        // Is ~0.010000000002037
        if ($abs > 0 && $abs < 0.011) {
            Mage::helper('factoring/tools')->addToDebug('Warning: Price rounding issue. ' . $grand_total . ' vs ' . $amount);
            return $amount;
        } else {
            return $grand_total;
        }
    }

    /**
     * Generate Invoice Print XML
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function getInvoiceExtraPrintBlocksXML($order)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $OnlineInvoice = $dom->createElement('OnlineInvoice');
        $dom->appendChild($OnlineInvoice);
        $OnlineInvoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $OnlineInvoice->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsd', 'http://www.w3.org/2001/XMLSchema');

        $OrderLines = $dom->createElement('OrderLines');
        $OnlineInvoice->appendChild($OrderLines);

        // Add Order Lines
        $items = $order->getAllVisibleItems();
        /** @var $item Mage_Sales_Model_Order_Item */
        foreach ($items as $item) {
            // @todo Calculate prices using Discount Rules
            // @todo Get children products from bundle
            if (!$item->getNoDiscount()) {
                Mage::helper('factoring/tools')->addToDebug('Warning: The product has a discount. There might be problems.', $order->getIncrementId());
            }

            $itemQty = (int)$item->getQtyOrdered();
            //$taxPrice = $item->getTaxAmount();
            $taxPrice = Mage::app()->getStore()->roundPrice($itemQty * $item->getPriceInclTax() - $itemQty * $item->getPrice());
            $taxPercent = $item->getTaxPercent();
            $priceWithTax = Mage::app()->getStore()->roundPrice($itemQty * $item->getPriceInclTax());

            // Calculate tax percent for Bundle products
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $taxPercent = ($taxPrice > 0) ? round(100 / (($priceWithTax - $taxPrice) / $taxPrice)) : 0;
            }

            $OrderLine = $dom->createElement('OrderLine');
            $OrderLine->appendChild($dom->createElement('Product', $item->getName()));
            $OrderLine->appendChild($dom->createElement('Qty', $itemQty));
            $OrderLine->appendChild($dom->createElement('UnitPrice', $item->getPrice()));
            $OrderLine->appendChild($dom->createElement('VatRate', $taxPercent));
            $OrderLine->appendChild($dom->createElement('VatAmount', $taxPrice));
            $OrderLine->appendChild($dom->createElement('Amount', $priceWithTax));
            $OrderLines->appendChild($OrderLine);
        }

        // add Discount
        $discount = $order->getDiscountAmount();

        // exclude shipping discount
        // discount is negative value
        $discount += $order->getShippingDiscountAmount();

        if (abs($discount) > 0) {
            $discount_descriiption = ($order->getDiscountDescription() !== null) ? Mage::helper('sales')->__('Discount (%s)', $order->getDiscountDescription()) : Mage::helper('sales')->__('Discount');

            $OrderLine = $dom->createElement('OrderLine');
            $OrderLine->appendChild($dom->createElement('Product', $discount_descriiption));
            $OrderLine->appendChild($dom->createElement('Qty', 1));
            $OrderLine->appendChild($dom->createElement('UnitPrice', $discount));
            $OrderLine->appendChild($dom->createElement('VatRate', 0));
            $OrderLine->appendChild($dom->createElement('VatAmount', 0));
            $OrderLine->appendChild($dom->createElement('Amount', $discount));
            $OrderLines->appendChild($OrderLine);
        }

        // Add Shipping Line
        if (!$order->getIsVirtual()) {
            $shipping = $order->getShippingAmount();
            $shippingIncTax = $order->getShippingInclTax();
            $shippingTax = $order->getShippingTaxAmount();
            $shippingTaxPercent = $shipping != 0 ? (int)((100 * ($shippingTax) / $shipping)) : 0;

            $OrderLine = $dom->createElement('OrderLine');
            $OrderLine->appendChild($dom->createElement('Product', $order->getShippingDescription()));
            $OrderLine->appendChild($dom->createElement('Qty', 1));
            $OrderLine->appendChild($dom->createElement('UnitPrice', $shipping));
            $OrderLine->appendChild($dom->createElement('VatRate', $shippingTaxPercent));
            $OrderLine->appendChild($dom->createElement('VatAmount', $shippingTax));
            $OrderLine->appendChild($dom->createElement('Amount', $shipping + $shippingTax));
            $OrderLines->appendChild($OrderLine);
        }

        // add Payment Fee
        $fee = $order->getFactoringPaymentFee();
        if ($fee > 0) {
            $OrderLine = $dom->createElement('OrderLine');
            $OrderLine->appendChild($dom->createElement('Product', Mage::helper('factoring')->__('Payment fee')));
            $OrderLine->appendChild($dom->createElement('Qty', 1));
            $OrderLine->appendChild($dom->createElement('UnitPrice', $fee));
            $OrderLine->appendChild($dom->createElement('VatRate', 0));
            $OrderLine->appendChild($dom->createElement('VatAmount', 0));
            $OrderLine->appendChild($dom->createElement('Amount', $fee));
            $OrderLines->appendChild($OrderLine);
        }

        return str_replace("\n", '', $dom->saveXML());
    }
}