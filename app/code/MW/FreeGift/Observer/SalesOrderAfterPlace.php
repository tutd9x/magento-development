<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;

class SalesOrderAfterPlace implements ObserverInterface
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
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleCustomerFactory;
    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $_coupon;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Usage
     */
    protected $_couponUsage;
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
        CustomerCart $cart,
        \MW\FreeGift\Model\RuleFactory $ruleFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\SalesRule\Model\Rule\CustomerFactory $ruleCustomerFactory,
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
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
        $this->_coupon = $coupon;
        $this->_couponUsage = $couponUsage;

    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helperFreeGift->getStoreConfig('mw_freegift/group_general/active'))
            return $this;

        $order = $observer->getEvent()->getOrder();
        $items = $order->getAllItems();

        if (!$order) {
            return $this;
        }

        $this->checkoutSession->unsRulegifts();
        $this->checkoutSession->unsProductgiftid();
        $this->checkoutSession->unsGooglePlus();
        $this->checkoutSession->unsLikeFb();
        $this->checkoutSession->unsShareFb();
        $this->checkoutSession->unsTwitter();
//        return $this;

        $rule_inserted = array();
        foreach ($items as $item) {
            if ($item->getParentItem())
                continue;

            $collection = $this->_ruleFactory->create()->getCollection();
            $resource = $this->_resource;
            $con = $resource->getConnection('core_write');
            //$con = Mage::getModel('core/resource')->getConnection('core_write');
            //Catalog rules
            $infoRequest = $item->getProductOptionByCode('info_buyRequest');

            // set times_used
            if (isset($infoRequest['applied_rule']) && $infoRequest['applied_rule']) {
                $applied_rules = explode(',',$infoRequest['applied_rules']);
                if (sizeof($applied_rules))
                    foreach ($applied_rules as $rule_id) {
                        // increase time_used
                        if (!in_array($rule_id, $rule_inserted['applied_rules'])) {
                            $sql = "UPDATE {$collection->getTable('freegift/rule')} SET times_used=times_used+1 WHERE rule_id={$rule_id}";
                            $con->query($sql);
                            $rule_inserted['applied_rules'][] = $rule_id;
                        }
                    }
            }

            if (isset($infoRequest['apllied_catalog_rules']) && $infoRequest['apllied_catalog_rules']) {
                $applied_rules = unserialize($infoRequest['apllied_catalog_rules']);
                if (sizeof($applied_rules))
                    foreach ($applied_rules as $rule_id) {
                        // increase time_used
                        if (!in_array($rule_id, $rule_inserted['apllied_catalog_rules'])) {
                            $sql = "UPDATE {$collection->getTable('freegift/rule')} SET times_used=times_used+1 WHERE rule_id={$rule_id}";
                            $con->query($sql);
                            $rule_inserted['apllied_catalog_rules'][] = $rule_id;
                        }
                    }
            }
            //Sales Rules
            if (!in_array($infoRequest['applied_rules'], $rule_inserted['applied_rules'])) {
                if (isset($infoRequest['applied_rules']) && $infoRequest['applied_rules']) {
                    if (isset($infoRequest['applied_rules'])):
                        $sql = "UPDATE {$collection->getTable('freegift/salesrule')} SET times_used=times_used+1 WHERE rule_id={$infoRequest['freegift_applied_rules']}";
                        $con->query($sql);
                    endif;
                }
                $rule_inserted['applied_rules'][] = $infoRequest['applied_rules'];
            }

            if (!in_array($infoRequest['rule_id'], $rule_inserted['applied_rules'])) {
                if (isset($infoRequest['freegift_with_code']) && $infoRequest['freegift_with_code']) {
                    if (isset($infoRequest['rule_id'])):
                        $sql = "UPDATE {$collection->getTable('freegift/salesrule')} SET times_used=times_used+1 WHERE rule_id={$infoRequest['rule_id']}";
                        $con->query($sql);
                    endif;
                }
                $rule_inserted['applied_rules'][] = $infoRequest['rule_id'];
            }
        }

        // lookup rule ids
        $ruleIds = explode(',', $order->getAppliedRuleIds());
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
                $rule->setTimesUsed($rule->getTimesUsed() + 1);
                $rule->save();

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

        $this->_coupon->load($order->getCouponCode(), 'code');
        if ($this->_coupon->getId()) {
            $this->_coupon->setTimesUsed($this->_coupon->getTimesUsed() + 1);
            $this->_coupon->save();
            if ($customerId) {
                $this->_couponUsage->updateCustomerCouponTimesUsed($customerId, $this->_coupon->getId());
            }
        }
        return $this;

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
