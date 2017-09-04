<?php

/**
 *
 */
class Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller extends Evalent_StoreSetup_Model_Moduleinstaller {

    public $_version = '0.0.0'; // changing the version will make it run again

    /**
     * Detirmins if the current version has run before
     * @return string
     */
    public function getInstallerVersion(){
        return $this->_version;
    }

    /**
     * Do all you need to do
     */
    public function run(){
        return true;
    }

    /**
     * Utillity method to update/add ACL-rule
     * @param $resource
     * @param $permission
     */
    public function _updateRule($resource,$permission){

        // check ir rule already exists
        $rule = Mage::getModel("admin/rules")->getCollection()
            ->addFieldToFilter("role_id",4)
            ->addFieldToFilter("resource_id",$resource)
            ->getFirstItem();

        // if it exists
        if($rule->getId()){

            if($rule->getPermission() != $permission) $rule->setPermission($permission)->save();

        } else {
            // create a new one
            Mage::getModel("admin/rules")->setData(array(
                "role_id" => 4,
                "resource_id" => $resource,
                "privileges" => null,
                "assert_id" => 0,
                "role_type" => "G",
                "permission" => $permission
            ))->save();
        }
    }
}