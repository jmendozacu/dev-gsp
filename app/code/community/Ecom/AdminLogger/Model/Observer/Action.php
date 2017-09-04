<?php

Class Ecom_AdminLogger_Model_Observer_Action {
	
	const XML_PATH_CONTROLLER_FILTER = 'adminlogger/controller_filter';
	
	private function _getActionLog($controller, $action) {
		$actionLog = array();
		$controllerFilter = Mage::getStoreConfig(self::XML_PATH_CONTROLLER_FILTER);

        if($controllerFilter && array_key_exists($controller, $controllerFilter)) {
			$actionFilter = $controllerFilter[$controller]['actions'];
			if($actionFilter && array_key_exists($action, $actionFilter)) {
				
				// Any model expected for this action must be saved in the register
				// so it can be accessed by the object observer
				$actionInfo = $actionFilter[$action];
				$actionLog['action'] = $action;
				
				if(array_key_exists('title', $actionInfo)) {
					$actionLog['title'] = $actionInfo['title'];
				} else {
					$actionLog['title'] = $action;
				}
	
				if(array_key_exists('model', $controllerFilter[$controller])) {					
					$actionLog['model'] = $controllerFilter[$controller]['model'];
				} else {
					$actionLog['model'] = '';
				}
				
				if(array_key_exists('short_details', $actionInfo)) {
					$actionLog['short_details'] = $actionInfo['short_details'];
				} else {
					$actionLog['short_details'] = '';
				}
				
				return $actionLog;
			}
		}
		
		return null;
	}
	
	public function controllerActionPredispatch($observer) {

		$user = Mage::getSingleton('admin/session')->getUser();		
		if (is_null($user) || !$user->getId()) {
			return;
		}

		$request = Mage::app()->getRequest();
		
		// Only log actions and controllers as set in config
		
		$actionLog = $this->_getActionLog($request->getControllerName(), $request->getActionName());
		if(!is_null($actionLog)) {
			
			$log = Mage::getModel('adminlogger/log')
					->setCreatedAt(date('Y-m-d H:i:s'))
					->setUsername($user->getUsername())
					->setController($request->getControllerName())
					->setActionType($actionLog['title']);

			if ($request->getParam('store')) {
				$store = $request->getParam('store');
				$storeObject = Mage::getModel('core/store')->load($store);
				$log->setStore($storeObject->getCode());
			} else {
				$log->setStore("All store views");
			}
			
			if($actionLog['short_details']) {
                if(is_array($shortDetails = $request->getParam($actionLog['short_details']))){
                    $shortDetails = join(', ',$shortDetails);
                }

				$log->setItem($shortDetails);
			}

			try {
				$log->save();
				
				if(array_key_exists('model', $actionLog)) {
					Mage::register('adminlogger_model', $actionLog['model']);
				}

				if(array_key_exists('short_details', $actionLog)) {
					Mage::register('adminlogger_short_details', $actionLog['short_details']);
				}
				
				Mage::register('adminlogger_last_event_id', $log->getId());
				
			} catch (Exception $e) {
                Mage::log('Adminlogger exception: ' . $e->getMessage());
				/* Silently die... */
			}
		}
	}

}
