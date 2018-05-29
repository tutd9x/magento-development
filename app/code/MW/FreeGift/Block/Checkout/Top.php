<?php

namespace MW\FreeGift\Block\Checkout;

class Top extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $_storeManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
        $this->_storeManager = $context->getStoreManager();
    }

    public function getRegistry()
    {
        return $this->_coreRegistry;
    }

    public function getSessionManager()
    {
        return $this->_session;
    }

    public function getCustomerSession()
    {
        return $this->_customerSession;
    }

    public function _getBaseUrl()
    {
//        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        /* @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        return $store->getBaseUrl();
    }
}
