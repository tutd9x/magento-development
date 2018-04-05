<?php
namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\Tab;

class Items extends \Magento\Backend\Block\Text\ListText implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
        return $this->_isEditing();
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return !$this->_isEditing();
    }

//    /**
//     * @return string
//     */
    public function getTabClass()
    {
        return 'ajax';
    }
//
//    /**
//     * @return string
//     */
    public function getTabUrl()
    {
        //$model = $this->_coreRegistry->registry('current_promo_catalog_rule');
        //return $this->getUrl('*/*/gift', ['_current' => true, 'stop_rules_processing' => $model->getStopRulesProcessing()]);
        return $this->getUrl('*/*/items', ['_current' => true]);
    }

    /**
     * Check whether we edit existing rule or adding new one
     *
     * @return bool
     */
    protected function _isEditing()
    {
        return true;
//        $priceRule = $this->_coreRegistry->registry('current_promo_catalog_rule');
//        return $priceRule->getRuleId() !== null;
    }
}
