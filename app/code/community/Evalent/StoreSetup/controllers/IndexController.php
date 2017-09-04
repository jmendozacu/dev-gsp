<?php

class Evalent_StoreSetup_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        exit("Disabled");
    }

    public function import_demo_productsAction()
    {
        echo "Import products:<br>";

        if (Mage::getStoreConfig("storesetup/import/done", 0)){
            echo "Import is allready performed once!";
            exit();
        }

        try {
            echo "Starting import...<br>";

            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

            ## Import demo products
            $import = Mage::getModel('importexport/import')->setEntity("catalog_product");
            $validationResult = $import->validateSource(Mage::getBaseDir('var') . "/import/demo/catalog_product.csv");

            $importModel = Mage::getModel('importexport/import');

            error_reporting((E_ALL | E_STRICT) - E_NOTICE); // suppress error message
            $importModel->importSource();
            $importModel->invalidateIndex(); // reset error reporting
            error_reporting(E_ALL | E_STRICT);

            Mage::getModel("core/config")->saveConfig("storesetup/import/done", "1", 'default', 0); // stop this from happening again
            echo "DONE!<br>";
        } catch (Exception $e) {
            echo "ERROR!<br>".$e->getMessage();
            Mage::log("Importing demo-products: " . $e->getMessage());
        }

        # trigger ultimo to create new css files
        try {
            echo "Generate ultimo css...<br>";
            Mage::getSingleton('ultimo/cssgen_generator')->generateCss('grid', NULL, NULL);
            Mage::getSingleton('ultimo/cssgen_generator')->generateCss('layout', NULL, NULL);
            Mage::getSingleton('ultimo/cssgen_generator')->generateCss('design', NULL, NULL);
        } catch (Exception $ex) {
            echo "ERROR!<br>".$e->getMessage();
            Mage::helper('storesetup')->log('Ultimo css-generation failed', null);
        }

        return;
    }
}