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
}
