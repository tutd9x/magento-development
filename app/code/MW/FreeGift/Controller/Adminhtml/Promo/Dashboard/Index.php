<?php

namespace MW\FreeGift\Controller\Adminhtml\Promo\Dashboard;

class Index extends \MW\FreeGift\Controller\Adminhtml\Promo\Dashboard
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MW_FreeGift::mw_freegift_dashboard');
        $resultPage->addBreadcrumb(__('Report'), __('Report'));
        $resultPage->getConfig()->getTitle()->prepend(__('Report'));

        return $resultPage;
    }
}
