<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;
class ApplyFreegiftCodeCanceled implements ObserverInterface
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
    protected $_couponFactory;
    protected $_salesruleFactory;
    protected $productRepository;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

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

        $quote = $observer->getQuote();
        $items = $quote->getAllVisibleItems();
        $oldCouponCode = $observer->getOldCouponCode();

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
        $this->checkoutSession->unsGiftSalesProductIds();
    }

    function xlog($message = 'null'){
        if(gettype($message) == 'string'){
        }else{
            $message = serialize($message);
        }
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }

}
