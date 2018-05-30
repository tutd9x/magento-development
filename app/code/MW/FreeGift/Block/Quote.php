<?php
namespace MW\FreeGift\Block;

use Magento\Store\Model\ScopeInterface;

class Quote extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $sessionManager;
    protected $_scopeConfig;
    protected $salesruleModel;
    /**
     * @var \MW\FreeGift\Model\ResourceModel\SalesRule\CollectionFactory
     */
    protected $_salesruleCollectionFactory;
    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::checkout/cart/quote.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MW\FreeGift\Model\SalesRule $salesruleModel,
        \MW\FreeGift\Model\ResourceModel\SalesRule\CollectionFactory $salesruleCollectionFactory,
        array $data = []
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->salesruleModel = $salesruleModel;
        $this->_salesruleCollectionFactory = $salesruleCollectionFactory;

        parent::__construct($context, $data);
    }

    public function getAllActiveRules()
    {
        $quote           = $this->checkoutSession->getQuote();
        $store           = $this->_storeManager->getStore($quote->getStoreId());
        $websiteId       = $store->getWebsiteId();
        $customerGroupId = $quote->getCustomerGroupId() ? $quote->getCustomerGroupId() : 0;
        $flagRule            = $this->_session->getFlagRule();

        $arrRule = explode(",", $flagRule);
        $allowRule = $arrRule;
        $collection = $this->_salesruleCollectionFactory->create()->setValidationFilter($websiteId, $customerGroupId);

        $aplliedRuleIds = $this->checkoutSession->getQuote()->getFreegiftAppliedRuleIds();
        $arrRuleApllieds = explode(',', $aplliedRuleIds);

        $collectionSaleRule = $this->_salesruleCollectionFactory->create()->setOrder("sort_order", "ASC");
        $collectionSaleRule->getSelect()->where('is_active = 1');
        $listSaleRule = [];
        foreach ($collectionSaleRule as $saleRule) {
            if (in_array($saleRule->getId(), $arrRuleApllieds)) {
                if ($saleRule->getStopRulesProcessing()) {
                    $listSaleRule[] = $saleRule->getId();
                    break;
                }
            }
            $listSaleRule[] = $saleRule->getId();
        }
        $collection->addFieldToFilter('rule_id', [
            'in' => $listSaleRule
        ]);

        if (sizeof($arrRuleApllieds)) {
            $collection->addFieldToFilter('rule_id', [
                'nin' => $arrRuleApllieds
            ]);
        }
        return $collection;
    }
    public function getRandomRule()
    {
        $ids      = $this->getAllActiveRules()->getAllIds();
        $rand_key = array_rand($ids);
        return $this->salesruleModel->load($ids[$rand_key]);
    }

    public function _toHtml()
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active', ScopeInterface::SCOPE_STORE)) {
            return '<div class="freegift_rules_container"></div>';
        }
        if (!count($this->getAllActiveRules())) {
            return '<div class="freegift_rules_container"></div>';
        }

        return $this->fetchView($this->getTemplateFile());
    }

    public function _getBaseUrl()
    {
        /* @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        return $store->getBaseUrl();
    }
}
