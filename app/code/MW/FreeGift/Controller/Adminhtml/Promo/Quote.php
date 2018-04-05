<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo;
use Magento\Framework\App\Filesystem\DirectoryList;
abstract class Quote extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;
    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $_directory;
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_fileFactory = $fileFactory;
        $this->_dateFilter = $dateFilter;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Initiate rule
     *
     * @return void
     */
    protected function _initRule()
    {
        $this->_coreRegistry->register(
            'current_promo_quote_rule',
            $this->_objectManager->create('MW\FreeGift\Model\Salesrule')
        );
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id && $this->getRequest()->getParam('rule_id')) {
            $id = (int)$this->getRequest()->getParam('rule_id');
        }

        if ($id) {
            $this->_coreRegistry->registry('current_promo_quote_rule')->load($id);
        }
    }

    /**
     * Initiate action
     *
     * @return this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('MW_FreeGift::mw_freegift_salesrule')->_addBreadcrumb(__('Promotions'), __('Promotions'));
        return $this;
    }

    /**
     * Returns result of current user permission check on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MW_FreeGift::quote');
    }
}
