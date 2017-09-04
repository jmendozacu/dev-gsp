<?php

/**
 * Shopping cart controller
 */
require_once  Mage::getModuleDir('controllers', 'Mage_Checkout').DS.'CartController.php';
class Ecom_AjaxBuy_CartController extends Mage_Checkout_CartController
{
	
	
	 /**
     * Add product to shopping cart action
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Exception
     */
    public function addAction()
    {
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
               $success = false;
			   $msg = Mage::helper('ajaxbuy')->__('An error occured, please try again');
			   $this->sendResponse(array('success' => false, 'msg'=>$msg),$product);
               return;
            }

            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            /**
             * @todo remove wishlist observer processAddToCart
             */
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );

            $this->sendResponse(array('success' => true),$product);
			
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $url = Mage::helper('checkout/cart')->getCartUrl();
            }
			
			$this->sendResponse(array('success' => false, 'redirect'=>$url),$product);
			return;
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
            Mage::logException($e);
            $this->sendResponse(array('success' => false, 'redirect'=>$this->_getBackUrl()),$product);
			return;
        }
    }
	
	public function sendResponse($response,$product){
		
		Mage::dispatchEvent('ajaxbuy_before_response_redirect',array('response'=>&$response,'product' => $product, 'request' => $this->getRequest()));
		
		// for compatibility with older version
		if(isset($response['redirect'])){
			$response['result'] = 'redirect';
			$response['url'] = $response['redirect'];
		} else $response['result'] = 'normal';
		$this->getResponse()->setHttpResponseCode(200)->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
	}
	
	protected function _getBackUrl()
    {
        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl) {

            if (!$this->_isUrlInternal($returnUrl)) {
                throw new Mage_Exception('External urls redirect to "' . $returnUrl . '" denied!');
            }

            return $returnUrl;
        } elseif (!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
            && !$this->getRequest()->getParam('in_cart')
            && $backUrl = $this->_getRefererUrl()
        ) {
            return $backUrl;
        } else {
            if (($this->getRequest()->getActionName() == 'add') && !$this->getRequest()->getParam('in_cart')) {
                $this->_getSession()->setContinueShoppingUrl($this->_getRefererUrl());
            }
            return Mage::helper('checkout/cart')->getCartUrl();
        }
    }

}
