<?php
namespace MW\FreeGift\Observer\Frontend\Quote;

use Magento\Framework\Event\ObserverInterface;

class ApplySalesrule implements ObserverInterface
{
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;

    /**
     * @var \MW\FreeGift\Model\SalesRuleFactory
     */
    protected $_salesRuleFactory;
    /**
     * @var \MW\FreeGift\Helper\Data
     */
    protected $helperFreeGift;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @param \MW\FreeGift\Model\Config $config
     * @param \MW\FreeGift\Model\SalesRuleFactory $salesRuleFactory
     * @param \MW\FreeGift\Helper\Data $helperFreeGift
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data = []
     */
    public function __construct(
        \MW\FreeGift\Model\Config $config,
        \MW\FreeGift\Model\SalesRuleFactory $salesRuleFactory,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->config = $config;

        $this->_salesRuleFactory = $salesRuleFactory;
        $this->helperFreeGift = $helperFreeGift;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Set Quote information about MSRP price enabled
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = $observer->getEvent()->getQuote();

        $salesruleData = [];
        $gift_sales_product_ids = [];
        $rule_ids = explode(',',$quote->getFreegiftAppliedRuleIds());

        foreach($rule_ids as $rule_id){
            $salesrule = $this->_salesRuleFactory->create()->load($rule_id);
            if($salesrule->getId()){
                $salesruleData[$rule_id] = $salesrule->getData();
            }
        }
        $gift_sales_product_ids = $this->helperFreeGift->getGiftDataBySalesRule($salesruleData);


        if (!empty($salesruleData)) {
            if(count($gift_sales_product_ids) > 0){
                $this->checkoutSession->setGiftSalesProductIds($gift_sales_product_ids);
            }
        }


    }
}
