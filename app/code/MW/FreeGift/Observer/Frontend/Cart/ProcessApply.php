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
        $randKey = md5(rand(1111, 9999));
        $giftData = [];
        $ruleData = null;
        $buy_x = 1;

        if (!$this->rulePricesStorage->hasRulePrice($key)) {
            $rulePrice = $this->resourceRuleFactory->create()->getRulePrice($date, $wId, $gId, $pId);
            $this->rulePricesStorage->setRulePrice($key, $rulePrice);
        }

        if ($this->rulePricesStorage->getRulePrice($key) !== false)
        {
            $dateTs = $this->localeDate->scopeTimeStamp($storeId);

            $ruleData = $this->resourceRuleFactory->create()->getRulesFromProduct($dateTs, $wId, $gId, $pId);
            if(!empty($ruleData)) {

                $giftData = $this->helper->getFreeGiftCatalogProduct($ruleData);

                if (count($giftData) <= 0) {
                    return $this;
                }


                $info_buyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

                if(isset($info_buyRequest['freegift_key'])) {
                    $randKey = $info_buyRequest['freegift_key'];
                } else {
                    $info_buyRequest['freegift_key'] = $randKey;
                    $applied_rule_ids = $this->helper->_prepareRuleIds($giftData);
                    $info_buyRequest['mw_applied_sales_rule'] = serialize($applied_rule_ids);
                    $item->getOptionByCode('info_buyRequest')->setValue(serialize($info_buyRequest));
                }

                /* Process for gift if exist */
                $current_qty = $item->getQty();
                $current_qty_gift = $this->_countGiftInCart($randKey);

                foreach ($giftData as $key => $val) {
                    // process for buy x get y
                    if (!empty($val) && $current_qty >= $val['buy_x']) {

                        if($val['buy_x'] > 0){
                            $buy_x = $val['buy_x'];
                        }

                        $qty_for_gift = (int)($current_qty / $buy_x) - $current_qty_gift;
                        if($qty_for_gift <= 0){
                            continue;
                        }

                        $params['product'] = $val['rule_gift_ids'];
                        $params['rule_name'] = $val['name'];
                        $params['qty'] = $qty_for_gift;


                        $product_gift = $this->productRepository->getById($val['rule_gift_ids'], false, $storeId);
                        if($product_gift->getTypeId() == 'simple') {
                            $product_gift->addCustomOption('free_catalog_gift', 1);
                            $product_gift->addCustomOption('freegift_parent_key', $randKey);

                            $additionalOptions = [[
                                'label' => __('Free Gift'),
                                'value' => $val['name'],
                                'print_value' => $val['name'],
                                'option_type' => 'text',
                                'custom_view' => TRUE,
                                'mw_freegift_rule_gift' => 1,
                                'mw_applied_catalog_rule' => $val['rule_id'],
                                'freegift_parent_key' => $randKey
                            ]];
                            // add the additional options array with the option code additional_options
                            $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
                            $this->cart->addProduct($product_gift, $params);
                        }
                    } else {
                        unset($giftData[$key]);
                    }
                }
            }
        }

        $this->_processRulePrice($observer, $randKey, $giftData);




//        if ($this->rulePricesStorage->getRulePrice($key) !== false) {
            //$finalPrice = min($product->getData('final_price'), $this->rulePricesStorage->getRulePrice($key));
            //$product->setFinalPrice($finalPrice);
//        }




        return $this;
    }

    /**
     * Process price for free product.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function _processRulePrice(\Magento\Framework\Event\Observer $observer, $randKey, $giftData)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        /* Call again because need use to get new product added */
        $item = $observer->getEvent()->getQuoteItem();
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        $gift_options = $item->getOptionByCode('free_catalog_gift');
        if($gift_options && $gift_options->getValue() == 1){
            $item->setCustomPrice(0);
            $item->setOriginalCustomPrice(0);
            $item->getProduct()->setIsSuperMode(true);
        }

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
            if($item->getOptionByCode('freegift_parent_key') && $item->getOptionByCode('freegift_parent_key')->getValue() == $parent_key){
                $count++;
//                return $item->getQty();
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

}
