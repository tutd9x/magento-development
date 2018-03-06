<?php
namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\Tab;

use \Magento\Framework\View\Element\Template;
use \Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * "Gift Items" Tab
 */
class GiftTab extends Template implements TabInterface
{
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
