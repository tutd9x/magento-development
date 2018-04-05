<?php

namespace MW\FreeGift\Model\Quote\Address\Total;

class Freegift extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->setCode('mw_freegift');
        $this->_eventManager = $eventManager;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
        //\Magento\Quote\Model\Quote\Address $address
    ){
        parent::collect($quote, $shippingAssignment, $total);
        //$quote = $address->getQuote();
        $address = $shippingAssignment->getShipping()->getAddress();
        $store = $this->_storeManager->getStore($quote->getStoreId());
        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $eventArgs = [
            'website_id' => $store->getWebsiteId(),
            'customer_group_id' => $quote->getCustomerGroupId(),
            'freegift_coupon_code' => $quote->getFreegiftCouponCode(),
        ];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            if ($item->getNoDiscount()) {

            } else {
                /**
                 * Child item discount we calculate for parent
                 */
                if ($item->getParentItemId()) {
                    continue;
                }

                /**
                 * Composite item discount calculation
                 */

                if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                    foreach ($item->getChildren() as $child) {
                        $eventArgs['item'] = $child;
                        $this->_eventManager->dispatch('freegift_quote_address_freegift_item', $eventArgs);
                    }
                } else {
                    $eventArgs['item'] = $item;
                    $this->_eventManager->dispatch('freegift_quote_address_freegift_item', $eventArgs);
                }
            }
        }
        return $this;
    }

    /**
     * Generate content to log file debug.log By Hattetek.Com
     *
     * @param  $message string|array
     * @return void
     */
    function xlog($message = 'null')
    {
        $log = print_r($message, true);
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($log)
        ;
    }
    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return $this
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        return $result;
//        $amount = $total->getDiscountAmount();
//        if ($amount != 0) {
//            $title = __('Discount UhOA');
//            $code = $address->getCouponCode();
//            if (strlen($code)) {
//                $title = __('Discount UhOA (%1)', $code);
//            }
//            $address->addTotal(['code' => $this->getCode(), 'title' => $title, 'value' => -$amount]);
//        }

//        if ($amount != 0) {
//            $description = $total->getDiscountDescription();
//            $result = [
//                'code' => $this->getCode(),
//                'title' => strlen($description) ? __('Discount (%1)', $description) : __('Discount'),
//                'value' => $amount
//            ];
//        }
//        return $result;
    }
}