<?php
/**
 * User: Anh TO
 * Date: 3/24/14
 * Time: 5:37 PM
 */
namespace MW\FreeGift\Block\Hidden\Inject;
use Magento\Store\Model\ScopeInterface;
class Template extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $checkoutCart;
    protected $_coreRegistry;
    protected $helperFreeGift;
    protected $helperCart;
    protected $_resourceRule;
    protected $productRepository;
    protected $salesruleModel;
    protected $_ruleArr = array();
    protected $_priceBlock = array();
    protected $_free_product = array();
    protected $_block = 'catalog/product_price';
    protected $_priceBlockDefaultTemplate = 'catalog/product/price.phtml';
    protected $_priceBlockTypes = array();
    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::checkout/cart/free_gift.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Checkout\Helper\Cart $helperCart,
        \Magento\Framework\Registry $coreRegistry,
        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
        \MW\FreeGift\Model\SalesRule $salesruleModel,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->checkoutCart = $checkoutCart;
        $this->helperFreeGift = $helperFreeGift;
        $this->helperCart = $helperCart;
        $this->_coreRegistry = $coreRegistry;
        $this->_resourceRule = $resourceRule;
        $this->productRepository = $productRepository;
        $this->salesruleModel = $salesruleModel;
        parent::__construct($context, $data);
    }
    public function _toHtml()
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';
        return $this->fetchView($this->getTemplateFile());
    }
}