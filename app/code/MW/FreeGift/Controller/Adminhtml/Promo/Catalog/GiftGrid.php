<?php
/**
 * Get related products grid
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

class GiftGrid extends Gift
{
    public function xexecute()
    {
//        print_r($this->getRequest()->getPost());
//        print_r($this->getRequest()->getParam('stop_rules_processing'));
//        $id = $this->getRequest()->getParam('id');
//        $model = $this->_objectManager->create('MW\FreeGift\Model\Rule');
//        if ($id) {
//            $model->load($id);
//            if (!$model->getRuleId()) {
//                $this->messageManager->addError(__('This rule no longer exists.'));
//                $this->_redirect('catalog_rule/*');
//                return;
//            }
//        }
        $gift_product_ids = $this->getRequest()->getParam('gift_product_ids');
        $this->_coreRegistry->register('gift_product_ids', $gift_product_ids ? $gift_product_ids : 0 );
        $this->productBuilder->build($this->getRequest());
        $resultLayout = $this->resultLayoutFactory->create();

        return $resultLayout;
    }
}
