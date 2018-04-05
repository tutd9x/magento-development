<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;

class ApplyDiscountSalesrule implements ObserverInterface
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
    protected $_salesruleFactory;

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
        $quote = $this->checkoutSession->getQuote();
        $salesruleData = [];
        $gift_sales_product_ids = [];
        $rule_ids = explode(',',$quote->getFreegiftAppliedRuleIds());

        foreach($rule_ids as $rule_id){
            $salesrule = $this->_salesruleFactory->create()->load($rule_id);
            $salesruleData[$rule_id] = $salesrule->getData();
        }
        $gift_sales_product_ids = $this->helperFreeGift->getFreeGiftCatalogProduct($salesruleData, 'getGiftOfSalesRule');

        if(!empty($salesruleData)){
            if(count($gift_sales_product_ids) > 0){
//                if(!$this->checkoutSession->getGiftSalesProductIds()){
                $this->checkoutSession->setGiftSalesProductIds($gift_sales_product_ids);
//                }
//                foreach($gift_sales_product_ids as $giftData){
//                    if ($giftData) {
//                        if($giftData['number_of_free_gift'] >= count($gift_sales_product_ids) ){
//                            //@@TODO $storeId
//                            $storeId = 1;
//                            $product_gift = $this->productRepository->getById($giftData['rule_gift_ids'], false, $storeId);
//                            if($product_gift->getTypeId() == 'simple'){
//                                $params['qty'] = 1;
//                                $params['product'] = $giftData['rule_gift_ids'];
//                                $params['freegift'] = '1';
//                                $params['freegift_name'] = $giftData['name'];
//                                $params['rule_id'] = $giftData['rule_id'];
//
//                                $product_gift->addCustomOption('freegift', 1);
//
//                                $additionalOptions = [[
//                                    'label' => 'Free Gift',
//                                    'value' => $giftData['name'],
//                                    'print_value' => $giftData['name'],
//                                    'option_type' => 'text',
//                                    'custom_view' => TRUE,
//                                    'freegift' => 1,
//                                    'freegift_name' => $giftData['name']
//                                ]];
//                                // add the additional options array with the option code additional_options
//                                $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
//                                $this->cart->addProduct($product_gift, $params);
//                            }
//                        }
//                    }
//                }
//                $this->cart->save();
            }
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
