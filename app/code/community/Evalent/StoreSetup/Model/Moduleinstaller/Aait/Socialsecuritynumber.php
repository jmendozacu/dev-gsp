<?php

/**
 *
 */
class Evalent_StoreSetup_Model_Moduleinstaller_Aait_Socialsecuritynumber extends Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller {

    public $_version = '0.0.1';

    /**
     * Do all you need to do
     */
    public function run(){

        $this->_updateRule("admin/system/config/aait_ssn",'allow');

        return true;
    }
}