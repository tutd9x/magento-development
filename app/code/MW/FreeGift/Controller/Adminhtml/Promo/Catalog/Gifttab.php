<?php

namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

use MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

class Gifttab extends Catalog
{
    /**
    //* @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->_initRule();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
    }
}
