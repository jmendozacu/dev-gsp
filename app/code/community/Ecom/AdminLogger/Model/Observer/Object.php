<?php

class Ecom_AdminLogger_Model_Observer_Object {

	public function beforeSave($observer) {
		$object = $observer->getObject();
		$expectedModel = Mage::registry('adminlogger_model');		

        //Mage::log("Before save: ". get_class($object)); // use this to find out the modelnames..

        if($expectedModel && $object instanceof $expectedModel) {
			$storeId = Mage::app()->getRequest()->getParam('store');
						
			$original = Mage::getModel($expectedModel)
					->setStoreId($storeId)
					->load($object->getId());

            $this->_saveDataBefore($object->getId(), $original->getData());
		}
	}
	
	public function afterSave($observer) {
		$object = $observer->getObject();

		$expectedModel = Mage::registry('adminlogger_model');
		$lastEventId = Mage::registry('adminlogger_last_event_id');

        //Mage::log("After save: ". get_class($object)); // use this to find out the modelnames..

        if($expectedModel && $object instanceof $expectedModel && $lastEventId) {
			try {

                $dataBefore = $this->_getDataBefore($object->getId());

				// If a new entity object was created we only store a single row showing this

                if(empty($dataBefore)) {

                    if($expectedModel == "Mage_Core_Model_Config_Data"){
                        $details = Mage::getModel('adminlogger/details')
                            ->setEventId($lastEventId)
                            ->setSourceName($expectedModel)
                            ->setSourceId($object->getId())
                            ->setPropertyName($object->getData('path'))
                            ->setOriginalData("[OVERRIDE]")
                            ->setResultData($object->getData('value'));
                    } else {
                        $details = Mage::getModel('adminlogger/details')
                            ->setEventId($lastEventId)
                            ->setSourceName($expectedModel)
                            ->setSourceId($object->getId())
                            ->setPropertyName('---')
                            ->setOriginalData('---')
                            ->setResultData('NEW OBJECT');
                    }
					
					$details->save();
					
					return;
				}
				
				// If entity was updated we store all relevant data changes

				$storeId = Mage::app()->getRequest()->getParam('store');
				if($storeId) {
					$original = Mage::getModel($expectedModel)
						->setStoreId($storeId)
						->load($object->getId());
					
					$dataAfter = $original->getData();
				} else {
					$dataAfter = $object->getData();
				}

                /*
                 * Debug
                 *
                foreach($dataAfter as $key => $value) {
                    // We only deal with non-complex values
                    if(is_object($value) || is_array($value)) {
                        continue;
                    }
					if(isset($dataBefore[$key])) Mage::log($key . ': ' . $dataBefore[$key] . " -> " . $value);
                    else Mage::log($key . ': ' . $value);
				}
                */

                foreach($dataBefore as $key => $value) {
					
					// We only deal with non-complex values
					if(is_object($value) || is_array($value)) {
						continue;
					}
					
					// Skip the following data fields
					if( $key == 'updated_at' || 
						$key == 'created_at' ||
						$key == 'new_password' ||
						$key == 'password' ||
						$key == 'password_hash') {
							continue;
					}
					
					if(isset($dataAfter[$key]) && $dataAfter[$key] != $value) {

                        if($expectedModel == "Mage_Core_Model_Config_Data") $propertyName = $dataAfter["path"];
                        else $propertyName = $key;

						$details = Mage::getModel('adminlogger/details')
							->setEventId($lastEventId)
							->setSourceName($expectedModel)
							->setSourceId($object->getId())
							->setPropertyName($propertyName)
							->setOriginalData($value)
							->setResultData($dataAfter[$key]);
						
						$details->save();
					}
				}
								
			} catch (Exception $e) {
				Mage::log('Adminlogger exception: ' . $e->getMessage());
			}
		}
	}
	
	public function afterDelete($observer) {
		$object = $observer->getObject();
		$expectedModel = Mage::registry('adminlogger_model');
		$lastEventId = Mage::registry('adminlogger_last_event_id');

        /*
         * debug
         *
		foreach($object->getData() as $key => $value) {
			if(is_object($value) || is_array($value)) {
				continue;
			}
			
            Mage::log($key . ' = ' . $value);
		}
        */
		
		if($expectedModel && $object instanceof $expectedModel) {

            if($expectedModel == "Mage_Core_Model_Config_Data"){
                // this happends when rechecking "use default value" in configuration
                $details = Mage::getModel('adminlogger/details')
                    ->setEventId($lastEventId)
                    ->setSourceName($expectedModel)
                    ->setSourceId($object->getId())
                    ->setPropertyName($object->getData('path'))
                    ->setOriginalData($object->getData('value')) // always empty??
                    ->setResultData('Removed override');
            } else {
                $details = Mage::getModel('adminlogger/details')
                    ->setEventId($lastEventId)
                    ->setSourceName($expectedModel)
                    ->setSourceId($object->getId())
                    ->setPropertyName('sku')
                    ->setOriginalData($object->getData('sku'))
                    ->setResultData('DELETED OBJECT');
            }


					
			$details->save();
		}
	}

    /**
     * Store data in Mage::register('adminlogger_object_data_before')
     * Store as an array, and add to existng if it allready exist
     *
     * @param $id
     * @param $data
     *
     * @return $this
     */
    private function _saveDataBefore($id, $data){

        // load the existing array and unset the register if it exists, create an empty array otherwise
        if(!is_array($array = Mage::registry('adminlogger_object_data_before'))){
            $array = array();
        } else {
            Mage::unregister("adminlogger_object_data_before");
        }

        $array[$id]=$data;

        Mage::register('adminlogger_object_data_before', $array);

        return $this;
    }

    /**
     * Get data from Mage::registry('adminlogger_object_data_before')
     *
     * @param $id
     *
     * @return array
     */
    private function _getDataBefore($id){

        // load the existing array if it exists, create an empty array otherwise
        if(!is_array($array = Mage::registry('adminlogger_object_data_before'))){
            $array = array();
        }

        if(isset($array[$id])) return $array[$id];
        else return array();
    }

}