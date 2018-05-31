<?php
/**
 * Created by PhpStorm.
 * User: lap15
 * Date: 9/4/2015
 * Time: 5:10 PM
 */

namespace MW\FreeGift\Block\Category;

class Freeproduct extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    protected $helperFreeGift;
    protected $_resourceRule;
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $configurable;
    protected $_productRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Framework\Registry $coreRegistry,
        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\Session $customerSession,
        \MW\FreeGift\Model\Config $config,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Catalog\Model\ProductRepository $productModelRepository,
        array $data = []
    ) {
        $this->helperFreeGift = $helperFreeGift;
        $this->_coreRegistry = $coreRegistry;
        $this->_resourceRule = $resourceRule;
        $this->productRepository = $productRepository;
        $this->_customerSession = $customerSession;
        $this->config = $config;
        $this->configurable = $configurable;
        $this->_productRepository = $productModelRepository;
        parent::__construct($context, $data);
    }

    public function getFreeGiftCatalog($current_product = null)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $customerGroupId = $this->_customerSession->getCustomerGroupId();
        $dateTs = $this->_localeDate->scopeTimeStamp($storeId);

        $freeGiftCatalogData = [];
        if ($current_product == null) {
            $current_product = $this->getCurrentProduct();
        }

        if ($additionalOption = $current_product->getCustomOption('mw_free_catalog_gift')) {
            if ($additionalOption->getValue() == 1) {
                $ruleData = $this->_resourceRule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $current_product->getId());
                /* Sort array by column sort_order */
                array_multisort(array_column($ruleData, 'sort_order'), SORT_ASC, $ruleData);
                $ruleData = $this->_filterByActionStop($ruleData);
                if (!empty($ruleData)) {
                    $freeGiftCatalogData = $this->helperFreeGift->getGiftDataByRule($ruleData);
                }
            }
        }
        return $freeGiftCatalogData;
    }

    public function getProductGiftData($productId)
    {
        $product_gift = null;
        $storeId = $this->_storeManager->getStore()->getId();
        if (isset($productId) && $productId != "") {
            $product_gift = $this->productRepository->getById($productId, false, $storeId);
        }
        return $product_gift;
    }

    public function getCurrentProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }

    private function _filterByActionStop($ruleData)
    {
        $result = [];
        foreach ($ruleData as $data) {
            $result[$data['rule_id']] = $data;
            if (isset($data['action_stop']) && $data['action_stop'] == '1') {
                break;
            }
        }
        return $result;
    }

    public function getUrlFreeGiftImage(){
        $url_image = $this->config->getImageFreeGift();
        if($url_image){
            return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product/placeholder/'.$url_image;
        }else{
            return $this->getViewFileUrl('MW_FreeGift::images/freegift_50.png');
        }
    }

    public function getFreeGiftProductUrl($productId)
    {
        $parentIds = $this->configurable->getParentIdsByChild($productId);
        $parentId = array_shift($parentIds);
        if(!empty($parentId)){
            $product = $this->_productRepository->getById($parentId);
            return $product->getUrlModel()->getUrl($product);
        }else{
            $product = $this->getProductGiftData($productId);
            return $product->getProductUrl();
        }
    }
}
