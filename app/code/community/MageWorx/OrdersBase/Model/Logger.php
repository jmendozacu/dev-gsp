<?php

/**
 * MageWorx
 * MageWorx Order Base Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersBase
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersBase_Model_Logger extends Mage_Core_Model_Abstract
{
    /**
     * Add comment and save order
     *
     * @param $text
     * @param Mage_Sales_Model_Order|object $entity
     * @param int $notify (0 - no one; 1 - only admin; 2 - notify all)
     * @return $this
     * @throws Exception
     */
    public function log($text, $entity, $notify)
    {
        if ($entity instanceof Mage_Sales_Model_Order) {
            $order = $entity;
        } elseif (is_object($entity)) {
            $order = $entity->getOrder();
            if (!($order instanceof Mage_Sales_Model_Order)) {
                return $this;
            }
        } else {
            return $this;
        }

        $order->addStatusHistoryComment($text, $order->getStatus())
            ->setIsVisibleOnFront(1)
            ->setIsCustomerNotified($notify > 1);

        if ($notify) {
            $order->sendOrderUpdateEmail($notify > 1, $text);
        }

        $order->save();

        return $this;
    }
}