<?php

namespace MW\FreeGift\Observer\Frontend\Cart;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
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
    protected $_ruleFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @param \MW\FreeGift\Model\ResourceModel\RuleFactory $resourceRuleFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     * @param CustomerModelSession $customerSession
     */
    public function __construct(
        \MW\FreeGift\Model\ResourceModel\RuleFactory $resourceRuleFactory,
        \MW\FreeGift\Model\RuleFactory $ruleFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CustomerModelSession $customerSession,
        \MW\FreeGift\Model\Config $config,
        \MW\FreeGift\Helper\Data $helper,
        \Magento\Checkout\Model\Session $resourceSession,
        CustomerCart $cart,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->resourceRuleFactory = $resourceRuleFactory;
        $this->_ruleFactory = $ruleFactory;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->helper = $helper;
        $this->checkoutSession = $resourceSession;
        $this->cart = $cart;
        $this->productFactory = $productFactory;
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

        /* @var $item \Magento\Quote\Model\Quote\Item */
        $item = $observer->getEvent()->getQuoteItem();
        $this->_addOptionGiftProductAgain($item);
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

        if ($this->_isGift($item) || $this->_isSalesGift($item)) {
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
            $current_qty = $this->_countCurrentItemInCart($freegift_keys);

            foreach ($giftData as $gift) {
                // process for buy x get y
                if (!empty($gift)) {
                    if ($gift['buy_x'] > 0) {
                        $buy_x = $gift['buy_x'];
                        $get_y = $gift['get_y'];

                        // Kiem tra so luong theo ti le buy x get y, khong tinh khi co phan du
                        if ($current_qty % $buy_x != 0) {
                            $current_qty = ((int)$current_qty / $buy_x) * $buy_x;
                        }

                        $current_qty_gift = $this->_countGiftInCart($gift, $freegift_keys);
                        $qty_for_gift = (int)($current_qty * $get_y / $buy_x) - $current_qty_gift;
                        if ($qty_for_gift <= 0) {
                            continue;
                        }

                        if ((int)($current_qty * $get_y / $buy_x) >= $qty_for_gift) {
                            $this->addProduct($gift, $qty_for_gift, $storeId, $gift['freegift_parent_key']);
                            unset($freegift_keys[$gift['freegift_parent_key']]);
                        }
                    }
                }
            }
        }

        return $this;
    }

    private function _filterByActionStop($ruleData)
    {
        $result = [];
        foreach ($ruleData as $data) {
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

    /*
     * add gift product to cart
     * */
    public function addProduct($rule, $qty_for_gift, $storeId, $parentKey)
    {

        $product = $this->productFactory->create()->load($rule['gift_id']);

        if (!$product) {
            return $this;
        }

        $params['uenc'] = $uenc = strtr(base64_encode($product->getProductUrl()), '+/=', '-_,');
        $params['product'] = $rule['gift_id'];
        $params['product'] = $rule['gift_id'];
        $params['rule_name'] = $rule['name'];
        $params['qty'] = $qty_for_gift;
        $params['freegift_parent_key'][$parentKey] = $parentKey;
        $params['freegift_qty_info'][$parentKey] = $qty_for_gift;
        $params['freegift_rule_data'][$parentKey] = $rule;

        /* remove custom option 'mw_free_catalog_gift' out of gift product */
        if ($product->hasCustomOptions() && $productCustomOptions = $product->getCustomOptions()) {
            if ($product->getCustomOption('mw_free_catalog_gift') && $product->getCustomOption('mw_free_catalog_gift')->getValue() == 1) {
                unset($productCustomOptions['mw_free_catalog_gift']);
                $product->setCustomOptions($productCustomOptions);
            }
        }

        if ($this->availableProductType($product->getTypeId())) {
            $additionalOptions = [[
                'label' => __('Free Gift'),
                'value' => $rule['name'],
                'print_value' => $rule['name'],
                'option_type' => 'text',
                'custom_view' => true,
                'rule_id' => $rule['rule_id']
            ]];
            // add the additional options array with the option code additional_options
            $product->addCustomOption('free_catalog_gift', 1);
            $product->addCustomOption('additional_options', serialize($additionalOptions));

            /* check item in cart */
            $itemInCart = $this->_getItemByProduct($product);
            if ($itemInCart == false) {
                $this->cart->addProduct($product, $params);
            } else {
                $qtyToUpdate = $itemInCart->getQty() + $qty_for_gift;
                $itemInCart->setQty($qtyToUpdate);

                if ($itemInCart->getOptionByCode('info_buyRequest') && $data = unserialize($itemInCart->getOptionByCode('info_buyRequest')->getValue())) {
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
                        $data['freegift_rule_data'][$parentKey] = $rule;
                    }
                    $itemInCart->getOptionByCode('info_buyRequest')->setValue(serialize($data));
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
     * @param $parent_keys
     * @return int $count
     */
    public function _countCurrentItemInCart($parent_keys)
    {
        $count = 0;
        foreach ($this->getQuote()->getAllItems() as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */
            if ($item->getParentItem()) {
                continue;
            }

            if (!$this->_isGift($item)) {
                $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $freegift_parent_key = isset($info['freegift_keys']) ? $info['freegift_keys'] : [];
                $result = array_intersect($parent_keys, $freegift_parent_key);
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
     * @param $gift
     * @param $parent_keys
     * @return int $count
     */
    public function _countGiftInCart($gift, $parent_keys)
    {
        $count = 0;
        foreach ($this->getQuote()->getAllItems() as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */
            if ($item->getParentItem()) {
                $item = $item->getParentItem();
            }

            if ($this->_isGift($item)) {
                if ($item->getProductId() == $gift['gift_id']) {
                    $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

                    $freegift_parent_key = $info['freegift_parent_key'];
                    $freegift_qty_info = $info['freegift_qty_info'];
                    $result = array_intersect($parent_keys, $freegift_parent_key);
                    if (empty($result)) {
                        continue;
                    }

                    $freegift_qty = '';
                    foreach ($result as $key) {
                        $freegift_qty = $freegift_qty_info[$key];
                    }

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
            /* @var $item \Magento\Quote\Model\Quote\Item */
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
        $item = $observer->getEvent()->getQuoteItem();
        if ($this->_isSalesGift($item)) {
            $salesGiftRemoved = $this->checkoutSession->getSalesGiftRemoved();
            $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

            if (!empty($salesGiftRemoved)) {
                $parentKey = $info['free_sales_key'];
                $result = array_intersect($parentKey, $salesGiftRemoved);
                if (empty($result)) {
                    foreach ($result as $key) {
                        unset($salesGiftRemoved[$key]);
                    }
                    $this->checkoutSession->setSalesGiftRemoved($salesGiftRemoved);
                }
            }
        }

        return $this;
    }

    private function _isGift($item)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        if ($item->getOptionByCode('free_catalog_gift') && $item->getOptionByCode('free_catalog_gift')->getValue() == 1) {
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

        if ($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1) {
            return true;
        }

        return false;
    }

    /**
     * Add gift product on slider to cart
     * @param $item
     * @return $this
     */

    public function _addOptionGiftProductAgain($item)
    {
        if ($item->getOptionByCode('info_buyRequest') && $data = unserialize($item->getOptionByCode('info_buyRequest')->getValue())) {
            if (array_key_exists('gift_from_slider', $data)) {
                if (array_key_exists('freegift_parent_key', $data) && isset($data['freegift_parent_key'])) {
                    $parent_gift_key = $data['freegift_parent_key'];
                    $data['freegift_parent_key'] = [
                        $data['freegift_parent_key'] => $data['freegift_parent_key']
                    ];

                    $ruleData = $this->_ruleFactory->create()->load($data['rule_id']);
                    $condition_customized = unserialize($ruleData->getData('condition_customized'));
                    $buy_x = $condition_customized['buy_x_get_y']['bx'];
                    $keySplit = $this->helper->splitKey($parent_gift_key);
                    $qty = 0;
                    $parents = $this->helper->getParentOfGift($parent_gift_key);
                    if (count($parents) <= 0) {
                        return $this;
                    }
                    foreach ($parents as $par) {
                        $parentItem = $this->checkoutSession->getQuote()->getItemById($par);
                        $condition_customized = unserialize(unserialize($parentItem->getOptionByCode('info_buyRequest')->getValue())['freegift_rule_data'][$data['rule_id']]['condition_customized']);
                        $buyX = $condition_customized['buy_x_get_y']['bx'];
                        $qty += $parentItem->getQty() * $buyX;
                    }
                    $data['freegift_qty_info'] = [
                        $parent_gift_key => $qty
                    ];

                    $data['freegift_rule_data'] = [
                        $parent_gift_key => [
                            'rule_id' => $data['rule_id'],
                            'name' => $data['rule_name'],
                            'product_id' => $keySplit['product_parent_id'],
                            'rule_product_id' => $keySplit['key_id'],
                            'gift_id' => $keySplit['product_gift_id'],
                            'buy_x' => $buy_x,
                            'freegift_parent_key'=> $parent_gift_key,
                        ]
                    ];
                    $rule = [
                        'gift_id' => $data['product'],
                        'name' => $data['rule_name']
                    ];

                    $additionalOptions = [[
                        'label' => __('Free Gift'),
                        'value' => $rule['name'],
                        'print_value' => $rule['name'],
                        'option_type' => 'text',
                        'custom_view' => true,
                    ]];
//             add the additional options array with the option code additional_options
                    $item->addOption(
                        [
                            'product_id' => $item->getProductId(),
                            'code' => 'free_catalog_gift',
                            'value' => 1,
                        ]
                    );
                    $item->addOption(
                        [
                            'product_id' => $item->getProductId(),
                            'code' => 'additional_options',
                            'value' => serialize($additionalOptions),
                        ]
                    );
                    unset($data['gift_from_slider']);
                    $item->getOptionByCode('info_buyRequest')->setValue(serialize($data));
                }
            }

            if (array_key_exists('sales_gift_from_slider', $data)) {
                if (array_key_exists('free_sales_key', $data) && isset($data['free_sales_key'])) {
                    $parent_gift_key = $data['free_sales_key'];
                    $freegift_coupon_code = $this->checkoutSession->getQuote()->getFreegiftCouponCode();
                    $data['free_sales_key'] = [
                        $data['free_sales_key'] => $data['free_sales_key']
                    ];

                    $data['freegift_qty_info'] = [
                        $parent_gift_key => 1
                    ];

                    $data['freegift_rule_data'] = [
                        $parent_gift_key => [
                            'rule_id' => $data['rule_id'],
                            'name' => $data['rule_name'],
                            'gift_id' => $data['product'],
                            'number_of_free_gift' => 1,
                            'freegift_sales_key' => $parent_gift_key,
                        ]
                    ];
                    if ($freegift_coupon_code) {
                        $data['freegift_with_code'] = 1;
                        $data['freegift_coupon_code'] = $freegift_coupon_code;
                    }

                    $rule = [
                        'gift_id' => $data['product'],
                        'name' => $data['rule_name']
                    ];

                    $additionalOptions = [[
                        'label' => __('Free Gift'),
                        'value' => $rule['name'],
                        'print_value' => $rule['name'],
                        'option_type' => 'text',
                        'custom_view' => true,
                        'freegift_with_code' => 1,
                        'freegift_coupon_code' => $freegift_coupon_code,
                    ]];
//             add the additional options array with the option code additional_options
                    $item->addOption(
                        [
                            'product_id' => $item->getProductId(),
                            'code' => 'free_sales_gift',
                            'value' => 1,
                        ]
                    );
                    $item->addOption(
                        [
                            'product_id' => $item->getProductId(),
                            'code' => 'additional_options',
                            'value' => serialize($additionalOptions),
                        ]
                    );
                    unset($data['gift_from_slider']);
                    $item->getOptionByCode('info_buyRequest')->setValue(serialize($data));
                }
            }
        }

        return $this;
    }

    /**
     * Check available product type can be add to cart
     *
     * @param string $type
     * @return bool
     */
    public function availableProductType($type = '')
    {
        $isAllow = false;
        if (in_array($type, ['simple', 'virtual'])) {
            $isAllow = true;
        }
        return $isAllow;
    }
}
