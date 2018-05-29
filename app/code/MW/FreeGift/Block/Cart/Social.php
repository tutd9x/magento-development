<?php
namespace MW\FreeGift\Block\Cart;

class Social extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \MW\FreeGift\Model\Config
     */
    protected $config;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \MW\FreeGift\Model\Config $config
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MW\FreeGift\Model\Config $config,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
        $this->config = $config;
        $this->dateTime = $dateTime;
    }

    public function getCustomStatus()
    {
        $rules = $this->getCheckoutSession()->getRulegifts();
        $enable_social = false;
        $enable_google = false;
        $enable_like_fb = false;
        $enable_share_fb = false;
        $enable_twitter = false;

        $default_message = '';

        if ($rules) {
            foreach ($rules as $rule) {
                if ($rule['enable_social'] == 1) $enable_social = true;
                if ($rule['google_plus'] == 1) $enable_google = true;
                if ($rule['like_fb'] == 1) $enable_like_fb = true;
                if ($rule['share_fb'] == 1) $enable_share_fb = true;
                if ($rule['twitter'] == 1) $enable_twitter = true;
                if ($default_message == '') $default_message = $rule['default_message'];
            }
        }

        $result = array('enable_social'=>$enable_social,'google_plus'=>$enable_google,'default_message'=>$default_message,
            'like_fb'=>$enable_like_fb,'share_fb'=>$enable_share_fb,'twitter'=>$enable_twitter);

        return $result;
    }

    public function getProductId()
    {
        $ids = $this->getCheckoutSession()->getProductgiftid();
        $url = "http://local.shared/magento/2110/josie-yoga-jacket.html"; // Mage::getModel('catalog/product')->load($ids[0])->getProductUrl();
        return $url;
    }

    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
    public function getDatime()
    {
        return $this->dateTime;
    }

    public function getStoreConfig($xmlPath)
    {
        return $this->config->getValue($xmlPath);
    }
}
