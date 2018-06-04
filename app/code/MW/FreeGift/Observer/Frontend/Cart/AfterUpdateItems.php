<?php

namespace MW\FreeGift\Observer\Frontend\Cart;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Cart as CustomerCart;

class AfterUpdateItems implements ObserverInterface
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

        $data = $observer->getEvent()->getInfo();
        $dataUpdate = [];
        if ($data) {
            foreach ($data->getData() as $itemId => $itemInfo) {
                $item = $this->getQuote()->getItemById($itemId);
                if (!$item) {
                    continue;
                }
                $this->_processCatalogRule($item);
            }
        }

        return $this;
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $dataToUpdate
     */
    private function _processCatalogRule(\Magento\Quote\Model\Quote\Item $itemGift)
    {
        $quote = $this->getQuote();
        if ($itemGift->getOptionByCode('free_catalog_gift') && $itemGift->getOptionByCode('free_catalog_gift')->getValue() == 1) {
            if ($itemGift->getOptionByCode('info_buyRequest') && $infoGift = unserialize($itemGift->getOptionByCode('info_buyRequest')->getValue())) {
                $freegift_parent_key = $infoGift['freegift_parent_key'];
                $qtyToUpdate = 0;
                foreach ($this->getQuote()->getAllItems() as $item) {
                    /* @var $item \Magento\Quote\Model\Quote\Item */
                    if (!$this->_isGift($item) && !$this->_isSalesGift($item)) {
                        if ($item->getParentItem()) {
                            continue;
                        }

                        $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                        $parent_keys = $info['freegift_keys'];

                        $result = array_intersect($parent_keys, $freegift_parent_key);
                        if (empty($result)) {
                            continue;
                        }

                        foreach ($result as $key) {
                            $ruleData = $infoGift['freegift_rule_data'][$key];
                            $buy_x = $ruleData['buy_x'];
                            $get_y = $ruleData['get_y'];

                            // Kiem tra so luong theo ti le buy x get y, khong tinh khi co phan du
                            $current_qty = $item->getQty();
                            if ($current_qty % $buy_x != 0) {
                                $current_qty = ((int)($current_qty / $buy_x)) * $buy_x;
                            }

                            if ($current_qty < $buy_x) {
                                continue;
                            }

                            $qty_for_gift = (int)($current_qty * $get_y / $buy_x);
                            $infoGift['freegift_qty_info'][$key] = $qty_for_gift;
                            $qtyToUpdate += $qty_for_gift;
                        }
                    }
                }

                if ($qtyToUpdate == 0) {
                    $quote->removeItem($itemGift->getItemId())->save();
                } else {
                    $infoGift['qty'] = $qtyToUpdate;
                    $itemGift->getOptionByCode('info_buyRequest')->setValue(serialize($infoGift));
                    $itemGift->setQty($qtyToUpdate);
                }
            }
        }
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
}
