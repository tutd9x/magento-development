<?php
namespace MW\FreeGift\Controller\Adminhtml\Report\Dashboard;

use Magento\Framework\Json\Helper\Data;

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
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Data $jsonHelper
    ) {
        parent::__construct($context);
        $this->_objectManager = $context->getObjectManager();
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @return $this
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
//                    print $dataReport;
                    return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($dataReport));
                    break;
            }
            exit;
        }

    }
}
