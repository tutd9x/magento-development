<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

class CouponsGrid extends \Magento\Framework\App\Action\Action
{
    /**
     * Coupon codes grid
     *
     * @return void
     */
    public function execute()
    {
//        $this->_coreRegistry->register(
//            'current_promo_catalog_rule',
//            $this->_objectManager->create('MW\FreeGift\Model\Rule')
//        );
//        $id = (int)$this->getRequest()->getParam('id');
//
//        if (!$id && $this->getRequest()->getParam('rule_id')) {
//            $id = (int)$this->getRequest()->getParam('rule_id');
//        }
//
//        if ($id) {
//            $this->_coreRegistry->registry('current_promo_catalog_rule')->load($id);
//        }
//        $this->_initRule();
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Cart Price Rules'));
        $this->_view->renderLayout();
    }
}
