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
        $quote = $this->getQuote();
        $item = $observer->getEvent()->getItem();
        $randKey = md5(rand(1111, 9999));

        $freegift_ids = $quote->getData('freegift_ids');
        $freegift_applied_rule_ids = $quote->getData('freegift_applied_rule_ids');
        $freegift_coupon_code = $quote->getData('freegift_coupon_code');

        $freegift_applied_rule_ids = $item->getData('freegift_applied_rule_ids');
        $storeId = $item->getData('store_id');

        $rulegifts = $this->checkoutSession->getData('rulegifts');
        $productgiftid = $this->checkoutSession->getData('productgiftid');

        $salesRuleData = [];
        $gift_sales_product_ids = [];
        $rule_ids = explode(',', $freegift_applied_rule_ids);

        foreach($rule_ids as $rule_id){
            $salesrule = $this->_salesRuleFactory->create()->load($rule_id);
            $salesRuleData[$rule_id] = $salesrule->getData();
        }

        if(!empty($salesRuleData)) {

            $giftData = $this->helper->getFreeGiftCatalogProduct($salesRuleData, 'getGiftOfSalesRule');

            if (count($giftData) <= 0) {
                return $this;
            }

            $this->checkoutSession->setGiftSalesProductIds($giftData);

            $info_buyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());

            if (isset($info_buyRequest['freegift_key'])) {
                $randKey = $info_buyRequest['freegift_key'];
            } else {
                $info_buyRequest['freegift_key'] = $randKey;
                $applied_rule_ids = $this->helper->_prepareRuleIds($giftData);
                $info_buyRequest['mw_applied_sales_rule'] = serialize($applied_rule_ids);
                /* if this item is gift then skip */
                if (!$item->getOptionByCode('free_sales_gift')){
                    $item->getOptionByCode('info_buyRequest')->setValue(serialize($info_buyRequest));
                }
            }

            /* @TODO xu ly khi tang qty cua parent product len nhung gap doan nay gift product ko tang dc nua */
            /* if this item is gift then return here */
            if ($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1) {
                return $this->_processRulePrice($observer, $randKey, $giftData);
            }

            /* Process for gift if exist */
            $current_qty = $item->getQty();
            $current_qty_gift = $this->_countGiftInCart($randKey);

            foreach ($giftData as $key => $val) {

                $qty_for_gift = (int)($current_qty - $current_qty_gift);
                if ($qty_for_gift <= 0) {
                    continue;
                }

                $params['product'] = $val['rule_gift_ids'];
                $params['rule_name'] = $val['name'];
                $params['qty'] = $qty_for_gift;

                $product_gift = $this->productRepository->getById($val['rule_gift_ids'], false, $storeId);
                if ($product_gift->getTypeId() == 'simple') {
                    $product_gift->addCustomOption('free_sales_gift', 1);
                    $product_gift->addCustomOption('freegift_parent_key', $randKey);

                    $additionalOptions = [[
                        'label' => __('Free Gift'),
                        'value' => $val['name'],
                        'print_value' => $val['name'],
                        'option_type' => 'text',
                        'custom_view' => TRUE,
                        'freegift' => 1,
                        'freegift_name' => $val['name'],
                        'mw_freegift_rule_gift' => 1,
                        'mw_applied_sales_rule' => $val['rule_id'],
                        'freegift_parent_key' => $randKey
                    ]];

                    // add the additional options array with the option code additional_options
                    $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
                    $this->cart->addProduct($product_gift, $params);
                }
            }
        }

        $this->_processRulePrice($observer, $randKey, $giftData);

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
        $item = $observer->getEvent()->getItem();
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        $gift_options = $item->getOptionByCode('free_sales_gift');
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
}
