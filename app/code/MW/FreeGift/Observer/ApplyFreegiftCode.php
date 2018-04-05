<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;

class ApplyFreegiftCode implements ObserverInterface
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
    protected $cart;
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
        $freegift_coupon_code = $quote->getFreegiftCouponCode();

        $salesrule_coupon = $this->_couponFactory->create()->loadByCode($freegift_coupon_code);
        $rule_id = $salesrule_coupon->getRuleId();
        $salesrule = $this->_salesruleFactory->create()->load($rule_id);
        $salesruleData[$rule_id] = $salesrule->getData();
        $gift_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($salesruleData, 'getGiftOfSalesRule');

        if(!empty($salesruleData)){
            if(count($gift_product_ids) > 0){
                if(!$this->checkoutSession->getGiftSalesProductIds()){
                    $this->checkoutSession->setGiftSalesProductIds($gift_product_ids);
                }

                foreach($gift_product_ids as $giftData){
                    if ($giftData) {
                        if($giftData['number_of_free_gift'] >= count($gift_product_ids) ){
                            $storeId = 1;
                            $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
                            if($product_gift->getTypeId() == 'simple') {
                                $params['product'] = $giftData['rule_gift_ids'];
                                $params['freegift_with_code'] = '1';
                                $params['freegift_coupon_code'] = $freegift_coupon_code;
                                $params['rule_id'] = $giftData['rule_id'];


                                $product_gift->addCustomOption('freegift_coupon_code', 1);

                                $additionalOptions = [[
                                    'label' => 'Free Gift',
                                    'value' => $giftData['name'],
                                    'print_value' => $giftData['name'],
                                    'option_type' => 'text',
                                    'custom_view' => TRUE,
                                    'freegift_with_code' => 1,
                                    'freegift_coupon_code' => $freegift_coupon_code
                                ]];
                                // add the additional options array with the option code additional_options
                                $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
                                $this->cart->addProduct($product_gift, $params);
                            }
                        }
                    }
                }
            }
            $this->cart->save();
        }

        /*
         * Next step: set custom price for gift at event checkout_cart_product_add_after
         * */
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
