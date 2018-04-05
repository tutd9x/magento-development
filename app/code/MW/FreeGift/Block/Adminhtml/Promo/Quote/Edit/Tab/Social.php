<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace MW\FreeGift\Block\Adminhtml\Promo\Quote\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject as ObjectConverter;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\System\Store;

/**
 * Cart Price Rule General Information Tab
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Social extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Framework\Convert\Object
     */
    protected $_objectConverter;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_salesRule;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param RuleFactory $salesRule
     * @param ObjectConverter $objectConverter
     * @param Store $systemStore
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        RuleFactory $salesRule,
        ObjectConverter $objectConverter,
        Store $systemStore,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_objectConverter = $objectConverter;
        $this->_salesRule = $salesRule;
        $this->groupRepository = $groupRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Social Sharing');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Social Sharing');
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
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_promo_quote_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Social Information')]);

//        if ($model->getId()) {
//            $fieldset->addField('rule_id', 'hidden', ['name' => 'rule_id']);
//        }

        //$fieldset->addField('product_ids', 'hidden', ['name' => 'product_ids']);

        $fieldset->addField(
            'enable_social',
            'select',
            [
                'label' => __('Social Sharing'),
                'title' => __('Social Sharing'),
                'name' => 'enable_social',
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        $fieldset->addField(
            'google_plus',
            'select',
            [
                'label' => __('Google Plus'),
                'title' => __('Google Plus'),
                'name' => 'google_plus',
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        $fieldset->addField(
            'like_fb',
            'select',
            [
                'label' => __('Facebook Like'),
                'title' => __('Facebook Like'),
                'name' => 'like_fb',
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        $fieldset->addField(
            'share_fb',
            'select',
            [
                'label' => __('Facebook Share'),
                'title' => __('Facebook Share'),
                'name' => 'share_fb',
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        $fieldset->addField(
            'twitter',
            'select',
            [
                'label' => __('Twitter Tweet'),
                'title' => __('Twitter Tweet'),
                'name' => 'twitter',
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        $fieldset->addField(
            'default_message',
            'textarea',
            [
                'name' => 'default_message',
                'label' => __('Default Message'),
                'title' => __('Default Message'),
                'style' => 'height: 100px;'
            ]
        );

        $form->setValues($model->getData());

        //$form->setUseContainer(true);

        $this->setForm($form);

        //$this->_eventManager->dispatch('adminhtml_promo_quote_edit_tab_main_prepare_form', ['form' => $form]);

        return parent::_prepareForm();
    }
}
