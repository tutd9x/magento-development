<?php
namespace MW\FreeGift\Observer\Frontend\Quote;

use Magento\Framework\Event\ObserverInterface;

class AddParentKey implements ObserverInterface
{
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @var \MW\FreeGift\Model\ResourceModel\RuleFactory
     */
    protected $resourceRuleFactory;


    /**
     * Initialize dependencies.
     *
     * @param \MW\FreeGift\Model\Config $config
     * @param \MW\FreeGift\Model\Validator $validator
     */
    public function __construct(
        \MW\FreeGift\Model\Config $config,
        \MW\FreeGift\Model\ResourceModel\RuleFactory $resourceRuleFactory
    ) {
        $this->config = $config;
        $this->resourceRuleFactory = $resourceRuleFactory;
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

        $data = $observer->getEvent()->getInfo();
        $cart = $observer->getEvent()->getCart();
        foreach ($data as $itemId => $itemInfo) {
            $item = $cart->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }
        }


        return $this;
    }
}
