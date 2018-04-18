<?php

namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class Gifttab extends Quote
{
    /**
    //* @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->_initRule();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
//        return $this;
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
//        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
//        return $resultLayout;

//        return $this->resultPageFactory->create();
    }
}
