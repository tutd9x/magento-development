<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Price rules observer model
 */
namespace MW\FreeGift\Model;

use Magento\Backend\Model\Session as BackendModelSession;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use MW\FreeGift\Model\Rule;
use MW\FreeGift\Model\Salesrule;
//use MW\FreeGift\Model\Rule\Product\Price;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Stdlib\DateTime;
use Magento\Checkout\Model\Cart as CustomerCart;
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Observer
{
    /**
     * Store calculated catalog rules prices for products
     * Prices collected per website, customer group, date and product
     *
     * @var array
     */
    protected $_rulePrices = [];

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CustomerModelSession
     */
    protected $_customerSession;

    /**
     * @var \MW\FreeGift\Model\ResourceModel\Rule\CollectionFactory
     * @var \MW\FreeGift\Model\ResourceModel\RuleFactory
     * @var \MW\FreeGift\Model\ResourceModel\Rule
     */
    protected $_ruleCollectionFactory;
    protected $_resourceRuleFactory;
    protected $_resourceRule;

    protected $_ruleFactory;
    protected $_salesruleFactory;
    protected $_couponFactory;

    /**
     * @var \MW\FreeGift\Model\ResourceModel\Salesrule\CollectionFactory
     * @var \MW\FreeGift\Model\ResourceModel\SalesruleFactory
     * @var \MW\FreeGift\Model\ResourceModel\Salesrule
     */
    protected $_salesruleCollectionFactory;
    protected $_resourceSalesruleFactory;
    protected $_resourceSalesrule;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;
    protected $logger;
    protected $helperFreeGift;
    protected $layoutFactory;
    /**
     * Discount calculation object
     *
     * @var \Magento\SalesRule\Model\Validator
     */
    protected $_calculator;

    protected $_validator;
    protected $productRepository;

    /**
     * Catalog product
     *
     * @var \Magento\Catalog\Helper\Product
     */
    protected $catalogProduct;
    /**
     * @param ResourceModel\RuleFactory $resourceRuleFactory
     * @param ResourceModel\Rule $resourceRule
     * @param ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     * @param CustomerModelSession $customerSession
     * @param Registry $coreRegistry
     * @param DateTime $dateTime
     * @param GroupManagementInterface $groupManagement
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \MW\FreeGift\Model\RuleFactory $ruleFactory,
        \MW\FreeGift\Model\SalesruleFactory $salesruleFactory,

        ResourceModel\RuleFactory $resourceRuleFactory,
        ResourceModel\Rule $resourceRule,
        ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,

        ResourceModel\SalesruleFactory $resourceSalesruleFactory,
        ResourceModel\Salesrule $resourceSalesrule,
        ResourceModel\Salesrule\CollectionFactory $salesruleCollectionFactory,

        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CustomerModelSession $customerSession,
        Registry $coreRegistry,
        DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        CustomerCart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \MW\FreeGift\Model\Validator $validator,
        \Magento\Framework\View\LayoutFactory $layoutFactory,

        \MW\FreeGift\Model\CouponFactory $couponFactory,
        \Magento\Catalog\Helper\Product $catalogProduct
    ) {

        $this->_ruleFactory = $ruleFactory;
        $this->_salesruleFactory = $salesruleFactory;

        $this->_resourceRuleFactory = $resourceRuleFactory;
        $this->_resourceRule = $resourceRule;
        $this->_ruleCollectionFactory = $ruleCollectionFactory;

        $this->_resourceSalesruleFactory = $resourceSalesruleFactory;
        $this->_resourceSalesrule = $resourceSalesrule;
        $this->_salesruleCollectionFactory = $salesruleCollectionFactory;

        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $coreRegistry;
        $this->dateTime = $dateTime;
        $this->groupManagement = $groupManagement;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->helperFreeGift = $helperFreeGift;
        $this->_calculator = $validator;
        $this->layoutFactory = $layoutFactory;

        $this->_couponFactory = $couponFactory;
        $this->catalogProduct = $catalogProduct;

    }
    // set custom price for gift in quote
    // 789
    public function processFrontFinalPrice($observer)
    {
        $product = $observer->getEvent()->getProduct();
//        print_r($product->getName()); exit;
        $this->logger->critical($product->getCustomOptions());
//        $pId = $product->getId();
//        $storeId = $product->getStoreId();
        if ($product->getCustomOption('free_catalog_gift') || $product->getCustomOption('freegift')) {
            $product->setFinalPrice(0);
//            $product->setCustomPrice(0);
//            $product->setPrice(0);
//            $product->setOriginalPrice(0);
//            $product->setPriceCalculation(0);
        }
    }
    // set custom price for gift in cart after add to cart
    // 123 checkout_cart_product_add_after
    public function applyDiscount($observer)
    {
        /*
         * Procress custom price for freegift of salesrule by coupon_code */
        $item = $observer->getQuoteItem();

//        $this->xlog(get_class_methods($item));
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        //process for child product item
        $item->setNoDiscount(1);
        $child_items = $item->getChildren();
        if (count($child_items) > 0) {
            foreach ($child_items as $child_item) {
                $info_buyRequest_child = $child_item->getOptionByCode('info_buyRequest');
                if ($info_buyRequest_child) {
                    $info = unserialize($info_buyRequest_child->getValue());
                    if((isset($info['freegift_with_code']) && $info['freegift_with_code'] == 1) || (isset($info['freegift']) && $info['freegift'] == 1)){
                        $child_item->setCustomPrice(0);
                        $child_item->setOriginalCustomPrice(0);
                        $child_item->setFinalPrice(0);
                        $child_item->setNoDiscount(0);

                        $child_item->getProduct()->setIsSuperMode(true);
                    }
                }
            }
        }

        //process for parent product item
        $giftcode_options = $item->getOptionByCode('freegift_with_code');
        $giftsales_options = $item->getOptionByCode('freegift');

        if(($giftcode_options && $giftcode_options->getValue() == 1) || ($giftsales_options && $giftsales_options->getValue() == 1)){
            $item->setCustomPrice(0);
            $item->setOriginalCustomPrice(0);
            $item->setFinalPrice(0);
//            $item->setPrice(0);
//            $item->setBasePrice(0);
//            $item->setOriginalPrice(0);
//            $item->setPriceCalculation(0);

            $item->getProduct()->setIsSuperMode(true);
//            $item->save();
//            $quote = $this->checkoutSession->getQuote();
//            $quote->setTotalsCollectedFlag(false)->collectTotals();
            return $this;
        }

        $product = $observer->getProduct();
        $randKey = md5(rand(1111, 9999));

        $storeId = $product->getStoreId();
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();

        if ($product->hasCustomerGroupId()) {
            $customerGroupId = $product->getCustomerGroupId();
        } else {
            $customerGroupId = $this->_customerSession->getCustomerGroupId();
        }

        $dateTs = $this->_localeDate->scopeTimeStamp($storeId);

//        $this->xlog($dateTs);
//        $this->xlog($websiteId);
//        $this->xlog($customerGroupId);

        $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $product->getId());

        if(!empty($ruleData)){
            $gift_product_ids = [];
            $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData);

            if(count($gift_product_ids) > 0){
                if(!$this->checkoutSession->getGiftProductIds()){
                    $this->checkoutSession->setGiftProductIds($gift_product_ids);
                }

                foreach($gift_product_ids as $giftData){
                    if ($giftData) {
                        $params['product'] = $giftData['rule_gift_ids'];
                        $params['rule_name'] = $giftData['name'];
                        $storeId = 1;
                        $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
//                        $product_gift->addCustomOption('gift', 1);
                        $product_gift->addCustomOption('free_catalog_gift', 1);
                        $product_gift->addCustomOption('freegift_parent_key', $randKey);

                        $additionalOptions = [[
                            'label' => 'Free Gift',
                            'value' => $giftData['name'],
                            'print_value' => $giftData['name'],
                            'option_type' => 'text',
                            'custom_view' => TRUE,
                            'mw_freegift_rule_gift' => 1,
                            'mw_applied_catalog_rule' => $giftData['rule_id'],
                            'freegift_parent_key' => $randKey
                        ]];
                        // add the additional options array with the option code additional_options
                        $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
                        $this->cart->addProduct($product_gift, $params);
                    }
                }
                $this->cart->save();
            }
        }

        /* @var $item Mage_Sales_Model_Quote_Item */
        /* Call again because need use to get new product added */
        $item = $observer->getQuoteItem();
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        $gift_options = $item->getOptionByCode('free_catalog_gift');
        if($gift_options && $gift_options->getValue() == 1){
            $item->setCustomPrice(0);
            $item->setOriginalCustomPrice(0);
            $item->getProduct()->setIsSuperMode(true);
        }

        if(!empty($ruleData) && count($gift_product_ids) > 0) {
            $infoRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

            $applied_rule_ids = $this->helperFreeGift->_prepareRuleIds($gift_product_ids);
            if (!isset($infoRequest['freegift_key'])) {
                $infoRequest['freegift_key'] = $randKey;
                $infoRequest['mw_applied_catalog_rule'] = serialize($applied_rule_ids);
            } else {
                $randKey = $infoRequest['freegift_key'];
            }

            $item->getOptionByCode('info_buyRequest')->setValue(serialize($infoRequest))->save();
        }
    }

    public  function test(){
//        $this->xlog('test');
    }
    /*
     * Process for salerule
     * */
    public function applyDiscountSalesrule()
    {
        $quote = $this->checkoutSession->getQuote();
        $salesruleData = [];
        $gift_sales_product_ids = [];
        $rule_ids = explode(',',$quote->getFreegiftAppliedRuleIds());

        foreach($rule_ids as $rule_id){
            $salesrule = $this->_salesruleFactory->create()->load($rule_id);
            $salesruleData[$rule_id] = $salesrule->getData();
        }

        $gift_sales_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($salesruleData, 'getGiftOfSalesRule');

        if(!empty($salesruleData)){
            if(count($gift_sales_product_ids) > 0){
//                if(!$this->checkoutSession->getGiftSalesProductIds()){
                    $this->checkoutSession->setGiftSalesProductIds($gift_sales_product_ids);
//                }

//                foreach($gift_sales_product_ids as $giftData){
//                    if ($giftData) {
//                        if($giftData['number_of_free_gift'] >= count($gift_sales_product_ids) ){
//                            //@@TODO $storeId
//                            $storeId = 1;
//                            $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
//                            if($product_gift->getTypeId() == 'simple'){
//                                $params['qty'] = 1;
//                                $params['product'] = $giftData['rule_gift_ids'];
//                                $params['freegift'] = '1';
//                                $params['freegift_name'] = $giftData['name'];
//                                $params['rule_id'] = $giftData['rule_id'];
//
//                                $product_gift->addCustomOption('freegift', 1);
//
//                                $additionalOptions = [[
//                                    'label' => 'Free Gift',
//                                    'value' => $giftData['name'],
//                                    'print_value' => $giftData['name'],
//                                    'option_type' => 'text',
//                                    'custom_view' => TRUE,
//                                    'freegift' => 1,
//                                    'freegift_name' => $giftData['name']
//                                ]];
//                                // add the additional options array with the option code additional_options
//                                $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
//                                $this->cart->addProduct($product_gift, $params);
//                            }
//                        }
//                    }
//                }
//                $this->cart->save();
            }
        }

        /*
         * Next step: set custom price for gift at event checkout_cart_product_add_after
         * */
    }

    /*
     * Process for salerule with freegift coupon code
     * */
    public function applyFreegiftCode($observer)
    {

        $quote = $observer->getQuote();
        $freegift_coupon_code = $quote->getFreegiftCouponCode();

        $salesrule_coupon = $this->_couponFactory->create()->loadByCode($freegift_coupon_code);
        $rule_id = $salesrule_coupon->getRuleId();
        $salesrule = $this->_salesruleFactory->create()->load($rule_id);
        $salesruleData[$rule_id] = $salesrule->getData();
        $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($salesruleData, 'getGiftOfSalesRule');

        if(!empty($salesruleData)){
            if(count($gift_product_ids) > 0){
                if(!$this->checkoutSession->getGiftSalesProductIds()){
                    $this->checkoutSession->setGiftSalesProductIds($gift_product_ids);
                }

                foreach($gift_product_ids as $giftData){
                    if ($giftData) {
                        if($giftData['number_of_free_gift'] >= count($gift_product_ids) ){
                            $storeId = 1;
                            $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
                            if($product_gift->getTypeId() == 'simple') {
                                $params['product'] = $giftData['rule_gift_ids'];
                                $params['freegift_with_code'] = '1';
                                $params['freegift_coupon_code'] = $freegift_coupon_code;
                                $params['rule_id'] = $giftData['rule_id'];


                                $product_gift->addCustomOption('freegift_coupon_code', 1);

                                $additionalOptions = [[
                                    'label' => 'Free Gift',
                                    'value' => $giftData['name'],
                                    'print_value' => $giftData['name'],
                                    'option_type' => 'text',
                                    'custom_view' => TRUE,
                                    'freegift_with_code' => 1,
                                    'freegift_coupon_code' => $freegift_coupon_code
                                ]];
                                // add the additional options array with the option code additional_options
                                $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
                                $this->cart->addProduct($product_gift, $params);
                            }
                        }
                    }
                }
            }
            $this->cart->save();
        }

        /*
         * Next step: set custom price for gift at event checkout_cart_product_add_after
         * */
    }

    public function applyFreegiftCodeCanceled($observer)
    {
        $quote = $observer->getQuote();
        $items = $quote->getAllVisibleItems();
        $oldCouponCode = $observer->getOldCouponCode();

        foreach($items as $item)
        {
            $info_buyRequest = $item->getOptionByCode('info_buyRequest');
            if(isset($info_buyRequest)) {
                $data = unserialize($info_buyRequest->getValue());
                if(isset($data['freegift_coupon_code']) && $data['freegift_coupon_code'] == $oldCouponCode) {
                    // remove out of quote
                    $quote->removeItem($item->getItemId())->save();
                }
            }
        }
        $this->checkoutSession->unsGiftSalesProductIds();
    }

    public function catalogCategoryProductLoadBefore($observer)
    {
        $gift_producty_ids = 0;
        $collection = $observer->getCollection();
        foreach($collection as $product){

            $storeId = $product->getStoreId();
            $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();

            if ($product->hasCustomerGroupId()) {
                $customerGroupId = $product->getCustomerGroupId();
            } else {
                $customerGroupId = $this->_customerSession->getCustomerGroupId();
            }

            $dateTs = $this->_localeDate->scopeTimeStamp($storeId);

//            $this->xlog($dateTs);
//            $this->xlog($websiteId);
//            $this->xlog($customerGroupId);

            $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $product->getId());
            if(empty($ruleData)){
                return $this;
            }
            $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData, TRUE);
            if(count($gift_product_ids) > 0){
                $product->addCustomOption('mw_free_catalog_gift', '1');
            }
        }
    }
    // add option for gift.
    // 000
    public function catalogProductLoadAfter($observer)
    {
        $gift_producty_ids = 0;
        $product = $observer->getProduct();

        $storeId = $product->getStoreId();
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();

        if ($product->hasCustomerGroupId()) {
            $customerGroupId = $product->getCustomerGroupId();
        } else {
            $customerGroupId = $this->_customerSession->getCustomerGroupId();
        }

        $dateTs = $this->_localeDate->scopeTimeStamp($storeId);
//        $this->xlog($dateTs);
//        $this->xlog($websiteId);
//        $this->xlog($customerGroupId);

        $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $product->getId());
