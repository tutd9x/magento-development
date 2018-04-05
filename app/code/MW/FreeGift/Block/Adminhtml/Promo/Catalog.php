<?php
namespace MW\FreeGift\Block\Adminhtml\Promo;
class Catalog extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'MW_FreeGift';
        $this->_controller = 'adminhtml_promo_catalog';
        $this->_headerText = __('Catalog Price Rule');
        $this->_addButtonLabel = __('Add New Rule');
        parent::_construct();

        $this->buttonList->add(
            'apply_rules',
            [
                'label' => __('Apply Rules'),
                'onclick' => "location.href='" . $this->getUrl('*/*/applyRules') . "'",
                'class' => 'apply'
            ]
        );
    }
}
