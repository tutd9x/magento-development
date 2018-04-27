<?php
namespace MW\FreeGift\Observer\Frontend\Cart;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Quote\Model\Quote;

class AfterRemoveItem implements ObserverInterface
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
    protected $_validator;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
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
        \MW\FreeGift\Model\SalesRuleFactory $salesruleFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CustomerCart $cart,
        \MW\FreeGift\Model\Config $config
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
        $this->config = $config;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        $quote = $this->getQuote();
        $items = $quote->getAllVisibleItems();

        if (count($items) >= 0 && $quote->getSubtotal() == 0) {
            $quote->unsFreegiftIds();
            $quote->unsFreegiftAppliedRuleIds();
            $quote->unsFreegiftCouponCode();
            $quote->removeAllItems()->save();
            $this->cart->truncate();
            $this->resetSession();
            return $this;
        }

        $this->_processCatalogRule($observer, $items);

        $this->resetSession();

        return $this;
    }

    private function _processCatalogRule($observer, $items)
    {
        $quote = $this->getQuote();
        $parent_key = [];
        $item_removed = $observer->getEvent()->getQuoteItem();

        $qtyRemoved = $item_removed->getQty();
        if ($this->_isGift($item_removed)) {
            return $this;
        }

        if ( $item_removed->getOptionByCode('info_buyRequest') && $itemInfo = unserialize($item_removed->getOptionByCode('info_buyRequest')->getValue()) ) {
            if (isset($itemInfo['freegift_keys'])) {
                $parent_key = $itemInfo['freegift_keys'];
                if (empty($parent_key)) {
                    return $this;
                }
            }
        }

        foreach ($items as $item) {

            if ($item->getParentItem()) {
                $item = $item->getParentItem();
            }

            if (!$this->_isGift($item)) {
                continue;
            }

            $data = [];
            if ( $item->getOptionByCode('info_buyRequest') && $data = unserialize($item->getOptionByCode('info_buyRequest')->getValue()) ) {
                if ( isset($data['freegift_parent_key']) && $freegift_parent_key = $data['freegift_parent_key'] ) {
                    $result = array_intersect($parent_key,$freegift_parent_key);
                    if(empty($result)){
                        continue;
                    }
                    $keys = array_keys($result); //array_search($parent_key, $data['freegift_parent_key']);
                    if (count($data['freegift_parent_key']) <= 1) {
                        $quote->removeItem($item->getItemId())->save();
                    } else {
                        foreach ($keys as $key){
                            unset($data['freegift_parent_key'][$key]);
                        }
                        $item->getOptionByCode('info_buyRequest')->setValue(serialize($data));
                        $item->setQty($item->getQty() - $qtyRemoved);
                    }
                }
            }
        }

        return $this;
    }

    protected function resetSession()
    {
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

    private function _isGift($item)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getOptionByCode('free_catalog_gift') && $item->getOptionByCode('free_catalog_gift')->getValue() == 1) {
            return true;
        }

//        if($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1){
//            return true;
//        }
        return false;
    }
}
