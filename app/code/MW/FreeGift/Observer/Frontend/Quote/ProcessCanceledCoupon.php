<?php
namespace MW\FreeGift\Observer\Frontend\Quote;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Store\Model\StoreManagerInterface;

class ProcessCanceledCoupon implements ObserverInterface
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
     * @var \MW\FreeGift\Model\CouponFactory
     */
    protected $_couponFactory;
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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

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
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \MW\FreeGift\Model\CouponFactory $couponFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->_calculator = $validator;
        $this->checkoutSession = $resourceSession;
        $this->_salesRuleFactory = $salesRuleFactory;
        $this->helper = $helper;
        $this->cart = $cart;
        $this->productFactory = $productFactory;
        $this->_couponFactory = $couponFactory;
        $this->_storeManager = $storeManager;
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
        $quote = $this->getQuote();
        $items = $quote->getAllVisibleItems();
        $oldCouponCode = $observer->getEvent()->getOldCouponCode();

        foreach($items as $item)
        {
            $info_buyRequest = $item->getOptionByCode('info_buyRequest');
            if(isset($info_buyRequest)) {
                $data = unserialize($info_buyRequest->getValue());
                if(isset($data['freegift_coupon_code']) && $data['freegift_coupon_code'] == $oldCouponCode) {
                    // remove out of quote
                    $quote->removeItem($item->getItemId())->save();
                }
            }
        }
//        $this->checkoutSession->unsGiftSalesProductIds();
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

        if($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1){
            return true;
        }

        return false;
    }

}
