<?php
namespace MW\FreeGift\Block;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Session\SessionManagerInterface;
class Quote extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $sessionManager;
//    protected $_coreRegistry;
//    protected $helperFreeGift;
//    protected $_resourceRule;
//    protected $productRepository;
    protected $_scopeConfig;
    protected $salesruleModel;
    /**
     * @var \MW\FreeGift\Model\ResourceModel\Salesrule\CollectionFactory
     */
    protected $_salesruleCollectionFactory;
    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::quote.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        SessionManagerInterface $sessionManager,
//        \MW\FreeGift\Helper\Data $helperFreeGift,
//        \Magento\Framework\Registry $coreRegistry,
//        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
//        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
//        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
//        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
//        \Magento\Customer\Helper\View $helperView,
        \MW\FreeGift\Model\Salesrule $salesruleModel,
        \MW\FreeGift\Model\ResourceModel\Salesrule\CollectionFactory $salesruleCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
//        $this->currentCustomer = $currentCustomer;
//        $this->_subscriberFactory = $subscriberFactory;
//        $this->_helperView = $helperView;
        $this->checkoutSession = $checkoutSession;
        $this->sessionManager = $sessionManager; // core/session
//        $this->helperFreeGift = $helperFreeGift;
//        $this->_coreRegistry = $coreRegistry;
//        $this->_resourceRule = $resourceRule;
//        $this->productRepository = $productRepository;
        $this->salesruleModel = $salesruleModel;
        $this->_scopeConfig = $scopeConfig;
        $this->_salesruleCollectionFactory = $salesruleCollectionFactory;

        parent::__construct($context, $data);
    }

    public function getAllActiveRules()
    {
        $quote           = $this->checkoutSession->getQuote();
        $store           = $this->_storeManager->getStore($quote->getStoreId());
        $websiteId       = $store->getWebsiteId();
        //$websiteId       = Mage::app()->getStore($quote->getStoreId())->getWebsiteId();
        $customerGroupId = $quote->getCustomerGroupId() ? $quote->getCustomerGroupId() : 0;
        $flagRule            = $this->sessionManager->getFlagRule();

        $arrRule = explode(",",$flagRule);
        $allowRule = $arrRule;
        $collection = $this->_salesruleCollectionFactory->create()->setValidationFilter($websiteId, $customerGroupId);

        $aplliedRuleIds = $this->checkoutSession->getQuote()->getFreegiftAppliedRuleIds();
        $arrRuleApllieds = explode(',',$aplliedRuleIds);

        $collection->getSelect()->where('((discount_qty > times_used) or (discount_qty=0))');
        $collectionSaleRule = $this->_salesruleCollectionFactory->create()->setOrder("sort_order", "ASC");
        $collectionSaleRule->getSelect()->where('is_active = 1');
        $listSaleRule = array();
        foreach ($collectionSaleRule as $saleRule) {
            if(in_array($saleRule->getId(),$arrRuleApllieds)){
                if($saleRule->getStopRulesProcessing()){
                    $listSaleRule[] = $saleRule->getId();
                    break;
                }
            }
            $listSaleRule[] = $saleRule->getId();
        }
        $collection->addFieldToFilter('rule_id', array(
            'in' => $listSaleRule
        ));

        if (sizeof($arrRuleApllieds)){
            $collection->addFieldToFilter('rule_id', array(
                'nin' => $arrRuleApllieds
            ));
        }
        //$this->xlog(serialize($collection->getData()));
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
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '<div class="freegift_rules_container"></div>';
        if (!sizeof($this->getAllActiveRules()))
            return '<div class="freegift_rules_container"></div>';

        return $this->fetchView($this->getTemplateFile());
    }
    function xlog($message = 'null'){
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }
}