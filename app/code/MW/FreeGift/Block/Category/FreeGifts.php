<?php
/**
 * Created by PhpStorm.
 * User: lap15
 * Date: 9/4/2015
 * Time: 5:10 PM
 */

namespace MW\FreeGift\Block\Category;


class FreeGifts extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    protected $helperFreeGift;
    protected $_resourceRule;
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /** @var CheckoutSession */
    protected $_checkoutSession;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Framework\Registry $coreRegistry,
        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->helperFreeGift = $helperFreeGift;
        $this->_coreRegistry = $coreRegistry;
        $this->_resourceRule = $resourceRule;
        $this->productRepository = $productRepository;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    public function getFreeGiftCatalog($current_product = null)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $customerGroupId = $this->_customerSession->getCustomerGroupId();
        $dateTs = $this->_localeDate->scopeTimeStamp($storeId);
        $freeGiftCatalogData = array();

        if ($current_product == null) {
            $current_product = $this->getCurrentProduct();
        }
//        if ($additionalOption = $current_product->getCustomOption('mw_free_catalog_gift'))
//        {
//            if($additionalOption->getValue() == 1){
                $ruleGift = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $current_product->getProductId());
                if(count($ruleGift)>0){
                    $freeGiftCatalogData = $this->helperFreeGift->getGiftDataByRule($ruleGift);
                }
//            }
//        }
        return $freeGiftCatalogData;
    }

    public function getProductGiftData($productId)
    {
        $product_gift = null;
        $storeId = $this->_storeManager->getStore()->getId();
        if(isset($productId) && $productId != "")
            $product_gift = $this->productRepository->getById($productId, false, $storeId);
        return $product_gift;
    }

    public function getCurrentProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }

    /**
     * Generate content to log file debug.log By Hattetek.Com
     *
     * @param  $message string|array
     * @return void
     */
    function xlog($message = 'null')
    {
        $log = print_r($message, true);
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($log)
        ;
    }
    /* Get product thoa man cac rule*/
    public function getProductGift(){
        // @TODO
        /** @var \Magento\Quote\Model\Quote  */
        $quote = $this->_checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
//        \Zend_Debug::dump(count($items)); die("items");
        foreach($items as $item){
            if($item->getOptionByCode('mw_free_catalog_gift') && $item->getOptionByCode('mw_free_catalog_gift')->getValue() == 1){
                return $this->getFreeGiftCatalog($item);
            }
        }
        return array();
    }

    public function getProductGiftsDeleted(){
        $productGiftsDeleted = $this->helperFreeGift->getProductGiftAvailable();
        return $productGiftsDeleted;
    }
}