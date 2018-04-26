<?php

namespace MW\FreeGift\Observer\Frontend\Cart;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Cart as CustomerCart;

class ProcessApply implements ObserverInterface
{
    /**
     * @var CustomerModelSession
     */
    protected $customerSession;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;
    /**
     * @var \MW\FreeGift\Model\ResourceModel\RuleFactory
     */
    protected $resourceRuleFactory;
    /**
     * @var RulePricesStorage
     */
    protected $rulePricesStorage;
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @var \MW\FreeGift\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var Quote
     */
    protected $quote;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param RulePricesStorage $rulePricesStorage
     * @param \MW\FreeGift\Model\ResourceModel\RuleFactory $resourceRuleFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     * @param CustomerModelSession $customerSession
     */
    public function __construct(
        RulePricesStorage $rulePricesStorage,
        \MW\FreeGift\Model\ResourceModel\RuleFactory $resourceRuleFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CustomerModelSession $customerSession,
        \MW\FreeGift\Model\Config $config,
        \MW\FreeGift\Helper\Data $helper,
        \Magento\Checkout\Model\Session $resourceSession,
        CustomerCart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->rulePricesStorage = $rulePricesStorage;
        $this->resourceRuleFactory = $resourceRuleFactory;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->helper = $helper;
        $this->checkoutSession = $resourceSession;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
    }

    /**
     * Apply catalog rules to product on frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        $product = $observer->getEvent()->getProduct();
        $pId = $product->getId();
        $storeId = $product->getStoreId();

        if ($observer->hasDate()) {
            $date = new \DateTime($observer->getEvent()->getDate());
        } else {
            $date = $this->localeDate->scopeDate($storeId);
        }

        if ($observer->hasWebsiteId()) {
            $wId = $observer->getEvent()->getWebsiteId();
        } else {
            $wId = $this->storeManager->getStore($storeId)->getWebsiteId();
        }

        if ($observer->hasCustomerGroupId()) {
            $gId = $observer->getEvent()->getCustomerGroupId();
        } elseif ($product->hasCustomerGroupId()) {
            $gId = $product->getCustomerGroupId();
        } else {
            $gId = $this->customerSession->getCustomerGroupId();
        }

        $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}";

        $this->_updateListGift($observer);

        $this->_processCatalogRule($observer, $key, $date, $wId, $gId);

        return $this;
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    private function _processCatalogRule(\Magento\Framework\Event\Observer $observer, $key, $date, $wId, $gId)
    {
        $product = $observer->getEvent()->getProduct();
        $pId = $product->getId();
        $storeId = $product->getStoreId();
        $item = $observer->getEvent()->getQuoteItem();
        $dateTs = $this->localeDate->scopeTimeStamp($storeId);

        //$randKey = md5(rand(1111, 9999));
        $giftData = [];
        $ruleData = null;
        $buy_x = 1;

        if($this->_isGift($item)) {
            return $this->_processRulePrice($observer);
        }
        
        /* @var $resourceModel \MW\FreeGift\Model\ResourceModel\Rule */
        $resourceModel = $this->resourceRuleFactory->create();
        $ruleData = $resourceModel->getRulesFromProduct($dateTs, $wId, $gId, $pId);
        /* Sort array by column sort_order */
        array_multisort(array_column($ruleData, 'sort_order'), SORT_ASC, $ruleData);
        $ruleData = $this->_filterByActionStop($ruleData);



        if (!empty($ruleData)) {

            $parentKey = [];
            foreach ($ruleData as $rule) {
                $parentKey[$rule['rule_product_id'] . '_' . $rule['rule_id'] . '_' . $rule['product_id']] = $rule['rule_product_id'] . '_' . $rule['rule_id'] . '_' . $rule['product_id'];
            }

            $giftData = $this->helper->getGiftDataByRule($ruleData);

            if (count($giftData) <= 0) {
                return $this;
            }

            $this->addRuleInfo($item, $ruleData, $giftData, $parentKey);



            /* Process for gift if exist */
            $current_qty = $item->getQty();
            $current_qty_gift = $this->_countGiftInCart($parentKey);

            foreach ($giftData as $key => $val) {
                // process for buy x get y
                if (!empty($val) && $current_qty >= $val['buy_x']) {

                    if ($val['buy_x'] > 0) {
                        $buy_x = $val['buy_x'];
                    }

                    $qty_for_gift = (int)($current_qty / $buy_x) - $current_qty_gift;
                    if ($qty_for_gift <= 0) {
                        continue;
                    }

                    $parentKey = [];
                    $parentKey[$rule['rule_product_id'] . '_' . $rule['rule_id'] . '_' . $rule['product_id']] = $rule['rule_product_id'] . '_' . $rule['rule_id'] . '_' . $rule['product_id'];
                    $this->addProduct($val, $qty_for_gift, $storeId, $parentKey);

                } else {
                    unset($giftData[$key]);
                }
            }
        }

