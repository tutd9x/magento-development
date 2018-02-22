<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

class NewAction extends \MW\FreeGift\Controller\Adminhtml\Promo\Catalog
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
