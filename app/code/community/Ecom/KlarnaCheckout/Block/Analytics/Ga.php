<?php

/**
 * Class Ecom_KlarnaCheckout_Block_Analytics_Ga
 */
class Ecom_KlarnaCheckout_Block_Analytics_Ga extends Mage_GoogleAnalytics_Block_Ga {


    /**
     * Render information about specified orders and their items
     *
     * @return string
     */
    protected function _getOrdersTrackingCode()
    {
        // backwards compatibility (Magento PRE 1.9 printed tracking in this method.)
        if(!method_exists($this->helper('googleanalytics'),'isUseUniversalAnalytics')) return $this->_getOrdersTrackingCodeAnalytics();
        else return parent::_getOrdersTrackingCode();
    }

    /**
     * Render information about specified orders and their items
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html#_gat.GA_Tracker_._addTrans
     * @return string
     */
    protected function _getOrdersTrackingCodeAnalytics() {

        $result = array();

        // backwards compatibility (Magento PRE 1.9 printed tracking in this method.)
        if(!method_exists($this->helper('googleanalytics'),'isUseUniversalAnalytics')) $result[] = parent::_getOrdersTrackingCode();
        else $result[] = parent::_getOrdersTrackingCodeAnalytics();

        if(!($order = $this->getKlarnaCheckoutOrder())) return implode("\n", $result);

        $result[] ="/* Tracking by KlarnaChekout */";

        $cart = $order->getCart();
        $shipping = 0;
        $cartItems = (isset($cart['items']) && is_array($cart['items'])) ? $cart['items'] : array();
        $grandTotal = isset($cart['total_price_excluding_tax']) ? $cart['total_price_excluding_tax'] / 100 : 0;
        $tax = isset($cart['total_tax_amount']) ? $cart['total_tax_amount'] / 100 : 0;

        foreach ($cartItems as $item) {
            if (isset($item['type']) && $item['type'] == 'shipping_fee' && isset($item['total_price_including_tax'])) {
                $shipping += $item['total_price_including_tax'] / 100;
                $grandTotal -= $item['total_price_including_tax'] / 100;
            }
        }

        $address = $order->getBillingInfo();

        $result[] = sprintf("_gaq.push(['_addTrans', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);",
            $order->getReservation(),
            $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
            number_format($grandTotal, 2, '.', ''),
            number_format($tax, 2, '.', ''),
            number_format($shipping, 2, '.', ''),
            $this->jsQuoteEscape(Mage::helper('core')->escapeHtml(isset($address['city']) ? $address['city'] : '')),
            $this->jsQuoteEscape(''), // region
            $this->jsQuoteEscape(Mage::helper('core')->escapeHtml(isset($address['country']) ? strtoupper($address['country']) : ''))
        );

        foreach ($cartItems as $item) {
            if (isset($item['type']) && $item['type'] == 'physical') {

                $result[] = sprintf("_gaq.push(['_addItem', '%s', '%s', '%s', '%s', '%s', '%s']);",
                    $order->getReservation(),
                    $this->jsQuoteEscape(isset($item['reference']) ? $item['reference'] : ''),
                    $this->jsQuoteEscape(isset($item['name']) ? $item['name'] : ''),
                    null, // category
                    number_format(isset($item['unit_price']) ? $item['unit_price'] / 100 : '', 2, '.', ''),
                    number_format(isset($item['quantity']) ? $item['quantity'] : '', 2, '.', '')
                );
            }
        }

        $result[] = "_gaq.push(['_trackTrans']);";

        return implode("\n", $result);
    }

    /**
     * Render information about specified orders and their items
     *
     * @return string
     */
    protected function _getOrdersTrackingCodeUniversal() {

        $result = array();
        $result[] = parent::_getOrdersTrackingCodeUniversal();

        if(!($order = $this->getKlarnaCheckoutOrder())) return implode("\n", $result);

        $result[] ="/* Tracking by KlarnaChekout */";

        $cart = $order->getCart();
        $shipping = 0;
        $cartItems = (isset($cart['items']) && is_array($cart['items'])) ? $cart['items'] : array();
        $grandTotal = isset($cart['total_price_excluding_tax']) ? $cart['total_price_excluding_tax'] / 100 : 0;
        $tax = isset($cart['total_tax_amount']) ? $cart['total_tax_amount'] / 100 : 0;

        foreach ($cartItems as $item) {
            if (isset($item['type']) && $item['type'] == 'shipping_fee' && isset($item['total_price_including_tax'])) {
                $shipping += $item['total_price_including_tax'] / 100;
                $grandTotal -= $item['total_price_including_tax'] / 100;
            }
        }

        $result[] = "ga('require', 'ecommerce')";

        $result[] = sprintf("ga('ecommerce:addTransaction', {
'id': '%s',
'affiliation': '%s',
'revenue': '%s',
'tax': '%s',
'shipping': '%s'
});",
            $order->getReservation(),
            $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
            number_format($grandTotal, 2, '.', ''),
            number_format($tax, 2, '.', ''),
            number_format($shipping, 2, '.', '')
        );


        foreach ($cartItems as $item) {
            if (isset($item['type']) && $item['type'] == 'physical') {

                $result[] = sprintf("ga('ecommerce:addItem', {
'id': '%s',
'sku': '%s',
'name': '%s',
'category': '%s',
'price': '%s',
'quantity': '%s'
});",
                    $order->getReservation(),
                    $this->jsQuoteEscape(isset($item['reference']) ? $item['reference'] : ''),
                    $this->jsQuoteEscape(isset($item['name']) ? $item['name'] : ''),
                    null, // category
                    number_format(isset($item['unit_price']) ? $item['unit_price'] / 100 : '', 2, '.', ''),
                    number_format(isset($item['quantity']) ? $item['quantity'] : '', 2, '.', '')
                );
            }
        }
        $result[] = "ga('ecommerce:send');";

        return implode("\n", $result);
    }

    /**
     * @return Ecom_KlarnaCheckout_Model_Klarnaorder
     */
    public function getKlarnaCheckoutOrder(){
        return Mage::registry("klarna_checkout_order_success");
    }
}
