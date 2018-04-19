<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Customer\Api\GroupManagementInterface;

class PrepareCatalogProductCollectionPrices implements ObserverInterface
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
    protected $productRepository;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;
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
        CustomerCart $cart,
        GroupManagementInterface $groupManagement
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
        $this->groupManagement = $groupManagement;
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

        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        //@@NOTE: remove all session of freegift
        if(count($items) >= 0 && $quote->getSubtotal() == 0) {
            $quote->unsFreegiftIds();
            $quote->unsFreegiftAppliedRuleIds();
            $quote->unsFreegiftCouponCode();
            $quote->removeAllItems()->save();
            $this->cart->truncate();
            $this->resetSession();
            return;
        }

        /* @var $collection ProductCollection */
        $collection = $observer->getCollection();
        $storeId = $observer->getStoreId();
        $store = $this->_storeManager->getStore($observer->getStoreId());
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
        /* @var $product  */
        foreach ($collection as $product) {
            $productIds[] = $product->getId();
        }

        if ($productIds) {
//            if (!$this->checkoutSession->getGiftProductIds()) {
//                $this->xlog($product->getData());
                if ($product->hasCustomerGroupId()) {
                    $customerGroupId = $product->getCustomerGroupId();
                } else {
                    $customerGroupId = $this->_customerSession->getCustomerGroupId();
                }

                $dateTs = $this->_localeDate->scopeTimeStamp($storeId);
    //            $salesruleGift = $this->_resourceSalesrule->getRulesFromProduct(null, 1, 1, $productIds);
                $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId,$customerGroupId, $productIds);

                if(!empty($ruleData)) {
                    $randKey = md5(rand(1111, 9999));
                    $gift_product_ids = [];
                    $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($ruleData);
                    if(count($gift_product_ids) > 0) {
                        foreach($items as $item) {
                            /* Process for gift if exist */
                            $current_qty = $item->getQty();
                            $current_qty_gift = $this->countGiftInCart();

                            $info_buyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                            if(!isset($info_buyRequest['freegift_key'])) {
                                $info_buyRequest['freegift_key'] = $randKey;
                            }

                            foreach($gift_product_ids as $key => $giftData) {
                                // process for buy x get y
                                if ($giftData && $current_qty >= $giftData['buy_x']) {
                                } else {
                                    if (isset($info_buyRequest['freegift_key'])) {
                                        unset($info_buyRequest['freegift_key']);
                                    }
                                    unset($gift_product_ids[$key]);
                                }
                            }

//                            if($item->getOptionByCode('additional_options')) {
//                                $additional_options = unserialize($item->getOptionByCode('additional_options')->getValue());
//                                $mw_additional_options = $additional_options[0];
//                                if(isset($mw_additional_options['freegift_parent_key'])) {
//                                    $info_buyRequest['freegift_key'] = $mw_additional_options['freegift_parent_key'];
//                                }
//                            }

                            $applied_rule_ids = $this->helperFreeGift->_prepareRuleIds($gift_product_ids);
                            $info_buyRequest['mw_applied_catalog_rule'] = serialize($applied_rule_ids);
                            $item->getOptionByCode('info_buyRequest')->setValue(serialize($info_buyRequest))->save();
                        }
                        if(!$this->checkoutSession->getGiftProductIds()) {
                            $this->checkoutSession->setGiftProductIds($gift_product_ids);
                        }
                    }
                }

        } else {
            $this->checkoutSession->unsetData('gift_product_ids');
        }
        return;
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

    protected function resetSession()
    {
        $this->checkoutSession->clearStorage();
//        $this->checkoutSession->clearQuote();
        $this->xlog(__LINE__.' '.__METHOD__);
//        $this->xlog($this->debug_backtrace_string());
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

    function debug_backtrace_string() {
        $stack = '';
        $i = 1;
        $trace = debug_backtrace();
        unset($trace[0]); //Remove call to this function from stack trace
        foreach($trace as $node) {
//            $this->xlog(array_keys($node));
//            $stack .= "#$i ";
//            $stack .= $node['file'];
//            $stack .= "(" .$node['line']."): ";
            if(isset($node['class'])) {
                $stack .= $node['class'] . "->";
            }
            $stack .= $node['function'] . "()" . PHP_EOL;
            $i++;
        }
        return $stack;
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
