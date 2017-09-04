<?php

class Ecom_AdminLogger_Adminhtml_LoggerController extends Mage_Adminhtml_Controller_Action {

	public function indexAction() {
		$this->loadLayout()->_setActiveMenu('system/tools/adminlogger');
		$this->_addContent($this->getLayout()->createBlock('adminlogger/adminhtml_index'));
		$this->renderLayout();
	}

	public function exportCsvAction() {
		$fileName = sprintf('ecom_adminlogger_%s.csv', date('d.m.Y-H.i.s'));
		$grid = $this->getLayout()->createBlock('adminlogger/adminhtml_index_grid');

		$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
	}

	public function exportExcelAction() {
		$fileName = sprintf('ecom_adminlogger_%s.xml', date('d.m.Y-H.i.s'));
		$grid = $this->getLayout()->createBlock('adminlogger/adminhtml_index_grid');
		$this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
	}

	public function detailsAction() {
		
		$eventId = $this->getRequest()->getParam('event_id');
        $model   = Mage::getModel('adminlogger/log')->load($eventId);
        if (!$model->getId()) {
            $this->_redirect('*/*/');
            return;
        }
		
        Mage::register('current_event', $model);
		
		$this->loadLayout()->_setActiveMenu('system/tools/adminlogger');
		$this->_addContent($this->getLayout()->createBlock('adminlogger/adminhtml_details'));
		$this->renderLayout();
	}

	protected function _isAllowed() {
		return Mage::getSingleton('admin/session')->isAllowed('system/tools/adminlogger');
	}

}