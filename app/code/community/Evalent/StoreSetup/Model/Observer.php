<?php

class Evalent_StoreSetup_Model_Observer {
    public function intercept($observer = null) {

        $helper = Mage::helper('storesetup');

        $helper->log('Evalent_StoreSetup_Model_Observer');

        $configValue = $helper->getConfigValue('active');
        if ($configValue) {
            $configValue = $helper->getConfigValue('initial_configuration_tax');

            if ($configValue) {

                $helper->setConfigValue('tax/calculation/algorithm', 'TOTAL_BASE_CALCULATION', $store = null);
                $helper->setConfigValue('tax/calculation/based_on', 'shipping', $store = null);
                $helper->setConfigValue('tax/calculation/price_includes_tax', '1', $store = null);
                $helper->setConfigValue('tax/calculation/shipping_includes_tax', '1', $store = null);
                $helper->setConfigValue('tax/calculation/apply_after_discount', '1', $store = null);
                $helper->setConfigValue('tax/calculation/discount_tax', '1', $store = null);
                $helper->setConfigValue('tax/calculation/apply_tax_on', '0', $store = null);
                $helper->setConfigValue('tax/calculation/cross_border_trade_enabled', '0', $store = null);
                $helper->log('Evalent_StoreSetup_Model_Observer:intercept:true', null);

            } else {

                $helper->setConfigValue('tax/calculation/algorithm', 'TOTAL_BASE_CALCULATION', $store = null);
                $helper->setConfigValue('tax/calculation/based_on', 'shipping', $store = null);
                $helper->setConfigValue('tax/calculation/price_includes_tax', '0', $store = null);
                $helper->setConfigValue('tax/calculation/shipping_includes_tax', '0', $store = null);
                $helper->setConfigValue('tax/calculation/apply_after_discount', '1', $store = null);
                $helper->setConfigValue('tax/calculation/discount_tax', '0', $store = null);
                $helper->setConfigValue('tax/calculation/apply_tax_on', '0', $store = null);
                $helper->setConfigValue('tax/calculation/cross_border_trade_enabled', '0', $store = null);
                $helper->log('Evalent_StoreSetup_Model_Observer:intercept:false', null);
            }
        }
    }
}
