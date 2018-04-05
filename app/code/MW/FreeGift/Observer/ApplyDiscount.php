<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;

class ApplyDiscount implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var CustomerModelSession
     */
    protected $_customerSession;
    protected $_resourceRule;
    protected $helperFreeGift;
    protected $checkoutSession;
    protected $productRepository;
    protected $cart;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CustomerModelSession $customerSession,
        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \MW\FreeGift\Model\CouponFactory $couponFactory,
        \MW\FreeGift\Model\SalesruleFactory $salesruleFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CustomerCart $cart
    ) {
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_customerSession = $customerSession;
        $this->_resourceRule = $resourceRule;
        $this->helperFreeGift = $helperFreeGift;
        $this->_couponFactory = $couponFactory;
        $this->_salesruleFactory = $salesruleFactory;
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
    }

    /**
     * Ddd option gift.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helperFreeGift->getStoreConfig('mw_freegift/group_general/active'))
            return;

        /*
        * Procress custom price for freegift of salesrule by coupon_code */
        $item = $observer->getQuoteItem();

        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }
        /*
        * Process for shopping cart rule
        */
        //process for child product item
        //$item->setNoDiscount(1); //@TODO uncomment for child product
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
        if(($giftcode_options && $giftcode_options->getValue() == 1) || ($giftsales_options && $giftsales_options->getValue() == 1)) {
            $item->setCustomPrice(0);
            $item->setOriginalCustomPrice(0);
            $item->setFinalPrice(0);
            $item->getProduct()->setIsSuperMode(true);
            return;
        }

        // process when add a product exist in cart, bypass rule
        //@@TODO check khong add duoc gift vao cart
        /*if($this->checkoutSession->getQuote()->getItemsCount() > 0) {
            foreach ($this->checkoutSession->getQuote()->getAllItems() as $item_in_cart) {
                if($item_in_cart->getItemId() == $item->getItemId()) {
//                    return;
                }
            }
        }*/

        /*
        * Process for catalog rule
        */
        $product = $observer->getProduct();
        $randKey = md5(rand(1111, 9999));

        $storeId = $product->getStoreId();
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        if (empty($product->hasCustomerGroupId())) {
            $customerGroupId = $product->getCustomerGroupId();
        } else {
            $customerGroupId = $this->_customerSession->getCustomerGroupId();
        }
        $dateTs = $this->_localeDate->scopeTimeStamp($storeId);
        $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $product->getId());
        if(!empty($ruleData)) {

            $gift_product_ids = [];
//            $this->xlog("applydiscount ". __LINE__);
            $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData);
            $this->xlog($gift_product_ids);
//            Array
//            (
//                [0] => Array
//                (
//                    [product_id] => 13
//                    [rule_id] => 1
//                    [rule_gift_ids] => 2044
//                    [name] => Category is 4
//                )
//            )

            if(count($gift_product_ids) > 0){

                /* Process for gift if exist */
                $current_qty = $item->getQty();
                $current_qty_gift = $this->countGiftInCart();
                $info_buyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                if(isset($info_buyRequest['freegift_key'])) {
                    $randKey = $info_buyRequest['freegift_key'];
                }

                foreach($gift_product_ids as $key => $giftData) {
                    // process for buy x get y
                    if (!empty($giftData) && $current_qty >= $giftData['buy_x']) {
//                        $giftData['buy_x'];

                        $qty_for_gift = (int)($current_qty / $giftData['buy_x']) - $current_qty_gift;
                        if($qty_for_gift <= 0){
                            return;
                        }

                        $params['product'] = $giftData['rule_gift_ids'];
                        $params['rule_name'] = $giftData['name'];
                        $params['qty'] = $qty_for_gift;
                        $storeId = 1;
                        $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
//                        if($product_gift->getTypeId() == 'simple') {
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
//                        }
                    }else{
                        unset($gift_product_ids[$key]);
                    }
                }
                $this->cart->save();

                if(!$this->checkoutSession->getGiftProductIds()) {
                    $this->checkoutSession->setGiftProductIds($gift_product_ids);
                }

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

    function countGiftInCart()
    {
        $count = 0;
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $item_in_cart) {
            if($item_in_cart->getOptionByCode('freegift_parent_key') && $item_in_cart->getOptionByCode('freegift_parent_key')->getValue()){
                //$count++;
                return $item_in_cart->getQty();
            }
        }
        return $count;
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
