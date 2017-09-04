<?php

/**
 *
 */
class Evalent_StoreSetup_Model_Moduleinstaller_Zendesk_Zendesk extends Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller {

    public $_version = '0.0.1';

    /**
     * Do all you need to do
     */
    public function run(){

        $this->_updateRule("admin/system/config/zendesk",'allow');
        $this->_updateRule("admin/zendesk",'allow');
        $this->_updateRule("admin/zendesk/zendesk_dashboard",'allow');
        $this->_updateRule("admin/zendesk/zendesk_create",'allow');
        $this->_updateRule("admin/zendesk/zendesk_launch",'allow');
        $this->_updateRule("admin/zendesk/zendesk_settings",'allow');

        return true;
    }
}