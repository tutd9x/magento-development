<?php

namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;
use MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

class GiftsMassUpdate extends Catalog
{
    /**
     * Gifts mass update action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initRule();
        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');

        if (!$model->getId()) {
            $this->_forward('noroute');
        }

        $codesIds = $this->getRequest()->getParam('product');
        $data = implode(',', $codesIds);
        $model->setGiftProductIds($data)->save();
    }
}
