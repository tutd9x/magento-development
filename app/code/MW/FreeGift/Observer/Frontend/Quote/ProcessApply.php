<?php
namespace MW\FreeGift\Observer\Frontend\Quote;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Cart as CustomerCart;

class ProcessApply implements ObserverInterface
{
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;

    /**
     * @var \MW\FreeGift\Model\Validator
     */
    protected $_calculator;
    protected $_validator;

    /**
     * @var Quote
     */
    protected $quote;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \MW\FreeGift\Model\SalesRuleFactory
     */
    protected $_salesRuleFactory;
    /**
     * @var \MW\FreeGift\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * Initialize dependencies.
     *
     * @param \MW\FreeGift\Model\Config $config
     * @param \MW\FreeGift\Model\Validator $validator
     */
    public function __construct(
        \MW\FreeGift\Model\Config $config,
        \MW\FreeGift\Model\Validator $validator,
        \Magento\Checkout\Model\Session $resourceSession,
        \MW\FreeGift\Model\SalesRuleFactory $salesRuleFactory,
        \MW\FreeGift\Helper\Data $helper,
        CustomerCart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->config = $config;
        $this->_calculator = $validator;
        $this->checkoutSession = $resourceSession;
        $this->_salesRuleFactory = $salesRuleFactory;
        $this->helper = $helper;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
    }

    /**
     * Ddd option gift.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        /* Get quote item validator/processor object */
        if (!$this->_validator) {
            $this->_validator = $this->_calculator->init(
                $observer->getEvent()->getWebsiteId(),
                $observer->getEvent()->getCustomerGroupId(),
                $observer->getEvent()->getFreegiftCouponCode()
            );
        }
        /** @var \MW\FreeGift\Model\Validator $_validator */
        $this->_validator->process($observer->getEvent()->getItem());

        $this->_processShoppingCartRule($observer);
        return $this;
    }

    /**
     * Apply cart rules to product on frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    private function _processShoppingCartRule(\Magento\Framework\Event\Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        if($this->_isGift($item)) {
            return $this->_prepareRuleGift($observer);
        }

        $freegift_applied_rule_ids = $item->getData('freegift_applied_rule_ids');
        $storeId = $item->getData('store_id');

        if ($freegift_applied_rule_ids == "") {
            return $this;
        }
        $rule_ids = explode(',', $freegift_applied_rule_ids);
        $ruleData = null;

        foreach ($rule_ids as $rule_id) {
            /* @var $salesrule \MW\FreeGift\Model\SalesRuleFactory */
            $salesrule = $this->_salesRuleFactory->create()->load($rule_id);
            $ruleData[$rule_id] = $salesrule->getData();
        }

        if (!empty($ruleData)) {

            /* Sort array by column sort_order */
            array_multisort(array_column($ruleData, 'sort_order'), SORT_ASC, $ruleData);
            $ruleData = $this->_filterByActionStop($ruleData);

            $giftData = $this->helper->getGiftDataBySalesRule($ruleData);

            if (count($giftData) <= 0) {
                return $this;
            }

            foreach ($giftData as $gift) {
                $parentKey = $gift['rule_id'] .'_'. $gift['gift_id'] .'_'. $gift['number_of_free_gift'];
                $current_qty_gift = $this->_countGiftInCart($gift, $parentKey);
                if ($gift['number_of_free_gift'] > $current_qty_gift) {
                    $this->addProduct($gift, $storeId, $parentKey);
                }else{
                    break;
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
            if (isset($data['stop_rules_processing']) && $data['stop_rules_processing'] == '1') {
                break;
            }
        }
        return $result;
    }

    public function addProduct($rule, $storeId, $parentKey)
    {
        $params['product'] = $rule['gift_id'];
        $params['rule_name'] = $rule['name'];
        $params['qty'] = 1;
        $params['free_sales_key'][$parentKey] = $parentKey;
        $params['freegift_qty_info'][$parentKey] = 1;

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
            $product->addCustomOption('free_sales_gift', 1);
            $product->addCustomOption('additional_options', serialize($additionalOptions));

            $this->cart->addProduct($product, $params);
        }

        return $this;
    }

    /**
     * Counting gift item in cart
     * @param $gift
     * @param $parentKey
     * @return int $count
     */
    public function _countGiftInCart($gift, $parentKey)
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

                    $free_sales_key = $info['free_sales_key'];
                    $freegift_qty_info = $info['freegift_qty_info'];

                    $data_to_compare[$parentKey] = $parentKey;
                    $result = array_intersect($data_to_compare,$free_sales_key);
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

        if($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1){
            return true;
        }

        return false;
    }

    /**
     * Process price for free product.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function _prepareRuleGift(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        $item = $observer->getEvent()->getItem();
        /* @var $quote \Magento\Quote\Model\Quote */
        $quote = $item->getQuote();

        $freeids = $quote->getFreegiftIds();
        $freeids = explode(",", $freeids);

        /* Remove gift item if it isn't gift */
        if (!in_array($item->getProductId(), $freeids)) {
            $quote->removeItem($item->getItemId());
            return $this;
        }

        $aplliedRuleIds = $quote->getFreegiftAppliedRuleIds();
        $aplliedRuleIds = explode(',',$aplliedRuleIds);
        $giftData = $this->checkoutSession->getGiftSalesProductIds();




        $compare_keys = [];
        foreach ($aplliedRuleIds as $ruleId){
            $found_key = array_search($ruleId, array_column($giftData, 'rule_id'));
            if($found_key !== false){
                $parentKey = $giftData[$found_key]['rule_id'] .'_'. $giftData[$found_key]['gift_id'] .'_'. $giftData[$found_key]['number_of_free_gift'];
                $compare_keys[$parentKey] = $parentKey;
            }
        }

        $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
        if (isset($info['free_sales_key']) && $free_sales_key = $info['free_sales_key']) {
            $result = array_intersect($compare_keys,$free_sales_key);
            if (empty($result)) {
                $quote->removeItem($item->getItemId());
                return $this;
            }
        }
        return $this;
    }
}
