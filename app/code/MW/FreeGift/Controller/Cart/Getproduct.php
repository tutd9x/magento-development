<?php
namespace MW\FreeGift\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class GetProduct extends \Magento\Checkout\Controller\Cart
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;
    protected $_layout;
    protected $helperFreeGift;
    protected $checkoutSession;
    protected $layoutFactory;
    protected $resultPageFactory;
    protected $_coreRegistry;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \MW\FreeGift\Model\CouponFactory $couponFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Checkout\Model\Session $checkoutSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Block\Product\ListProduct $listProduct,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_layout = $layout;
        $this->helperFreeGift = $helperFreeGift;
        $this->checkoutSession = $checkoutSession;
        $this->layoutFactory = $layoutFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->productRepository = $productRepository;
        $this->_listProduct = $listProduct;
        $this->_coreRegistry = $registry;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product || false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    public function execute()
    {
        $cart = $this->checkoutSession;
        $layout = $this->layoutFactory->create();
        $update = $layout->getUpdate();
        $update->load('catalog_product_view');
        $layout->generateXml();
        $layout->generateElements();
        $params = $this->getRequest()->getParams();

        if (!isset($params['action'])) {
            $params['action'] = 'view';
        }

        switch ($params['action']) {
            case 'view':
                $product = $this->_initProduct();
                if (!$product) {
                    return;
                }
                $textBtn = __("Add to Cart");
                $session_id = false;
                $qty = 1;
                break;
            case 'configure':
                /* Edit product in cart based id of item */
                $id = (int) $params['item_id'];
                $quoteItem = null;
                $cart = $this->cart; //Mage::getSingleton('checkout/cart');

                if ($id) {
                    $quoteItem = $cart->getQuote()->getItemById($id);
                }

                if (!$quoteItem) {
                    return;
                }

                $qty = $quoteItem->getQty();
                $product = $this->_initProduct($quoteItem->getProduct()->getId());

                if (!$product) {
                    return;
                }

                try {
                    $_params = new \Magento\Framework\DataObject();
                    $_params->setCategoryId(false);
                    $_params->setConfigureMode(true);
                    $_params->setBuyRequest($quoteItem->getBuyRequest());

                    $product = $this->helperFreeGift->prepareAndRender($quoteItem->getProduct()->getId(), $_params);
                } catch (\Exception $e) {
                    $this->messageManager->addError(
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml(__('Cannot configure product.'))
                    );
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                    return;
                }
                $textBtn = __("Update Cart");
                $session_id = true;
                break;
        }

        $html  = "";
        $addToCartUrl = $this->_listProduct->getAddToCartUrl($product);
        $html .=  "<form data-role='tocart-form' data-product-sku='". $product->getSku(). " action=". $addToCartUrl ."' method='post'>
                <input type='hidden' name='product' value='" .$product->getId(). "'>
                <input type='hidden' name='selected_configurable_option' value=''>
                <input type='hidden' name='related_product' id='related-products-field' value=''>
                <input type='hidden' name='sales_gift_from_slider' value='1'>";
        $html .= "<div class='product-options-top'>";
        if ($product->getTypeId() == 'configurable') {
            try {
                $addtocartHtml = '';
                $resultPage = $this->resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->prepend(__(' heading '));

                $configurable     = $layout->createBlock('Magento\ConfigurableProduct\Block\Product\View\Type\Configurable')
                    ->setData('area', 'frontend')
                    ->setProduct($product)
                    ->setTemplate('MW_FreeGift::product/view/type/options/configurable.phtml');

                $html .= $configurable->tohtml();

                $html .= "</div>"; /* End from div product-options-top */
                if ($params['is_gift'] == "true" || $params['action'] == 'configure') {
                    $html .= "<div class='product-options-bottom'>"
                        .$addtocartHtml.
                        "<div class='add-to-cart'>
                            <button type='submit' title='".$textBtn."' class='button btn-cart'><span><span>".$textBtn."</span></span></button>
                        </div>".
                        "</div> </form>";
                } else {
                    $html .= "<div class='product-options-bottom'>".
                        "</div>";
                }
            } catch (\Exception $e) {
            }
        } elseif ($product->getTypeId() == 'simple') {
            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if ($addtocartBlock) {
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }
            if ($params['is_gift'] == "false" || $params['action'] == 'configure') {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "<div class=\"add-to-cart\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    "</div>";
            } else {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "<div class=\"add-to-cart\">
                        <label for=\"qty\">Qty:</label>
                        <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    "</div>";
            }
        } elseif ($product->getTypeId() == 'bundle') {
            $this->_coreRegistry->register('product', $product);
            $this->_coreRegistry->register('current_product', $product);
            // Get the product and the product's options - In this case, the options are associated products attached to a bundled parent product
            $resultPage = $this->resultPageFactory->create();

            $blockAddTocart = $resultPage->getLayout()
                ->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('MW_FreeGift::product/view/addtocart.phtml')
                ->toHtml();
            $addtocartHtml = $blockAddTocart;

            $bundle = $layout->createBlock('Magento\Bundle\Block\Catalog\Product\View\Type\Bundle')
                ->setTemplate('Magento_Bundle::catalog/product/view/type/bundle/options.phtml');
            $bundle->setChild('select', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Select',
                'product.info.bundle.options.select'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/select.phtml'));
            $bundle->setChild('multi', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Multi',
                'product.info.bundle.options.multi'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/multi.phtml'));
            $bundle->setChild('radio', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Radio',
                'product.info.bundle.options.radio'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/radio.phtml'));
            $bundle->setChild('checkbox', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Checkbox',
                'product.info.bundle.options.checkbox'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/checkbox.phtml'));

            $html .= $bundle->toHtml();
            $html .= "</div>"; /* End from div product-options-top */

            if ($params['is_gift'] == "false" || $params['action'] == 'configure') {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            } else {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            }
        } elseif ($product->getTypeId() == 'downloadable') {
            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if ($addtocartBlock) {
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }
            $downloadable     = $this->_layout->createBlock('Magento\Downloadable\Block\Catalog\Product\Links')
                ->setData('area', 'frontend')
                ->setProduct($product)
                ->setTemplate('MW_FreeGift::downloadable/catalog/product/links.phtml');

            $downloadableData     = $this->_layout->createBlock('Magento\Downloadable\Block\Catalog\Product\View\Type')
                ->setData('area', 'frontend')
                ->setProduct($product)
                ->setTemplate('Magento_Downloadable::catalog/product/type.phtml');
            $html .= $downloadable->tohtml();
            $html .= $downloadableData->tohtml();
            $html .= "</div>"; /* End from div product-options-top */

            if ($params['is_gift'] == "false" || $params['action'] == 'configure') {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            } else {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            }
        } elseif ($product->getTypeId() == 'grouped') {
            $this->_coreRegistry->register('current_product', $product);

            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if ($addtocartBlock) {
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }

            $grouped   = $layout->createBlock('Magento\GroupedProduct\Block\Product\View\Type\Grouped')
                ->setProduct($product)
                ->setTemplate('Magento_GroupedProduct::product/view/type/grouped.phtml');

            $html .= $grouped->tohtml();
            $html .= "</div>"; /* End from div product-options-top */
            if ($params['is_gift'] == "false" || $params['action'] == 'configure') {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            } else {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            }
        } else {
            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if ($addtocartBlock) {
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }

            if ($params['is_gift'] == "false" || $params['action'] == 'configure') {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            } else {
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            }
        }

        $this->getResponse()->setBody($html);
    }
}
