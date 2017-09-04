<?php
class Dibspw_Dibspw_Model_Service_Quote extends Mage_Sales_Model_Service_Quote
{
    public function submitOrder()
    {
        $order = parent::submitOrder();
        $payment_method = $order->getPayment()->getMethodInstance()->getCode(); // evalent: added this whole line

        // Prevent the cart to be emptied before payment response 
        if($payment_method == "Dibspw") $this->_quote->setIsActive(true); // evalent: added check for correct method
        return $order;
    }
}
