<?php
namespace MW\FreeGift\Block;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
class Promotionbanner extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $_coreRegistry;
    protected $helperFreeGift;
    protected $_resourceRule;
    protected $productRepository;
    /**
     * Image factory
     *
     * @var \Magento\Framework\Image\Factory
     */
    protected $imageFactory;
    /**
     * Media directory
     *
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * Root directory
     *
     * @var WriteInterface
     */
    protected $rootDirectory;

    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::promotion_banner.phtml';


    protected $_scopeConfig;
    protected $salesruleModel;
    protected $sessionManager;
    /**
     * @var \MW\FreeGift\Model\ResourceModel\Salesrule\CollectionFactory
     */
    protected $_salesruleCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        SessionManagerInterface $sessionManager,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Framework\Filesystem $filesystem,
        \MW\FreeGift\Model\Salesrule $salesruleModel,
        \MW\FreeGift\Model\ResourceModel\Salesrule\CollectionFactory $salesruleCollectionFactory,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession; // checkout/session
        $this->helperFreeGift = $helperFreeGift;
        $this->sessionManager = $sessionManager; // core/session
        $this->salesruleModel = $salesruleModel;
        $this->_scopeConfig = $scopeConfig;
        $this->_salesruleCollectionFactory = $salesruleCollectionFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->imageFactory = $imageFactory;
        parent::__construct($context, $data);
    }

    public function getAllActiveRules()
    {
        $quote           = $this->checkoutSession->getQuote();
        $store           = $this->_storeManager->getStore($quote->getStoreId());
        $websiteId       = $store->getWebsiteId();

        $customerGroupId = $quote->getCustomerGroupId() ? $quote->getCustomerGroupId() : 0;
        $flagRule            = $this->sessionManager->getFlagRule();

        $arrRule = explode(",",$flagRule);
        $allowRule = $arrRule;
        $collection = $this->_salesruleCollectionFactory->create()->setValidationFilter($websiteId, $customerGroupId);

        $aplliedRuleIds = $this->checkoutSession->getQuote()->getFreegiftAppliedRuleIds();
        $arrRuleApllieds = ( $aplliedRuleIds != '' ? explode(',',$aplliedRuleIds) : array() );

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
        return $collection;
    }

    public function resizeImg($fileName, $width, $height = '', $folderResized = "resized")
    {
        $resizedURL = null;
        if($this->mediaDirectory->isExist($fileName)){
            if ($width != '') {
                $image = $this->imageFactory->create($this->mediaDirectory->getAbsolutePath($fileName));
                $image->constrainOnly(true);
                $image->keepFrame(FALSE);
                $image->keepAspectRatio(FALSE);
                $image->resize($width, $height);
                $image->save($this->mediaDirectory->getAbsolutePath($folderResized.'/'.$fileName));
                $resizedURL = $this->getImageUrl($fileName, $folderResized);
            }
            return $resizedURL;
        }
        return false;
    }

    /**
     * Retrieve image URL
     *
     * @return string
     */
    public function getImageUrl($image, $folder = null)
    {
        $url = false;
//        $image = $this->getImage();
        if ($image) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $folder .'/'. $image;
        }
        return $url;
    }

    public function _toHtml()
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';
        if (!sizeof($this->getAllActiveRules()))
            return '<div class="freegift_rules_banner_container"></div>';
        return $this->fetchView($this->getTemplateFile());
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