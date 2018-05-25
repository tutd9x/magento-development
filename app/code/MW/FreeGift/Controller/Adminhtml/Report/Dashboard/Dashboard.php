<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Report\Dashboard;

class Dashboard extends \MW\FreeGift\Controller\Adminhtml\Promo\Dashboard
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_objectManager = $context->getObjectManager();
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
//        echo 123; die;
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        if($this->getRequest()->getPost('ajax') == 'true'){
            $data = $this->getRequest()->getPostValue();
            switch($this->getRequest()->getPost('type'))
            {
                case 'dashboard':
                    $data["to"]  = "05/25/2018 12:17:00";
                    $dataReport = $this->_objectManager->create('MW\FreeGift\Model\Report')->prepareCollection($data);
                    print $dataReport;
                    break;
            }
            exit;
        }

    }
}
