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
     * @param \MW\FreeGift\Model\ResourceModel\RuleFactory $resourceRuleFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     * @param CustomerModelSession $customerSession
     */
    public function __construct(
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

        $this->_updateListGift($observer);

        $this->_processCatalogRule($observer, $wId, $gId, $pId, $storeId);

        return $this;
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    private function _processCatalogRule(\Magento\Framework\Event\Observer $observer, $wId, $gId, $pId, $storeId)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        $item = $observer->getEvent()->getQuoteItem();

        if($this->_isGift($item) || $this->_isSalesGift($item)) {
            return $this->_processRulePrice($observer);
        }

        $dateTs = $this->localeDate->scopeTimeStamp($storeId);

        $ruleData = null;
        $buy_x = 1;

        /* @var $resourceModel \MW\FreeGift\Model\ResourceModel\Rule */
        $resourceModel = $this->resourceRuleFactory->create();
        $ruleData = $resourceModel->getRulesFromProduct($dateTs, $wId, $gId, $pId);

        if (!empty($ruleData)) {

            /* Sort array by column sort_order */
            array_multisort(array_column($ruleData, 'sort_order'), SORT_ASC, $ruleData);
            $ruleData = $this->_filterByActionStop($ruleData);
            $giftData = $this->helper->getGiftDataByRule($ruleData);

            if (count($giftData) <= 0) {
                return $this;
            }

            $this->addRuleInfo($item, $ruleData, $giftData);

            /* Process for gift if exist */
            $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            $freegift_keys = $info['freegift_keys'];
            $current_qty = $this->_countCurrentItemInCart($item, $freegift_keys);

            foreach ($giftData as $gift) {
                // process for buy x get y
                if (!empty($gift) && $current_qty >= $gift['buy_x']) {

                    if ($gift['buy_x'] > 0) {
                        $buy_x = $gift['buy_x'];
                    }

                    $current_qty_gift = $this->_countGiftInCart($gift, $freegift_keys);
                    $qty_for_gift = (int)($current_qty / $buy_x) - $current_qty_gift;
                    if ($qty_for_gift <= 0) {
                        continue;
                    }

                    $this->addProduct($gift, $qty_for_gift, $storeId, $gift['freegift_parent_key']);
                }
            }
        }

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

    /* Add rule info to parent of gift item */
    public function addRuleInfo($item, $ruleData, $giftData)
    {
        $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
        $parentKeys = [];

        foreach ($giftData as $data) {
            $parentKeys[$data['freegift_parent_key']] = $data['freegift_parent_key'];
        }

        $info['freegift_keys'] = $parentKeys;
        $info['freegift_rule_data'] = $ruleData;

        $item->getOptionByCode('info_buyRequest')->setValue(serialize($info));

        return $this;
    }

    public function addProduct($rule, $qty_for_gift, $storeId, $parentKey)
    {
        $params['product'] = $rule['gift_id'];
        $params['rule_name'] = $rule['name'];
        $params['qty'] = $qty_for_gift;
        $params['freegift_parent_key'][$parentKey] = $parentKey;
        $params['freegift_qty_info'][$parentKey] = $qty_for_gift;

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

            /* check item in cart */
            $itemInCart = $this->_getItemByProduct($product, $storeId);
            if ($itemInCart == false) {
                $this->cart->addProduct($product, $params);
            } else {
                $qtyToUpdate = $itemInCart->getQty() + $qty_for_gift;
                $itemInCart->setQty($qtyToUpdate);

                if ( $itemInCart->getOptionByCode('info_buyRequest') && $data = unserialize($itemInCart->getOptionByCode('info_buyRequest')->getValue()) ) {
                    if (isset($data['freegift_parent_key']) && $freegift_parent_key = $data['freegift_parent_key']) {
                        $data['freegift_parent_key'][$parentKey] = $parentKey;
                        $data['qty'] = $qtyToUpdate;

                        /* update quantity infomation for free gift */
                        if (array_key_exists($parentKey, $data['freegift_qty_info'])) {
                            $current_qty_info = $data['freegift_qty_info'][$parentKey];
                        } else {
                            $current_qty_info = 0;
                        }

                        $data['freegift_qty_info'][$parentKey] = $current_qty_info + $qty_for_gift;
                    }
                    $itemInCart->getOptionByCode('info_buyRequest')->setValue(serialize($data))->save();
                }
            }
        }

        return $this;
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
     * Counting current item in cart
     *
     * @return $count
     */
    public function _countCurrentItemInCart($item, $parent_keys)
    {
        $count = 0;
        foreach ($this->getQuote()->getAllItems() as $item) {

            /* @var $item \Magento\Quote\Model\Quote\Item */
            if ($item->getParentItem()) {
                continue;
            }

            if (!$this->_isGift($item)) {
                $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $freegift_parent_key = $info['freegift_keys'];
                $result = array_intersect($parent_keys,$freegift_parent_key);
                if (empty($result)) {
                    continue;
                } else {
                    $count += $item->getQty();
                }
            }
        }
        return $count;
    }

    /**
     * Counting gift item in cart
     *
     * @return $count
     */
    public function _countGiftInCart($gift, $parent_keys)
    {
        $count = 0;
        foreach ($this->getQuote()->getAllItems() as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */
            if ($item->getParentItem()) {
                $item = $item->getParentItem();
            }

            $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

            if ($this->_isGift($item)) {
                $freegift_parent_key = $info['freegift_parent_key'];
                $freegift_qty_info = $info['freegift_qty_info'];
                $result = array_intersect($parent_keys,$freegift_parent_key);
                if (empty($result)) {
                    continue;
                }

                foreach ($result as $key) {
                    $freegift_qty = $freegift_qty_info[$key];
                }

                if ($item->getProductId() == $gift['gift_id']) {
                    // @TODO test voi multi rule cho 1 product
                    $count = $freegift_qty;
                    break;
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

    public function _getItemByProduct($product)
    {
        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($item->representProduct($product)) {
                return $item;
            }
        }
        return false;
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

        if($item->getOptionByCode('free_catalog_gift') && $item->getOptionByCode('free_catalog_gift')->getValue() == 1){
            return true;
        }

        return false;
    }


    private function _isSalesGift($item)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        if($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1){
            return true;
        }

        return false;
    }
}
