<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Block\Cart;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
class Social extends \Magento\Checkout\Block\Cart\AbstractCart
{
    protected $_scopeConfig;
    protected $_freegiftHelper;
    protected $_storeManager;
    protected $checkoutSession;
    protected $productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        \MW\FreeGift\Helper\Data $freegiftHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime $dateFormat,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->_isScopePrivate = true;
        $this->_storeManager = $context->getStoreManager();
        $this->_freegiftHelper = $freegiftHelper;
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * @return string
     */
    public function _getStoreConfig($xmlPath)
    {
        return $this->_freegiftHelper->getStoreConfig($xmlPath);
    }

    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }
    public function _gmtTimestamp()
    {
        return $this->dateTime->gmtTimestamp();
    }
    /**
     * @return string
     */
    public function _getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getCustomStatus(){
//        $this->checkoutSession->unsLikeFb();
//        $this->checkoutSession->unsShareFb();
        $rules = $this->checkoutSession->getRulegifts();
//        $this->xlog($rules);
        // tra ve trang thai cua cac nut social sharing, gia tri la khi tu 'true' hoac 'false'
        $enable_social = 'false';
        $enable_google = 'false';
        $enable_like_fb = 'false';
        $enable_share_fb = 'false';
        $enable_twitter = 'false';

        $default_message = '';
        if(is_array($rules) && count($rules) > 0){
            foreach($rules as $rule){
                if($rule['enable_social'] == 1) $enable_social = 'true';
                if($rule['google_plus'] == 1) $enable_google = 'true';
                if($rule['like_fb'] == 1) $enable_like_fb = 'true';
                if($rule['share_fb'] == 1) $enable_share_fb = 'true';
                if($rule['twitter'] == 1) $enable_twitter = 'true';
                if($default_message == '') {
                    $default_message = $rule['default_message'];
                }
            }
        }

        $result = array(
            'enable_social'=>$enable_social,
            'google_plus'=>$enable_google,
            'like_fb'=>$enable_like_fb,
            'share_fb'=>$enable_share_fb,
            'twitter'=>$enable_twitter,
            'default_message'=>$default_message
        );

        return $result;
    }

    public function getProductId(){
        $url = '';
        $ids = $this->checkoutSession->getProductgiftid();
        if(is_array($ids) && count($ids) > 0){
            $url =  $this->productRepository->getById($ids[0])->getProductUrl();
        }
        return $url;
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

}
