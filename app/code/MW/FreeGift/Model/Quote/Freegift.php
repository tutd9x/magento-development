<?php
namespace MW\FreeGift\Model\Quote;

class Freegift extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Discount calculation object
     *
     * @var \Magento\SalesRule\Model\Validator
     */
    protected $calculator;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \MW\FreeGift\Model\Validator $validator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MW\FreeGift\Model\Validator $validator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->setCode('mw_freegift');
        $this->eventManager = $eventManager;
        $this->calculator = $validator;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->checkoutSession = $checkoutSession;
    }



    /**
     * Collect address discount amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $store = $this->storeManager->getStore($quote->getStoreId());

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            $this->resetSession();
            return $this;
        }

        $eventArgs = [
            'website_id' => $store->getWebsiteId(),
            'customer_group_id' => $quote->getCustomerGroupId(),
            'freegift_coupon_code' => $quote->getFreegiftCouponCode(),
        ];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            if ($item->getNoDiscount() || !$this->calculator->canApplyDiscount($item)) {
                continue;
            }
            // to determine the child item discount, we calculate the parent
            if ($item->getParentItem()) {
                continue;
            }

            $eventArgs['item'] = $item;
            $this->eventManager->dispatch('freegift_quote_address_freegift_item', $eventArgs);

            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    $eventArgs['item'] = $child;
                    $this->eventManager->dispatch('freegift_quote_address_freegift_item', $eventArgs);
                }
//            } else {
                //$this->calculator->process($item);
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

    /**
     * Add discount total information to address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        return $result;
    }
}
