<?php
namespace MW\FreeGift\Block\Adminhtml\Promo\Quote\Edit\Tab;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

/**
 * "Gift Items" Tab
 */
class GiftTab extends Template implements TabInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;
    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Gift Items');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Gift Items');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
//        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
//        if(isset($model) && $model->getId()){
//            return true;
//        }
//        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setCanSHow($canShow)
    {
        $this->_data['config']['canShow'] = $canShow;
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
//    public function getTabUrl()
//    {
//        return $this->getUrl('mw_freegift/*/gifttab', ['_current' => true]);
//    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isAjaxLoaded()
    {
        return false;
    }
}
