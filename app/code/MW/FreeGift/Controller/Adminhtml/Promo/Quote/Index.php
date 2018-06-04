<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class Index extends Quote
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Shopping Cart'), __('Shopping Cart'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shopping Cart Rules'));
        $this->_view->renderLayout();
    }
}
