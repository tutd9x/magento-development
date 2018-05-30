<?php
/**
 * User: Anh TO
 * Date: 1/17/14
 * Time: 3:18 PM
 */
namespace MW\FreeGift\Block\Hidden\Inject\Checkout\Cart;

class Init extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $checkoutCart;
    protected $_coreRegistry;
    protected $helperFreeGift;
    protected $helperCart;
    protected $_resourceRule;
    protected $productFactory;
    protected $salesruleModel;
    protected $_ruleArr = [];
    protected $_priceBlock = [];
    protected $_free_product = [];
    protected $_block = 'catalog/product_price';
    protected $_priceBlockDefaultTemplate = 'catalog/product/price.phtml';
    protected $_priceBlockTypes = [];
    protected $helperImage;

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
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Helper\Image $helperImage,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->checkoutCart = $checkoutCart;
        $this->helperFreeGift = $helperFreeGift;
        $this->helperCart = $helperCart;
        $this->_coreRegistry = $coreRegistry;
        $this->_resourceRule = $resourceRule;
        $this->productFactory = $productFactory;
        $this->salesruleModel = $salesruleModel;
        $this->helperImage = $helperImage;
        parent::__construct($context, $data);
    }
    public function init()
    {
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        if (!empty($items)) {
            $init = [];
            foreach ($items as $item) {
                $product = $this->productFactory->create()->load($item->getProduct()->getId());
                $product_name = str_replace("'", "", $product->getName());
                $init[$item->getItemId()] = [
                    'product_id'                =>  $product->getId(),
                    'product_name'              =>  $product_name,
                    'product_image'             =>  $this->helperImage->init($product, 'category_page_list')->constrainOnly(true)->keepAspectRatio(true)->keepFrame(true)->resize(265, 265)->getUrl(),
                    'product_has_options'       =>  ($product->getOptions() ? "1" : "0"),
                    'product_type'              =>  $product->getTypeId(),
                ];
            }

            return json_encode($init);
        }

        return "";
    }
}
