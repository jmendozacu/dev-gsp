<?php

/**
 * Class Evalent_StoreSetup_Model_Moduleinstaller
 *
 * This classes primary purpose is to set correct ACL based on what modules is installed
 * at the time
 *
 * @author: Andre Klang
 */
class Evalent_StoreSetup_Model_Moduleinstaller extends Mage_Core_Model_Abstract {

    const XML_PATH_DATASTORE = 'store_setup/moduleinstallers/datastore';

    private $_dataStore = array();

    /**
     * Defines which installers are available
     * This HAS to be the acctual module-name that the installer relates to
     * Each row relates to an installer-model like this
     *  Evalent_StoreSetup_Model_Moduleinstaller_Evalent_Recursiveacl
     *
     * NOTE: the keys in the array below has to have EXACT case, same as the modulename
     *  But the acctual model need to have first letter uppercase, rest lower.
     *  (see Evalent_RecursiveAcl as example)
     *
     * @var array
     */
    private $_installers = array(
        "Evalent_RecursiveAcl",
        "AAIT_SocialSecurityNumber",
        "Billmate_Common",
        "Zendesk_Zendesk",
        "Klevu_Search",
        "Evalent_Blog"
    );

    /**
     * Run the installer
     * This runs on event "admin_session_user_login_success"
     */
    public function run(){

        # run installers whos module is installed
        foreach( $this->_installers as $module ){

            // skip the ones that is not installed
            if(!Mage::helper('core')->isModuleEnabled($module)) continue;

            // get an installer object
            $installer = $this->_getInstaller($module);

            // make sure that we got an installer
            if(!$installer) continue;

            // skip the ones that is already installed
            if($this->hasInstallerRun($installer)) continue;

            // well, run it!
            try {
                if($installer->run()){
                    $this->storeInstallerRun($installer);
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Load the installer history from "core_config_data"
     */
    public function __construct(){
        parent::__construct();

        # load history
        # have to get it from database manually to get past the cache
        $config = Mage::getModel("core/config_data")->getCollection()
            ->addFieldToFilter("scope_id","0")
            ->addFieldToFilter("path",self::XML_PATH_DATASTORE)
            ->getFirstItem();

        if($config->getId()){
            $this->_dataStore = json_decode($config->getValue(),true);
        } else {
            $this->_dataStore = json_decode(Mage::getStoreConfig(self::XML_PATH_DATASTORE,0),true);
        }
    }

    /**
     * Get installer models (located in folder Model/Moduleinstaller/[Namespace]/[Modulename])
     * @param $key
     *
     * @return false|Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller
     */
    private function _getInstaller($key){

        // fetch a model
        $model = Mage::getModel("storesetup/moduleinstaller_".strtolower($key));

        // if we got it, set the key, we need it later
        if($model !== false) $model->setKey($key);

        // return whatever we got
        return $model;
    }


    /**
     * Find out if the current version of an installer has run
     * @param Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller $installer
     * @return bool
     */
    public function hasInstallerRun(Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller $installer) {

        if(isset($this->_dataStore[$installer->getKey()]['has_run'][$installer->getInstallerVersion()])) return true;
        else return false;
    }


    /**
     * Store that an installer has run
     *
     * @param Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller $installer
     * @return $this
     */
    public function storeInstallerRun(Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller $installer) {
        $this->_dataStore[$installer->getKey()]['has_run'][$installer->getInstallerVersion()] = date("Ymd H:i:s");

        Mage::getModel("core/config")->saveConfig(self::XML_PATH_DATASTORE, json_encode($this->_dataStore), 'default', 0);

        return $this;
    }
}