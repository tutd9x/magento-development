<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;

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

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helperFreeGift->getStoreConfig('mw_freegift/group_general/active'))
            return;

        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        if(count($items) >= 0 && $quote->getSubtotal() == 0){

            $quote->unsFreegiftIds();
            $quote->unsFreegiftAppliedRuleIds();
            $quote->unsFreegiftCouponCode();

            $quote->removeAllItems()->save();
            $this->cart->truncate();

            $this->resetSession();

            return;
        }

        $info_buyRequest_of_item_removed = [];
        $parent_key_removed = '';
        $quote_item_removed = $observer->getQuoteItem();
        if($quote_item_removed->getOptionByCode('info_buyRequest')){
            $info_buyRequest_of_item_removed = unserialize($quote_item_removed->getOptionByCode('info_buyRequest')->getValue());
        }

        if(isset($info_buyRequest_of_item_removed['freegift_key'])){
            $parent_key_removed = $info_buyRequest_of_item_removed['freegift_key'];
        }

        if($parent_key_removed != ''){
            foreach ( $items as $item ) {
                $additional_options = $item->getOptionByCode('additional_options');
                if(isset($additional_options)){
                    $dataOptions = unserialize($additional_options->getValue());
                    foreach($dataOptions as $data){
                        if(isset($data['freegift_parent_key']) && $data['freegift_parent_key'] == $parent_key_removed) {
                            // remove out of quote
                            $quote->removeItem($item->getItemId())->save();
                        }
                    }
                }

            }
        }

        // process for salesrule gift

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
