<?php

class Ecom_KlarnaCheckout_Block_Track extends Mage_Core_Block_Text {

    private $_centList = array(
        'tax_amount',
        'grand_total',
        'grand_total_incl_tax',
        'shipping',
        'shipping_incl_tax',
        'subtotal',
        'subtotal_incl_tax'
    );

    function getInfoArray(){

        $order = $this->getOrder();

        $cart = $order->getCart();
        $shipping = 0;
        $shippingTax = 0;
        $cartItems = (isset($cart['items']) && is_array($cart['items'])) ? $cart['items'] : array();
        $grandTotal = isset($cart['total_price_excluding_tax']) ? $cart['total_price_excluding_tax'] / 100 : 0;
        $tax = isset($cart['total_tax_amount']) ? $cart['total_tax_amount'] / 100 : 0;

        foreach ($cartItems as $item) {
            if (isset($item['type']) && $item['type'] == 'shipping_fee' && isset($item['total_price_excluding_tax'])) {
                $shipping += $item['total_price_excluding_tax'] / 100;
                $shippingTax += $item['total_tax_amount'] / 100;
            }
        }


        // define return variables
        $return["tax_amount"] = $tax;
        $return["grand_total"] = $grandTotal;
        $return["grand_total_incl_tax"] = $grandTotal+$return["tax_amount"];
        $return["shipping"] = $shipping;
        $return["shipping_incl_tax"] = $shipping+$shippingTax;
        $return["subtotal"] = $return["grand_total"] - $return["shipping"];
        $return["subtotal_incl_tax"] = $return["grand_total_incl_tax"]-$return["shipping_incl_tax"];
        $return["discount"] = '';
        $return["order_id"] = '';
        $return["reservation_id"] = $order->getReservation();
        $return["coupon_code"] = '';

        // add all "centable" values
        foreach($this->_centList as $key){
            $return["cent_".$key] = round($return[$key] * 100);
        }

        return $return;
    }

    /**
     * @return Ecom_KlarnaCheckout_Model_Klarnaorder
     */
    function getOrder(){
        return Mage::registry("klarna_checkout_order_success");
    }

    function getText(){

        $position = $this->getPosition(); // head, after_body_start, before_body_end
        $template = Mage::getStoreConfig("klarnacheckout/tracking/".$position);

        if(!$template) return ""; // the template is empty

        $info = $this->getInfoArray();

        $search = $replace = array();

        foreach($info as $key => $value){
            $search[] = "%".$key."%";
            $replace[] = $value;
        }

        return str_replace($search,$replace,$template);

    }

}