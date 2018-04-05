<?php
namespace MW\FreeGift\Block\Adminhtml\Promo;
class Quote extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'MW_FreeGift';
        $this->_controller = 'adminhtml_promo_quote';
        $this->_headerText = __('Cart Price Rules');
        $this->_addButtonLabel = __('Add New Rule');
        parent::_construct();

    }
}
