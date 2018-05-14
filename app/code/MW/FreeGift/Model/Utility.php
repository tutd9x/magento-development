<?php

namespace MW\FreeGift\Model;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Utility
 *
 * @package Magento\SalesRule\Model
 */
class Utility
{
    /**
     * @var array
     */
    protected $_roundingDeltas = [];

    /**
     * @var array
     */
    protected $_baseRoundingDeltas = [];

    /**
     * @var \MW\FreeGift\Model\ResourceModel\Coupon\UsageFactory
     */
    protected $usageFactory;

    /**
     * @var \MW\FreeGift\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \MW\FreeGift\Model\SalesRule\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $objectFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;
    protected $checkoutSession;

    /**
     * @param \MW\FreeGift\Model\ResourceModel\Coupon\UsageFactory $usageFactory
     * @param CouponFactory $couponFactory
     * @param SalesRule\CustomerFactory $customerFactory
     * @param \Magento\Framework\DataObjectFactory $objectFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \MW\FreeGift\Model\ResourceModel\Coupon\UsageFactory $usageFactory,
        \MW\FreeGift\Model\CouponFactory $couponFactory,
        \MW\FreeGift\Model\SalesRule\CustomerFactory $customerFactory,
        \Magento\Framework\DataObjectFactory $objectFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->couponFactory = $couponFactory;
        $this->customerFactory = $customerFactory;
        $this->usageFactory = $usageFactory;
        $this->objectFactory = $objectFactory;
        $this->priceCurrency = $priceCurrency;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Check if rule can be applied for specific address/quote/customer
     *
     * @param \MW\FreeGift\Model\SalesRule $rule
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function canProcessRule($rule, $address)
    {
        if ($rule->hasIsValidForAddress($address) && !$address->isObjectNew()) {
            return $rule->getIsValidForAddress($address);
        }

        /**
         * check per coupon usage limit
         */
        if ($rule->getCouponType() != \MW\FreeGift\Model\Salesrule::COUPON_TYPE_NO_COUPON) {
            $couponCode = $address->getQuote()->getFreegiftCouponCode();
            if (strlen($couponCode)) {
                /** @var \MW\FreeGift\Model\Coupon $coupon */
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if ($coupon->getId()) {
                    // check entire usage limit
                    if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
                        $rule->setIsValidForAddress($address, false);
                        return false;
                    }
                    // check per customer usage limit
                    $customerId = $address->getQuote()->getCustomerId();
                    if ($customerId && $coupon->getUsagePerCustomer()) {
                        $couponUsage = $this->objectFactory->create();
                        $this->usageFactory->create()->loadByCustomerCoupon(
                            $couponUsage,
                            $customerId,
                            $coupon->getId()
                        );
                        if ($couponUsage->getCouponId() &&
                            $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
                        ) {
                            $rule->setIsValidForAddress($address, false);
                            return false;
                        }
                    }
                }
            }
        }

        /**
         * check per rule usage limit
         */
        $ruleId = $rule->getId();
        if ($ruleId && $rule->getUsesPerCustomer()) {
            $customerId = $address->getQuote()->getCustomerId();
            /** @var \MW\FreeGift\Model\SalesRule\Customer $ruleCustomer */
            $ruleCustomer = $this->customerFactory->create();
            $ruleCustomer->loadByCustomerRule($customerId, $ruleId);
            if ($ruleCustomer->getId()) {
                if ($ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
                    $rule->setIsValidForAddress($address, false);
                    return false;
                }
            }
        }

        /*
         * check rule with social sharing
         * */
        $temp_rule = $this->checkoutSession->getRulegifts();
        if($rule->getEnableSocial() == '1'){

            array_push($temp_rule,$rule->getData());
            $this->checkoutSession->setRulegifts($temp_rule);

//            $google_plus    = $rule->getGooglePlus();
//            $like_fb        = $rule->getLikeFb();
//            $share_fb       = $rule->getShareFb();
//            $twitter        = $rule->getTwitter();

            // check trang thai cua action share social, neu dung thi hien thi free gift, neu sai thi khong hien thi.
            $google_plus    = $this->checkoutSession->getGooglePlus();
            $like_fb        = $this->checkoutSession->getLikeFb();
            $share_fb       = $this->checkoutSession->getShareFb();
            $twitter        = $this->checkoutSession->getTwitter();

            if( $google_plus == true || $like_fb == true || $share_fb == true || $twitter == true ){
            }else{
                $rule->setIsValidForAddress($address, false);
                return false;
            }
        }

        $rule->afterLoad();
        /**
         * quote does not meet rule's conditions
         */
        if (!$rule->validate($address)) {
            $rule->setIsValidForAddress($address, false);
            return false;
        }
        /**
         * passed all validations, remember to be valid
         */
        $rule->setIsValidForAddress($address, true);
        return true;
    }

    /**
     * @param \MW\FreeGift\Model\SalesRule\Action\Discount\Data $discountData
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param float $qty
     * @return void
     */
    public function minFix(
        \MW\FreeGift\Model\SalesRule\Action\Discount\Data $discountData,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $qty
    ) {
        $itemPrice = $this->getItemPrice($item);
        $baseItemPrice = $this->getItemBasePrice($item);

        $itemDiscountAmount = $item->getDiscountAmount();
        $itemBaseDiscountAmount = $item->getBaseDiscountAmount();

        $discountAmount = min($itemDiscountAmount + $discountData->getAmount(), $itemPrice * $qty);
        $baseDiscountAmount = min($itemBaseDiscountAmount + $discountData->getBaseAmount(), $baseItemPrice * $qty);

        $discountData->setAmount($discountAmount);
        $discountData->setBaseAmount($baseDiscountAmount);
    }

    /**
     * Process "delta" rounding
     *
     * @param \MW\FreeGift\Model\SalesRule\Action\Discount\Data $discountData
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function deltaRoundingFix(
        \MW\FreeGift\Model\SalesRule\Action\Discount\Data $discountData,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item
    ) {
        $store = $item->getQuote()->getStore();
        $discountAmount = $discountData->getAmount();
        $baseDiscountAmount = $discountData->getBaseAmount();

        //TODO Seems \Magento\Quote\Model\Quote\Item\AbstractItem::getDiscountPercent() returns float value
        //that can not be used as array index
        $percentKey = $item->getDiscountPercent();
        if ($percentKey) {
            $delta = isset($this->_roundingDeltas[$percentKey]) ? $this->_roundingDeltas[$percentKey] : 0;
            $baseDelta = isset($this->_baseRoundingDeltas[$percentKey]) ? $this->_baseRoundingDeltas[$percentKey] : 0;

            $discountAmount += $delta;
            $baseDiscountAmount += $baseDelta;

            $this->_roundingDeltas[$percentKey] = $discountAmount - $this->priceCurrency->round($discountAmount);
            $this->_baseRoundingDeltas[$percentKey] = $baseDiscountAmount
                - $this->priceCurrency->round($baseDiscountAmount);
        }

        $discountData->setAmount($this->priceCurrency->round($discountAmount));
        $discountData->setBaseAmount($this->priceCurrency->round($baseDiscountAmount));

        return $this;
    }

    /**
     * Return item price
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return float
     */
    public function getItemPrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        $calcPrice = $item->getCalculationPrice();
        return $price === null ? $calcPrice : $price;
    }

    /**
     * Return item base price
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return float
     */
    public function getItemBasePrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        return $price !== null ? $item->getBaseDiscountCalculationPrice() : $item->getBaseCalculationPrice();
    }

    /**
     * Return discount item qty
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \MW\FreeGift\Model\SalesRule $rule
     * @return int
     */
    public function getItemQty($item, $rule)
    {
        $qty = $item->getTotalQty();
        $discountQty = $rule->getDiscountQty();
        return $discountQty ? min($qty, $discountQty) : $qty;
    }

    /**
     * Merge two sets of ids
     *
     * @param array|string $a1
     * @param array|string $a2
     * @param bool $asString
     * @return array|string
     */
    public function mergeIds($a1, $a2, $asString = true)
    {
        if (!is_array($a1)) {
            $a1 = empty($a1) ? [] : explode(',', $a1);
        }
        if (!is_array($a2)) {
            $a2 = empty($a2) ? [] : explode(',', $a2);
        }
        $a = array_unique(array_merge($a1, $a2));
        if ($asString) {
            $a = implode(',', $a);
        }
        return $a;
    }

    /**
     * @return void
     */
    public function resetRoundingDeltas()
    {
        $this->_roundingDeltas = [];
        $this->_baseRoundingDeltas = [];
    }
}