//        $ruleData = $this->helperFreeGift->getRulesByProductId($product->getId());
        if(empty($ruleData)){
            return $this;
        }
        //$rule_id = $ruleData[0]['rule_id'];
        //$collection = $this->_ruleCollectionFactory->create();
        //$gift_producty_ids = explode(",",$collection->getItemById($rule_id)->getGiftProductIds());

        $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData, TRUE);

        if(count($gift_product_ids) > 0){
            $product->addCustomOption('mw_free_catalog_gift', 1);
        }
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    // add gift to cart.
    // x456 checkout_cart_add_product_complete
    public function processAddToCart($observer)
    {
        //$gift_product_ids = array();
        $request = $observer->getEvent()->getRequest();
        $params = $request->getParams();
        $product = $observer->getProduct();

//        $ruleData = $this->_resourceRule->getRulesFromProduct(null, 1, 1, $params['product']);
//        $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData);
//        if(!empty($ruleData)){
//
//            if(count($gift_product_ids) > 0){
//                if(!$this->checkoutSession->getGiftProductIds()){
//                    $this->checkoutSession->setGiftProductIds($gift_product_ids);
//                }
//
//                foreach($gift_product_ids as $giftData){
//                    if ($giftData) {
//                        $params['product'] = $giftData['rule_gift_ids'];
//                        $params['rule_name'] = $giftData['name'];
//                        $storeId = 1;
//                        $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
//                        $product_gift->addCustomOption('gift', 1);
//
//                        $additionalOptions = [[
//                            'label' => 'Free Gift',
//                            'value' => $giftData['name'],
//                            'print_value' => $giftData['name'],
//                            'option_type' => 'text',
//                            'custom_view' => TRUE,
//                            'mw_freegift_rule_gift' => 1
//                        ]];
//                        // add the additional options array with the option code additional_options
//                        $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
//                        $this->cart->addProduct($product_gift, $params);
//                    }
//                }
//            }
//            $this->cart->save();
//        }

        return $this;
        exit;
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function processFrontFinalPrice1($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $pId = $product->getId();
        $storeId = $product->getStoreId();

        if ($observer->hasDate()) {
            $date = new \DateTime($observer->getEvent()->getDate());
        } else {
            $date = $this->_localeDate->scopeDate($storeId);
        }

        if ($observer->hasWebsiteId()) {
            $wId = $observer->getEvent()->getWebsiteId();
        } else {
            $wId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        }

        if ($observer->hasCustomerGroupId()) {
            $gId = $observer->getEvent()->getCustomerGroupId();
        } elseif ($product->hasCustomerGroupId()) {
            $gId = $product->getCustomerGroupId();
        } else {
            $gId = $this->_customerSession->getCustomerGroupId();
        }

        $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}";
        if (!isset($this->_rulePrices[$key])) {
            $rulePrice = $this->_resourceRuleFactory->create()->getRulePrice($date, $wId, $gId, $pId);
//            print_r($rulePrice); exit;
            $this->_rulePrices[$key] = $rulePrice;
        }
        if ($this->_rulePrices[$key] !== false) {
            $finalPrice = min($product->getData('final_price'), $this->_rulePrices[$key]);
            $product->setFinalPrice($finalPrice);
        }
        return $this;
    }

    /**
     * Apply catalog price rules to product in admin
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function processAdminFinalPrice($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        $date = $this->_localeDate->scopeDate($storeId);
        $key = false;

        $ruleData = $this->_coreRegistry->registry('rule_data');
        if ($ruleData) {
            $wId = $ruleData->getWebsiteId();
            $gId = $ruleData->getCustomerGroupId();
            $pId = $product->getId();

            $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}";
        } elseif ($product->getWebsiteId() !== null && $product->getCustomerGroupId() !== null) {
            $wId = $product->getWebsiteId();
            $gId = $product->getCustomerGroupId();
            $pId = $product->getId();
            $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}";
        }

        if ($key) {
            if (!isset($this->_rulePrices[$key])) {
                $rulePrice = $this->_resourceRuleFactory->create()->getRulePrice($date, $wId, $gId, $pId);
                $this->_rulePrices[$key] = $rulePrice;
            }
            if ($this->_rulePrices[$key] !== false) {
                $finalPrice = min($product->getData('final_price'), $this->_rulePrices[$key]);
                $product->setFinalPrice($finalPrice);
            }
        }

        return $this;
    }

    /**
     * Clean out calculated catalog rule prices for products
     *
     * @return void
     */
    public function flushPriceCache()
    {
        $this->_rulePrices = [];
    }

    /**
     * event: sales_quote_item_collection_products_after_load
     * xu ly product in quote. (validate rule)
     * @param EventObserver $observer
     * @return $this
     */
    public function prepareCatalogProductCollectionPrices(EventObserver $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        if(count($items) >= 0 && $quote->getSubtotal() == 0){

            $quote->unsFreegiftIds();
            $quote->unsFreegiftAppliedRuleIds();
            $quote->unsFreegiftCouponCode();

            $quote->removeAllItems()->save();
            $this->cart->truncate();

            $this->resetSession();

            return $this;
        }


        //print_r($this->checkoutSession->getGiftProductIds()); die;
        /* @var $collection ProductCollection */
        $collection = $observer->getEvent()->getProductCollection();
        $store = $this->_storeManager->getStore($observer->getEvent()->getStoreId());
        $websiteId = $store->getWebsiteId();
        if ($observer->getEvent()->hasCustomerGroupId()) {
            $groupId = $observer->getEvent()->getCustomerGroupId();
        } else {
            if ($this->_customerSession->isLoggedIn()) {
                $groupId = $this->_customerSession->getCustomerGroupId();
            } else {
                $groupId = $this->groupManagement->getNotLoggedInGroup()->getId();
            }
        }
        if ($observer->getEvent()->hasDate()) {
            $date = new \DateTime($observer->getEvent()->getDate());
        } else {
            $date = (new \DateTime())->setTimestamp($this->_localeDate->scopeTimeStamp($store));
        }

        $productIds = [];
        /* @var $product Product */
        foreach ($collection as $product) {
//                $this->xlog(get_class_methods($product));
//                $this->xlog($product->getCustomOptions());
            $productIds[] = $product->getId();
        }

        if ($productIds) {

            $storeId = $product->getStoreId();
            $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();

            if ($product->hasCustomerGroupId()) {
                $customerGroupId = $product->getCustomerGroupId();
            } else {
                $customerGroupId = $this->_customerSession->getCustomerGroupId();
            }

            $dateTs = $this->_localeDate->scopeTimeStamp($storeId);

//            $this->xlog($dateTs);
//            $this->xlog($websiteId);
//            $this->xlog($customerGroupId);

//            $salesruleGift = $this->_resourceSalesrule->getRulesFromProduct(null, 1, 1, $productIds);
            $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId,$customerGroupId, $productIds);
//            $this->xlog(serialize($ruleGift));
            $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData);

            if (!$this->checkoutSession->getGiftProductIds()) {
                $this->checkoutSession->setGiftProductIds($gift_product_ids);
            }

        } else {
            $this->checkoutSession->unsetData('gift_product_ids');
            return $this;
        }

        return $this;
    }

    /**
     * Get quote item validator/processor object
     *
     * @deprecated
     * @param   Varien_Event $event
     * @return  Mage_SalesRule_Model_Validator
     */
    public function getValidator($event)
    {
        if (!$this->_validator) {
            $this->_validator = $this->_calculator->init($event->getWebsiteId(), $event->getCustomerGroupId(), $event->getFreegiftCouponCode());
        }
        return $this->_validator;
    }

    function processQuoteDiscount($observer){
        $this->getValidator($observer->getEvent())->process($observer->getEvent()->getItem());
    }

    protected function resetSession()
    {
        $this->xlog(__LINE__.' '.__FILE__);
        $this->checkoutSession->unsetData('gift_product_ids');
        $this->checkoutSession->unsetData('gift_sales_product_ids');

        $this->checkoutSession->unsRulegifts();
        $this->checkoutSession->unsProductgiftid();
        $this->checkoutSession->unsGooglePlus();
        $this->checkoutSession->unsLikeFb();
        $this->checkoutSession->unsShareFb();
        $this->checkoutSession->unsTwitter();

        return $this;
    }

    public function afterRemoveItem($observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        if(count($items) >= 0 && $quote->getSubtotal() == 0){

            $quote->unsFreegiftIds();
            $quote->unsFreegiftAppliedRuleIds();
            $quote->unsFreegiftCouponCode();

            $quote->removeAllItems()->save();
            $this->cart->truncate();

            $this->resetSession();

            return;
        }

        $info_buyRequest_of_item_removed = [];
        $parent_key_removed = '';
        $quote_item_removed = $observer->getQuoteItem();
        if($quote_item_removed->getOptionByCode('info_buyRequest')){
            $info_buyRequest_of_item_removed = unserialize($quote_item_removed->getOptionByCode('info_buyRequest')->getValue());
        }

        if(isset($info_buyRequest_of_item_removed['freegift_key'])){
            $parent_key_removed = $info_buyRequest_of_item_removed['freegift_key'];
        }

        if($parent_key_removed != ''){
            foreach ( $items as $item ) {
                $additional_options = $item->getOptionByCode('additional_options');
                if(isset($additional_options)){
                    $dataOptions = unserialize($additional_options->getValue());
                    foreach($dataOptions as $data){
                        if(isset($data['freegift_parent_key']) && $data['freegift_parent_key'] == $parent_key_removed) {
                            // remove out of quote
                            $quote->removeItem($item->getItemId())->save();
                        }
                    }
                }

            }
        }

        // process for salesrule gift



    }

    // add gift to cart when gift product type is special
    public function checkout_cart_add_product($params, $product, $cart)
    {
        //$cart = $this->checkoutSession;
        $layout = $this->layoutFactory->create();
//        $update = $layout->getUpdate();
        $layout->getUpdate()->load(['checkout_cart_index']);
        $layout->generateXml();
        $layout->generateElements();

        // process for gift by catalog rule
        if (isset($params['free_catalog_gift'])) {
            $flag_update = true;
            if (isset($params['super_attribute'])) {
                foreach ($params['super_attribute'] as $k => $attr) {
                    if (empty($attr)) {
                        $flag_update = false;
                    }
                }
            }
            if (isset($params['options'])) {
                foreach ($params['options'] as $k => $attr) {
                    if (empty($attr)) {
                        $flag_update = false;
                    }
                }
            }

            if (!isset($params['upd'])) {
                $block_product = $layout->createBlock('MW\FreeGift\Block\Product');
                $missingGiftProducts = $this->helperFreeGift->getFreeGiftCatalogProduct(); //$block_product->getFreeGiftCatalogProduct();

                $quote_parent_item = false; // = $this->getQuoteItemByGiftItemId($params['free_catalog_gift']);
                $items = $this->checkoutSession->getQuote()->getAllItems();
                foreach ($items as $item) {
                    if ($params['free_catalog_gift'] == $item->getItemId()){
                        $quote_parent_item = $item;
                        break;
                    }
                }

//                $optionParentCollection = Mage::getModel('sales/quote_item_option')
//                    ->getCollection()
//                    ->addFieldToFilter('item_id', $params['free_catalog_gift']);
//
//                foreach ($optionParentCollection as $opt) {
//                    if ($opt->getCode() == 'info_buyRequest') {
//                        $infoRequest = unserialize($opt->getValue());
//                        break;
//                    }
//                }
            }


            if ($flag_update === false) {
                echo json_encode(array('message' => 'Empty options.', 'error' => 1, 'action' => 'load_in_page', 'item_id' => ''));
                exit;
            }
//            if (isset($params['upd'])) {
//                if ($flag_update) {
//                    $quote_item = false; // $this->getQuoteItemByGiftItemId($params['item_id']);
//
//                    $items = $this->checkoutSession->getQuote()->getAllItems();
//                    foreach ($items as $item) {
//                        if ($params['item_id'] == $item->getItemId())
//                            $quote_item = $item;
//                    }
//
//                    if (isset($params['options'])) {
//                        foreach ($params['options'] as $optId => $opt) {
//                            $quote_item->getOptionByCode('option_' . $optId)->setValue($opt);
//                        }
//                    }
//                    $quote_item->getOptionByCode('attributes')->setValue(serialize($params['super_attribute']));
//                    $quote_item->getQuote()->save();
//                    $cart->save();
//
//                    $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
//                    echo json_encode(array(
//                        'message'   => '',
//                        'error'     => 0,
//                        'upd'       => 1,
//                        'item_id'   => $quote_item->getItemId(),
//                        'item_html' => $block_cart->getItemHtml($quote_item)
//                    ));
//                    return;
//                }
//            }

            $params['qty'] = 1;

            if ($quote_parent_item && $quote_parent_item->getItemId()) {

                $_product = $this->productRepository->getById($quote_parent_item->getProductId());
                $stock_qty = 100 ; //(int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty();
                $infoRequestParent = unserialize($quote_parent_item->getOptionByCode('info_buyRequest')->getValue());
                $catalogRules = unserialize($infoRequestParent['mw_applied_catalog_rule']);

                if(!in_array($params['applied_catalog_rule'], $catalogRules)){
                    return false;
                }
                $rule = $this->_ruleFactory->create()->load($params['applied_catalog_rule']);

                if ( 1 == 1
                //isset($missingGiftProducts[$params['free_catalog_gift']])
                    //&& in_array($params['product'], $missingGiftProducts[$params['free_catalog_gift']][$params['applied_catalog_rule']])
                ) {

                    $qty_4gift = 1 ; //$this->getQtyToAdded($product, $params, $quote_parent_item, $stock_qty);
                    unset($params['freegift']);

                    if ($qty_4gift > 0) {

//                        $product->addCustomOption(base64_encode(microtime()), serialize(array(time())));

                        $product->setFinalPrice(0);
                        $product->setCustomPrice(0);
                        $product->setPrice(0);
                        $product->setOriginalPrice(0);
                        $product->setPriceCalculation(0);

                        $product->addCustomOption('free_catalog_gift', 1);
                        $product->addCustomOption('freegift_parent_key', $infoRequestParent['freegift_key']);

                        $additionalOptions = [[
                            'label' => 'Free Gift',
                            'value' => $rule->getName(),
                            'print_value' => $rule->getName(),
                            'option_type' => 'text',
                            'custom_view' => TRUE,
                            'mw_freegift_rule_gift' => 1,
                            'mw_applied_catalog_rule' => $rule->getRuleId(),
                            'freegift_parent_key' => $infoRequestParent['freegift_key']
                        ]];
                        // add the additional options array with the option code additional_options
                        $product->addCustomOption('additional_options', serialize($additionalOptions));

                        $params['qty'] = $qty_4gift;
                        $params['mw_freegift_rule_gift'] = 1;
                        $params['freegift_parent_key'] = $infoRequestParent['freegift_key'];
                        $params['mw_applied_catalog_rule'] = $params['applied_catalog_rule'];
                        $params['text_gift'] = array(
                            'label' => 'Free Gift',
                            'value' => $rule->getName()
                        );

                        $cart->addProduct($product, $params);
                        $cart->save();

                        $last_item = '';
                        $items_reloaded = $this->checkoutSession->getQuote()->getAllItems();
                        $last_item = end($items_reloaded);

//                        foreach ($items as $item) {
//                            $last_item = $item;
//                        }

                        if (isset($params['ajax_gift']) && $params['ajax_gift'] == "1") {
                            //$block_cart = $layout->getBlock('checkout.cart.form');
                            $block_cart = $layout
                                ->createBlock('Magento\Checkout\Block\Cart');

                            $block_cart->addChild('renderer.list', '\Magento\Framework\View\Element\RendererList');
                            $block_cart->getChildBlock(
                                'renderer.list'
                            )->addChild(
                                'default',
                                '\Magento\Checkout\Block\Cart\Item\Renderer',
                                ['template' => 'cart/item/default.phtml']
                            );
//                            $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');

                            $block_freegift = $layout->createBlock('MW\FreeGift\Block\Product'); //Mage::app()->getLayout()->createBlock('freegift/product');
//                            $last_item = $this->getLastItemAdded();
                            $quote_item = $last_item; // $this->getQuoteItemByGiftItemId($last_item['item_id']);
                            echo json_encode(array(
                                'message'       => '',
                                'error'         => 0,
                                'item_id'       => $quote_item->getItemId(),
                                'item_html'     => $block_cart->getItemHtml($quote_item),
                                'freegift'      => $block_freegift->toHtml(),
                            ));
                            exit;
                        }
                        $this->checkoutSession->setCartWasUpdated(false);
                    } else {
                        echo json_encode(array('message' => 'Out of stock.', 'error' => 1));
                        exit;
                    }
                }
            }
        }

        // process for shopping cart rule
        if ((isset($params['freegift']) && $params['freegift']) || isset($params['freegift_with_code']) && $params['freegift_with_code'])
        {
            if (isset($params['product']) && is_numeric($params['product'])) {

                $params['qty'] = 1; // (!isset($params['qty'])) ? 1 : $params['qty'];
                $stock_qty = 100 ; //(int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty();
                $qty_4gift = 1 ; //$this->getQtyToAdded($product, $params, $quote_parent_item, $stock_qty);
                if ($qty_4gift > 0) {

//                    $product->addCustomOption(base64_encode(microtime()), serialize(array(time())));
                    $product->setFinalPrice(0);
                    $product->setCustomPrice(0);
                    $product->setOriginalCustomPrice(0);
                    $product->setPrice(0);
                    $product->setOriginalPrice(0);
                    $product->setPriceCalculation(0);

                    $product->addCustomOption('freegift_coupon_code', 1);

                    $options = [];
                    $options = [
                        'label' => 'Free Gift',
                        'value' => $params['rule_name'],
                        'print_value' => $params['rule_name'],
                        'option_type' => 'text',
                        'custom_view' => TRUE
                    ];

                    if(isset($params['freegift_with_code'])){
                        $options['freegift_with_code'] = 1;
                        $options['freegift_coupon_code'] = $params['freegift_coupon_code'];
                    }
                    if(isset($params['freegift'])){
                        $options['freegift'] = 1;
                    }

                    $additionalOptions = [$options];

                    if ($product->getTypeId() == 'grouped')
                    {
                        $_associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
                        foreach($_associatedProducts as $child_product){
                            $child_product->addCustomOption('additional_options', serialize($additionalOptions));
                            $child_product->addCustomOption('freegift', 1);
                            $child_product->setPrice(0);
                            $child_product->setCustomPrice(0);
                            $child_product->setOriginalCustomPrice(0);
                            $child_product->setOriginalPrice(0);
                            $child_product->setFinalPrice(0);
                            $child_product->setPriceCalculation(0);
                            $child_product->setIsSuperMode(true);
                        }
                    }

                    // add the additional options array with the option code additional_options
                    $product->addCustomOption('additional_options', serialize($additionalOptions));
                    $product->addCustomOption('freegift', 1);

                    //$params['qty'] = $qty_4gift;
                    $params['freegift_with_code'] = 1;
                    //$params['freegift_coupon_code'] = $params['freegift_coupon_code'];
                    $params['rule_id'] = $params['applied_rule'];

                    $this->cart->addProduct($product, $params);
                    $cart->save();

                    $last_item = '';
                    $quote = $this->checkoutSession->getQuote();
                    $items_reloaded = $quote->getAllItems();
                    $last_item = $quote->getItemByProduct($product);
                    if(!isset($last_item) || $last_item == false){
                        foreach($items_reloaded as $item){

                            $item_option_freegift = ($item->getOptionByCode('freegift') ? $item->getOptionByCode('freegift')->getValue() : null);
                            $item_option_info_buyRequest = ($item->getOptionByCode('info_buyRequest') ? unserialize($item->getOptionByCode('info_buyRequest')->getValue()) : null);
//                            $item_option_additional_options = ($item->getOptionByCode('additional_options') ? unserialize($item->getOptionByCode('additional_options')->getValue()) : null);
                            if($item_option_freegift){
                                if($item_option_info_buyRequest['super_product_config']['product_id']){
                                    if($item_option_info_buyRequest['super_product_config']['product_id'] == $product->getId()){
                                        $item->setNoDiscount(1);
                                        $item->setPrice(0);
                                        $item->setCustomPrice(0);
                                        $item->setOriginalCustomPrice(0);
                                        $item->setIsSuperMode(true);
//                                        $item->save();
                                        $last_item = $item;
                                    }
                                }

                            }
                        }
                    }else{
                        $last_item = end($items_reloaded);
                    }
//                    $last_item = $quote->getItemByProduct($product);

//                        foreach ($items as $item) {
//                            $last_item = $item;
//                        }

                    if (isset($params['ajax_gift']) && $params['ajax_gift'] == "1") {
//                        $block_cart = $layout->getBlock('checkout.cart.form');
                        $block_cart = $layout
                            ->createBlock('Magento\Checkout\Block\Cart');

                        $block_cart->addChild('renderer.list', '\Magento\Framework\View\Element\RendererList');
                        $block_cart->getChildBlock(
                            'renderer.list'
                        )->addChild(
                            'default',
                            '\Magento\Checkout\Block\Cart\Item\Renderer',
                            ['template' => 'cart/item/default.phtml']
                        );

//                            $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');

                        $block_freegift = $layout->createBlock('MW\FreeGift\Block\Product'); //Mage::app()->getLayout()->createBlock('freegift/product');
//                            $last_item = $this->getLastItemAdded();
                        $quote_item = $last_item; // $this->getQuoteItemByGiftItemId($last_item['item_id']);
                        echo json_encode(array(
                            'message'       => '',
                            'error'         => 0,
                            'item_id'       => $quote_item->getItemId(),
                            'item_html'     => $block_cart->getItemHtml($quote_item),
                            'freegift'      => $block_freegift->toHtml(),
                        ));
                        exit;
                    }
                    $this->checkoutSession->setCartWasUpdated(false);
                } else {
                    echo json_encode(array('message' => 'Out of stock.', 'error' => 1));
                    exit;
                }

            }
        }


        /*if ((isset($params['freegift']) && $params['freegift']) || isset($params['freegift_with_code']) && $params['freegift_with_code']) {
            if (isset($params['product']) && is_numeric($params['product'])) {
                $flag_update = true;
                if (isset($params['super_attribute'])) {
                    foreach ($params['super_attribute'] as $k => $attr) {
                        if (empty($attr)) {
                            $flag_update = false;
                        }
                    }
                }
                if (isset($params['options'])) {
                    foreach ($params['options'] as $k => $attr) {
                        if (empty($attr)) {
                            $flag_update = false;
                        }
                    }
                }
                if ($flag_update === false) {
                    echo json_encode(array('message' => 'Empty options.', 'error' => 1, 'action' => 'load_in_page', 'item_id' => ''));
                    exit;
                }

                if (isset($params['upd'])) {
                    $quote_item = $this->getQuoteItemByGiftItemId($params['item_id']);
                    if (isset($params['options'])) {
                        foreach ($params['options'] as $optId => $opt) {
                            $quote_item->getOptionByCode('option_' . $optId)->setValue($opt);
                        }
                    }
                    $quote_item->getOptionByCode('attributes')->setValue(serialize($params['super_attribute']));
                    $quote_item->getQuote()->save();
                    $cart->save();
                    $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
                    echo json_encode(array(
                        'message'   => '',
                        'error'     => 0,
                        'upd'       => 1,
                        'item_id'   => $quote_item->getItemId(),
                        'item_html' => $block_cart->addItemRender($quote_item->getProduct()->getTypeId(), 'checkout/cart_item_renderer', 'mw_freegift/checkout/cart/item/default.phtml')->getItemHtml($quote_item)
                    ));
                    return;
                }

                $params['qty'] = (!isset($params['qty'])) ? 1 : $params['qty'];
                $stock_qty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

                $qty_4gift = $this->getQtyToAdded($product, $params, null, $stock_qty);
                if (Mage::app()->getFrontController()->getRequest()->getParam('apllied_rule')) {
                    $rule = Mage::getModel('freegift/salesrule')->load((int)Mage::app()->getFrontController()->getRequest()->getParam('apllied_rule'));
                    $params['text_gift'] = array(
                        'label' => (isset($params['freegift_coupon_code']) || isset($params['freegift_with_code']) ? 'Free Gift with coupon' : 'Free Gift'),
                        'value' => $rule->getName()
                    );
                }

                $product->addCustomOption((isset($params['freegift_with_code']) ? 'freegift_with_code' : 'freegift'), 1);
                $product->addCustomOption(base64_encode(microtime()), serialize(array(time())));
                $product->setPrice(0);

                $quote_item = $cart->addProduct($product, $params);
                $cart->save();
                $last_item = $cart->getItems()->addFieldToFilter('product_id', $params['product'])->getLastItem();
                if ($last_item->getParentItem()) {
                    $last_item_insert_id = $last_item->getParentItemId();
                } else {
                    $last_item_insert_id = $last_item->getItemId();
                }

                $last_item_insert = Mage::getModel('sales/quote_item')->setStoreId(Mage::app()->getStore()->getId())->load($last_item_insert_id);

                foreach ($cart->getQuote()->getAllVisibleItems() as $item) {
                    if ($item->getItemId() != $last_item_insert_id) continue;
                    $quote_item = $item;
                    break;
                }

                $this->addProductWithRule($params, $product, $quote_item);
                if (isset($params['ajax_gift']) && $params['ajax_gift']) {
                    $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
                    $block_totals = Mage::app()->getLayout()->createBlock('checkout/cart_totals');
                    $block_freegift = Mage::app()->getLayout()->createBlock('freegift/product');
                    echo json_encode(array(
                        'message'       => '',
                        'error'         => 0,
                        'item_id'       => $quote_item->getItemId(),
                        'item_html'     => $block_cart->addItemRender($quote_item->getProduct()->getTypeId(), 'checkout/cart_item_renderer', 'mw_freegift/checkout/cart/item/default.phtml')->getItemHtml($quote_item),
                        'freegift'      => $block_freegift->toHtml(),
                    ));
                    exit;
                }
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                exit;
            }
        }*/


        return false;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function salesOrderAfterPlace($observer)
    {
        return $this;

        $order = $observer->getEvent()->getOrder();
        $items = $order->getAllItems();

        if (!$order) {
            return $this;
        }

        $this->checkoutSession->unsRulegifts();
        $this->checkoutSession->unsProductgiftid();
        $this->checkoutSession->unsGooglePlus();
        $this->checkoutSession->unsLikeFb();
        $this->checkoutSession->unsShareFb();
        $this->checkoutSession->unsTwitter();
//        return $this;

        $rule_inserted = array();
        foreach ($items as $item) {
            if ($item->getParentItem())
                continue;

            $collection = $this->_ruleFactory->create()->getCollection();
            //$con = Mage::getModel('core/resource')->getConnection('core_write');
            //Catalog rules
            $infoRequest = $item->getProductOptionByCode('info_buyRequest');

            // set times_used
            if (isset($infoRequest['applied_rule']) && $infoRequest['applied_rule']) {
                $applied_rules = explode(',',$infoRequest['applied_rules']);
                if (sizeof($applied_rules))
                    foreach ($applied_rules as $rule_id) {
                        // increase time_used
                        if (!in_array($rule_id, $rule_inserted['applied_rules'])) {
                            $sql = "UPDATE {$collection->getTable('freegift/rule')} SET times_used=times_used+1 WHERE rule_id={$rule_id}";
                            $con->query($sql);
                            $rule_inserted['applied_rules'][] = $rule_id;
                        }
                    }
            }

            if (isset($infoRequest['apllied_catalog_rules']) && $infoRequest['apllied_catalog_rules']) {
                $applied_rules = unserialize($infoRequest['apllied_catalog_rules']);
                if (sizeof($applied_rules))
                    foreach ($applied_rules as $rule_id) {
                        // increase time_used
                        if (!in_array($rule_id, $rule_inserted['apllied_catalog_rules'])) {
                            $sql = "UPDATE {$collection->getTable('freegift/rule')} SET times_used=times_used+1 WHERE rule_id={$rule_id}";
                            $con->query($sql);
                            $rule_inserted['apllied_catalog_rules'][] = $rule_id;
                        }
                    }
            }
            //Sales Rules
            if (!in_array($infoRequest['applied_rules'], $rule_inserted['applied_rules'])) {
                if (isset($infoRequest['applied_rules']) && $infoRequest['applied_rules']) {
                    if (isset($infoRequest['applied_rules'])):
                        $sql = "UPDATE {$collection->getTable('freegift/salesrule')} SET times_used=times_used+1 WHERE rule_id={$infoRequest['freegift_applied_rules']}";
                        $con->query($sql);
                    endif;
                }
                $rule_inserted['applied_rules'][] = $infoRequest['applied_rules'];
            }

            if (!in_array($infoRequest['rule_id'], $rule_inserted['applied_rules'])) {
                if (isset($infoRequest['freegift_with_code']) && $infoRequest['freegift_with_code']) {
                    if (isset($infoRequest['rule_id'])):
                        $sql = "UPDATE {$collection->getTable('freegift/salesrule')} SET times_used=times_used+1 WHERE rule_id={$infoRequest['rule_id']}";
                        $con->query($sql);
                    endif;
                }
                $rule_inserted['applied_rules'][] = $infoRequest['rule_id'];
            }
        }

        // lookup rule ids
        $ruleIds = explode(',', $order->getAppliedRuleIds());
        $ruleIds = array_unique($ruleIds);

        $ruleCustomer = null;
        $customerId = $order->getCustomerId();

        // use each rule (and apply to customer, if applicable)
        foreach ($ruleIds as $ruleId) {
            if (!$ruleId) {
                continue;
            }
            /** @var \Magento\SalesRule\Model\Rule $rule */
            $rule = $this->_ruleFactory->create();
            $rule->load($ruleId);
            if ($rule->getId()) {
                $rule->setTimesUsed($rule->getTimesUsed() + 1);
                $rule->save();

                if ($customerId) {
                    /** @var \Magento\SalesRule\Model\Rule\Customer $ruleCustomer */
                    $ruleCustomer = $this->_ruleCustomerFactory->create();
                    $ruleCustomer->loadByCustomerRule($customerId, $ruleId);

                    if ($ruleCustomer->getId()) {
                        $ruleCustomer->setTimesUsed($ruleCustomer->getTimesUsed() + 1);
                    } else {
                        $ruleCustomer->setCustomerId($customerId)->setRuleId($ruleId)->setTimesUsed(1);
                    }
                    $ruleCustomer->save();
                }
            }
        }

        $this->_coupon->load($order->getCouponCode(), 'code');
        if ($this->_coupon->getId()) {
            $this->_coupon->setTimesUsed($this->_coupon->getTimesUsed() + 1);
            $this->_coupon->save();
            if ($customerId) {
                $this->_couponUsage->updateCustomerCouponTimesUsed($customerId, $this->_coupon->getId());
            }
        }
        return $this;
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
}

