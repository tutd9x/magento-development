<?php
namespace MW\FreeGift\Observer\Frontend\Order;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Quote\Model\Quote;

class OrderPlaceAfter implements ObserverInterface
{
    protected $_ruleFactory;
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
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var \MW\FreeGift\Model\SalesRuleFactory
     */
    protected $_ruleCustomerFactory;
    /**
     * @var \MW\FreeGift\Model\CouponFactory
     */
    protected $_couponFactory;
    /**
     * @var \MW\FreeGift\Model\ResourceModel\Coupon\Usage
     */
    protected $_couponUsage;
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_collection;
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
        \MW\FreeGift\Model\RuleFactory $ruleFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \MW\FreeGift\Model\SalesRule\CustomerFactory $ruleCustomerFactory,
        \MW\FreeGift\Model\Config $config,
        \Magento\Sales\Model\ResourceModel\Order\Collection $collection,
        \MW\FreeGift\Model\ResourceModel\Coupon\Usage $couponUsage
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
        $this->_ruleFactory = $ruleFactory;
        $this->_resource = $resource;
        $this->_ruleCustomerFactory = $ruleCustomerFactory;
        $this->_couponUsage = $couponUsage;
        $this->_collection = $collection;
        $this->config = $config;

    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if (!$this->config->isEnabled()) {
            return $this;
        }

        $this->resetSession();

        $orderIds = $observer->getEvent()->getOrderIds();
        $this->_collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        /** @var $order \Magento\Sales\Model\Order */
        foreach ($this->_collection as $order) {
            $items = $order->getAllItems();
            if (!$order) {
                return $this;
            }
            $rule_inserted = array();
            $sales_rule_inserted = array();
            $couponSalesRule = null;
            foreach ($items as $item) {
                if ($item->getParentItem())
                    continue;
                $resource = $this->_resource;
                $con = $resource->getConnection('core_write');
                //Catalog rules
                $infoRequest = $item->getProductOptionByCode('info_buyRequest');
                // set times_used
                if (isset($infoRequest['freegift_rule_data']) && isset($infoRequest['free_sales_key'])) {
                    $collection = $this->_ruleFactory->create()->getCollection();
                    $applied_rules = array();
                    foreach($infoRequest['freegift_rule_data'] as $ruleData){
                        $applied_rules[] = $ruleData['rule_id'];
                    }
                    if (sizeof($applied_rules))
                        foreach ($applied_rules as $rule_id) {
                            // increase time_used
                            if (!in_array($rule_id, $rule_inserted)) {
//                                $sql = "UPDATE {$collection->getTable('freegift/rule')} SET times_used=times_used+1 WHERE rule_id={$rule_id}";
//                                $con->query($sql);
                                $ruleData = $collection->addFieldToFilter('rule_id', $rule_id)->getFirstItem();
                                $timeUsed = $ruleData->getTimesUsed();
                                $collection->getFirstItem()->setTimesUsed($timeUsed + 1)->save();
                                $rule_inserted[] = $rule_id;
                            }
                        }
                }
                //Sales Rules
                if (isset($infoRequest['free_sales_key']) && isset($infoRequest['freegift_rule_data'])) {
                    $collectionSalesRule = $this->_salesruleFactory->create()->getCollection();
                    $applied_salesrules = array();
                    foreach($infoRequest['freegift_rule_data'] as $salesruleData){
                        $applied_salesrules[] = $salesruleData['rule_id'];
                    }
                    if (sizeof($applied_salesrules)){
                        foreach ($applied_salesrules as $salesrule_id) {
                            // increase time_used
                            if (!in_array($salesrule_id, $sales_rule_inserted)) {
                                $sql = "UPDATE {$collectionSalesRule->getTable('mw_freegift_salesrule')} SET times_used=times_used+1 WHERE rule_id={$salesrule_id}";
                                $con->query($sql);
//                                $ruleData = $collectionSalesRule->addFieldToFilter('rule_id', $salesrule_id)->getFirstItem();
//                                $timeUsed = $ruleData->getTimesUsed();
//                                $collectionSalesRule->setTimesUsed($timeUsed + 1)->save();
                                $sales_rule_inserted[] = $salesrule_id;
                            }
                        }
                    }

                    if(isset($infoRequest['freegift_coupon_code'])){
                        $couponSalesRule = $infoRequest['freegift_coupon_code'];
                    }
                }
            }
            // lookup rule ids
            $ruleIds = $sales_rule_inserted;
            $ruleIds = array_unique($ruleIds);
            $ruleCustomer = null;
            $customerId = $order->getCustomerId();
            // use each rule (and apply to customer, if applicable)
            foreach ($ruleIds as $ruleId) {
                if (!$ruleId) {
                    continue;
                }
                /** @var \Magento\SalesRule\Model\Rule $rule */
                $rule = $this->_ruleFactory->create();
                $rule->load($ruleId);
                if ($rule->getId()) {
                    if ($customerId) {
                        /** @var \Magento\SalesRule\Model\Rule\Customer $ruleCustomer */
                        $ruleCustomer = $this->_ruleCustomerFactory->create();
                        $ruleCustomer->loadByCustomerRule($customerId, $ruleId);
                        if ($ruleCustomer->getId()) {
                            $ruleCustomer->setTimesUsed($ruleCustomer->getTimesUsed() + 1);
                        } else {
                            $ruleCustomer->setCustomerId($customerId)->setRuleId($ruleId)->setTimesUsed(1);
                        }
                        $ruleCustomer->save();
                    }
                }
            }

            $couponCollection = $this->_couponFactory->create()->getCollection();
            $couponData = $couponCollection->addFieldToFilter('code',$couponSalesRule)->getFirstItem();
            if ($couponData->getId()) {
                $couponData->setTimesUsed($couponData->getTimesUsed() + 1);
                $couponData->save();
                if ($customerId) {
                    $this->_couponUsage->updateCustomerCouponTimesUsed($customerId, $couponData->getId());
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
}
