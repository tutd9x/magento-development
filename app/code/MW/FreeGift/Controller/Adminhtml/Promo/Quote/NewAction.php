<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class NewAction extends \MW\FreeGift\Controller\Adminhtml\Promo\Quote
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