        //$this->_processRulePrice($observer);

        return $this;
    }

    private function _filterByActionStop($ruleData)
    {
        $result = [];
        foreach($ruleData as $data) {
            $result[$data['rule_id']] = $data;
            if (isset($data['action_stop']) && $data['action_stop'] == '1') {
                break;
            }
        }
        return $result;
    }

    /* Add rule info to item */
    public function addRuleInfo($item, $ruleData, $giftData, $parentKey)
    {
        $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

        if (!$this->_isGift($item)) {
            $info['freegift_key'] = $parentKey;
            $info['freegift_rule_data'] = $ruleData;
//        } else {
//            unset($info['freegift_parent_key']);
//            foreach ($giftData as $data) {
//                $parentKey = [];
//                $parentKey[$data['rule_product_id'] . '_' . $data['rule_id'] . '_' . $data['product_id']] = $data['rule_product_id'] . '_' . $data['rule_id'] . '_' . $data['product_id'];
//
//                $info['freegift_parent_key'] = $parentKey;
//            }
        }

        $item->getOptionByCode('info_buyRequest')->setValue(serialize($info));

        return $this;
    }

    public function addProduct($rule, $qty_for_gift, $storeId, $parentKey)
    {
        $params['product'] = $rule['gift_id'];
        $params['rule_name'] = $rule['name'];
        $params['qty'] = $qty_for_gift;
        $params['freegift_parent_key'] = $parentKey;

        $product = $this->productRepository->getById($rule['gift_id'], false, $storeId);

        if($product->getTypeId() == 'simple') {
            $additionalOptions = [[
                'label' => __('Free Gift'),
                'value' => $rule['name'],
                'print_value' => $rule['name'],
                'option_type' => 'text',
                'custom_view' => TRUE,
            ]];
            // add the additional options array with the option code additional_options
            $product->addCustomOption('free_catalog_gift', 1);
            $product->addCustomOption('additional_options', serialize($additionalOptions));
            $this->cart->addProduct($product, $params);
        }
    }

    /**
     * Process price for free product.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function _processRulePrice(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        $item = $observer->getEvent()->getQuoteItem();
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        $item->setCustomPrice(0);
        $item->setOriginalCustomPrice(0);
        $item->getProduct()->setIsSuperMode(true);

        return $this;
    }

    /**
     * Counting gift item in cart
     *
     * @return $count
     */
    public function _countGiftInCart($parent_key)
    {
        $count = 0;
        foreach ($this->getQuote()->getAllItems() as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */
            if ($item->getParentItem()) {
                $item = $item->getParentItem();
            }

            if ($this->_isGift($item)) {
                $info_buyRequest = $item->getOptionByCode('info_buyRequest');
                if (isset($info_buyRequest) && $data = unserialize($info_buyRequest->getValue())) {
                    if (isset($data['freegift_parent_key']) && in_array($parent_key, $data['freegift_parent_key'])) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Retrieve sales quote model
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (empty($this->quote)) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }


    /**
     * Update list gift product in checkout session
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    private function _updateListGift(\Magento\Framework\Event\Observer $observer)
    {
        return $this;
    }

    private function _isGift($item)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        /* @var $item \Magento\Quote\Model\Quote\Item */
        if($item->getOptionByCode('free_catalog_gift') && $item->getOptionByCode('free_catalog_gift')->getValue() == 1){
            return true;
        }

//        if($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1){
//            return true;
//        }

        return false;
    }
}
