<?php
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
     * @return $this
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        if($this->getRequest()->getPost('ajax') == 'true'){
            $data = $this->getRequest()->getPostValue();
            switch($this->getRequest()->getPost('type'))
            {
                case 'dashboard':
                    $dataReport = $this->_objectManager->create('MW\FreeGift\Model\Report')->prepareCollection($data);
                    return $this->getResponse()->setBody($dataReport);
                    break;
            }
            exit;
        }
        return $this->getResponse()->setBody([]);
    }
}
