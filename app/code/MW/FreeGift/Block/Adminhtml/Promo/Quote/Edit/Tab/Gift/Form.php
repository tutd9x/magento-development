<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Block\Adminhtml\Promo\Quote\Edit\Tab\Gift;

/**
 * Coupons generation parameters form
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Sales rule coupon
     *
     * @var \Magento\SalesRule\Helper\Coupon
     */
    protected $_salesRuleCoupon = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\SalesRule\Helper\Coupon $salesRuleCoupon
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
//        \Magento\SalesRule\Helper\Coupon $salesRuleCoupon,
        array $data = []
    ) {
//        $this->_salesRuleCoupon = $salesRuleCoupon;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare coupon codes generation parameters form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        //disable reload when process ajax of grid gift
        if($this->getRequest()->getParam('ajax') == 'true'){
            return parent::_prepareForm();
        }
//        $stop_rules_processing = $this->_coreRegistry->registry('stop_rules_processing');
        $model = $this->_coreRegistry->registry('current_promo_quote_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        /**
         * @var \Magento\SalesRule\Helper\Coupon $couponHelper
         */
//        $couponHelper = $this->_salesRuleCoupon;

        $form->setHtmlIdPrefix('coupons_');

        // @NOTE: for js
//        $gridBlock = $this->getLayout()->getBlock('promo_catalog_edit_tab_gift_grid');
//
//        $gridBlockJsObject = '';
//        if ($gridBlock) {
//            $gridBlockJsObject = $gridBlock->getJsObjectName();
//        }

//        $fieldset = $form->addFieldset('information_fieldset', ['legend' => __('Coupons Information')]);
        $fieldset = $form->addFieldset('giftitem_fieldset', ['legend' => __('Update gift items using following information')] );
        $fieldset->addClass('ignore-validate');

        $fieldset->addField(
            'stop_rules_processing',
            'select',
            [
                'label' => __('Discard subsequent rules'),
                'title' => __('Discard subsequent rules'),
                'name' => 'stop_rules_processing',
                'values' => ['1' => __('Yes'), '0' => __('No')],
//                'value' => $stop_rules_processing ? $stop_rules_processing : 0
                'value' => $model->getStopRulesProcessing() ? $model->getStopRulesProcessing() : 0
            ]
        );

        $fieldset->addField(
            'number_of_free_gift',
            'text',
            [
                'name' => 'number_of_free_gift',
                'label' => __('Number of Free Gifts'),
                'title' => __('Number of Free Gifts'),
                'value' => $model->getNumberOfFreeGift() ? $model->getNumberOfFreeGift() : 1
            ]
        );

        $form->setValues($model->getData());

        /*if ($model->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                $element->setReadonly(true, true);
            }
        }*/

        $this->setForm($form);

        return parent::_prepareForm();
    }

}
