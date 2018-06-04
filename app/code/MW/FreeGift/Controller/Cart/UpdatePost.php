<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace MW\FreeGift\Controller\Cart;

class UpdatePost extends \Magento\Checkout\Controller\Cart
{
    /**
     * Empty customer's shopping cart
     *
     * @return void
     */
    protected function _emptyShoppingCart()
    {
        try {
            $this->cart->truncate()->save();
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->messageManager->addError($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addException($exception, __('We can\'t update the shopping cart.'));
        }
    }

    /**
     * Update customer's shopping cart
     *
     * @return void
     */
    protected function _updateShoppingCart()
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }

                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId(null);
                }

                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData)->save();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t update the shopping cart.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
    }

    /**
     * Update shopping cart data action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $params = $this->getRequest()->getPost();
        $google_plus = $params['google_plus'];
        if($google_plus == '' || $google_plus == null || $google_plus == 'false'){

        }else{
            $this->_checkoutSession->setGooglePlus($google_plus);
        }

        $like_fb = $params['like_fb'];
        if($like_fb == '' || $like_fb == null || $like_fb == 'false'){

        }else{
            $this->_checkoutSession->setLikeFb($like_fb);
        }

        $share_fb = $params['share_fb'];
        if($share_fb == '' || $share_fb == null || $share_fb == 'false'){

        }else{
            $this->_checkoutSession->setShareFb($share_fb);
        }

        $twitter = $params['twitter'];
        if($twitter == '' || $twitter == null || $twitter == 'false'){

        }else{
            $this->_checkoutSession->setTwitter($twitter);
        }

        if(isset($params['ajax_add'])){
            //$this->addAjaxAction();
            //exit;
        }

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                $this->_updateShoppingCart();
                break;
            default:
                $this->_updateShoppingCart();
        }

        if ($this->getRequest()->isAjax()) {
            $helper = $this->_objectManager->get('MW\FreeGift\Helper\Data');
            $data['cart'] = json_encode($helper->dataInCart());
            echo $data['cart'];
            exit;
        }
        return $this->_goBack();
    }

    /**
     * Generate content to log file debug.log By Hattetek.Com
     *
     * @param  $message string|array
     * @return void
     */
    function xlog($message = 'null')
    {
        $log = print_r($message, true);
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($log)
        ;
    }

//    protected  function addAjaxAction(){
//        $cart   = $this->_getCart();
//        $params = $this->getRequest()->getPost();
//        if(isset($params['options']) && count($params['options']) > 0){
//            $options = array();
//            foreach($params['options'] as $opt_id => $val){
//                if(is_array($val)){
//                    foreach($val as $k => $v){
//                        if(!in_array($v, $options[$opt_id])){
//                            $options[$opt_id][$k]  = $v;
//                        }
//                    }
//                }else{
//                    $options[$opt_id]  = $val;
//                }
//            }
//        }
//        $params['options']  = $options;
//        try{
//            $product = $this->_initProduct($params['product']);
//            if (!$product) {
//                echo json_encode(array(
//                    'error' => 1,
//                    'msg'   => "No product"
//                ));
//                return;
//            }
//            $params['cusotm_unec'] = 'By Ajax Cart - Mage-World.COM';
//
//            $cart->addProduct($product, $params);
//            $cart->save();
//            $this->_getSession()->setCartWasUpdated(true);
//
//            Mage::dispatchEvent('checkout_cart_add_product_complete',
//                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse(), 'params' => $params, 'cart' => $cart)
//            );
//
//            echo json_encode(array(
//                'error' => 0,
//            ));
//        }catch (Mage_Core_Exception $e){
//            echo json_encode(array(
//                'error' => 1,
//                'msg'   => $e->getMessage()
//            ));
//        }catch(Exception $e){
//            echo json_encode(array(
//                'error' => 1,
//                'msg'   => $e->getMessage()
//            ));
//        }
//        exit;
//    }

}
