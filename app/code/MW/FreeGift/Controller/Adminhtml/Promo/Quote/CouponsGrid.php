<?php

namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class CouponsGrid extends Quote
{
    /**
     * Coupon codes grid
     *
     * @return void
     */
    public function execute()
    {
        $this->_initRule();
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shopping Cart Rules'));
        $this->_view->renderLayout();
    }
}
