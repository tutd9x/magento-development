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
        $this->_processSalesRule($observer, $items);

        //$this->resetSession();

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

        //$current_qty = $this->_countCurrentItemInCart($item_removed, $parent_key);

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
                    if (empty($result)) {
                        continue;
                    }
                    $freegift_rule_data = $data['freegift_rule_data'];
                    $qtyToUpdate = 0;
                    foreach ($result as $key) {
                        $qtyGiftToRemove = ($freegift_rule_data[$key]['buy_x'] * $qtyRemoved);

                        $qtyToUpdate = $item->getQty() - $qtyGiftToRemove;
                        if ($qtyToUpdate <= 0) {
                            $quote->removeItem($item->getItemId())->save();
                        } else {
                            $freegift_qty_info = $data['freegift_qty_info'][$key] - $qtyGiftToRemove;
                            if($freegift_qty_info == 0){
                                unset($data['freegift_parent_key'][$key]);
                                unset($data['freegift_qty_info'][$key]);
                                unset($data['freegift_rule_data'][$key]);
                            }else{
                                $data['freegift_qty_info'][$key] = $freegift_qty_info;
                            }
                            $item->getOptionByCode('info_buyRequest')->setValue(serialize($data));
                            $item->setQty($qtyToUpdate);
                        }
                    }
                }
            }
        }

        return $this;
    }

    private function _processSalesRule($observer, $items)
    {
        $item_removed = $observer->getEvent()->getQuoteItem();
        $salesGiftRemoved = $this->checkoutSession->getSalesGiftRemoved();

        if ($this->_isSalesGift($item_removed)) {

            if ( $item_removed->getOptionByCode('info_buyRequest') && $itemInfo = unserialize($item_removed->getOptionByCode('info_buyRequest')->getValue()) ) {

                if (isset($itemInfo['free_sales_key'])) {
                    $parent_keys = $itemInfo['free_sales_key'];
                    if (empty($parent_keys)) {
                        return $this;
                    }
                }

                foreach ($parent_keys as $key){
                    if(array_key_exists($key, $itemInfo['freegift_rule_data'])) {
                        $freegift_rule_data = $itemInfo['freegift_rule_data'][$key];
                        $freegift_sales_key = $freegift_rule_data['freegift_sales_key'];
                        $salesGiftRemoved[$freegift_sales_key] = $freegift_sales_key;
                        $this->checkoutSession->setSalesGiftRemoved($salesGiftRemoved);
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
        $this->checkoutSession->unsetData('sales_gift_removed');
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

        if($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1){
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

    /**
     * Counting current item in cart
     *
     * @return $count
     */
    public function _countCurrentItemInCart($item_removed, $parent_keys)
    {
        $count = 0;
        foreach ($this->getQuote()->getAllItems() as $item) {
            if($item->getId() == $item_removed->getId()) {
                /* @var $item \Magento\Quote\Model\Quote\Item */
                if ($item->getParentItem()) {
                    continue;
                }

                if (!$this->_isGift($item)) {
                    $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                    $freegift_parent_key = isset($info['freegift_keys']) ? $info['freegift_keys'] : array();
                    $result = array_intersect($parent_keys,$freegift_parent_key);
                    if (empty($result)) {
                        continue;
                    } else {
                        $count += $item->getQty();
                    }
                }
            }
        }
        return $count;
    }

}
