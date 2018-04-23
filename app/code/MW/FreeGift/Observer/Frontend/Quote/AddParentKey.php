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


        $randKey = md5(rand(1111, 9999));
        $items = $observer->getEvent()->getItems();

        $groups = array();
        foreach ($items as $item) {
            $infoRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            $productId = $infoRequest['product'];
            $additionalOption = $item->getOptionByCode('mw_free_catalog_gift');
            if (isset($additionalOption)) {
                $dataOptions = $additionalOption->getValue();
//                $applied_rule_ids = $this->helper->_prepareRuleIds($giftData);
                if ($dataOptions && !isset($infoRequest['freegift_key'])) {
                    $infoRequest['freegift_key'] = $randKey;
                    $item->getOptionByCode('info_buyRequest')->setValue(serialize($infoRequest));
//                    $infoRequest['mw_applied_catalog_rule'] = serialize($applied_rule_ids);
                }
            }
        }
//        $this->_getSession()->getQuote()->setTotalsCollectedFlag(false);
//        $this->_getSession()->getQuote()->collectTotals();
//        $this->_getSession()->getQuote()->setTotalsCollectedFlag(false);

        return $this;
    }
}
