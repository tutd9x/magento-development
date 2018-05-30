<?php
namespace MW\FreeGift\Block\Adminhtml\Report\Dashboard;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::report/dashboard.phtml';
    private $_currency;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendSession;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $_backendHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Directory\Model\CurrencyFactory $currency,
        array $data = []
    ) {
        $this->_backendHelper = $backendHelper;
        $this->_backendSession = $context->getBackendSession();
        $this->_currency = $currency;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Setting default for every grid on dashboard
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * Retrieve current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        return (int)($storeId);
    }

    public function showCurrencyLabel()
    {
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency = $this->_currency->create()->load($currencyCode);
        return $currencySymbol = $currency->getCurrencySymbol();
    }
}
