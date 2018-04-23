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

        $info_buyRequest_of_item_removed = [];
        $parent_key_removed = '';
        $quote_item_removed = $observer->getEvent()->getQuoteItem();
        if ($quote_item_removed->getOptionByCode('info_buyRequest')) {
            $info_buyRequest_of_item_removed = unserialize($quote_item_removed->getOptionByCode('info_buyRequest')->getValue());
        }

        if (isset($info_buyRequest_of_item_removed['freegift_key'])) {
            $parent_key_removed = $info_buyRequest_of_item_removed['freegift_key'];
        }

        if ($parent_key_removed != '') {
            foreach ( $items as $item ) {
                $additional_options = $item->getOptionByCode('additional_options');
                if (isset($additional_options)) {
                    $dataOptions = unserialize($additional_options->getValue());
                    foreach ($dataOptions as $data) {
                        if (isset($data['freegift_parent_key']) && $data['freegift_parent_key'] == $parent_key_removed) {
                            // remove out of quote
                            $quote->removeItem($item->getItemId())->save();
                        }
                    }
                }
            }
        }

        // process for salesrule gift

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
}
