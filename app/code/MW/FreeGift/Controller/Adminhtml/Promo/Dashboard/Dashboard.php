<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Dashboard;

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
//        $resultPage = $this->resultPageFactory->create();
//        $resultPage->setActiveMenu('MW_FreeGift::mw_freegift_dashboard');
//        $resultPage->addBreadcrumb(__('Report'), __('Report'));
//        $resultPage->getConfig()->getTitle()->prepend(__('Report'));

//        return $resultPage;

        if($this->getRequest()->getPost('ajax') == 'true'){
            $data = $this->getRequest()->getPostValue();
            switch($this->getRequest()->getPost('type'))
            {
                case 'dashboard':
                    $dataReport = $this->_objectManager->create('MW\FreeGift\Model\Report')->prepareCollection($data);
                    print $dataReport;
//                    $this->xlog($test);
//                    print '{"report":{"redeemed":[[2015,9,17,0],[2015,9,18,0],[2015,9,19,0],[2015,9,20,0],[2015,9,21,0],[2015,9,22,0],[2015,9,23,0],[2015,9,24,0],[2015,9,25,0],[2015,9,26,0],[2015,9,27,0],[2015,9,28,0],[2015,9,29,0],[2015,9,30,0],[2015,9,1,0],[2015,9,2,0],[2015,9,3,0],[2015,9,4,0],[2015,9,5,0],[2015,9,6,0],[2015,9,7,0],[2015,9,8,0],[2015,9,9,0],[2015,9,10,0],[2015,9,11,0],[2015,9,12,0],[2015,9,13,0],[2015,9,14,0],[2015,9,15,0],[2015,9,16,0],[2015,10,1,0],[2015,10,2,0],[2015,10,3,0],[2015,10,4,0],[2015,10,5,0],[2015,10,6,0],[2015,10,7,0],[2015,10,8,0],[2015,10,9,0],[2015,10,10,0],[2015,10,11,0],[2015,10,12,0],[2015,10,13,0],[2015,10,14,0],[2015,10,15,45],[2015,10,16,0]],"rewarded":[[2015,9,17,0],[2015,9,18,0],[2015,9,19,0],[2015,9,20,0],[2015,9,21,0],[2015,9,22,0],[2015,9,23,0],[2015,9,24,0],[2015,9,25,0],[2015,9,26,0],[2015,9,27,0],[2015,9,28,0],[2015,9,29,0],[2015,9,30,0],[2015,9,1,0],[2015,9,2,0],[2015,9,3,0],[2015,9,4,0],[2015,9,5,0],[2015,9,6,0],[2015,9,7,0],[2015,9,8,0],[2015,9,9,0],[2015,9,10,0],[2015,9,11,0],[2015,9,12,0],[2015,9,13,0],[2015,9,14,0],[2015,9,15,0],[2015,9,16,0],[2015,10,1,0],[2015,10,2,0],[2015,10,3,0],[2015,10,4,0],[2015,10,5,0],[2015,10,6,0],[2015,10,7,0],[2015,10,8,0],[2015,10,9,0],[2015,10,10,0],[2015,10,11,0],[2015,10,12,0],[2015,10,13,0],[2015,10,14,0],[2015,10,15,92],[2015,10,16,0]]},"title":"FreeGift \/ Report FreeGift","report_activities":"","statistics":{"total_order":"$92.00","total_gift":"$45.00","number_customer":"$1.00","avg_gift_per_customer":"$45.00","avg_gift_per_order":"$45.00"}}';
//                    print '{"report":{"redeemed":[[2015,9,16,0],[2015,9,17,0],[2015,9,18,0],[2015,9,19,0],[2015,9,20,0],[2015,9,21,0],[2015,9,22,0],[2015,9,23,0],[2015,9,24,0],[2015,9,25,0],[2015,9,26,0],[2015,9,27,0],[2015,9,28,0],[2015,9,29,0],[2015,9,30,0],[2015,9,1,0],[2015,9,2,0],[2015,9,3,0],[2015,9,4,0],[2015,9,5,0],[2015,9,6,0],[2015,9,7,0],[2015,9,8,0],[2015,9,9,0],[2015,9,10,0],[2015,9,11,0],[2015,9,12,0],[2015,9,13,0],[2015,9,14,0],[2015,9,15,0],[2015,10,1,0],[2015,10,2,0],[2015,10,3,0],[2015,10,4,0],[2015,10,5,0],[2015,10,6,0],[2015,10,7,0],[2015,10,8,0],[2015,10,9,0],[2015,10,10,0],[2015,10,11,0],[2015,10,12,0],[2015,10,13,290],[2015,10,14,0],[2015,10,15,0]],"rewarded":[[2015,9,16,0],[2015,9,17,0],[2015,9,18,0],[2015,9,19,0],[2015,9,20,0],[2015,9,21,0],[2015,9,22,0],[2015,9,23,0],[2015,9,24,0],[2015,9,25,0],[2015,9,26,0],[2015,9,27,0],[2015,9,28,0],[2015,9,29,0],[2015,9,30,0],[2015,9,1,0],[2015,9,2,0],[2015,9,3,0],[2015,9,4,0],[2015,9,5,0],[2015,9,6,0],[2015,9,7,0],[2015,9,8,0],[2015,9,9,0],[2015,9,10,0],[2015,9,11,0],[2015,9,12,0],[2015,9,13,0],[2015,9,14,0],[2015,9,15,0],[2015,10,1,0],[2015,10,2,0],[2015,10,3,0],[2015,10,4,0],[2015,10,5,0],[2015,10,6,0],[2015,10,7,0],[2015,10,8,0],[2015,10,9,0],[2015,10,10,0],[2015,10,11,0],[2015,10,12,0],[2015,10,13,895],[2015,10,14,0],[2015,10,15,0]],"order":null},"title":"FreeGift \/ Report FreeGift","report_activities":"","statistics":{"total_order":"895,00\u00a0US$","total_gift":"290,00\u00a0US$","number_customer":"1,00\u00a0US$","avg_gift_per_customer":"290,00\u00a0US$","avg_gift_per_order":"96,67\u00a0US$"}}';
                    break;
            }
            exit;
        }

    }
    function xlog($message = 'null'){
        if(gettype($message) == 'string'){
        }else{
            $message = serialize($message);
        }

        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }
}
