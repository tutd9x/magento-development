<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo;

abstract class Index extends \Magento\Backend\App\Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MW_FreeGift::promo');
    }

    /**
     * @return void
     */
    public function execute()
    {
        echo 123; exit;
        $this->_view->loadLayout();
        $this->_setActiveMenu('MW_FreeGift::promo');
        $this->_addBreadcrumb(__('Promotions'), __('Promo'));
        $this->_view->renderLayout();
    }
}
