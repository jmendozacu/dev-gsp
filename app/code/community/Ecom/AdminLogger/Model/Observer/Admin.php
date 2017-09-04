<?php

Class Ecom_AdminLogger_Model_Observer_Admin {
	
	public function afterFail($observer) {

        $loginData = Mage::app()->getRequest()->getParam('login');
        $username = (is_array($loginData) && array_key_exists('username', $loginData)) ? $loginData['username'] : null;
        $password = (is_array($loginData) && array_key_exists('password', $loginData)) ? $loginData['password'] : null;
        $password = "**".substr($password,2,strlen($password)-4)."**";
			
        $log = Mage::getModel('adminlogger/log')
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setUsername('')
                ->setStore("All store views")
                ->setController("login")
                ->setActionType("Failed login")
                ->setItem("User: '".$username."' Pass: '".$password."'");


        try {
            $log->save();

        } catch (Exception $e) {
            Mage::log('Adminlogger exception: ' . $e->getMessage());
        }

	}

    public function afterSuccess($observer) {

        $loginData = Mage::app()->getRequest()->getParam('login');
        $username = (is_array($loginData) && array_key_exists('username', $loginData)) ? $loginData['username'] : null;

        $log = Mage::getModel('adminlogger/log')
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUsername($username)
            ->setStore("All store views")
            ->setController("login")
            ->setActionType("Successful login")
            ->setItem('***');


        try {
            $log->save();

        } catch (Exception $e) {
            Mage::log('Adminlogger exception: ' . $e->getMessage());
        }

    }

}
