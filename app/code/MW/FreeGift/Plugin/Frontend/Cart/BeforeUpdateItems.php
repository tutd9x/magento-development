<?php
namespace MW\FreeGift\Plugin\Frontend\Cart;

use Magento\Quote\Model\Quote;

class BeforeUpdateItems
{
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @var \MW\FreeGift\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @param \MW\FreeGift\Model\Config $config
     * @param \MW\FreeGift\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $resourceSession
     */
    public function __construct(
        \MW\FreeGift\Model\Config $config,
        \MW\FreeGift\Helper\Data $helper,
        \Magento\Checkout\Model\Session $resourceSession
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->checkoutSession = $resourceSession;
    }

    public function beforeUpdateItems(
        \Magento\Checkout\Model\Cart $subject,
        $data
    ) {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        $infoDataObject = new \Magento\Framework\DataObject($data);
        if ($infoDataObject) {
            foreach ($infoDataObject->getData() as $itemId => $itemInfo) {
                $item = $this->getQuote()->getItemById($itemId);
                if ($this->_isSalesGift($item)) {
                    $data[$itemId]['qty'] = 1;
                    $data[$itemId]['before_suggest_qty'] = 1;
                }
            }
        }
        return [$data];
    }

    /**
     * Retrieve sales quote model
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (empty($this->quote)) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    private function _isGift($item)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        if ($item->getOptionByCode('free_catalog_gift') && $item->getOptionByCode('free_catalog_gift')->getValue() == 1) {
            return true;
        }

        return false;
    }

    private function _isSalesGift($item)
    {
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        if ($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1) {
            return true;
        }

        return false;
    }
}
