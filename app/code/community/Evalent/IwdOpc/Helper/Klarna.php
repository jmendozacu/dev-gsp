<?php
class Evalent_IwdOpc_Helper_Klarna extends Vaimo_Klarna_Helper_Data{

    /**
     * Check if OneStepCheckout/Iwd_Opc is activated or not
     * It also checks if OneStepCheckout/Iwd_Opc is activated, but it's currently using
     * standard checkout
     *
     * @return bool
     */
    public function isOneStepCheckout($store = null)
    {
        # backwards compat
        if(parent::isOneStepCheckout($store)) return true;


        $res = false;
        if (Mage::getStoreConfig('opc/global/status', $store)) {
            $res = true;
            $request = Mage::app()->getRequest();
            $requestedRouteName = $request->getRequestedRouteName();
            $requestedControllerName = $request->getRequestedControllerName();
            if ($requestedRouteName == 'checkout' && $requestedControllerName == 'onepage') {
                $res = false;
            }
        }
        return $res;
    }
}
