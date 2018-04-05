<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Cart;

class Delete extends \Magento\Checkout\Controller\Cart
{
    /**
     * Delete shopping cart item action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->cart->removeItem($id)->save();
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t remove the item.'));
                if ($this->getRequest()->isAjax()) {
                    echo json_encode(array('error' => 1, 'msg' => $e->getMessage()));
                    exit;
                }
                //$this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
        }
        if ($this->getRequest()->isAjax()) {
            echo json_encode(array('error' => 0));
            exit;
        }
        $defaultUrl = $this->_objectManager->create('Magento\Framework\UrlInterface')->getUrl('*/*');
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
    }
}
