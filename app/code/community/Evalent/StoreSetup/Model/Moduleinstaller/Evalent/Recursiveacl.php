<?php

/**
 *
 */
class Evalent_StoreSetup_Model_Moduleinstaller_Evalent_RecursiveAcl extends Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller {

    public $_version = '0.0.1';

    /**
     * Do all you need to do
     */
    public function run(){

        $this->_updateRule("admin/system/acl",'allow');
        $this->_updateRule("admin/system/acl/roles",'allow');
        $this->_updateRule("admin/system/acl/users",'allow');

        return true;
    }
}